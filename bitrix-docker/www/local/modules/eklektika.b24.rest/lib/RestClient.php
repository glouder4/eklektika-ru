<?php

namespace OnlineService\B24;

use OnlineService\B24\Config\RestTransportConfig;

/**
 * Единый транспорт HTTP для Bitrix24: прямой REST по входящему вебхуку и POST на прокси-сценарии сайта.
 */
final class RestClient
{
    private static function readSiteSyncLocalConfig(): array
    {
        $path = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\') . '/local/sync/config.local.php';
        if (!is_file($path)) {
            return [];
        }
        $cfg = include $path;
        return is_array($cfg) ? $cfg : [];
    }

    private static function resolveInboundSyncToken(): string
    {
        $cfg = $GLOBALS['EKLEKTIKA_SYNC_CONFIG'] ?? [];
        $cfgToken = is_array($cfg) ? (string)($cfg['inbound_secret'] ?? '') : '';
        if (trim($cfgToken) !== '') {
            return trim($cfgToken);
        }

        $localCfg = self::readSiteSyncLocalConfig();
        $localToken = (string)($localCfg['inbound_secret'] ?? '');
        if (trim($localToken) !== '') {
            return trim($localToken);
        }

        $envToken = getenv('EKLEKTIKA_SYNC_INBOUND_SECRET');
        return is_string($envToken) ? trim($envToken) : '';
    }

    private static function buildOptionalSyncHeaders(string $queryUrl): array
    {
        if (strpos($queryUrl, RestTransportConfig::SITE_REQUESTS_HANDLER_PATH) === false) {
            return [];
        }

        $token = self::resolveInboundSyncToken();
        if ($token === '') {
            return [];
        }

        return ['X-Sync-Token: ' . $token];
    }

    private static function shouldVerifyTls(string $queryUrl): bool
    {
        $envValue = getenv('B24_REST_TLS_VERIFY');
        if ($envValue !== false) {
            $normalized = strtolower(trim((string)$envValue));
            if (in_array($normalized, ['0', 'false', 'off', 'no'], true)) {
                return false;
            }
            if (in_array($normalized, ['1', 'true', 'on', 'yes'], true)) {
                return true;
            }
        }

        $host = (string)parse_url($queryUrl, PHP_URL_HOST);
        if ($host === '' || in_array($host, ['localhost', '127.0.0.1'], true)) {
            return false;
        }

        return true;
    }

    /**
     * POST на URL вида .../rest/1/{token}/{method}.json; при успехе возвращает $decoded['result'] (как legacy sendRequestB24).
     *
     * @return mixed значение ключа result, либо массив ошибки с ключом success === 0
     */
    public static function callRestMethod(string $method, array $params, bool $debug = false)
    {
        $response = self::postSiteRequestsHandler([
            'ACTION' => 'CRM_METHOD',
            'METHOD' => $method,
            'PARAMS' => $params,
        ], $debug);

        if (isset($response['success']) && (int)$response['success'] === 1 && array_key_exists('result', $response)) {
            return $response['result'];
        }

        if (
            $method === 'crm.contact.list'
            && isset($response['success'], $response['error'])
            && (int)$response['success'] === 0
            && strpos((string)$response['error'], 'Unsupported CRM METHOD: crm.contact.list') !== false
        ) {
            return [];
        }

        return [
            'success' => 0,
            'error' => 'CRM method call via site_requests_handler failed',
            'transport_response' => $response,
        ];
    }

    /**
     * POST на прокси /local/classes/ajax.php — полный декодированный JSON (как legacy sendRequest()).
     */
    public static function postAjaxProxy(array $params, bool $debug = false): array
    {
        if (!defined('URL_B24')) {
            return [
                'success' => 0,
                'error' => 'URL_B24 is not defined',
            ];
        }

        $queryUrl = URL_B24 . \ltrim(RestTransportConfig::SITE_AJAX_PROXY_PATH, '/');

        return self::executePostFull($queryUrl, $params, $debug);
    }

    /**
     * POST на прокси site_requests_handler.php — полный декодированный JSON (базовый класс Request).
     */
    public static function postSiteRequestsHandler(array $params, bool $debug = false): array
    {
        if (!defined('URL_B24')) {
            return [
                'success' => 0,
                'error' => 'URL_B24 is not defined',
            ];
        }

        $queryUrl = URL_B24 . \ltrim(RestTransportConfig::SITE_REQUESTS_HANDLER_PATH, '/');

        return self::executePostFull($queryUrl, $params, $debug);
    }

    /**
     * Префикс URL вебхука для kit.productapplications.* (со слешем на конце).
     */
    public static function getKitWebhookPrefix(): string
    {
        if (!defined('URL_B24') || !defined('B24_REST_WEBHOOK_KIT')) {
            return '';
        }

        return RestTransportConfig::buildKitWebhookPrefix();
    }

    /**
     * GET по полному URL после префикса kit-вебхука (контракт вида kit.productapplications....?ID=...).
     *
     * @return array полный декодированный JSON или структура ошибки с success => 0
     */
    public static function callKitRestGet(string $pathAfterKitPrefix, bool $debug = false): array
    {
        $prefix = self::getKitWebhookPrefix();
        if ($prefix === '') {
            return [
                'success' => 0,
                'error' => 'B24 kit webhook is not configured (URL_B24 / B24_REST_WEBHOOK_KIT)',
            ];
        }

        $queryUrl = $prefix . $pathAfterKitPrefix;

        return self::executeGetFull($queryUrl, $debug);
    }

    /**
     * @return array полный декодированный ответ или структура ошибки с success => 0
     */
    private static function executeGetFull(string $queryUrl, bool $debug): array
    {
        $curl = \curl_init();
        $verifyTls = self::shouldVerifyTls($queryUrl);

        \curl_setopt_array($curl, [
            CURLOPT_SSL_VERIFYPEER => $verifyTls,
            CURLOPT_SSL_VERIFYHOST => $verifyTls ? 2 : 0,
            CURLOPT_HTTPGET => true,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $queryUrl,
            CURLOPT_TIMEOUT => RestTransportConfig::REQUEST_TIMEOUT_SECONDS,
            CURLOPT_CONNECTTIMEOUT => RestTransportConfig::CONNECT_TIMEOUT_SECONDS,
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

        $decodedResult = \json_decode($result, true);

        if (\json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => 0,
                'error' => 'JSON Parse Error: ' . \json_last_error_msg(),
                'raw_response' => $result,
            ];
        }

        return $decodedResult;
    }

    /**
     * @return array полный декодированный ответ или структура ошибки с success => 0
     */
    private static function executePostFull(string $queryUrl, array $params, bool $debug): array
    {
        $curl = \curl_init();
        $queryData = \http_build_query($params);
        $verifyTls = self::shouldVerifyTls($queryUrl);
        $headers = self::buildOptionalSyncHeaders($queryUrl);

        \curl_setopt_array($curl, [
            CURLOPT_SSL_VERIFYPEER => $verifyTls,
            CURLOPT_SSL_VERIFYHOST => $verifyTls ? 2 : 0,
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $queryUrl,
            CURLOPT_POSTFIELDS => $queryData,
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

        $decodedResult = \json_decode($result, true);

        if (\json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => 0,
                'error' => 'JSON Parse Error: ' . \json_last_error_msg(),
                'raw_response' => $result,
            ];
        }

        return $decodedResult;
    }
}
