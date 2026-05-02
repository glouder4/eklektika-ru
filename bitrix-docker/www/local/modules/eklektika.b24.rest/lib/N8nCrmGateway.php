<?php

namespace OnlineService\B24;

use OnlineService\B24\Config\RestTransportConfig;

/**
 * Проксирование вызовов crm.* в Bitrix24 через n8n (вебхук).
 * Ответ совместим с {@see RestClient::callRestMethod}: success/result или фолбэк к «сырому» JSON B24.
 */
final class N8nCrmGateway
{
    /**
     * URL вебхука n8n с телом { METHOD, PARAMS } (ветка WH legacy в workflow Site to CRM).
     * Приоритет: env EKLEKTIKA_N8N_CRM_WEBHOOK_URL → GLOBALS/config.local `n8n_crm_rest_proxy_webhook_url`.
     * Используется указанная строка URL без преобразований.
     */
    public static function resolveWebhookUrlForTransport(): string
    {
        return self::readWebhookUrl();
    }

    private static function readSyncLocalConfigArray(): array
    {
        $doc = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
        $path = $doc . '/local/modules/eklektika.sync/config.local.php';
        if (is_file($path)) {
            $cfg = include $path;

            return is_array($cfg) ? $cfg : [];
        }

        return [];
    }

    private static function readWebhookUrl(): string
    {
        $v = getenv('EKLEKTIKA_N8N_CRM_WEBHOOK_URL');
        if (\is_string($v) && \trim($v) !== '') {
            return \trim($v);
        }
        $u = '';
        $cfg = $GLOBALS['EKLEKTIKA_SYNC_CONFIG'] ?? [];
        if (\is_array($cfg)) {
            $u = \trim((string) ($cfg['n8n_crm_rest_proxy_webhook_url'] ?? ''));
        }
        if ($u === '') {
            $local = self::readSyncLocalConfigArray();
            $u = \trim((string) ($local['n8n_crm_rest_proxy_webhook_url'] ?? ''));
        }

        return $u;
    }

    private static function readOutboundSecret(): string
    {
        $v = getenv('EKLEKTIKA_N8N_SITE_OUTBOUND_SECRET');
        if (is_string($v) && trim($v) !== '') {
            return trim($v);
        }

        $cfg = $GLOBALS['EKLEKTIKA_SYNC_CONFIG'] ?? [];
        if (is_array($cfg)) {
            $fromSync = (string) ($cfg['inbound_secret'] ?? '');
            if (trim($fromSync) !== '') {
                return trim($fromSync);
            }
        }

        return '';
    }

    private static function shouldVerifyTls(string $queryUrl): bool
    {
        $envValue = getenv('N8N_CRM_BRIDGE_TLS_VERIFY');
        if ($envValue !== false) {
            $normalized = strtolower(trim((string) $envValue));
            if (in_array($normalized, ['0', 'false', 'off', 'no'], true)) {
                return false;
            }
            if (in_array($normalized, ['1', 'true', 'on', 'yes'], true)) {
                return true;
            }
        }

        $host = (string) parse_url($queryUrl, PHP_URL_HOST);
        if ($host === '' || in_array($host, ['localhost', '127.0.0.1'], true)) {
            return false;
        }

        return true;
    }

    /**
     * Уже резолвленный URL вебхука (как задан в конфиге или env).
     *
     * @return mixed результат REST (ключ result) либо структура ошибки ['success'=>0,...]
     */
    public static function callRestMethodWithWebhookUrl(string $webhookUrl, string $method, array $params, bool $debug = false)
    {
        $url = \trim($webhookUrl);
        if ($url === '') {
            return [
                'success' => 0,
                'error' => 'n8n webhook URL is empty',
            ];
        }

        $payload = [
            'METHOD' => $method,
            'PARAMS' => $params,
        ];
        $jsonBody = \json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($jsonBody === false) {
            return [
                'success' => 0,
                'error' => 'json_encode_failed',
            ];
        }

        $headers = [
            'Content-Type: application/json; charset=UTF-8',
            'Accept: application/json',
        ];
        $secret = self::readOutboundSecret();
        if ($secret !== '') {
            $headers[] = 'X-Sync-Token: ' . $secret;
        }

        $curl = \curl_init();
        $verifyTls = self::shouldVerifyTls($url);
        \curl_setopt_array($curl, [
            CURLOPT_SSL_VERIFYPEER => $verifyTls,
            CURLOPT_SSL_VERIFYHOST => $verifyTls ? 2 : 0,
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_POSTFIELDS => $jsonBody,
            CURLOPT_TIMEOUT => RestTransportConfig::REQUEST_TIMEOUT_SECONDS,
            CURLOPT_CONNECTTIMEOUT => RestTransportConfig::CONNECT_TIMEOUT_SECONDS,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $result = \curl_exec($curl);
        $httpCode = \curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = \curl_error($curl);
        $curlErrno = \curl_errno($curl);
        \curl_close($curl);

        if ($curlErrno) {
            return [
                'success' => 0,
                'error' => 'CURL Error: ' . $curlError,
                'errno' => $curlErrno,
            ];
        }

        if ($httpCode !== 200) {
            return [
                'success' => 0,
                'error' => 'HTTP Error: ' . $httpCode,
                'response' => $result,
            ];
        }

        $decoded = \json_decode((string) $result, true);
        if (\json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => 0,
                'error' => 'JSON Parse Error: ' . \json_last_error_msg(),
                'raw_response' => $result,
            ];
        }

        if (isset($decoded['success']) && (int) $decoded['success'] === 1 && \array_key_exists('result', $decoded)) {
            return $decoded['result'];
        }

        if (\is_array($decoded) && \array_key_exists('result', $decoded) && !\array_key_exists('success', $decoded)) {
            if (\array_key_exists('error', $decoded) && $decoded['error'] !== null && $decoded['error'] !== '') {
                return [
                    'success' => 0,
                    'error' => (string) $decoded['error'],
                    'error_description' => isset($decoded['error_description']) ? (string) $decoded['error_description'] : '',
                ];
            }

            return $decoded['result'];
        }

        if (\is_array($decoded) && isset($decoded['error'])) {
            return [
                'success' => 0,
                'error' => (string) $decoded['error'],
                'error_description' => isset($decoded['error_description']) ? (string) $decoded['error_description'] : '',
            ];
        }

        return [
            'success' => 0,
            'error' => 'unexpected_n8n_response',
            'transport_response' => $decoded,
        ];
    }

    /**
     * Глобальный транспорт (legacy вебхук / env), не используется {@see RegisterUserCompany} для регистрации.
     *
     * @return mixed результат REST (ключ result) либо структура ошибки ['success'=>0,...]
     */
    public static function callRestMethod(string $method, array $params, bool $debug = false)
    {
        $url = self::readWebhookUrl();
        if ($url === '') {
            return [
                'success' => 0,
                'error' => 'n8n CRM webhook is not configured (EKLEKTIKA_N8N_CRM_WEBHOOK_URL or n8n_crm_rest_proxy_webhook_url)',
            ];
        }

        return self::callRestMethodWithWebhookUrl($url, $method, $params, $debug);
    }
}
