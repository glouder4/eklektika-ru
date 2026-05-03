<?php

namespace OnlineService\B24\Registration\AjaxRegister;

use Bitrix\Main\Web\HttpClient;

/**
 * HTTP-вызовы n8n для сценария регистрации (конфиг EKLEKTIKA_SYNC_CONFIG / config.local.php).
 * Не содержит доменной логики «компания vs контакт» — только транспорт.
 */
final class CrmRegistrationN8nTransport
{
    /**
     * Абсолютный путь к `eklektika.sync/config.local.php` (DOCUMENT_ROOT или рядом с eklektika.b24.registration).
     */
    private static function resolveSyncLocalConfigPath(): ?string
    {
        static $done = false;
        static $path = null;
        if ($done) {
            return $path;
        }
        $done = true;
        $candidates = [];
        $doc = \rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
        if ($doc !== '') {
            $candidates[] = $doc . '/local/modules/eklektika.sync/config.local.php';
        }
        $b24ModuleDir = \dirname(__DIR__, 3);
        $candidates[] = \dirname($b24ModuleDir) . '/eklektika.sync/config.local.php';
        foreach ($candidates as $p) {
            if (\is_file($p)) {
                $path = $p;

                return $path;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function readSyncLocalConfigFile(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $path = self::resolveSyncLocalConfigPath();
        if ($path === null) {
            return $cache = [];
        }
        $local = include $path;

        return $cache = (\is_array($local) ? $local : []);
    }

    private static function getSyncConfigValue(string $key, $default = null)
    {
        $cfg = $GLOBALS['EKLEKTIKA_SYNC_CONFIG'] ?? [];
        if (\is_array($cfg) && \array_key_exists($key, $cfg)) {
            $v = $cfg[$key];
            // Плейсхолдер `''` из дефолтов bootstrap не должен блокировать чтение `config.local.php`
            // (иначе resolveRegistrationWebhookUrl собирает URL только из base+suffix и легко получить 404 в n8n).
            if ($v !== '' && $v !== null) {
                return $v;
            }
        }

        $localCfg = self::readSyncLocalConfigFile();
        if (\array_key_exists($key, $localCfg)) {
            return $localCfg[$key];
        }

        return $default;
    }

    /**
     * Префикс REST Bitrix24 для n8n: либо из {@see registration_webhook_*} как массив с `b24_rest_prefix`,
     * либо глобальный KIT из {@see RestTransportConfig::buildKitWebhookPrefix} (как раньше).
     */
    public static function resolveRegistrationWebhookB24Prefix(string $configKey): string
    {
        $raw = self::getSyncConfigValue($configKey, null);
        if (\is_array($raw)) {
            $p = \trim((string) ($raw['b24_rest_prefix'] ?? $raw['B24_REST_PREFIX'] ?? $raw['bitrix_rest_prefix'] ?? ''));
            if ($p !== '') {
                return \rtrim($p, '/');
            }
        }

        return self::resolveN8nOutboundB24RestPrefix();
    }

    /**
     * Канонический `crm.*` для ключа (если в конфиге не задано явно в массиве `crm_method`).
     * Для `registration_webhook_company_updates_url` по умолчанию `crm.company.get` (сценарий может отличаться).
     *
     * @return string пустая строка — не добавлять CRM_METHOD в payload
     */
    public static function resolveRegistrationWebhookCrmMethod(string $configKey): string
    {
        $raw = self::getSyncConfigValue($configKey, null);
        if (\is_array($raw)) {
            $m = \trim((string) ($raw['crm_method'] ?? $raw['method'] ?? $raw['METHOD'] ?? ''));
            if ($m !== '') {
                return $m;
            }
        }

        return self::canonicalCrmMethodForRegistrationWebhookKey($configKey);
    }

    private static function canonicalCrmMethodForRegistrationWebhookKey(string $configKey): string
    {
        static $map = [
            'registration_webhook_unique_url' => 'crm.contact.list',
            'registration_webhook_inn_url' => 'crm.requisite.list',
            'registration_webhook_company_add_url' => 'crm.company.add',
            'registration_webhook_contact_add_url' => 'crm.contact.add',
            'registration_webhook_crm_company_get_url' => 'crm.company.get',
            'registration_webhook_crm_company_update_url' => 'crm.company.update',
            'registration_webhook_crm_contact_company_add_url' => 'crm.contact.company.add',
            'registration_webhook_crm_company_contact_add_url' => 'crm.company.contact.add',
            'registration_webhook_crm_requisite_list_url' => 'crm.requisite.list',
            'registration_webhook_crm_requisite_update_url' => 'crm.requisite.update',
            'registration_webhook_crm_requisite_add_url' => 'crm.requisite.add',
            'registration_webhook_crm_contact_list_url' => 'crm.contact.list',
            'registration_webhook_crm_contact_update_url' => 'crm.contact.update',
            'registration_webhook_company_updates_url' => 'crm.company.get',
        ];

        return (string) ($map[$configKey] ?? '');
    }

    private static function enrichRegistrationWebhookPayload(string $configKey, array $payload): array
    {
        $prefix = self::resolveRegistrationWebhookB24Prefix($configKey);
        if ($prefix !== '') {
            $payload['B24_REST_PREFIX'] = $prefix;
        }
        $crmMethod = self::resolveRegistrationWebhookCrmMethod($configKey);
        if ($crmMethod !== '') {
            $payload['CRM_METHOD'] = $crmMethod;
        }

        return $payload;
    }

    private static function resolveN8nOutboundB24RestPrefix(): string
    {
        if (!\class_exists(\OnlineService\B24\Config\RestTransportConfig::class)) {
            return '';
        }
        if (!\defined('URL_B24') || !\defined('B24_REST_WEBHOOK_KIT')) {
            return '';
        }

        return \OnlineService\B24\Config\RestTransportConfig::buildKitWebhookPrefix();
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

    public static function resolveRegistrationWebhookUrl(string $configKey): string
    {
        $raw = self::getSyncConfigValue($configKey, null);
        if (\is_array($raw)) {
            $u = \trim((string) ($raw['url'] ?? $raw['n8n_url'] ?? $raw['webhook_url'] ?? ''));
            if ($u !== '') {
                return $u;
            }
        } else {
            $direct = \trim((string) $raw);
            if ($direct !== '') {
                return $direct;
            }
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

    public static function resolveAsyncPostRegisterWebhookUrl(): string
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

    public static function formatRegistrationWebhookFailureMessage(array $webhook): string
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

    public static function throwWebhookFailure(array $webhook): void
    {
        global $APPLICATION;
        $APPLICATION->ThrowException(self::formatRegistrationWebhookFailureMessage($webhook), 'n8n_registration_webhook');
    }

    /**
     * POST JSON на вебхук регистрации.
     *
     * @return array{used?: bool, ok?: bool, status?: int, error?: string, data?: array, raw_preview?: string}
     */
    public static function post(string $configKey, array $payload): array
    {
        $payload = self::enrichRegistrationWebhookPayload($configKey, $payload);
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
}

