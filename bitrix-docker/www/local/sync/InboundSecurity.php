<?php
namespace OnlineService\Sync;

/**
 * Проверка входящих запросов на ajax.php (канал CRM → сайт).
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

        $token = $_SERVER['HTTP_X_SYNC_TOKEN'] ?? ($_REQUEST['sync_token'] ?? '');
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
        echo json_encode(['success' => 0, 'error' => 'sync_forbidden'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
