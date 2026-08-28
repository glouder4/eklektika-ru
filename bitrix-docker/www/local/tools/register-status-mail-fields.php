<?php
/**
 * Регистрирует #ORDER_LIST#, #ORDER_CHANGE_REASON#, #PRICE# (и HTML_*)
 * в DESCRIPTION типов SALE_STATUS_CHANGED_* — блок «Доступные поля» в админке.
 *
 * /local/tools/register-status-mail-fields.php?key=register-status-mail
 * /local/tools/register-status-mail-fields.php?key=register-status-mail&all=1
 */
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

global $USER;

$accessKey = (string)($_GET['key'] ?? '');
$isAdmin = is_object($USER) && $USER->IsAdmin();
$isCli = (PHP_SAPI === 'cli');
if (!$isCli && !$isAdmin && $accessKey !== 'register-status-mail') {
    http_response_code(403);
    die('Access denied');
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/classes/sale/OrderStatusChangedEventTypeRegistrar.php';

$all = isset($_GET['all']) || ($isCli && in_array('all', $argv ?? [], true));
$statusId = $all ? null : 'N';

try {
    \Bitrix\Main\Config\Option::set('main', 'eklektika_status_mail_fields_v1', '');
    $result = \OnlineService\Sale\OrderStatusChangedEventTypeRegistrar::ensureExtraFields($statusId);
    \Bitrix\Main\Config\Option::set('main', 'eklektika_status_mail_fields_v1', 'Y');
} catch (\Throwable $e) {
    $result = [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
