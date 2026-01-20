<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
use Bitrix\Sale;
use Bitrix\Currency\CurrencyManager;

if (!Loader::includeModule('sale') || !Loader::includeModule('catalog') || !Loader::includeModule('iblock')) {
    echo json_encode(['error' => 'Модули не загружены']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['ajax_basket'] ?? '') !== 'Y') {
    http_response_code(400);
    echo json_encode(['error' => 'Неверный запрос']);
    exit;
}

$productId = (int)($_POST['productId'] ?? 0);
$offerId   = (int)($_POST['offerId']   ?? 0);
$quantity  = max(1, (int)($_POST['quantity'] ?? 1));

if ($productId <= 0 || $offerId <= 0) {
    echo json_encode(['error' => 'Некорректные ID']);
    exit;
}

// === Универсальная проверка ===
$productIblockId = 13; // ← ТВОЙ ID товаров
$offersIblockId = 14;  // ← ТВОЙ ID предложений
$linkPropertyCode = 'CML2_LINK';

$el = \CIBlockElement::GetList([], ['ID' => $offerId, 'ACTIVE' => 'Y'], false, false, ['IBLOCK_ID'])->Fetch();
if (!$el) {
    echo json_encode(['error' => 'Товар не найден']);
    exit;
}

$elementIblockId = (int)$el['IBLOCK_ID'];

if ($elementIblockId == $offersIblockId) {
    // Проверка связи предложения с товаром
    if (!\CIBlockElement::GetList([], [
        'ID' => $offerId,
        'IBLOCK_ID' => $offersIblockId,
        'PROPERTY_' . $linkPropertyCode => $productId
    ], false, ['nTopCount' => 1])->Fetch()) {
        echo json_encode(['error' => 'Предложение не соответствует товару']);
        exit;
    }
} elseif ($elementIblockId == $productIblockId) {
    // Обычный товар
    if ($productId != $offerId) {
        echo json_encode(['error' => 'Несоответствие ID']);
        exit;
    }
} else {
    echo json_encode(['error' => 'Недопустимый тип']);
    exit;
}

// === Работа с корзиной ===
$fuserId = Sale\Fuser::getId();
$basket = Sale\Basket::loadItemsForFUser($fuserId, SITE_ID);

if ($item = $basket->getExistsItem('catalog', $offerId)) {
    $item->setField('QUANTITY', $item->getQuantity() + $quantity);
} else {
    $item = $basket->createItem('catalog', $offerId);
    $item->setFields(['QUANTITY' => $quantity, 'PRODUCT_PROVIDER_CLASS' => 'CCatalogProductProvider']);
}

$result = $basket->save();

if (!$result->isSuccess()) {
    error_log('BASKET ERROR: ' . implode('; ', $result->getErrorMessages()));
    echo json_encode(['success' => false, 'error' => implode('; ', $result->getErrorMessages())]);
} else {
    echo json_encode([
        'success' => true,
        'cart_count' => array_sum($basket->getQuantityList()),
        'offer_id' => $offerId,
        'quantity' => $item->getQuantity()
    ]);
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");