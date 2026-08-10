<?php
/**
 * Регистрирует почтовое событие SALE_ORDER_BASKET_CHANGED + шаблон.
 *
 * /local/tools/register-basket-changed-mail.php?key=register-basket-mail
 */
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

global $USER;

$accessKey = (string)($_GET['key'] ?? '');
$isAdmin = is_object($USER) && $USER->IsAdmin();
if (!$isAdmin && $accessKey !== 'register-basket-mail') {
    http_response_code(403);
    die('Access denied');
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/classes/sale/OrderBasketChangedMailNotifier.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $result = \OnlineService\Sale\OrderBasketChangedMailNotifier::ensureMailEvent();
} catch (\Throwable $e) {
    $result = ['error' => $e->getMessage()];
}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
