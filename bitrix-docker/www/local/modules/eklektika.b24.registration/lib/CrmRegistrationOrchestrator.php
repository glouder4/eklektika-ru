<?php
namespace OnlineService\B24\Registration;

use intec\eklectika\advertising_agent\Company;
use Bitrix\Main\Web\HttpClient;
use OnlineService\B24\Registration\Config\RegisterUserCompanyConfig;
use OnlineService\B24\UserSync\Config\UserSyncConfig;
use OnlineService\B24\User;
use OnlineService\B24\Request;
use OnlineService\Sync\FromCrm\CrmInboundUfMap;

/**
 * Оркестрация регистрации юрлица в CRM (n8n webhooks + REST‑прокси).
 * Единственная реализация CRM‑ветки регистрации на сайте (ранее usersync `RegisterUserCompany`).
 */
class CrmRegistrationOrchestrator extends Request
{ 
    private int $lastSyncedCompanyB24Id = 0;
    private int $lastSyncedContactB24Id = 0;
    private const ASYNC_POST_REGISTER_MAX_ATTEMPTS = 3;
    private const ASYNC_POST_REGISTER_BASE_BACKOFF_SECONDS = 2;
    private const ASYNC_POST_REGISTER_MAX_BACKOFF_SECONDS = 15;

    public function __construct()
    {
    }

    public function isUserRegistered($arFields,$debug = false){
        $userObject = $this->crmCheckUniqueContact([
            'EMAIL' => (string) ($arFields['EMAIL'] ?? ''),
            'PERSONAL_PHONE' => (string) ($arFields['PERSONAL_PHONE'] ?? ''),
        ]);

        // если такой пользователь есть, то вывести предупреждение
        if ($userObject && !empty($userObject)) {
            return $userObject;
        }

        return false;
    }

    private static function isTruthy($value): bool
    {
        return \in_array(\strtolower(\trim((string)$value)), ['1', 'true', 'on', 'yes'], true);
    }

    private static function getSyncConfigValue(string $key, $default = null)
    {
        $cfg = $GLOBALS['EKLEKTIKA_SYNC_CONFIG'] ?? [];
        if (\is_array($cfg) && \array_key_exists($key, $cfg)) {
            return $cfg[$key];
        }

        $doc = \rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
        $path = $doc . '/local/modules/eklektika.sync/config.local.php';
        if (\is_file($path)) {
            $localCfg = include $path;
            if (\is_array($localCfg) && \array_key_exists($key, $localCfg)) {
                return $localCfg[$key];
            }
        }

        return $default;
    }

    private static function registrationWebhookRelativePath(string $configKey): ?string
    {
        $suffixes = self::getSyncConfigValue('registration_webhook_path_suffixes', []);
        if (!\is_array($suffixes) || !\array_key_exists($configKey, $suffixes)) {
            return null;
        }
        $rel = \trim((string) $suffixes[$configKey]);

        return $rel !== '' ? $rel : null;
    }

    private static function resolveRegistrationWebhookUrl(string $configKey): string
    {
        $direct = \trim((string) self::getSyncConfigValue($configKey, ''));
        if ($direct !== '') {
            return $direct;
        }
        $base = \trim((string) self::getSyncConfigValue('n8n_registration_http_base', ''));
        if ($base === '') {
            return '';
        }
        $rel = self::registrationWebhookRelativePath($configKey);
        if ($rel === null || $rel === '') {
            return '';
        }

        return \rtrim($base, '/') . '/' . \ltrim($rel, '/');
    }

    private static function resolveAsyncPostRegisterWebhookUrl(): string
    {
        $direct = \trim((string) self::getSyncConfigValue('async_post_register_webhook_url', ''));
        if ($direct !== '') {
            return $direct;
        }
        $base = \trim((string) self::getSyncConfigValue('n8n_registration_http_base', ''));
        if ($base === '') {
            return '';
        }

        return \rtrim($base, '/') . '/' . 'registration/crm-register-post-sync-v1';
    }

    private static function formatRegistrationWebhookFailureMessage(array $webhook): string
    {
        $parts = [];
        $status = (int) ($webhook['status'] ?? 0);
        if ($status > 0) {
            $parts[] = 'HTTP ' . $status;
        }
        if (!empty($webhook['error'])) {
            $parts[] = (string) $webhook['error'];
        }
        $data = $webhook['data'] ?? null;
        if (\is_array($data)) {
            if (isset($data['message']) && $data['message'] !== '') {
                $parts[] = (string) $data['message'];
            }
            if (isset($data['hint']) && $data['hint'] !== '') {
                $parts[] = (string) $data['hint'];
            }
        }
        if (!empty($webhook['raw_preview'])) {
            $parts[] = \mb_substr((string) $webhook['raw_preview'], 0, 200);
        }
        $msg = \implode(' — ', \array_filter($parts, static function ($p) {
            return $p !== '';
        }));

        return $msg !== '' ? $msg : 'Ошибка запроса к n8n (webhook).';
    }

    private static function throwRegistrationWebhookFailure(array $webhook): void
    {
        global $APPLICATION;
        $APPLICATION->ThrowException(self::formatRegistrationWebhookFailureMessage($webhook), 'n8n_registration_webhook');
    }

