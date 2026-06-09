<?php
/**
 * Диагностика json_naneseniya при оформлении заказа.
 *
 * URL: /local/tools/debug-json-naneseniya.php?order_id=123
 *      /local/tools/debug-json-naneseniya.php?order_id=123&action=persist
 *      /local/tools/debug-json-naneseniya.php?order_id=123&action=simulate
 *
 * Доступ: администратор Bitrix или key=debug-naneseniya (GET).
 */
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Bitrix\Sale;
use Bitrix\Sale\Internals\OrderPropsTable;
use Bitrix\Sale\Internals\OrderPropsValueTable;
use OnlineService\Sale\JsonNaneseniyaPersister;
use OnlineService\Sale\OrderJsonNaneseniyaProperty;

global $USER;

$accessKey = (string)($_GET['key'] ?? '');
$isAdmin = is_object($USER) && $USER->IsAdmin();
$isCli = (php_sapi_name() === 'cli');
if (!$isCli && !$isAdmin && $accessKey !== 'debug-naneseniya') {
    http_response_code(403);
    die('Access denied. Admin or ?key=debug-naneseniya required.');
}

if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/classes/sale/OrderJsonNaneseniyaProperty.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/classes/sale/JsonNaneseniyaPersister.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/classes/sale/BasketNaneseniyaStorage.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/classes/sale/OrderPropsValueStorage.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/classes/catalog/NanesenieOptionsResolver.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/components/online-service/order.form/class.php';

$orderId = (int)($_GET['order_id'] ?? ($isCli ? ($argv[1] ?? 0) : 0));
$action = (string)($_GET['action'] ?? ($isCli ? ($argv[2] ?? 'report') : 'report'));
$fuserId = (int)($_GET['fuser_id'] ?? 0);

$report = [
    'meta' => [
        'time' => date('c'),
        'order_id' => $orderId,
        'action' => $action,
        'site_id' => defined('SITE_ID') ? SITE_ID : null,
    ],
    'steps' => [],
];

function step(array &$report, string $name, mixed $data): void
{
    $report['steps'][$name] = $data;
}

if (!Loader::includeModule('sale')) {
    step($report, 'error', 'Module sale not loaded');
    outputReport($report);
    exit;
}

OrderJsonNaneseniyaProperty::ensureMaxLength();
\OnlineService\Sale\BasketNaneseniyaStorage::ensureValueColumn();
\OnlineService\Sale\OrderPropsValueStorage::ensureValueColumn();

step($report, '0_schema', [
    'basket_props_value_column' => fetchColumnType('b_sale_basket_props', 'VALUE'),
    'order_props_value_column' => fetchColumnType('b_sale_order_props_value', 'VALUE'),
]);
$propRows = [];
$rsProps = OrderPropsTable::getList([
    'filter' => ['=CODE' => 'json_naneseniya'],
    'select' => ['*'],
]);
while ($row = $rsProps->fetch()) {
    $propRows[] = $row;
}
step($report, '1_property_definitions', $propRows);

// --- 2. Последние заказы и значение json_naneseniya ---
$recentOrders = [];
$rsOrders = \CSaleOrder::GetList(
    ['ID' => 'DESC'],
    [],
    false,
    ['nTopCount' => 10],
    ['ID', 'DATE_INSERT', 'USER_ID', 'PERSON_TYPE_ID', 'XML_ID']
);
while ($o = $rsOrders->Fetch()) {
    $oid = (int)$o['ID'];
    $recentOrders[] = [
        'id' => $oid,
        'date' => $o['DATE_INSERT'],
        'person_type_id' => (int)$o['PERSON_TYPE_ID'],
        'xml_id' => (string)($o['XML_ID'] ?? ''),
        'json_naneseniya_d7' => JsonNaneseniyaPersister::readValue($oid),
        'json_naneseniya_legacy' => readLegacyPropValue($oid),
    ];
}
step($report, '2_recent_orders', $recentOrders);

if ($orderId <= 0 && !empty($recentOrders[0]['id'])) {
    $orderId = (int)$recentOrders[0]['id'];
    $report['meta']['order_id'] = $orderId;
    $report['meta']['order_id_auto'] = true;
}

if ($orderId <= 0) {
    step($report, 'error', 'No order_id and no orders in DB');
    outputReport($report);
    exit;
}

// --- 3. Заказ и property collection ---
$order = Sale\Order::load($orderId);
if (!$order) {
    step($report, 'error', "Order #{$orderId} not found");
    outputReport($report);
    exit;
}

$personTypeId = (int)$order->getPersonTypeId();
$collectionCodes = [];
$collection = $order->getPropertyCollection();
if ($collection) {
    foreach ($collection as $propItem) {
        $code = (string)($propItem->getField('CODE') ?? '');
        $collectionCodes[$code] = [
            'code' => $code,
            'value_preview' => mb_substr((string)$propItem->getValue(), 0, 120),
            'order_props_id' => (int)($propItem->getField('ORDER_PROPS_ID') ?? 0),
        ];
    }
}

