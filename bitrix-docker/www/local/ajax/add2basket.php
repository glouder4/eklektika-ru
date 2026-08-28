<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
use Bitrix\Sale;
use OnlineService\Catalog\NanesenieOptionsResolver;
use OnlineService\Sale\BasketNaneseniyaStorage;

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
$nanesenieValues = NanesenieOptionsResolver::collectSubmittedValuesFromRequest($_POST);

// Пакет размеров: items=[{offerId,quantity}, ...] или items как JSON-строка
$items = [];
$rawItems = $_POST['items'] ?? null;
if (is_string($rawItems) && $rawItems !== '') {
    $decoded = json_decode($rawItems, true);
    if (is_array($decoded)) {
        $rawItems = $decoded;
    }
}
if (is_array($rawItems)) {
    foreach ($rawItems as $row) {
        if (!is_array($row)) {
            continue;
        }
        $oid = (int)($row['offerId'] ?? $row['offer_id'] ?? 0);
        $qty = max(1, (int)($row['quantity'] ?? 0));
        if ($oid > 0 && $qty > 0) {
            $items[] = ['offerId' => $oid, 'quantity' => $qty];
        }
    }
}

// Обратная совместимость: один offerId + quantity
if ($items === []) {
    $offerId = (int)($_POST['offerId'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));
    if ($offerId > 0) {
        $items[] = ['offerId' => $offerId, 'quantity' => $quantity];
    }
}

if ($productId <= 0 || $items === []) {
    echo json_encode(['error' => 'Некорректные ID']);
    exit;
}

$productIblockId = 13;
$offersIblockId = 14;
$linkPropertyCode = 'CML2_LINK';

/**
 * @return string|null error text
 */
$validateOfferBelongsToProduct = static function (int $offerId, int $productId) use ($productIblockId, $offersIblockId, $linkPropertyCode): ?string {
    $el = \CIBlockElement::GetList([], ['ID' => $offerId, 'ACTIVE' => 'Y'], false, false, ['IBLOCK_ID'])->Fetch();
    if (!$el) {
        return 'Товар не найден';
    }

    $elementIblockId = (int)$el['IBLOCK_ID'];

    if ($elementIblockId === $offersIblockId) {
        if (!\CIBlockElement::GetList([], [
            'ID' => $offerId,
            'IBLOCK_ID' => $offersIblockId,
            'PROPERTY_' . $linkPropertyCode => $productId,
        ], false, ['nTopCount' => 1])->Fetch()) {
            return 'Предложение не соответствует товару';
        }
        return null;
    }

    if ($elementIblockId === $productIblockId) {
        if ($productId !== $offerId) {
            return 'Несоответствие ID';
        }
        return null;
    }

    return 'Недопустимый тип';
};

$fuserId = Sale\Fuser::getId();
$basket = Sale\Basket::loadItemsForFUser($fuserId, SITE_ID);

BasketNaneseniyaStorage::ensureValueColumn();

$added = [];
$errors = [];
$lastOfferId = 0;

foreach ($items as $row) {
    $offerId = (int)$row['offerId'];
    $quantity = (int)$row['quantity'];

    $validationError = $validateOfferBelongsToProduct($offerId, $productId);
    if ($validationError !== null) {
        $errors[] = ['offerId' => $offerId, 'error' => $validationError];
        continue;
    }

    if ($item = $basket->getExistsItem('catalog', $offerId)) {
        $item->setField('QUANTITY', $item->getQuantity() + $quantity);
    } else {
        $item = $basket->createItem('catalog', $offerId);
        $item->setFields([
            'QUANTITY' => $quantity,
            'PRODUCT_PROVIDER_CLASS' => 'CCatalogProductProvider',
        ]);
    }

    $propertyCollection = $item->getPropertyCollection();
    NanesenieOptionsResolver::applyBasketPropertyCollection($propertyCollection, $nanesenieValues);

    $added[] = [
        'offerId' => $offerId,
        'quantity' => (float)$item->getQuantity(),
    ];
    $lastOfferId = $offerId;
}

if ($added === []) {
    echo json_encode([
        'success' => false,
        'error' => $errors[0]['error'] ?? 'Не удалось добавить товары',
        'errors' => $errors,
    ]);
    require($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/main/include/epilog_after.php');
    exit;
}

$result = $basket->save();

if (!$result->isSuccess()) {
    if (defined('EKLEKTIKA_BASKET_DEBUG_LOG') && EKLEKTIKA_BASKET_DEBUG_LOG) {
        error_log('BASKET ERROR: ' . implode('; ', $result->getErrorMessages()));
    }
    echo json_encode(['success' => false, 'error' => implode('; ', $result->getErrorMessages())]);
} else {
    $_SESSION['MINI_CART_LAST_OFFER_ID'] = $lastOfferId;
    echo json_encode([
        'success' => true,
        'cart_count' => array_sum($basket->getQuantityList()),
        'offer_id' => $lastOfferId,
        'items' => $added,
        'errors' => $errors,
        'nanesenie' => $nanesenieValues,
    ]);
}

require($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/main/include/epilog_after.php');