    /**
     * POST JSON на вебхук регистрации.
     *
     * @return array{used?: bool, ok?: bool, status?: int, error?: string, data?: array, raw_preview?: string}
     */
    private static function postRegistrationWebhook(string $configKey, array $payload): array
    {
        $webhookUrl = self::resolveRegistrationWebhookUrl($configKey);
        if ($webhookUrl === '') {
            return ['used' => false];
        }

        try {
            $http = new HttpClient([
                'socketTimeout' => 6,
                'streamTimeout' => 6,
                'disableSslVerification' => false,
                'waitResponse' => true,
            ]);
            $http->setHeader('Content-Type', 'application/json; charset=UTF-8');
            $http->setHeader('Accept', 'application/json');

            $syncToken = \trim((string) self::getSyncConfigValue('inbound_secret', ''));
            if ($syncToken !== '') {
                $http->setHeader('X-Sync-Token', $syncToken);
            }

            $json = \json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json === false) {
                return ['used' => true, 'ok' => false, 'status' => 0, 'error' => 'json_encode_failed'];
            }

            $http->post($webhookUrl, $json);
            $status = (int) $http->getStatus();
            $raw = (string) $http->getResult();
            $decoded = \json_decode($raw, true);
            if (!\is_array($decoded)) {
                return [
                    'used' => true,
                    'ok' => false,
                    'status' => $status,
                    'error' => 'invalid_json',
                    'raw_preview' => \mb_substr($raw, 0, 400),
                ];
            }

            return [
                'used' => true,
                'ok' => $status >= 200 && $status < 300,
                'status' => $status,
                'data' => $decoded,
            ];
        } catch (\Throwable $e) {
            return ['used' => true, 'ok' => false, 'status' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * n8n иногда отдаёт JSON-массив из одного envelope: `[{"success":1,"result":107}]` вместо объекта.
     * Без распаковки {@see normalizeCrmAddResult} не видит числовой ID и обрывает сценарий до REST-прокси.
     *
     * @param array<string, mixed>|list<array<string, mixed>>|null $decoded
     *
     * @return mixed
     */
    private static function unwrapRegistrationWebhookResult($decoded)
    {
        if (!\is_array($decoded)) {
            return null;
        }
        $decoded = self::unwrapRegistrationWebhookSingleElementEnvelope($decoded);
        if (isset($decoded['success']) && (int) $decoded['success'] === 0) {
            return $decoded;
        }
        if (\array_key_exists('result', $decoded)) {
            return $decoded['result'];
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed>|list<mixed> $decoded
     *
     * @return array<string, mixed>
     */
    private static function unwrapRegistrationWebhookSingleElementEnvelope(array $decoded): array
    {
        while ($decoded !== []) {
            if (\count($decoded) !== 1 || !isset($decoded[0]) || !\is_array($decoded[0])) {
                break;
            }
            $keys = \array_keys($decoded);
            if ($keys !== [0]) {
                break;
            }
            $inner = $decoded[0];
            if (
                \array_key_exists('success', $inner)
                || \array_key_exists('result', $inner)
                || (\array_key_exists('error', $inner) && (string) ($inner['error'] ?? '') !== '')
            ) {
                $decoded = $inner;
                continue;
            }
            break;
        }

        return $decoded;
    }

    /**
     * Контракт «поиск/пречек»: HTTP 200 и JSON с success=1; поле result обязательно (может быть [] или объект контакта и т.п.).
     *
     * @param array{used?: bool, ok?: bool, data?: array|null|mixed} $webhook
     */
    private static function assertProbeWebhookContract(string $configKey, array $webhook): void
    {
        if (empty($webhook['used']) || empty($webhook['ok'])) {
            return;
        }
        $data = $webhook['data'] ?? null;
        if (!\is_array($data)) {
            self::throwWebhookContractViolation($configKey, 'body_not_json_object');
        }
        $peeled = self::unwrapRegistrationWebhookSingleElementEnvelope($data);
        if (!\array_key_exists('success', $peeled) || !self::registrationWebhookSuccessIsPositive($peeled['success'])) {
            self::throwWebhookContractViolation($configKey, 'expected_success_1');
        }
        if (!\array_key_exists('result', $peeled)) {
            self::throwWebhookContractViolation($configKey, 'missing_result');
        }
    }

    /**
     * Контракт создания сущности (company/contact): success=1 и result — числовой ID или объект с ID.
     *
     * @param array{used?: bool, ok?: bool, data?: array|null|mixed} $webhook
     */
    private static function assertMutationAddWebhookContract(string $configKey, array $webhook): void
    {
        if (empty($webhook['used']) || empty($webhook['ok'])) {
            return;
        }
        $data = $webhook['data'] ?? null;
        if (!\is_array($data)) {
            self::throwWebhookContractViolation($configKey, 'body_not_json_object');
        }
        $peeled = self::unwrapRegistrationWebhookSingleElementEnvelope($data);
        if (!\array_key_exists('success', $peeled) || !self::registrationWebhookSuccessIsPositive($peeled['success'])) {
            self::throwWebhookContractViolation($configKey, 'expected_success_1');
        }
        if (!\array_key_exists('result', $peeled)) {
            self::throwWebhookContractViolation($configKey, 'missing_result');
        }
        $rid = $peeled['result'];
        if (\is_numeric($rid)) {
            return;
        }
        if (\is_array($rid) && \is_numeric($rid['ID'] ?? null)) {
            return;
        }
        self::throwWebhookContractViolation($configKey, 'result_not_entity_id');
    }

    /**
     * Контракт списка (crm.requisite.list): success=1 и result — массив (может быть пустым).
     *
     * @param array{used?: bool, ok?: bool, data?: array|null|mixed} $webhook
     */
    private static function assertProbeListWebhookContract(string $configKey, array $webhook): void
    {
        if (empty($webhook['used']) || empty($webhook['ok'])) {
            return;
        }
        $data = $webhook['data'] ?? null;
        if (!\is_array($data)) {
            self::throwWebhookContractViolation($configKey, 'body_not_json_object');
        }
        $peeled = self::unwrapRegistrationWebhookSingleElementEnvelope($data);
        if (!\array_key_exists('success', $peeled) || !self::registrationWebhookSuccessIsPositive($peeled['success'])) {
            self::throwWebhookContractViolation($configKey, 'expected_success_1');
        }
        if (!\array_key_exists('result', $peeled)) {
            self::throwWebhookContractViolation($configKey, 'missing_result');
        }
        if (!\is_array($peeled['result'])) {
            self::throwWebhookContractViolation($configKey, 'result_must_be_array');
        }
    }

    private static function registrationWebhookSuccessIsPositive($value): bool
    {
        if ($value === true) {
            return true;
        }
        if (\is_numeric($value)) {
            return (int) $value === 1;
        }
        if (\is_string($value)) {
            return \in_array(\strtolower(\trim($value)), ['1', 'true', 'yes'], true);
        }

        return false;
    }

    private static function throwWebhookContractViolation(string $configKey, string $reasonCode): void
    {
        global $APPLICATION;
        $APPLICATION->ThrowException(
            'Ответ CRM (n8n) для «' . $configKey . '» не соответствует контракту (`' . $reasonCode . '`). Ожидается JSON с полями success и result — см. docs/reference/registration-n8n-webhooks.md.',
            'n8n_registration_webhook_contract'
        );
    }

    /**
     * Регистрация не может считаться успешной при ошибке REST через прокси (если ответ распознан как сбой транспорта/CRM).
     *
     * @param mixed $result
     */
    private function assertRegistrationRestProxyOk($result, string $crmMethod): void
    {
        if (!$this->isB24RestFailure($result)) {
            return;
        }
        global $APPLICATION;
        $APPLICATION->ThrowException(
            'CRM вернула ошибку при «' . $crmMethod . '». Регистрация прервана.',
            'crm_rest_proxy_failed'
        );
    }

    private static function registrationPrecheckResponseIndicatesSuccess(array $data, $result): bool
    {
        if ((int) ($data['success'] ?? 0) === 1) {
            return true;
        }
        if (\is_array($result) && (int) ($result['success'] ?? 0) === 1) {
            return true;
        }

        return false;
    }

    private static function isProbableN8nErrorResponseBody($data): bool
    {
        if (!\is_array($data) || $data === []) {
            return false;
        }
        if (\array_key_exists('success', $data) || \array_key_exists('result', $data)) {
            return false;
        }
        if (empty($data['message']) || !\is_string($data['message'])) {
            return false;
        }
        if (\array_key_exists('code', $data)) {
            return true;
        }
        if (!empty($data['hint']) || !empty($data['stacktrace'])) {
            return true;
        }

        return false;
    }

    /**
     * @param array<string, mixed> $result
     */
    private static function formatCrmPrecheckRejectionMessage(array $result): string
    {
        foreach (['error_description', 'error', 'message', 'hint'] as $key) {
            if (!empty($result[$key]) && \is_scalar($result[$key])) {
                return \trim((string) $result[$key]);
            }
        }

        return 'Проверка в CRM завершилась с ошибкой.';
    }

    /**
     * Контракт ответа n8n crm-check-unique-contact-v1:
     * пустой массив — уникально; непустой массив — найден контакт; false — ошибка запроса/конфигурации.
     *
     * @param array{EMAIL?: string, PERSONAL_PHONE?: string} $contactProbeFields
     *
     * @return array<mixed>|false
     */
    private function crmCheckUniqueContact(array $contactProbeFields)
    {
        $webhookPayload = [
            'EMAIL' => (string) ($contactProbeFields['EMAIL'] ?? ''),
            'PERSONAL_PHONE' => (string) ($contactProbeFields['PERSONAL_PHONE'] ?? ''),
        ];
        $webhook = self::postRegistrationWebhook('registration_webhook_unique_url', $webhookPayload);
        if (!empty($webhook['used'])) {
            if (empty($webhook['ok'])) {
                self::throwRegistrationWebhookFailure($webhook);

                return false;
            }
            $data = $webhook['data'] ?? null;
            if (\is_array($data) && self::isProbableN8nErrorResponseBody($data)) {
                self::throwRegistrationWebhookFailure($webhook);

                return false;
            }
            self::assertProbeWebhookContract('registration_webhook_unique_url', $webhook);
            $result = self::unwrapRegistrationWebhookResult($data);

            if (\is_array($result) && isset($result['success']) && (int) $result['success'] === 0) {
                global $APPLICATION;
                $APPLICATION->ThrowException(
                    self::formatCrmPrecheckRejectionMessage($result),
                    'crm_precheck_unique'
                );

                return false;
            }
            if (\is_array($result) && isset($result[0]) && \is_array($result[0])) {
                return $result[0];
            }
            if (\is_array($result) && !empty($result['ID'])) {
                return $result;
            }

            if (!\is_array($data) || !self::registrationPrecheckResponseIndicatesSuccess($data, $result)) {
                global $APPLICATION;
                $APPLICATION->ThrowException(
                    'Проверка уникальности в CRM вернула неожиданный ответ. Повторите попытку позже или обратитесь в поддержку.',
                    'crm_precheck_unique_ambiguous'
                );

                return false;
            }

            return [];
        }

        global $APPLICATION;
        $APPLICATION->ThrowException(
            'Регистрация через n8n: задайте registration_webhook_unique_url или n8n_registration_http_base (crm-check-unique-contact-v1).'
        );

        return false;
    }

    /**
     * @param array|false $response результат {@see crmCheckUniqueContact}
     */
    private static function haltIfDuplicateContactFromCrmCheck($response): bool
    {
        global $APPLICATION;
        if ($response === false) {
            return false;
        }
        if ($response) {
            if ((isset($response['PHONE']) && !empty($response['PHONE'])) || (isset($response['EMAIL']) && !empty($response['EMAIL']))) {
                $APPLICATION->ThrowException('Пользователь с указанными почтой или телефоном уже существует в системе. Вы можете <a href="/personal/profile/">авторизоваться</a> или <a href="/personal/profile/?forgot_password=yes">восстановить пароль</a>', 'already_registered');
            } else {
                $APPLICATION->ThrowException('Что-то пошло не так.', 'already_registered');
            }

            return false;
        }

        return true;
    }

    private function isLegacySyncEnabled(): bool
    {
        return self::isTruthy(self::getSyncConfigValue('sync_legacy', true));
    }

    private function isAsyncPostRegisterEnabled(): bool
    {
        return self::isTruthy(self::getSyncConfigValue('async_post_register', false));
    }

    private function getAsyncRegisterLogsDir(): string
    {
        return \rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\') . '/local/logs';
    }

    private function getAsyncRegisterStatePath(string $idempotencyKey): string
    {
        return $this->getAsyncRegisterLogsDir() . '/async-register-state-' . $idempotencyKey . '.json';
    }

    private function getAsyncRegisterLockPath(string $idempotencyKey): string
    {
        return $this->getAsyncRegisterLogsDir() . '/async-register-lock-' . $idempotencyKey . '.lock';
    }

    private function getAsyncRegisterMetricsPath(): string
    {
        return $this->getAsyncRegisterLogsDir() . '/async-register-metrics.log';
    }

    private function getAsyncRegisterDeadLetterPath(): string
    {
        return $this->getAsyncRegisterLogsDir() . '/async-register-dead-letter.log';
    }

    private function appendJsonLine(string $path, array $payload): void
    {
        @\mkdir(\dirname($path), 0777, true);
        @\file_put_contents(
            $path,
            \json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }

    private function emitAsyncWebhookMetric(string $event, array $context = []): void
    {
        $metric = [
            'ts' => \date('c'),
            'metric' => 'registration.async_post_register.' . $event,
            'event' => $event,
        ] + $context;
        $this->appendJsonLine($this->getAsyncRegisterMetricsPath(), $metric);
    }

    private function readAsyncWebhookState(string $statePath): array
    {
        if (!\is_file($statePath)) {
            return [];
        }

        $raw = @\file_get_contents($statePath);
        if (!\is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = \json_decode($raw, true);
        return \is_array($decoded) ? $decoded : [];
    }

    private function writeAsyncWebhookState(string $statePath, array $state): void
    {
        @\mkdir(\dirname($statePath), 0777, true);
        @\file_put_contents(
            $statePath,
            \json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );
    }

    private function computeAsyncRetryDelaySeconds(int $attempt): int
    {
        if ($attempt <= 1) {
            return 0;
        }

        $delay = self::ASYNC_POST_REGISTER_BASE_BACKOFF_SECONDS * (2 ** ($attempt - 2));
        return (int)\min($delay, self::ASYNC_POST_REGISTER_MAX_BACKOFF_SECONDS);
    }

    private function sendAsyncPostRegisterWebhookAttempt(string $webhookUrl, array $payload, string $idempotencyKey): array
    {
        try {
            $http = new HttpClient([
                'socketTimeout' => 3,
                'streamTimeout' => 3,
                'disableSslVerification' => false,
                'waitResponse' => true,
            ]);
            $http->setHeader('Content-Type', 'application/json');
            if ($idempotencyKey !== '') {
                $http->setHeader('X-Idempotency-Key', $idempotencyKey);
            }
            $http->post($webhookUrl, \json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $status = (int)$http->getStatus();
            if ($status >= 200 && $status < 300) {
                return ['success' => true, 'status' => $status, 'error' => ''];
            }

            return ['success' => false, 'status' => $status, 'error' => 'Unexpected HTTP status'];
        } catch (\Throwable $e) {
            return ['success' => false, 'status' => 0, 'error' => $e->getMessage()];
        }
    }

    private function callRegistrationWebhook(string $configKey, array $payload): array
    {
        return self::postRegistrationWebhook($configKey, $payload);
    }

    private function registrationWebhookFailAndThrow(array $webhook): void
    {
        self::throwRegistrationWebhookFailure($webhook);
    }

    private function normalizeCrmAddResult($result)
    {
        if (\is_numeric($result)) {
            return (int) $result;
        }
        if (\is_array($result) && \array_key_exists('success', $result) && (int) $result['success'] === 0) {
            return false;
        }
        if (\is_array($result) && \is_numeric($result['ID'] ?? null)) {
            return (int) $result['ID'];
        }
        if (
            \is_array($result)
            && \array_key_exists('result', $result)
            && \is_numeric($result['result'])
            && (!isset($result['success']) || (int) $result['success'] === 1)
        ) {
            return (int) $result['result'];
        }

        return false;
    }

    /**
     * Нормализация ответа crm.company.get (n8n может вернуть «сырой» result или ошибку без поля ID).
     *
     * @param mixed $raw
     *
     * @return array<string, mixed>|null
     */
    private static function normalizeCrmCompanyRecord($raw)
    {
        if (\is_numeric($raw)) {
            $id = (int) $raw;

            return $id > 0 ? ['ID' => $id] : null;
        }
        if (!\is_array($raw)) {
            return null;
        }
        if (isset($raw['success']) && (int) $raw['success'] === 0) {
            return null;
        }
        $id = $raw['ID'] ?? $raw['id'] ?? null;
        if ($id !== null && \is_numeric($id) && (int) $id > 0) {
            return $raw;
        }

        return null;
    }

    /**
     * @param mixed $raw
     */
    private function companyRowAfterGet($raw, int $fallbackCompanyId): array
    {
        $n = self::normalizeCrmCompanyRecord($raw);
        if ($n !== null && (int) ($n['ID'] ?? 0) > 0) {
            return $n;
        }
        if ($fallbackCompanyId > 0) {
            return ['ID' => $fallbackCompanyId];
        }

        return ['ID' => 0];
    }

    /**
     * @param mixed $result
     */
    private function isB24RestFailure($result): bool
    {
        if ($result === null || $result === false) {
            return true;
        }
        if (\is_array($result) && \array_key_exists('success', $result) && (int) $result['success'] === 0) {
            return true;
        }
        if (\is_array($result) && isset($result['error']) && (string) $result['error'] !== '') {
            return true;
        }

        return false;
    }

    private function bindContactToCompany(int $companyId, int $contactId): void
    {
        $r = $this->callB24Method('crm.contact.company.add', [
            'id' => $contactId,
            'fields' => [
                'COMPANY_ID' => $companyId,
                'IS_PRIMARY' => 'Y',
            ],
        ], false);
        if (!$this->isB24RestFailure($r)) {
            return;
        }
        $r2 = $this->callB24Method('crm.company.contact.add', [
            'id' => $companyId,
            'fields' => [
                'CONTACT_ID' => $contactId,
                'IS_PRIMARY' => 'Y',
            ],
        ], false);
        if ($this->isB24RestFailure($r2)) {
            global $APPLICATION;
            $APPLICATION->ThrowException(
                'Не удалось привязать контакт к компании в CRM (crm.contact.company.add / crm.company.contact.add). Регистрация прервана.',
                'crm_contact_company_bind_failed'
            );
        }
    }

    /**
     * @return array|false Пустой массив — ок; false — ошибка конфигурации (уже ThrowException).
     */
    private function crmCheckInnUniqueness(array $arFields)
    {
        $inn = self::normalizeInnValue((string)($arFields['UF_INN'] ?? ''));
        if ($inn === '') {
            return [];
        }

        $webhook = $this->callRegistrationWebhook('registration_webhook_inn_url', [
            'UF_INN' => $inn,
            'INN' => $inn,
        ]);
        if (!empty($webhook['used'])) {
            if (empty($webhook['ok'])) {
                $this->registrationWebhookFailAndThrow($webhook);

                return false;
            }
            $data = $webhook['data'] ?? null;
            if (\is_array($data) && self::isProbableN8nErrorResponseBody($data)) {
                $this->registrationWebhookFailAndThrow($webhook);

                return false;
            }
            self::assertProbeWebhookContract('registration_webhook_inn_url', $webhook);
            $result = self::unwrapRegistrationWebhookResult($data);
            if (\is_array($result) && isset($result['success']) && (int)$result['success'] === 0) {
                global $APPLICATION;
                $APPLICATION->ThrowException(self::formatCrmPrecheckRejectionMessage($result), 'crm_precheck_inn');

                return false;
            }
            if (!\is_array($data) || !self::registrationPrecheckResponseIndicatesSuccess($data, $result)) {
                global $APPLICATION;
                $APPLICATION->ThrowException(
                    'Проверка ИНН в CRM вернула неожиданный ответ. Повторите попытку позже или обратитесь в поддержку.',
                    'crm_precheck_inn_ambiguous'
                );

                return false;
            }

            return \is_array($result) ? $result : [];
        }

        global $APPLICATION;
        $APPLICATION->ThrowException(
            'Регистрация через n8n: задайте registration_webhook_inn_url или n8n_registration_http_base (crm-check-inn-v1).'
        );

        return false;
    }

    /**
     * @param array<string, string> $post поля как после {@see \OnlineService\B24\UserSync\AjaxRegister\AjaxRegisterPostParser::parse}
     *
     * @return array<string, string>
     */
    private static function buildEarlyAjaxPrecheckArFields(array $post): array
    {
        $personalPhone = \trim((string) ($post['mobilephone'] ?? ''));
        if ($personalPhone === '') {
            $personalPhone = \trim((string) ($post['phone'] ?? ''));
        }

        return [
            'EMAIL' => \trim((string) ($post['email'] ?? '')),
            'PERSONAL_PHONE' => $personalPhone,
            'WORK_PHONE' => \trim((string) ($post['phone'] ?? '')),
            'UF_INN' => self::normalizeInnValue((string) ($post['inn'] ?? '')),
            'UF_TYPE' => '5',
        ];
    }

    /**
     * Проверка ИНН юрлица в CRM (n8n crm-check-inn-v1). При пустом ИНН после нормализации HTTP не вызывается.
     *
     * @return array{ok: bool, inn_precheck: array|null} inn_precheck — полезная нагрузка ответа для ветвления регистрации (COMPANY_MODE).
     */
    public function runAjaxCompanyInnPrecheck(array $post): array
    {
        $arFields = self::buildEarlyAjaxPrecheckArFields($post);
        $innNorm = self::normalizeInnValue((string) ($arFields['UF_INN'] ?? ''));
        if ($innNorm === '') {
            return ['ok' => true, 'inn_precheck' => null];
        }

        $innCheck = $this->crmCheckInnUniqueness($arFields);
        if ($innCheck === false) {
            return ['ok' => false, 'inn_precheck' => null];
        }

        return [
            'ok' => true,
            'inn_precheck' => \is_array($innCheck) ? $innCheck : null,
        ];
    }

    /**
     * Последовательность ранних пречеков ajax до записи в b_user: контакт (unique), затем ИНН.
     */
    public function runAjaxFormCrmPrecheck(array $post): bool
    {
        $contactProbe = self::buildEarlyAjaxPrecheckArFields($post);
        $resp = $this->crmCheckUniqueContact([
            'EMAIL' => (string) ($contactProbe['EMAIL'] ?? ''),
            'PERSONAL_PHONE' => (string) ($contactProbe['PERSONAL_PHONE'] ?? ''),
        ]);
        if (!self::haltIfDuplicateContactFromCrmCheck($resp)) {
            return false;
        }

        return $this->runAjaxCompanyInnPrecheck($post)['ok'];
    }

    private function crmAddCompany(array $payload)
    {
        $webhook = $this->callRegistrationWebhook('registration_webhook_company_add_url', ['PARAMS' => $payload]);
        if (!empty($webhook['used'])) {
            if (empty($webhook['ok'])) {
                $this->registrationWebhookFailAndThrow($webhook);

                return false;
            }
            $data = $webhook['data'] ?? null;
            if (\is_array($data) && self::isProbableN8nErrorResponseBody($data)) {
                $this->registrationWebhookFailAndThrow($webhook);

                return false;
            }
            self::assertMutationAddWebhookContract('registration_webhook_company_add_url', $webhook);

            return $this->normalizeCrmAddResult(self::unwrapRegistrationWebhookResult($data));
        }

        global $APPLICATION;
        $APPLICATION->ThrowException(
            'Регистрация через n8n: задайте registration_webhook_company_add_url или n8n_registration_http_base (crm-company-add-v1).'
        );

        return false;
    }

    private function crmAddContact(array $payload)
    {
        $webhook = $this->callRegistrationWebhook('registration_webhook_contact_add_url', ['PARAMS' => $payload]);

        if (!empty($webhook['used'])) {
            if (empty($webhook['ok'])) {
                $this->registrationWebhookFailAndThrow($webhook);

                return false;
            }
            $data = $webhook['data'] ?? null;
            if (\is_array($data) && self::isProbableN8nErrorResponseBody($data)) {
                $this->registrationWebhookFailAndThrow($webhook);

                return false;
            }
            self::assertMutationAddWebhookContract('registration_webhook_contact_add_url', $webhook);

            return $this->normalizeCrmAddResult(self::unwrapRegistrationWebhookResult($data));
        }

        global $APPLICATION;
        $APPLICATION->ThrowException(
            'Регистрация через n8n: задайте registration_webhook_contact_add_url или n8n_registration_http_base (crm-contact-add-v1).'
        );

        return false;
    }

    private function runSyncPreCheck(array &$arFields): bool
    {
        global $APPLICATION;

        $response = $this->crmCheckUniqueContact([
            'EMAIL' => (string) ($arFields['EMAIL'] ?? ''),
            'PERSONAL_PHONE' => (string) ($arFields['PERSONAL_PHONE'] ?? ''),
        ]);
        if (!self::haltIfDuplicateContactFromCrmCheck($response)) {
            return false;
        }

        if (($arFields['PASSWORD'] ?? null) !== ($arFields['CONFIRM_PASSWORD'] ?? null)) {
            $APPLICATION->ThrowException('Указанные пароли не совпадают.');
            return false;
        }

        $isCompanyRegistration = (($arFields['UF_TYPE'] ?? '') === '5' || ($arFields['UF_TYPE'] ?? '') === '6');
        if ($isCompanyRegistration && empty($arFields['UF_INN']) && empty($arFields['UF_NAME_COMPANY'])) {
            $APPLICATION->ThrowException('Вы регистрируйтесь как рекламный агент или юридическое лицо. Поля "Название компании", "ИНН организации" обязательно для заполнения!');
            return false;
        }

        if ($isCompanyRegistration) {
            $innNorm = self::normalizeInnValue((string) ($arFields['UF_INN'] ?? ''));
            if ($innNorm !== '') {
                $innCheck = $this->crmCheckInnUniqueness($arFields);
                if ($innCheck === false) {
                    return false;
                }
            }
        }

        $arFields['UF_ADVERSTERING_AGENT'] = "";
        return true;
    }

    private function registerCoreInCrm(array &$arFields): bool
    {
        return $this->createB24Company($arFields) !== false;
    }

    private function buildAsyncPostRegisterPayload(array $arFields, int $contactId): array
    {
        return [
            'event' => 'user_register_post_sync',
            'site_user_id' => (int)($arFields['USER_ID'] ?? 0),
            'email' => (string)($arFields['EMAIL'] ?? ''),
            'phone' => (string)($arFields['PERSONAL_PHONE'] ?? ''),
            'contact_id' => $contactId,
            'company_id' => $this->lastSyncedCompanyB24Id,
            'inn' => self::normalizeInnValue((string)($arFields['UF_INN'] ?? '')),
            'idempotency_key' => \sha1(
                (string)($arFields['USER_ID'] ?? 0) . '|' .
                (string)($arFields['EMAIL'] ?? '') . '|' .
                (string)$contactId . '|' .
                (string)$this->lastSyncedCompanyB24Id
            ),
        ];
    }

    private function runAsyncPostRegisterWebhook(array $payload): void
    {
        $webhookUrl = self::resolveAsyncPostRegisterWebhookUrl();
        if ($webhookUrl === '') {
            $this->emitAsyncWebhookMetric('skipped_empty_url');
            return;
        }

        $idempotencyKey = (string)($payload['idempotency_key'] ?? '');
        if ($idempotencyKey === '') {
            $idempotencyKey = \sha1(\json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $payload['idempotency_key'] = $idempotencyKey;
        }

        $lockPath = $this->getAsyncRegisterLockPath($idempotencyKey);
        $statePath = $this->getAsyncRegisterStatePath($idempotencyKey);
        $lockHandle = @\fopen($lockPath, 'c+');
        if (!$lockHandle) {
            $this->emitAsyncWebhookMetric('lock_open_failed', ['idempotency_key' => $idempotencyKey]);
            return;
        }

        if (!@\flock($lockHandle, LOCK_EX | LOCK_NB)) {
            @\fclose($lockHandle);
            $this->emitAsyncWebhookMetric('dedupe_inflight_skip', ['idempotency_key' => $idempotencyKey]);
            return;
        }

        $state = $this->readAsyncWebhookState($statePath);
        $attemptsUsed = (int)($state['attempts'] ?? 0);
        $status = (string)($state['status'] ?? '');
        if ($status === 'success') {
            $this->emitAsyncWebhookMetric('dedupe_success_skip', ['idempotency_key' => $idempotencyKey]);
            @\flock($lockHandle, LOCK_UN);
            @\fclose($lockHandle);
            return;
        }
        if ($attemptsUsed >= self::ASYNC_POST_REGISTER_MAX_ATTEMPTS) {
            $deadLetterPayload = [
                'ts' => \date('c'),
                'reason' => 'max_attempts_reached',
                'idempotency_key' => $idempotencyKey,
                'payload' => $payload,
                'state' => $state,
            ];
            $this->appendJsonLine($this->getAsyncRegisterDeadLetterPath(), $deadLetterPayload);
            $this->emitAsyncWebhookMetric('dead_letter_max_attempts', [
                'idempotency_key' => $idempotencyKey,
                'attempts' => $attemptsUsed,
            ]);
            @\flock($lockHandle, LOCK_UN);
            @\fclose($lockHandle);
            return;
        }

        $error = '';
        $attempt = $attemptsUsed;
        $lastStatus = 0;
        while ($attempt < self::ASYNC_POST_REGISTER_MAX_ATTEMPTS) {
            $attempt++;
            $delaySeconds = $this->computeAsyncRetryDelaySeconds($attempt);
            if ($delaySeconds > 0) {
                \sleep($delaySeconds);
            }

            $result = $this->sendAsyncPostRegisterWebhookAttempt($webhookUrl, $payload, $idempotencyKey);
            $lastStatus = (int)($result['status'] ?? 0);
            if (!empty($result['success'])) {
                $successState = [
                    'status' => 'success',
                    'attempts' => $attempt,
                    'last_status' => $lastStatus,
                    'last_error' => '',
                    'updated_at' => \date('c'),
                ];
                $this->writeAsyncWebhookState($statePath, $successState);
                $this->emitAsyncWebhookMetric('delivered', [
                    'idempotency_key' => $idempotencyKey,
                    'attempt' => $attempt,
                    'status' => $lastStatus,
                ]);
                @\flock($lockHandle, LOCK_UN);
                @\fclose($lockHandle);
                return;
            }

            $error = (string)($result['error'] ?? 'unknown_error');
            $retryState = [
                'status' => 'retrying',
                'attempts' => $attempt,
                'last_status' => $lastStatus,
                'last_error' => $error,
                'idempotency_key' => $idempotencyKey,
                'next_retry_at' => \date('c', \time() + $this->computeAsyncRetryDelaySeconds($attempt + 1)),
                'updated_at' => \date('c'),
            ];
            $this->writeAsyncWebhookState($statePath, $retryState);
            $this->emitAsyncWebhookMetric('retry_scheduled', [
                'idempotency_key' => $idempotencyKey,
                'attempt' => $attempt,
                'status' => $lastStatus,
                'error' => $error,
            ]);
        }

        $failedState = [
            'status' => 'failed',
            'attempts' => $attempt,
            'last_status' => $lastStatus,
            'last_error' => $error,
            'updated_at' => \date('c'),
        ];
        $this->writeAsyncWebhookState($statePath, $failedState);
        $this->appendJsonLine($this->getAsyncRegisterDeadLetterPath(), [
            'ts' => \date('c'),
            'reason' => 'delivery_failed_after_retries',
            'idempotency_key' => $idempotencyKey,
            'payload' => $payload,
            'state' => $failedState,
        ]);
        $this->emitAsyncWebhookMetric('dead_letter_delivery_failed', [
            'idempotency_key' => $idempotencyKey,
            'attempts' => $attempt,
            'status' => $lastStatus,
            'error' => $error,
        ]);
        @\flock($lockHandle, LOCK_UN);
        @\fclose($lockHandle);
    }

    private function createCompanyElement($params)
    {
        $company = new \OnlineService\Site\Company();

        return $company->createCompanyElement($params);
    }

    /**
     * Единственная точка записи локальной связи компании с B24 в сценарии регистрации.
     * Создаёт/обновляет карточку в ИБ 23 только при уже существующей компании в B24 (companyId из CRM, этот хук после contact.add).
     * После успешного createCompanyElement пишет в crm.company UF с ID элемента на сайте (без догадок по getCompanyByB24ID).
     */
    private function upsertSiteCompanyLinkByB24Id(int $companyId, array $arFields, array &$dataContact): void
    {
        if ($companyId <= 0) {
            return;
        }

        $companyElementParams = [
            'OS_COMPANY_INN' => $arFields['UF_INN'],
            'OS_COMPANY_WEB_SITE' => $arFields['UF_SITE'],
            'OS_COMPANY_NAME' => $arFields['UF_NAME_COMPANY'],
            'OS_COMPANY_EMAIL' => $arFields['EMAIL'],
            'OS_COMPANY_PHONE' => $arFields['PERSONAL_PHONE'],
            'OS_COMPANY_B24_ID' => $companyId,
            'OS_COMPANY_CITY' => $arFields['UF_CITY'],
            'OS_COMPANY_ACTIVITY' => $arFields['UF_SPERE'] ?? '',
            'OS_COMPANY_JUR_ADDRESS' => $arFields['UF_JUR_ADDRESS'] ?? '',
            'OS_REQUSITES_FILE' => $this->getConfiguredFieldValue($arFields, RegisterUserCompanyConfig::getRequisitesFileField()),
            'LEGAN_MAIN_PHONE' => (string)($arFields['UF_MAIN_PHONE'] ?? ($arFields['WORK_PHONE'] ?? '')),
            'LEGAN_MOBILE_PHONE' => (string)($arFields['UF_MOBILE_PHONE'] ?? ($arFields['PERSONAL_PHONE'] ?? '')),
        ];

        // Доп. свойства элемента компании, полученные из webhook registration_webhook_company_updates_url.
        if (isset($arFields['CRM_COMPANY_UPDATES_IBLOCK_PROPS']) && \is_array($arFields['CRM_COMPANY_UPDATES_IBLOCK_PROPS'])) {
            foreach ($arFields['CRM_COMPANY_UPDATES_IBLOCK_PROPS'] as $k => $v) {
                if (!\is_string($k) || $k === '') {
                    continue;
                }
                if ($v === null || $v === '') {
                    continue;
                }
                $companyElementParams[$k] = $v;
            }
        }

        if (isset($arFields['USER_ID'])) {
            $companyElementParams['USER_ID'] = $arFields['USER_ID'];
        }

        $iblockElementId = $this->createCompanyElement($companyElementParams);
        if ($iblockElementId === false || (int) $iblockElementId <= 0) {
            global $APPLICATION;
            $APPLICATION->ThrowException(
                'Не удалось создать карточку компании на сайте (инфоблок). Регистрация прервана.',
                'site_company_iblock_failed'
            );

            return;
        }
        $result = $this->callB24Method('crm.company.update', [
            'id' => $companyId,
            'fields' => [
                CrmInboundUfMap::COMPANY_SITE_IBLOCK_ELEMENT_ID_UF => (string) (int) $iblockElementId,
            ],
        ], false);
        if (\is_array($result) && \array_key_exists('success', $result) && (int) $result['success'] === 0) {
            global $APPLICATION;
            $APPLICATION->ThrowException(
                'Не удалось записать в CRM связь компании с каталогом сайта (crm.company.update). Регистрация прервана.',
                'crm_company_site_uf_failed'
            );
        }
    }

    /**
     * Универсальный вызов CRM REST (`crm.*`) через n8n-прокси, а не прямой запрос к Bitrix24 с сайта.
     *
     * URL задаётся ключом `registration_crm_rest_proxy_webhook_url` (операция n8n `registration/crm-registration-rest-v1`);
     * транспорт — {@see \OnlineService\B24\N8nCrmGateway::callRestMethodWithWebhookUrl} (JSON: METHOD + PARAMS).
     * Для отдельных сценариев регистрации используются именованные вебхуки {@see callRegistrationWebhook}
     * (`registration_webhook_company_add_url`, `registration_webhook_contact_add_url` и т.д.).
     *
     * @param string $method Имя метода REST API, например `crm.requisite.add`
     * @param array $params Параметры в формате Bitrix REST
     */
    private function callB24Method($method, array $params, $debug = false)
    {
        $regProxy = \trim((string) self::getSyncConfigValue('registration_crm_rest_proxy_webhook_url', ''));
        if ($regProxy !== '') {
            return \OnlineService\B24\N8nCrmGateway::callRestMethodWithWebhookUrl(
                $regProxy,
                $method,
                $params,
                (bool) $debug
            );
        }

        global $APPLICATION;
        $APPLICATION->ThrowException(
            'Регистрация через n8n: задайте registration_crm_rest_proxy_webhook_url (узел registration/crm-registration-rest-v1).'
        );

        return [
            'success' => 0,
            'error' => 'registration_crm_rest_proxy_missing',
            'error_description' => 'registration_crm_rest_proxy_webhook_url is empty',
        ];
    }

    private function getConfiguredFieldValue(array $arFields, $fieldName)
    {
        return $arFields[$fieldName] ?? null;
    }

    /**
     * Мультиполя PHONE / EMAIL для crm.*.add: тип WORK, пустые VALUE не передаём (B24 иначе может не записать).
     *
     * @return array{PHONE: list<array{VALUE: string, VALUE_TYPE: string}>, EMAIL: list<array{VALUE: string, VALUE_TYPE: string}>}
     */
    private function buildB24CrmWorkPhoneAndEmailFields(array $arFields): array
    {
        $mobilePhone = \trim((string)($arFields['PERSONAL_PHONE'] ?? ''));
        $workPhone = \trim((string)($arFields['WORK_PHONE'] ?? ''));
        $email = \trim((string)($arFields['EMAIL'] ?? ''));
        $out = [
            'PHONE' => [],
            'EMAIL' => [],
        ];
        // crm.contact.add: PHONE — crm_multifield, можно передать несколько номеров с VALUE_TYPE (WORK/HOME/MOBILE и т.п.).
        // В регистрации: phone (WORK_PHONE) и mobilephone (PERSONAL_PHONE). Отправляем оба, если заданы и различаются.
        if ($workPhone !== '') {
            $out['PHONE'][] = ['VALUE' => $workPhone, 'VALUE_TYPE' => 'WORK'];
        }
        if ($mobilePhone !== '' && $mobilePhone !== $workPhone) {
            $out['PHONE'][] = ['VALUE' => $mobilePhone, 'VALUE_TYPE' => 'MOBILE'];
        }
        if ($out['PHONE'] === [] && $mobilePhone !== '') {
            $out['PHONE'][] = ['VALUE' => $mobilePhone, 'VALUE_TYPE' => 'WORK'];
        }
        if ($email !== '') {
            $out['EMAIL'][] = ['VALUE' => $email, 'VALUE_TYPE' => 'WORK'];
        }

        return $out;
    }

    private function findSiteCompanyByInn(string $inn): array
    {
        $inn = self::normalizeInnValue($inn);
        if ($inn === '') {
            return [];
        }

        $iblockId = 23;
        $baseSelect = ['ID', 'NAME', 'CODE', 'XML_ID', 'PROPERTY_OS_COMPANY_B24_ID'];
        $filters = [
            ['IBLOCK_ID' => $iblockId, '=PROPERTY_LEGAN_ENTITY_INN' => $inn],
            ['IBLOCK_ID' => $iblockId, '=PROPERTY_OS_COMPANY_INN' => $inn],
        ];

        foreach ($filters as $filter) {
            $rs = \CIBlockElement::GetList(['ID' => 'ASC'], $filter, false, ['nTopCount' => 1], $baseSelect);
            if ($row = $rs->Fetch()) {
                $resolvedInnHashes = [];
                $dbProps = \CIBlockElement::GetProperty($iblockId, (int)($row['ID'] ?? 0), ['sort' => 'asc']);
                while ($prop = $dbProps->Fetch()) {
                    $code = (string)($prop['CODE'] ?? '');
                    if ($code !== 'LEGAN_ENTITY_INN' && $code !== 'LEGAL_ENTITY_INN' && $code !== 'OS_COMPANY_INN') {
                        continue;
                    }
                    $propInn = self::normalizeInnValue((string)($prop['VALUE'] ?? ''));
                    if ($propInn !== '') {
                        $resolvedInnHashes[] = \substr(\sha1($propInn), 0, 8);
                    }
                }
                $resolvedInnHashes = \array_values(\array_unique($resolvedInnHashes));
                $innHash = \substr(\sha1($inn), 0, 8);
                $isExactByProps = \in_array($innHash, $resolvedInnHashes, true);
                if (!$isExactByProps) {
                    continue;
                }
                return [
                    'ID' => (int)($row['ID'] ?? 0),
                    'CODE' => (string)($row['CODE'] ?? ''),
                    'XML_ID' => (string)($row['XML_ID'] ?? ''),
                    'OS_COMPANY_B24_ID' => (string)($row['PROPERTY_OS_COMPANY_B24_ID_VALUE'] ?? ''),
                ];
            }
        }

        return [];
    }

    private static function normalizeInnValue($inn): string
    {
        return (string)\preg_replace('/\D+/', '', (string)$inn);
    }

    private function resolveExactCompanyIdByInnFromRequisites($requisites, string $targetInn): int
    {
        if (!\is_array($requisites)) {
            return 0;
        }
        $normalizedTargetInn = self::normalizeInnValue($targetInn);
        if ($normalizedTargetInn === '') {
            return 0;
        }
        foreach ($requisites as $row) {
            if (!\is_array($row)) {
                continue;
            }
            $entityId = (int)($row['ENTITY_ID'] ?? 0);
            if ($entityId <= 0) {
                continue;
            }
            $rowInn = self::normalizeInnValue($row['RQ_INN'] ?? '');
            if ($rowInn === '' || $rowInn !== $normalizedTargetInn) {
                continue;
            }

            return $entityId;
        }

        return 0;
    }

    /**
     * Опционально: webhook `check-crm-company-updates` — подтягивает актуальные поля компании из CRM для слияния в $arFields до contact.add.
     * Контракт: см. docs/reference/registration-n8n-webhooks.md (`registration_webhook_company_updates_url`).
     *
     * @param array<string, mixed> $arFields
     */
    private function maybeMergeCompanyUpdatesFromN8n(int $crmCompanyId, array &$arFields): void
    {
        if ($crmCompanyId <= 0) {
            return;
        }
        if (\trim((string) self::resolveRegistrationWebhookUrl('registration_webhook_company_updates_url')) === '') {
            return;
        }

        $webhook = self::postRegistrationWebhook('registration_webhook_company_updates_url', [
            'COMPANY_ID' => $crmCompanyId,
        ]);
        if (empty($webhook['used'])) {
            return;
        }
        if (empty($webhook['ok'])) {
            self::throwRegistrationWebhookFailure($webhook);
        }
        self::assertProbeWebhookContract('registration_webhook_company_updates_url', $webhook);
        $data = $webhook['data'] ?? null;
        if (!\is_array($data)) {
            self::throwWebhookContractViolation('registration_webhook_company_updates_url', 'body_not_json_object');
        }
        $result = self::unwrapRegistrationWebhookResult($data);
        if (!\is_array($result)) {
            self::throwWebhookContractViolation('registration_webhook_company_updates_url', 'result_not_object');
        }

        // 1) Патч полей регистрации (UF_*, email/phone и т.п.) — влияет на payload crm.contact.add и локальную карточку.
        $patch = $result['registration_fields'] ?? $result['site_user_fields'] ?? $result['merge_fields'] ?? null;
        if (\is_array($patch)) {
            foreach ($patch as $k => $v) {
                if (!\is_string($k) || $k === '') {
                    continue;
                }
                if ($v === null || $v === '') {
                    continue;
                }
                $arFields[$k] = $v;
            }
        }

        // 2) Пользовательские свойства элемента компании (ИБ) — будут применены при upsertSiteCompanyLinkByB24Id().
        $props = $result['iblock_company_properties'] ?? null;
        if (\is_array($props) && $props !== []) {
            $arFields['CRM_COMPANY_UPDATES_IBLOCK_PROPS'] = $props;
        }
    }

    private function enforceCompanyInnInRequisites(int $companyId, array $arFields, string $hypothesisId = 'H19'): void
    {
        if ($companyId <= 0) {
            return;
        }

        $companyRequisites = $this->callB24Method('crm.requisite.list', [
            'select' => ['ID', 'RQ_INN', 'ENTITY_ID', 'ENTITY_TYPE_ID', 'NAME'],
            'filter' => [
                'ENTITY_TYPE_ID' => 4,
                'ENTITY_ID' => $companyId,
            ],
        ], false);
        $this->assertRegistrationRestProxyOk($companyRequisites, 'crm.requisite.list');
        if (!\is_array($companyRequisites)) {
            global $APPLICATION;
            $APPLICATION->ThrowException(
                'Неожиданный ответ CRM (crm.requisite.list). Регистрация прервана.',
                'crm_requisite_list_invalid'
            );

            return;
        }

        foreach ($companyRequisites as $requisiteRow) {
            $requisiteId = (int)($requisiteRow['ID'] ?? 0);
            $requisiteInn = (string)($requisiteRow['RQ_INN'] ?? '');
            if ($requisiteId <= 0 || $requisiteInn !== '') {
                continue;
            }

            $forceUpdateResult = $this->callB24Method('crm.requisite.update', [
                'id' => $requisiteId,
                'fields' => [
                    'ENTITY_ID' => $companyId,
                    'ENTITY_TYPE_ID' => 4,
                    'RQ_INN' => (string)$arFields['UF_INN'],
                    'RQ_COMPANY_FULL_NAME' => (string)$arFields['UF_NAME_COMPANY'],
                ],
            ], false);
            $this->assertRegistrationRestProxyOk($forceUpdateResult, 'crm.requisite.update');
        }
    }

    private function createB24Company(array &$arFields)
    {
        global $APPLICATION;
        $this->lastSyncedCompanyB24Id = 0;
        $this->lastSyncedContactB24Id = 0;
        $companyId = false;
        $reqFile = [];
        $file = [];
        if( !empty($arFields['UF_REQ']) && !empty($arFields['UF_REQ']['name']) ){
            $file = $arFields['UF_REQ'];

            // Сохраняем в систему Битрикс
            $savedFileId = \CFile::SaveFile($file, 'os_requisites');
            $fileInfo = \CFile::GetFileArray($savedFileId);

            if ($file['error'] === 0) {
                $fileName = $file['name'];
                $filePath = $file['tmp_name'];

                // Читаем содержимое файла
                $fileContent = file_get_contents($filePath);

                if ($fileContent !== false) {
                    // Кодируем в base64
                    $fileData = [
                        $fileName,
                        base64_encode($fileContent),
                    ];

                    // Передаём в поле Bitrix24
                    $arFields[RegisterUserCompanyConfig::getRequisitesFileField()] = [
                        'fileData' => $fileData
                    ];
                }
            }
			else{
                // Вывести подробную ошибку
                $errorMessage = 'Ошибка загрузки файла реквизитов: ';
                switch ($file['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                        $errorMessage .= 'Размер файла превышает максимально допустимый размер, указанный в php.ini.';
                        break;
                    case UPLOAD_ERR_FORM_SIZE:
                        $errorMessage .= 'Размер файла превышает максимально допустимый размер, указанный в форме.';
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $errorMessage .= 'Файл был загружен только частично.';
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $errorMessage .= 'Файл не был загружен.';
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $errorMessage .= 'Отсутствует временная папка для загрузки файла.';
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $errorMessage .= 'Не удалось записать файл на диск.';
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        $errorMessage .= 'Загрузка файла была остановлена расширением PHP.';
                        break;
                    default:
                        $errorMessage .= 'Неизвестная ошибка (код: ' . $file['error'] . ').';
                        break;
                }
                $APPLICATION->ThrowException($errorMessage);
                return false;
            }
        }

        $phoneEmailForCrm = $this->buildB24CrmWorkPhoneAndEmailFields($arFields);

        // данные для контакта
        $dataContact = [
            'fields' => [
                'NAME' => $arFields['NAME'],
                'SECOND_NAME' => $arFields['SECOND_NAME'],
                'LAST_NAME' => $arFields['LAST_NAME'],
                'POST' => $arFields['WORK_POSITION'],
                'BIRTHDATE' => $arFields['PERSONAL_BIRTHDAY'],
                'OPENED' => 'Y',
                'ASSIGNED_BY_ID' => RegisterUserCompanyConfig::ASSIGNED_BY_ID,
                RegisterUserCompanyConfig::CRM_CONTACT_CITY_FIELD => $arFields['UF_CITY'],
            ],
            'params' => []
        ];
        if (!empty($phoneEmailForCrm['PHONE'])) {
            $dataContact['fields']['PHONE'] = $phoneEmailForCrm['PHONE'];
        }
        if (!empty($phoneEmailForCrm['EMAIL'])) {
            $dataContact['fields']['EMAIL'] = $phoneEmailForCrm['EMAIL'];
        }

        // если это компания или рекламынй агент
        if ($arFields['UF_TYPE'] == '5' || $arFields['UF_TYPE'] == '6') {
            $blockFallbackCompanyCreateDueToInnAmbiguity = false;
            // проверить заполненность ИНН и названия компании
            if (empty($arFields['UF_INN']) && empty($arFields['UF_NAME_COMPANY'])) {
                $APPLICATION->ThrowException('Вы регистрируйтесь как рекламный агент или юридическое лицо. Поля "Название компании", "ИНН организации" обязательно для заполнения!');
                return false;
            } else {
                // если это рекламный агент
                if ($arFields['UF_ADVERSTERING_AGENT'] == 'on') {
                    $dataContact['fields'][RegisterUserCompanyConfig::CRM_CONTACT_NOTE_FIELD] = RegisterUserCompanyConfig::REGISTRATION_NOTE_AD_AGENT;
                }
                $dataRequisite = [];
                $normalizedInn = self::normalizeInnValue((string) ($arFields['UF_INN'] ?? ''));
                if ($normalizedInn !== '') {
                    $dataRequisite = [];
                    if (isset($arFields['CRM_INN_PRECHECK']) && \is_array($arFields['CRM_INN_PRECHECK'])) {
                        $dataRequisite = (array) $arFields['CRM_INN_PRECHECK'];
                    } else {
                        $dataRequisite = $this->crmCheckInnUniqueness($arFields);
                    }
                    if ($dataRequisite === false) {
                        return false;
                    }
                }

                $localCompany = $this->findSiteCompanyByInn((string)$arFields['UF_INN']);
                $localB24Id = (int)($localCompany['OS_COMPANY_B24_ID'] ?? 0);
                $resolvedByLocalB24Id = false;
                $localBindingExists = !empty($localCompany) && $localB24Id > 0;
                if ($localBindingExists) {
                    $companyById = $this->callB24Method('crm.company.get', ['id' => $localB24Id], false);
                    $resolvedByLocalB24Id = is_array($companyById) && (int)($companyById['ID'] ?? 0) > 0;
                    if ($resolvedByLocalB24Id) {
                        if (!empty($dataRequisite)) {
                            $crmCompanyIdForInn = $this->resolveExactCompanyIdByInnFromRequisites($dataRequisite, $normalizedInn);
                            if ($crmCompanyIdForInn > 0 && $crmCompanyIdForInn !== $localB24Id) {
                                $APPLICATION->ThrowException(
                                    'Привязка компании на сайте по ИНН не совпадает с результатом проверки CRM (n8n). Обратитесь к менеджеру.'
                                );

                                return false;
                            }
                        }
                        $dataRequisite = [[
                            'ID' => 0,
                            'RQ_INN' => '',
                            'ENTITY_ID' => $localB24Id,
                        ]];
                        $companyId = $localB24Id;
                        $dataContact['fields']['COMPANY_ID'] = $localB24Id;
                        $this->maybeMergeCompanyUpdatesFromN8n($localB24Id, $arFields);
                        $this->enforceCompanyInnInRequisites($localB24Id, $arFields, 'H19');
                    } else {
                        $localBindingExists = false;
                    }
                }

                if (!empty($dataRequisite)) {
                    $candidateCompanyId = $this->resolveExactCompanyIdByInnFromRequisites($dataRequisite, (string) $arFields['UF_INN']);
                    $candidateCompanyExists = false;
                    if ($candidateCompanyId > 0) {
                        // Не REST‑прокси: отдельный webhook n8n `crm.requisite.list` для регистрации.
                        // Идея: повторно запросить реквизиты по ИНН и убедиться, что ENTITY_ID (companyId) не фантомный.
                        $innNorm = self::normalizeInnValue((string) ($arFields['UF_INN'] ?? ''));
                        $webhook = self::postRegistrationWebhook('registration_webhook_crm_requisite_list_url', [
                            'crmMethod' => 'crm.requisite.list',
                            'crmParams' => [
                                'select' => ['ID', 'RQ_INN', 'ENTITY_TYPE_ID', 'ENTITY_ID'],
                                'filter' => [
                                    'ENTITY_TYPE_ID' => 4,
                                    'RQ_INN' => $innNorm,
                                ],
                            ],
                        ]);
                        if (!empty($webhook['used'])) {
                            if (empty($webhook['ok'])) {
                                self::throwRegistrationWebhookFailure($webhook);
                            }
                            self::assertProbeListWebhookContract('registration_webhook_crm_requisite_list_url', $webhook);
                            $unwrapped = self::unwrapRegistrationWebhookResult($webhook['data'] ?? []);
                            $candidateCompanyExists = \is_array($unwrapped)
                                && $this->resolveExactCompanyIdByInnFromRequisites($unwrapped, $innNorm) === (int) $candidateCompanyId;
                        }
                    }


                    if (!$candidateCompanyExists) {
                        $dataRequisite = [];
                        if ($candidateCompanyId > 0) {
                            $blockFallbackCompanyCreateDueToInnAmbiguity = true;
                        }
                    } else {
                        $dataContact['fields']['COMPANY_ID'] = $candidateCompanyId;
                        $companyId = $candidateCompanyId;
                        $this->maybeMergeCompanyUpdatesFromN8n((int) $candidateCompanyId, $arFields);
                        $this->enforceCompanyInnInRequisites((int)$companyId, $arFields, 'H19');
                    }
                }
                if ($blockFallbackCompanyCreateDueToInnAmbiguity && (int) ($companyId ?: 0) <= 0) {
                    $APPLICATION->ThrowException(
                        'Компания по ИНН найдена в CRM, но подтверждение через n8n не удалось. Обратитесь к менеджеру или повторите позже.',
                        'crm_inn_company_ambiguous'
                    );

                    return false;
                }
                if ((int) ($companyId ?: 0) <= 0) {
                    $crmCompanyWebField = 'UF_CRM_1777119084064';
                    $crmCompanyMainPhoneField = 'UF_CRM_1777069666894';
                    $crmCompanyMobilePhoneField = 'UF_CRM_1777069676348';
                    $crmCompanySphereField = 'UF_CRM_1777119807943';
                    $crmCompanyJurAddressField = 'UF_CRM_1777120939583';
                    $crmCompanyCityField = RegisterUserCompanyConfig::CRM_COMPANY_CITY_FIELD;
                    /*Создание компании*/
                    $peCompany = $this->buildB24CrmWorkPhoneAndEmailFields($arFields);
                    $qrCompanyInfo = [
                        'fields' => [
                            'TITLE' => $arFields['UF_NAME_COMPANY'],
                            'WEB' => [[
                                'VALUE' => $arFields['UF_SITE'],
                                "VALUE_TYPE" => "WORK"
                            ]],
                            $crmCompanyWebField => $arFields['UF_SITE'],
                            $crmCompanySphereField => $arFields['UF_SPERE'],
                            $crmCompanyJurAddressField => $arFields['UF_JUR_ADDRESS'],
                            $crmCompanyCityField => $arFields['UF_CITY'],
                            $crmCompanyMainPhoneField => (string)($arFields['UF_MAIN_PHONE'] ?? ($arFields['WORK_PHONE'] ?? '')),
                            $crmCompanyMobilePhoneField => (string)($arFields['UF_MOBILE_PHONE'] ?? ($arFields['PERSONAL_PHONE'] ?? '')),
                            RegisterUserCompanyConfig::CRM_REQUISITES_FILE_FIELD => $this->getConfiguredFieldValue($arFields, RegisterUserCompanyConfig::getRequisitesFileField()),
                            'COMPANY_TYPE' => 'CUSTOMER',
                            'ASSIGNED_BY_ID' => RegisterUserCompanyConfig::ASSIGNED_BY_ID,
                        ]
                    ];
                    if (!empty($peCompany['PHONE'])) {
                        $qrCompanyInfo['fields']['PHONE'] = $peCompany['PHONE'];
                    }
                    if (!empty($peCompany['EMAIL'])) {
                        $qrCompanyInfo['fields']['EMAIL'] = $peCompany['EMAIL'];
                    }

                    $companyId = $this->crmAddCompany($qrCompanyInfo);
					
                    if (!empty($companyId)) {
                        $addedCompanyId = (int) $companyId;
                        $dataCompany = $this->companyRowAfterGet(
                            $this->callB24Method('crm.company.get', ['id' => $addedCompanyId]),
                            $addedCompanyId
                        );
                        $entityCompanyId = (int) ($dataCompany['ID'] ?? 0);

                        /*Добавление реквизита к компании*/
                        $qrRequisite = [
                            'fields' => [
                                'ENTITY_ID' => $entityCompanyId,
                                'ENTITY_TYPE_ID' => 4,
                                'NAME' => 'Реквизит с формы сайта',
                                'PRESET_ID' => 1,
                                'ACTIVE' => 'Y',
                                'RQ_INN' => (string)$arFields['UF_INN'],
                                'RQ_COMPANY_FULL_NAME' => (string)$arFields['UF_NAME_COMPANY'],
                            ]
                        ];
                        $requisiteId = $this->callB24Method("crm.requisite.add", $qrRequisite);
                        $this->assertRegistrationRestProxyOk($requisiteId, 'crm.requisite.add');
                        if (!\is_numeric($requisiteId) || (int) $requisiteId <= 0) {
                            global $APPLICATION;
                            $APPLICATION->ThrowException(
                                'CRM не вернула ID реквизита (crm.requisite.add). Регистрация прервана.',
                                'crm_requisite_add_no_id'
                            );
                        }

                        /*Обновление реквизитов у компании*/
                        $requisiteUpdateResult = null;
                        $qrRequisites = array(
                            'id' => $requisiteId,
                            'fields' => [
                                'ENTITY_ID' => $entityCompanyId,
                                'ENTITY_TYPE_ID' => 4,
                                'RQ_INN' => (string)$arFields['UF_INN'],
                                'RQ_KPP' => (string)$arFields['UF_KPP'],
                                'RQ_COMPANY_FULL_NAME' => (string)$arFields['UF_NAME_COMPANY']
                            ]
                        );
                        $requisiteUpdateResult = $this->callB24Method("crm.requisite.update", $qrRequisites);
                        $this->assertRegistrationRestProxyOk($requisiteUpdateResult, 'crm.requisite.update');

                        $dataContact['fields']['COMPANY_ID'] = $entityCompanyId;
                    }
                }
            }
        }

        $companyIdInt = \is_numeric($companyId) ? (int) $companyId : 0;

        if ($companyIdInt <= 0) {
            global $APPLICATION;

            $APPLICATION->ThrowException(
                'Не удалось завершить регистрацию в CRM. Повторите попытку позже или обратитесь в поддержку.',
                'crm_company_create_failed'
            );

            return false;
        }

        $siteUserId = (int)($arFields['USER_ID'] ?? 0);
        if ($siteUserId > 1) {
            $dataContact['fields'][RegisterUserCompanyConfig::CRM_CONTACT_SITE_USER_ID_FIELD] = $siteUserId;
        }

        $contactId = $this->crmAddContact($dataContact);

        $summarizeRestResult = static function ($result): string {
            if (is_scalar($result) || $result === null) {
                return (string)$result;
            }
            if (!is_array($result)) {
                return '[' . gettype($result) . ']';
            }

            $summary = [
                'success' => isset($result['success']) ? (string)$result['success'] : '',
                'error' => isset($result['error']) ? (string)$result['error'] : '',
                'error_description' => isset($result['error_description']) ? (string)$result['error_description'] : '',
            ];
            if (isset($result['transport_response']) && is_array($result['transport_response'])) {
                $transport = $result['transport_response'];
                if (isset($transport['error'])) {
                    $summary['transport_error'] = (string)$transport['error'];
                }
                if (isset($transport['response']) && is_scalar($transport['response'])) {
                    $summary['transport_response'] = (string)$transport['response'];
                }
            }

            return json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        };

        $contactIdInt = \is_numeric($contactId) ? (int) $contactId : 0;


        if ($companyIdInt > 0 && $contactIdInt > 0) {
            $this->bindContactToCompany($companyIdInt, $contactIdInt);
            // Локальные сущности создаём только после успешного обмена с B24.
            $this->upsertSiteCompanyLinkByB24Id($companyIdInt, $arFields, $dataContact);
            $this->lastSyncedCompanyB24Id = $companyIdInt;
            $this->lastSyncedContactB24Id = $contactIdInt;
            return true;
        }
        $companyIdScalar = $summarizeRestResult($companyId);
        $contactIdScalar = $summarizeRestResult($contactId);

        $APPLICATION->ThrowException(
            'Не удалось завершить регистрацию в CRM. Повторите попытку позже или обратитесь в поддержку.',
            'crm_register_incomplete'
        );
        return false;
    } 


    public function OnBeforeUserRegisterHandler(&$arFields) {
        if (!empty($GLOBALS['OS_REGISTER_USER_PRECHECK_DONE'])) {
            $arFields['ACTIVE'] = 'N';

            return $arFields;
        }
        $arFields['ACTIVE'] = 'N';

        if ($this->runSyncPreCheck($arFields)) {
            return $arFields;
        }
        return false;
    }

    public function OnAfterUserRegisterHandler(&$arFields) {
        // если регистрация успешна то
        if($arFields["USER_ID"]>0)
        {
            $response = $this->isUserRegistered($arFields,false);
            $legacySyncEnabled = $this->isLegacySyncEnabled();
            if ($legacySyncEnabled && !$response) {
                $this->registerCoreInCrm($arFields);
                $response = $this->isUserRegistered($arFields,false);
            }

            if( $response ){
                $contactId = (int)($response['ID'] ?? 0);
                if ($contactId <= 0) {
                    return;
                }

                // Обновляем пользователя: ID контакта B24 в каноническом UF и в легаси-поле
                $targetUserId = (int)($arFields["USER_ID"] ?? 0);
                if ($targetUserId <= 1) {
                    return;
                }
                $user = new \CUser;
                $user->Update($targetUserId, [
                    'ACTIVE' => 'N',
                    UserSyncConfig::USER_UF_CONTACT_B24_ID => $contactId,
                    UserSyncConfig::USER_UF_CONTACT_B24_ID_LEGACY => $contactId,
                ]);

                if ($this->isAsyncPostRegisterEnabled()) {
                    $payload = $this->buildAsyncPostRegisterPayload($arFields, $contactId);
                    $this->runAsyncPostRegisterWebhook($payload);
                }

                unset($arFields["PASSWORD"]);
                unset($arFields["CONFIRM_PASSWORD"]);

                \Bitrix\Main\Mail\Event::send([
                    'EVENT_NAME' => 'NEW_USER_CONFIRM',
                    'LID' => 's1', // ID вашего сайта
                    'C_FIELDS' => $arFields,
                ]);
            } elseif ($this->isAsyncPostRegisterEnabled()) {
                $payload = $this->buildAsyncPostRegisterPayload($arFields, 0);
                $this->runAsyncPostRegisterWebhook($payload);
            }
        }
    }

    /**
     * Безопасный путь синхронизации из ajax-register-action:
     * создаёт/находит компанию и контакт в B24, но не трогает ACTIVE у пользователей сайта.
     */
    public function syncFromSiteRegistration(array $arFields): bool
    {
        $result = $this->registerCoreInCrm($arFields);
        if ($result !== false) {
            $targetUserId = (int)($arFields['USER_ID'] ?? 0);
            if ($targetUserId > 1 && $this->lastSyncedContactB24Id > 0) {
                $user = new \CUser();
                $user->Update($targetUserId, [
                    UserSyncConfig::USER_UF_CONTACT_B24_ID => $this->lastSyncedContactB24Id,
                    UserSyncConfig::USER_UF_CONTACT_B24_ID_LEGACY => $this->lastSyncedContactB24Id,
                ]);
            }
        }

        return $result !== false;
    }

    private function deleteStaffB24($arUser, $companyId, $idCompanySite) {
        $qrList = [
            'fields' => [],
            'params' => [],
            'select' => [],
            'filter' => ["EMAIL" => $arUser["EMAIL"]]
        ];
        $arResult = $this->callB24Method("crm.contact.list", $qrList);

        if ($arResult['ID']) {
            // убрать рекламную агентность		
            $this->callB24Method("crm.contact.update", [
                "id" => $arResult['ID'],
                "fields" => [
                    RegisterUserCompanyConfig::CRM_CONTACT_AD_AGENT_FIELD => ''
                ]
            ]);
            intec\eklectika\advertising_agent\Client::eraseStatusRA($arUser["ID"], $idCompanySite);

            // уволить его!		
            $this->callB24Method("crm.contact.company.delete", [
                'id' => $arResult['ID'],
                'fields' => array('COMPANY_ID' => $companyId),
            ]);
            // прощай сотрудник, ты больше нам не нужен =(
        }
    }
}