step($report, '3_order', [
    'id' => $orderId,
    'person_type_id' => $personTypeId,
    'xml_id' => (string)$order->getField('XML_ID'),
    'property_collection_codes' => array_keys($collectionCodes),
    'json_in_collection' => $collectionCodes['json_naneseniya'] ?? null,
    'json_getItemByCode' => (function () use ($collection) {
        if (!$collection) {
            return null;
        }
        $item = $collection->getItemByOrderPropertyCode('json_naneseniya');
        if (!$item) {
            return ['found' => false];
        }
        return [
            'found' => true,
            'value_preview' => mb_substr((string)$item->getValue(), 0, 120),
        ];
    })(),
]);

// --- 4. Корзина заказа: NANESENIE ---
$basketItems = [];
$basket = $order->getBasket();
if ($basket) {
    foreach ($basket as $item) {
        $props = [];
        $propCollection = $item->getPropertyCollection();
        if ($propCollection) {
            foreach ($propCollection as $p) {
                $props[] = [
                    'code' => (string)($p->getField('CODE') ?? ''),
                    'name' => (string)($p->getField('NAME') ?? ''),
                    'value' => (string)($p->getField('VALUE') ?? ''),
                    'value_length' => strlen((string)($p->getField('VALUE') ?? '')),
                    'parsed_naneseniya' => mb_strtoupper((string)($p->getField('CODE') ?? '')) === 'NANESENIE'
                        ? \OnlineService\Catalog\NanesenieOptionsResolver::parseNaneseniyaRawValueForExport($p->getField('VALUE'))
                        : null,
                ];
            }
        }
        $basketItems[] = [
            'product_id' => (int)$item->getProductId(),
            'name' => (string)$item->getField('NAME'),
            'props' => $props,
        ];
    }
}
step($report, '4_order_basket_nanesenie', $basketItems);

// --- 5. FUSER корзина (если указан fuser_id или текущий) ---
if ($fuserId <= 0 && is_object($USER) && $USER->IsAuthorized()) {
    $fuserId = (int)Sale\Fuser::getId(true);
}
$fuserBasketItems = [];
if ($fuserId > 0) {
    $fuserBasket = Sale\Basket::loadItemsForFUser($fuserId, SITE_ID);
    foreach ($fuserBasket as $item) {
        $props = [];
        $propCollection = $item->getPropertyCollection();
        if ($propCollection) {
            foreach ($propCollection as $p) {
                $props[] = [
                    'code' => (string)($p->getField('CODE') ?? ''),
                    'value' => (string)($p->getField('VALUE') ?? ''),
                ];
            }
        }
        $fuserBasketItems[] = [
            'product_id' => (int)$item->getProductId(),
            'props' => $props,
        ];
    }
}
step($report, '5_fuser_basket', [
    'fuser_id' => $fuserId,
    'items' => $fuserBasketItems,
]);

// --- 6. Сбор JSON через OrderFormComponent (reflection) ---
$dummy = new CBitrixComponent();
$handler = new \OnlineService\OrderForm\OrderFormComponent($dummy);
$ref = new ReflectionClass($handler);

$initResult = $ref->getMethod('initResult');
$initResult->setAccessible(true);
$initResult->invoke($handler);

$loadBasket = $ref->getMethod('loadBasket');
$loadBasket->setAccessible(true);
$loadBasket->invoke($handler);

$arResultProp = $ref->getProperty('arResult');
$arResultProp->setAccessible(true);
$arResult = $arResultProp->getValue($handler);

$snapshot = $ref->getMethod('snapshotNaneseniyaItemsForOrder');
$snapshot->setAccessible(true);
$snapshot->invoke($handler);

$arResult = $arResultProp->getValue($handler);

$buildItems = $ref->getMethod('buildNaneseniyaItemsFromBasket');
$buildItems->setAccessible(true);

$itemsFromFuser = $buildItems->invoke($handler, $arResult['_BASKET'] ?? null);
$itemsFromOrder = $buildItems->invoke($handler, $basket instanceof Sale\Basket ? $basket : null);
$itemsFromSnapshot = $arResult['_NANESENIYA_ITEMS'] ?? [];

$wrapPayload = $ref->getMethod('wrapNaneseniyaPayload');
$wrapPayload->setAccessible(true);
$encodeJson = $ref->getMethod('encodeNaneseniyaJson');
$encodeJson->setAccessible(true);

$testXmlId = trim((string)$order->getField('XML_ID')) ?: 'debug-test-xml-id';
$jsonFromSnapshot = $encodeJson->invoke($handler, $wrapPayload->invoke($handler, $itemsFromSnapshot, $testXmlId));

$buildJsonForOrder = $ref->getMethod('buildJsonForOrder');
$buildJsonForOrder->setAccessible(true);
$jsonFromOrder = (string)$buildJsonForOrder->invoke($handler, $order);

step($report, '6_build_json', [
    'items_from_fuser_basket' => $itemsFromFuser,
    'items_from_order_basket' => $itemsFromOrder,
    'items_from_snapshot' => $itemsFromSnapshot,
    'json_from_order_basket' => mb_substr($jsonFromOrder, 0, 500),
    'json_from_order_length' => strlen($jsonFromOrder),
    'json_preview_legacy_snapshot' => mb_substr($jsonFromSnapshot, 0, 500),
]);

