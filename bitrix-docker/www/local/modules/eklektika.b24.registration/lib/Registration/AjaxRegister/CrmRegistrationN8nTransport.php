<?php

namespace OnlineService\B24\Registration\AjaxRegister;

use Bitrix\Main\Web\HttpClient;

/**
 * HTTP-вызовы n8n для сценария регистрации (конфиг EKLEKTIKA_SYNC_CONFIG / config.local.php).
 * Не содержит доменной логики «компания vs контакт» — только транспорт.
 */
final class CrmRegistrationN8nTransport
{
    private static function getSyncConfigValue(string $key, $default = null)
    {
        $cfg = $GLOBALS['EKLEKTIKA_SYNC_CONFIG'] ?? [];
        if (\is_array($cfg) && \array_key_exists($key, $cfg)) {
            return $cfg[$key];
        }

        $doc = \rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
        $path = $doc . '/local/modules/eklektika.sync/config.local.php';
        if (\is_file($path)) {
            $localCfg = include $path;
            if (\is_array($localCfg) && \array_key_exists($key, $localCfg)) {
                return $localCfg[$key];
            }
        }

        return $default;
    }

    private static function enrichRegistrationWebhookPayload(array $payload): array
    {
        $prefix = self::resolveN8nOutboundB24RestPrefix();
        if ($prefix !== '') {
            $payload['B24_REST_PREFIX'] = $prefix;
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
        $payload = self::enrichRegistrationWebhookPayload($payload);
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

