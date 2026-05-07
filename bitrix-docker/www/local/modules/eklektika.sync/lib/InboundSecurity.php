<?php
namespace OnlineService\Sync;

/**
 * Проверка входящих запросов на inbound CRM (см. {@see \OnlineService\Sync\Config\CrmInboundEndpoint}).
 * Если inbound_secret не задан — пропуск (удобно для dev); в prod задать секрет.
 */
class InboundSecurity
{
    public static function assertInboundAllowed(): void
    {
        $cfg = $GLOBALS['EKLEKTIKA_SYNC_CONFIG'] ?? [];
        $secret = (string)($cfg['inbound_secret'] ?? '');
        if ($secret === '') {
            return;
        }

        $tokenFromHeader = $_SERVER['HTTP_X_SYNC_TOKEN'] ?? '';
        $tokenFromRequest = $_REQUEST['sync_token'] ?? '';
        $token = $tokenFromHeader !== '' ? $tokenFromHeader : $tokenFromRequest;
        $token = is_scalar($token) ? (string)$token : '';
        if ($token === '' || !hash_equals($secret, $token)) {
            self::deny();
        }
    }

    private static function deny(): void
    {
        if (!headers_sent()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=UTF-8');
        }
        echo json_encode([
            'success' => 0,
            'error' => 'sync_forbidden',
            // Единый envelope для n8n Http Request (избегать обращения к undefined `.data`)
            'data' => [],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
