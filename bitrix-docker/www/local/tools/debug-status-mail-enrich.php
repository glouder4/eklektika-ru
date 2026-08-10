<?php
/**
 * Диагностика обогащения SALE_STATUS_CHANGED_*.
 *
 * /local/tools/debug-status-mail-enrich.php?order_id=72&key=debug-status-mail
 * /local/tools/debug-status-mail-enrich.php?order_id=72&key=debug-status-mail&send=1
 */
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Bitrix\Sale\Order;
use OnlineService\Sale\OrderNanesenieMailFormatter;

global $USER;

$accessKey = (string)($_GET['key'] ?? '');
$isAdmin = is_object($USER) && $USER->IsAdmin();
$isCli = (PHP_SAPI === 'cli');
if (!$isCli && !$isAdmin && $accessKey !== 'debug-status-mail') {
    http_response_code(403);
    die('Access denied');
}

header('Content-Type: application/json; charset=utf-8');

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/classes/sale/OrderNanesenieMailFormatter.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/classes/catalog/NanesenieOptionsResolver.php';

$orderId = (int)($_GET['order_id'] ?? ($isCli ? ($argv[1] ?? 0) : 0));
$doSend = isset($_GET['send']) || ($isCli && in_array('send', $argv ?? [], true));

$report = [
    'order_id' => $orderId,
    'handlers' => [
        'OnBeforeEventAdd' => [],
        'OnOrderStatusSendEmail' => [],
    ],
    'class_exists' => class_exists(OrderNanesenieMailFormatter::class),
    'methods' => [
        'onBeforeEventAdd' => method_exists(OrderNanesenieMailFormatter::class, 'onBeforeEventAdd'),
        'onOrderStatusSendEmail' => method_exists(OrderNanesenieMailFormatter::class, 'onOrderStatusSendEmail'),
    ],
];

foreach (GetModuleEvents('main', 'OnBeforeEventAdd', true) as $h) {
    $report['handlers']['OnBeforeEventAdd'][] = $h;
}
foreach (GetModuleEvents('sale', 'OnOrderStatusSendEmail', true) as $h) {
    $report['handlers']['OnOrderStatusSendEmail'][] = $h;
}

if (!Loader::includeModule('sale')) {
    $report['error'] = 'sale module not loaded';
    echo json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$order = $orderId > 0 ? Order::load($orderId) : null;
if (!$order) {
    $report['error'] = 'order not found';
    echo json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$statusId = (string)$order->getField('STATUS_ID');
$fields = [
    'ORDER_ID' => $order->getField('ACCOUNT_NUMBER') ?: $order->getId(),
    'ORDER_REAL_ID' => $order->getId(),
    'ORDER_ACCOUNT_NUMBER_ENCODE' => urlencode((string)($order->getField('ACCOUNT_NUMBER') ?: $order->getId())),
    'ORDER_STATUS' => '',
    'ORDER_DESCRIPTION' => '',
    'EMAIL' => '',
    'TEXT' => '',
    'SALE_EMAIL' => (string)\Bitrix\Main\Config\Option::get('sale', 'order_email', ''),
    'ORDER_PUBLIC_URL' => '',
    'ORDER_DATE' => (string)$order->getField('DATE_INSERT'),
];

if ($statusData = CSaleStatus::GetByID($statusId, 'ru')) {
    $fields['ORDER_STATUS'] = (string)($statusData['NAME'] ?? '');
    $fields['ORDER_DESCRIPTION'] = (string)($statusData['DESCRIPTION'] ?? '');
}

$propertyCollection = $order->getPropertyCollection();
$emailProp = $propertyCollection ? $propertyCollection->getUserEmail() : null;
if ($emailProp) {
    $fields['EMAIL'] = (string)$emailProp->getValue();
}

$eventName = 'SALE_STATUS_CHANGED_' . $statusId;
OrderNanesenieMailFormatter::onOrderStatusSendEmail($order->getId(), $eventName, $fields, $statusId);

$report['event_name'] = $eventName;
$report['fields_after_enrich'] = [
    'ORDER_LIST' => $fields['ORDER_LIST'] ?? null,
    'ORDER_CHANGE_REASON' => $fields['ORDER_CHANGE_REASON'] ?? null,
    'PRICE' => $fields['PRICE'] ?? null,
    'TEXT' => $fields['TEXT'] ?? null,
    'EMAIL' => $fields['EMAIL'] ?? null,
];
$report['field_keys'] = array_keys($fields);

if ($doSend) {
    $event = new CEvent();
    $sendResult = $event->Send($eventName, $order->getSiteId() ?: 's1', $fields, 'N');
    $report['send'] = [
        'requested' => true,
        'result' => $sendResult,
        'note' => 'Send with "N" = do not duplicate check differently; check newest b_event C_FIELDS for ORDER_LIST',
    ];
}

$logFile = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/mail-enrich.log';
$report['log_tail'] = is_file($logFile)
    ? array_slice(file($logFile, FILE_IGNORE_NEW_LINES), -20)
    : [];

echo json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