// --- 7. Тест persist ---
if ($action === 'persist' || $action === 'simulate') {
    $jsonToWrite = $jsonFromOrder;
    if ($jsonToWrite === '' || $jsonToWrite === '[]') {
        $jsonToWrite = $encodeJson->invoke($handler, $wrapPayload->invoke($handler, $itemsFromOrder, $testXmlId));
    }

    if ($action === 'simulate') {
        step($report, '7_persist_simulate', [
            'would_write_length' => strlen($jsonToWrite),
            'would_write_preview' => mb_substr($jsonToWrite, 0, 300),
            'person_type_id' => $personTypeId,
        ]);
    } else {
        $ok = JsonNaneseniyaPersister::persist($orderId, $personTypeId, $jsonToWrite);
        step($report, '7_persist_result', [
            'success' => $ok,
            'error' => JsonNaneseniyaPersister::getLastError(),
            'read_back' => JsonNaneseniyaPersister::readValue($orderId),
            'read_back_length' => strlen((string)JsonNaneseniyaPersister::readValue($orderId)),
        ]);
    }
}

// --- 8. writeJsonNaneseniyaForOrder как в production ---
if ($action === 'finalize') {
    $writeJson = $ref->getMethod('writeJsonNaneseniyaForOrder');
    $writeJson->setAccessible(true);
    $writeJson->invoke($handler, $order, true);

    step($report, '8_finalize', [
        'read_back' => JsonNaneseniyaPersister::readValue($orderId),
        'persister_error' => JsonNaneseniyaPersister::getLastError(),
    ]);
}

// --- 9. Raw DB rows ---
step($report, '9_db_props_value', fetchDbPropValues($orderId));

outputReport($report);

function readLegacyPropValue(int $orderId): string
{
    $row = \CSaleOrderPropsValue::GetList([], [
        'ORDER_ID' => $orderId,
        'CODE' => 'json_naneseniya',
    ])->Fetch();

    return is_array($row) ? (string)($row['VALUE'] ?? '') : '';
}

/**
 * @return array<int, array<string, mixed>>
 */
function fetchDbPropValues(int $orderId): array
{
    $rows = [];
    $rs = OrderPropsValueTable::getList([
        'filter' => ['=ORDER_ID' => $orderId],
        'select' => ['ID', 'ORDER_PROPS_ID', 'CODE', 'NAME', 'VALUE'],
    ]);
    while ($row = $rs->fetch()) {
        if ((string)($row['CODE'] ?? '') === 'json_naneseniya' || mb_stripos((string)($row['NAME'] ?? ''), 'json') !== false) {
            $row['VALUE_PREVIEW'] = mb_substr((string)($row['VALUE'] ?? ''), 0, 200);
            $row['VALUE_LENGTH'] = strlen((string)($row['VALUE'] ?? ''));
            $rows[] = $row;
        }
    }
    return $rows;
}

function fetchColumnType(string $table, string $column): ?string
{
    $connection = \Bitrix\Main\Application::getConnection();
    if (!$connection->isTableExists($table)) {
        return null;
    }

    $helper = $connection->getSqlHelper();
    $sql = 'SHOW COLUMNS FROM ' . $helper->quote($table) . ' LIKE \'' . $helper->forSql($column) . '\'';
    $result = $connection->query($sql);
    $row = $result->fetch();

    return is_array($row) ? (string)($row['Type'] ?? '') : null;
}

function outputReport(array $report): void
{
    global $isCli;
    if ($isCli) {
        echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        return;
    }

    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>debug json_naneseniya</title>';
    echo '<style>body{font:14px/1.4 monospace;padding:16px;background:#111;color:#ddd}pre{background:#222;padding:12px;overflow:auto;border:1px solid #444}h1{color:#9cf} a{color:#6cf}</style></head><body>';
    echo '<h1>debug json_naneseniya</h1>';
    echo '<p>Order ID: <strong>' . (int)($report['meta']['order_id'] ?? 0) . '</strong></p>';
    echo '<p>Actions: ';
    $oid = (int)($report['meta']['order_id'] ?? 0);
    $key = htmlspecialchars((string)($_GET['key'] ?? ''), ENT_QUOTES, 'UTF-8');
    $q = $key !== '' ? '&amp;key=' . $key : '';
    echo '<a href="?order_id=' . $oid . '&amp;action=report' . $q . '">report</a> | ';
    echo '<a href="?order_id=' . $oid . '&amp;action=simulate' . $q . '">simulate</a> | ';
    echo '<a href="?order_id=' . $oid . '&amp;action=persist' . $q . '">persist</a> | ';
    echo '<a href="?order_id=' . $oid . '&amp;action=finalize' . $q . '">finalize</a>';
    echo '</p>';
    echo '<pre>' . htmlspecialchars(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') . '</pre>';
    echo '</body></html>';
}
