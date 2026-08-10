<?php
/**
 * Обновляет HTML-шаблон почтового события SALE_STATUS_CHANGED_N.
 *
 * CLI:  php local/tools/update-sale-status-changed-mail.php
 * HTTP: /local/tools/update-sale-status-changed-mail.php?key=update-status-mail (только админ или key)
 */
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;

global $USER;

$accessKey = (string)($_GET['key'] ?? '');
$isAdmin = is_object($USER) && $USER->IsAdmin();
$isCli = (PHP_SAPI === 'cli');
if (!$isCli && !$isAdmin && $accessKey !== 'update-status-mail') {
    http_response_code(403);
    die('Access denied');
}

if (!Loader::includeModule('main')) {
    die('main module missing');
}

$templatePath = $_SERVER['DOCUMENT_ROOT'] . '/local/docs/mail/SALE_STATUS_CHANGED_N.html';
if (!is_file($templatePath)) {
    die('Template file not found: ' . $templatePath);
}

$messageHtml = file_get_contents($templatePath);
if (!is_string($messageHtml) || $messageHtml === '') {
    die('Template is empty');
}

$eventName = 'SALE_STATUS_CHANGED_N';
$updated = 0;
$ids = [];
$errors = [];

$filters = [
    ['TYPE_ID' => $eventName],
    ['EVENT_NAME' => $eventName],
];

$seen = [];
foreach ($filters as $filter) {
    $rs = CEventMessage::GetList($by = 'id', $order = 'asc', $filter);
    while ($row = $rs->Fetch()) {
        $id = (int)$row['ID'];
        if ($id <= 0 || isset($seen[$id])) {
            continue;
        }
        $seen[$id] = true;

        $ok = CEventMessage::Update($id, [
            'MESSAGE' => $messageHtml,
            'BODY_TYPE' => 'html',
        ]);
        if ($ok) {
            $updated++;
            $ids[] = $id;
        } else {
            global $APPLICATION;
            $errors[] = [
                'id' => $id,
                'error' => is_object($APPLICATION) ? $APPLICATION->GetException() : 'update failed',
            ];
        }
    }
}

$result = [
    'event' => $eventName,
    'updated' => $updated,
    'ids' => $ids,
    'errors' => $errors,
    'fields_hint' => [
        'ORDER_LIST' => 'состав заказа (заполняется OnBeforeEventAdd)',
        'ORDER_CHANGE_REASON' => 'что изменилось: статус + описание + комментарий',
        'PRICE' => 'сумма заказа',
    ],
];

if ($isCli) {
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), PHP_EOL;
    exit($updated > 0 ? 0 : 1);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
