<?
$GLOBALS['ADDITIONAL_WRAPPER_CLASSES'] = 'content';
$GLOBALS['SHOW_SYSTEM_TITLE'] = "N";

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
use Bitrix\Main\Loader;
use Bitrix\Sale\OrderTable;
use Bitrix\Catalog\ProductTable;
use Bitrix\Sale\BasketTable;

$APPLICATION->SetTitle("Просмотр заказов");
$APPLICATION->AddChainItem("Просмотр заказов", "/personal/lichnyj-kabinet.php");

global $USER;
if (!$USER->IsAuthorized()) {
    header("Location: /");
    exit();
}

if (!Loader::includeModule('sale') || !Loader::includeModule('iblock')) {
    echo json_encode(['success' => false, 'error' => 'Требуемые модули не подключены'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Укажите ID инфоблока с товарами
$catalogIblockId = 13;
$catalogOffersIblockId = 14;

try {
    $userId = (int)$USER->GetID();

    // === Шаг 1: Получаем заказы ===
    $dbOrders = \CSaleOrder::GetList(
        ['DATE_INSERT' => 'DESC'],
        ['USER_ID' => $userId, 'CANCELED' => 'N'],
        false,
        ['nTopCount' => 50],
        ['ID', 'DATE_INSERT', 'STATUS_ID', 'PRICE', 'CURRENCY', 'PAYED']
    );

    $orderIds = [];
    $orderData = [];

    while ($order = $dbOrders->Fetch()) {
        $orderId = (int)$order['ID'];
        $orderIds[] = $orderId;
        $orderData[$orderId] = [
            'id'       => $orderId,
            'date'     => $order['DATE_INSERT'],
            'status'   => $order['STATUS_ID'],
            'price'    => (float)$order['PRICE'],
            'currency' => $order['CURRENCY'],
            'paid'     => $order['PAYED'] === 'Y',
            'items'    => []
        ];
    }

    if (empty($orderIds)) {
        echo json_encode(['success' => true, 'orders' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // === Шаг 2: Получаем позиции корзины ===
    $dbBasket = \CSaleBasket::GetList(
        ['ID' => 'ASC'],
        ['ORDER_ID' => $orderIds],
        false,
        false,
        ['ID', 'ORDER_ID', 'PRODUCT_ID', 'NAME', 'QUANTITY', 'PRICE', 'CURRENCY', 'BASE_PRICE', 'DISCOUNT_PRICE']
    );

    $productIds = [];
    $basketItems = [];

    while ($item = $dbBasket->Fetch()) {
        $productId = (int)$item['PRODUCT_ID'];
        if ($productId > 0) {
            $productIds[] = $productId;
        }
        $basketItems[] = $item;
    }

    if (empty($productIds)) {
        $orders = array_values($orderData);
        echo json_encode(['success' => true, 'orders' => $orders], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $productIds = array_unique($productIds);

    // === Шаг 3: Получаем данные по каждому PRODUCT_ID ===
    $productInfoMap = [];

    foreach ($productIds as $productId) {
        $catalogProduct = \CCatalogProduct::GetList(
            [],
            ['ID' => $productId],
            false,
            false,
            ['ID', 'TYPE', 'PARENT_PRODUCT_ID']
        )->Fetch();

        if (!$catalogProduct) {
            $productInfoMap[$productId] = ['detail_url' => '', 'artikul' => ''];
            continue;
        }

        $isOffer = ($catalogProduct['TYPE'] == \CCatalogProduct::TYPE_OFFER);

        $res = \CIBlockElement::GetByID($productId);
        $row = $res->GetNext();
        $url = $row["DETAIL_PAGE_URL"];

        $propertyIblockId = $isOffer ? $catalogOffersIblockId : $catalogIblockId;

        $prop = \CIBlockElement::GetProperty(
            $propertyIblockId,
            $productId,
            ['sort' => 'asc'],
            ['CODE' => 'ARTIKUL_POSTAVSHCHIKA']
        )->Fetch();

        $artikul = $prop ? $prop['VALUE'] : '';

        $productInfoMap[$productId] = [
            'detail_url' => $url,
            'artikul'    => $artikul
        ];
    }

    // === Шаг 4: Собираем всё вместе ===
    foreach ($basketItems as $item) {
        $orderId = (int)$item['ORDER_ID'];
        $productId = (int)$item['PRODUCT_ID'];

        if (!isset($orderData[$orderId])) continue;

        $finalPrice = (float)($item['PRICE'] ?? 0);
        $quantity   = (float)($item['QUANTITY'] ?? 1);
        $lineTotal  = $finalPrice * $quantity;

        $info = $productInfoMap[$productId] ?? ['detail_url' => '', 'artikul' => ''];

        $orderData[$orderId]['items'][] = [
            'product_id'     => $productId,
            'name'           => $item['NAME'],
            'quantity'       => $quantity,
            'price'          => $finalPrice,
            'base_price'     => (float)($item['BASE_PRICE'] ?? $finalPrice),
            'discount_price' => (float)($item['DISCOUNT_PRICE'] ?? 0),
            'final_price'    => $finalPrice,
            'total'          => $lineTotal,
            'currency'       => $item['CURRENCY'],
            'detail_url'     => $info['detail_url'], // ← ссылка в нужном формате
            'properties'     => [
                'ARTIKUL_POSTAVSHCHIKA' => $info['artikul']
            ],
        ];
    }

    $orders = array_values($orderData);

} catch (Exception $e) {
}
?>
<div class="orders-list">
    <br>
    <a href="/personal/lichnyj-kabinet.php">Главная страница личного кабинета</a> &nbsp; &nbsp;
    <a href="/personal/redaktirovanie-dannyh.php">Редактировать данные</a> &nbsp; &nbsp;
    <a href="/personal/prosmotr-zakazov.php">Просмотр заказов</a> &nbsp; &nbsp;
    <h1>Просмотр заказов</h1>

    <div class="orders-list-content">
        <?php
            foreach ($orders as $order){ ?>
                <hr>
                <h3><?=$order['id'];?></h3>
                <span style="font-weiht:bold;">Дата:</span> <?=$order['date'];?> <span style="font-weiht:bold;">Сумма:</span> <?=$order['price'];?> руб.
                <ul>
                    <?php
                        foreach ($order['items'] as $item){ ?>
                            <li>
                                <span style="font-weiht:bold;">Артикул:</span>  <?=$item['properties']['ARTIKUL_POSTAVSHCHIKA']?>
                                <br>
                                <a href="<?=$item['detail_url'];?>"><?=$item['name']?></a>
                                <br>
                                <?=$item['quantity']?> шт. x   <?=($item['discount_price'] > 0) ? $item['discount_price'] : $item['price']?> руб.  = <?=$item['total']?> руб.
                            </li>
                        <?php }
                    ?>
                </ul>
         <?php   }
        ?>
    </div>

</div>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
