<?php
/**
 * Диагностика SALE_STATUS_CHANGED_*.
 *
 * HTTP:
 *   ?order_id=72&key=force-status-mail
 *   &mode=cevent|notify|exec|diagnose
 *   &event_id=470  (для mode=exec)
 *
 * CLI из корня сайта:
 *   php local/tools/force-status-mail.php 72
 *   php local/tools/force-status-mail.php 72 cevent
 *   php local/tools/force-status-mail.php 72 exec 470
 */
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);

if (PHP_SAPI === 'cli') {
    $docRoot = realpath(dirname(__DIR__, 2));
    if ($docRoot === false || !is_file($docRoot . '/bitrix/modules/main/include/prolog_before.php')) {
        fwrite(STDERR, "Cannot resolve DOCUMENT_ROOT from " . __FILE__ . PHP_EOL);
        exit(1);
    }
    $_SERVER['DOCUMENT_ROOT'] = $docRoot;
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Bitrix\Main\Mail\Event as MailEvent;
use Bitrix\Main\Mail\Internal\EventAttachmentTable;
use Bitrix\Main\Mail\Internal\EventTable;
use Bitrix\Main\Type\DateTime as BitrixDateTime;
use Bitrix\Sale\Order;

global $USER;

$accessKey = (string)($_GET['key'] ?? '');
$isAdmin = is_object($USER) && $USER->IsAdmin();
$isCli = (PHP_SAPI === 'cli');
if (!$isCli && !$isAdmin && $accessKey !== 'force-status-mail') {
    http_response_code(403);
    die('Access denied');
}

if (!$isCli) {
    header('Content-Type: application/json; charset=utf-8');
}

$orderId = (int)($_GET['order_id'] ?? 0);
$mode = (string)($_GET['mode'] ?? 'diagnose');
$eventId = (int)($_GET['event_id'] ?? 0);
if ($isCli) {
    $orderId = (int)($argv[1] ?? $orderId);
    $mode = (string)($argv[2] ?? $mode);
    $eventId = (int)($argv[3] ?? $eventId);
}

$out = [
    'order_id' => $orderId,
    'mode' => $mode,
    'php' => PHP_VERSION,
    'handlers_OnOrderStatusSendEmail' => [],
    'handlers_OnBeforeEventAdd' => [],
    'handlers_OnBeforeMailSend' => [],
];

foreach (GetModuleEvents('sale', 'OnOrderStatusSendEmail', true) as $h) {
    $out['handlers_OnOrderStatusSendEmail'][] = $h['TO_NAME'] ?? ($h['TO_CLASS'] ?? '') . '::' . ($h['TO_METHOD'] ?? '');
}
foreach (GetModuleEvents('main', 'OnBeforeEventAdd', true) as $h) {
    $out['handlers_OnBeforeEventAdd'][] = $h['TO_NAME'] ?? ($h['TO_CLASS'] ?? '') . '::' . ($h['TO_METHOD'] ?? '');
}
foreach (GetModuleEvents('main', 'OnBeforeMailSend', true) as $h) {
    $out['handlers_OnBeforeMailSend'][] = $h['TO_NAME'] ?? ($h['TO_CLASS'] ?? '') . '::' . ($h['TO_METHOD'] ?? '');
}

if (!Loader::includeModule('sale') || !Loader::includeModule('main')) {
    $out['error'] = 'modules';
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$connection = \Bitrix\Main\Application::getConnection();
$out['last_status_events'] = $connection->query(
    "SELECT ID, EVENT_NAME, SUCCESS_EXEC, DUPLICATE FROM b_event
     WHERE EVENT_NAME LIKE 'SALE_STATUS_CHANGED_%'
     ORDER BY ID DESC LIMIT 5"
)->fetchAll();

if ($mode === 'exec') {
    if ($eventId <= 0) {
        $eventId = (int)($out['last_status_events'][0]['ID'] ?? 0);
    }
    $out['exec_event_id'] = $eventId;

    $row = EventTable::getById($eventId)->fetch();
    if (!$row) {
        $out['error'] = 'event not found';
        echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    $fields = $row['C_FIELDS'] ?? [];
    if (!is_array($fields)) {
        $fields = [];
    }
    $out['event_email'] = $fields['EMAIL'] ?? null;
    $out['event_sale_email'] = $fields['SALE_EMAIL'] ?? null;
    $out['event_field_keys'] = array_keys($fields);
    $out['event_before_flag'] = $row['SUCCESS_EXEC'] ?? null;

    $files = [];
    $rs = EventAttachmentTable::getList([
        'select' => ['FILE_ID'],
        'filter' => ['=EVENT_ID' => $eventId],
    ]);
    while ($f = $rs->fetch()) {
        $files[] = $f['FILE_ID'];
    }
    $row['FILE'] = $files;
    $row['FIELDS'] = $fields;

    // сброс в N чтобы можно было повторить F
    EventTable::update($eventId, [
        'SUCCESS_EXEC' => 'N',
        'DATE_EXEC' => null,
    ]);

    try {
        $flag = MailEvent::handleEvent($row);
        EventTable::update($eventId, [
            'SUCCESS_EXEC' => $flag,
            'DATE_EXEC' => new BitrixDateTime(),
        ]);
        $out['handleEvent_flag'] = $flag;
        $out['flag_meaning'] = [
            'Y' => 'все шаблоны ушли',
            'F' => 'все шаблоны failed (SMTP/mail/To/OnBeforeMailSend/StopException)',
            'P' => 'частично',
            '0' => 'шаблон не найден / нет charset сайта',
            'N' => 'не обработано',
            'E' => 'exception',
        ][$flag] ?? 'unknown';
    } catch (\Throwable $e) {
        EventTable::update($eventId, [
            'SUCCESS_EXEC' => 'E',
            'DATE_EXEC' => new BitrixDateTime(),
        ]);
        $out['handleEvent_exception'] = $e->getMessage();
        $out['handleEvent_file'] = $e->getFile() . ':' . $e->getLine();
    }

    // шаблоны события
    $templates = [];
    $rsMsg = CEventMessage::GetList($by = 'id', $orderBy = 'asc', [
        'TYPE_ID' => (string)$row['EVENT_NAME'],
        'ACTIVE' => 'Y',
    ]);
    while ($m = $rsMsg->Fetch()) {
        $templates[] = [
            'ID' => $m['ID'],
            'EMAIL_TO' => $m['EMAIL_TO'] ?? null,
            'SUBJECT' => $m['SUBJECT'] ?? null,
            'BODY_TYPE' => $m['BODY_TYPE'] ?? null,
            'MESSAGE_LEN' => isset($m['MESSAGE']) ? strlen((string)$m['MESSAGE']) : 0,
            'EMAIL_FROM' => $m['EMAIL_FROM'] ?? null,
        ];
    }
    $out['templates'] = $templates;

    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$order = $orderId > 0 ? Order::load($orderId) : null;
if (!$order) {
    $out['error'] = 'order not found';
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$statusId = (string)$order->getField('STATUS_ID');
$statusData = CSaleStatus::GetByID($statusId, 'ru') ?: [];
$eventName = 'SALE_STATUS_CHANGED_' . $statusId;

$out['status'] = [
    'ID' => $statusId,
    'NOTIFY' => $statusData['NOTIFY'] ?? null,
    'NAME' => $statusData['NAME'] ?? null,
];

$msgCount = 0;
$rsMsg = CEventMessage::GetList($by = 'id', $orderBy = 'asc', ['TYPE_ID' => $eventName, 'ACTIVE' => 'Y']);
while ($m = $rsMsg->Fetch()) {
    $msgCount++;
    $out['template_preview'][] = [
        'ID' => $m['ID'],
        'EMAIL_TO' => $m['EMAIL_TO'] ?? null,
        'EMAIL_FROM' => $m['EMAIL_FROM'] ?? null,
        'SUBJECT' => $m['SUBJECT'] ?? null,
    ];
}
$out['active_templates'] = $msgCount;

$rsType = CEventType::GetList(['EVENT_NAME' => $eventName, 'LID' => 'ru']);
$typeRow = $rsType->Fetch();
$out['event_type_exists'] = (bool)$typeRow;
$out['event_type_desc_has_ORDER_LIST'] = $typeRow
    ? (strpos((string)$typeRow['DESCRIPTION'], '#ORDER_LIST#') !== false)
    : false;

if ($mode === 'cevent' || $mode === 'notify') {
    $propertyCollection = $order->getPropertyCollection();
    $emailProp = $propertyCollection ? $propertyCollection->getUserEmail() : null;
    $email = $emailProp ? (string)$emailProp->getValue() : '';

    $siteId = $order->getSiteId() ?: 's1';
    $saleEmail = (string)\Bitrix\Main\Config\Option::get('sale', 'order_email', '', $siteId);
    if ($saleEmail === '') {
        $saleEmail = (string)\Bitrix\Main\Config\Option::get('sale', 'order_email', '');
    }
    if ($saleEmail === '') {
        $saleEmail = 'order@' . ($_SERVER['SERVER_NAME'] ?? 'localhost');
    }

    $fields = [
        'ORDER_ID' => $order->getField('ACCOUNT_NUMBER') ?: $order->getId(),
        'ORDER_REAL_ID' => $order->getId(),
        'ORDER_ACCOUNT_NUMBER_ENCODE' => urlencode((string)($order->getField('ACCOUNT_NUMBER') ?: $order->getId())),
        'ORDER_STATUS' => (string)($statusData['NAME'] ?? ''),
        'ORDER_DESCRIPTION' => (string)($statusData['DESCRIPTION'] ?? ''),
        'EMAIL' => $email,
        'TEXT' => 'diag force mail ' . date('c'),
        'ORDER_LIST' => 'diag ORDER_LIST',
        'SALE_EMAIL' => $saleEmail,
        'ORDER_PUBLIC_URL' => '',
        'ORDER_DATE' => (string)$order->getField('DATE_INSERT'),
    ];
    $out['send_fields_email'] = $email;
    $out['send_fields_sale_email'] = $fields['SALE_EMAIL'];
    $out['sale_email_site'] = $siteId;

    if ($mode === 'cevent') {
        // Duplicate=Y как у ядра Notify
        $event = new CEvent();
        $id = $event->Send($eventName, $order->getSiteId() ?: 's1', $fields, 'Y');
        $out['cevent_send_id'] = $id;

        if ($id) {
            // сразу исполнить
            $_GET['event_id'] = (string)$id;
            $row = EventTable::getById((int)$id)->fetch();
            if ($row) {
                $row['FILE'] = [];
                $row['FIELDS'] = is_array($row['C_FIELDS']) ? $row['C_FIELDS'] : [];
                try {
                    $flag = MailEvent::handleEvent($row);
                    EventTable::update((int)$id, [
                        'SUCCESS_EXEC' => $flag,
                        'DATE_EXEC' => new BitrixDateTime(),
                    ]);
                    $out['immediate_flag'] = $flag;
                } catch (\Throwable $e) {
                    EventTable::update((int)$id, [
                        'SUCCESS_EXEC' => 'E',
                        'DATE_EXEC' => new BitrixDateTime(),
                    ]);
                    $out['immediate_exception'] = $e->getMessage();
                }
            }
        }
    }

    if ($mode === 'notify') {
        $statuses = [];
        $rsSt = CSaleStatus::GetList(['SORT' => 'ASC'], [], false, false, ['ID', 'NOTIFY']);
        while ($st = $rsSt->Fetch()) {
            $statuses[] = $st;
        }
        $out['all_statuses'] = $statuses;

        $alt = (string)($_GET['alt'] ?? '');
        if ($alt === '') {
            foreach ($statuses as $st) {
                if ((string)$st['ID'] !== $statusId) {
                    $alt = (string)$st['ID'];
                    break;
                }
            }
        }
        if ($alt === '' || !CSaleStatus::GetByID($alt, 'ru')) {
            $out['notify_error'] = 'no alt status; pass &alt=XX';
        } else {
            $order->setField('STATUS_ID', $alt);
            $save1 = $order->save();
            $out['save_to_alt'] = $save1->isSuccess();
            $out['alt'] = $alt;

            $order2 = Order::load($orderId);
            $order2->setField('STATUS_ID', $statusId);
            $save2 = $order2->save();
            $out['save_back'] = $save2->isSuccess();
            $out['final_status'] = (string)$order2->getField('STATUS_ID');
        }
    }

    $out['last_status_events_after'] = $connection->query(
        "SELECT ID, EVENT_NAME, SUCCESS_EXEC, DUPLICATE FROM b_event
         WHERE EVENT_NAME LIKE 'SALE_STATUS_CHANGED_%'
         ORDER BY ID DESC LIMIT 5"
    )->fetchAll();
}

echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
