<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
use Bitrix\Sale;

if (!Loader::includeModule('sale')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Модуль sale не загружен']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['ajax_basket'] ?? '') !== 'Y') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Неверный запрос']);
    exit;
}

$action = $_POST['action'] ?? 'update'; // 'update' или 'remove'
$offerId = (int)($_POST['offerId'] ?? 0);

if ($offerId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Некорректный ID']);
    exit;
}

$fuserId = Sale\Fuser::getId();
$basket = Sale\Basket::loadItemsForFUser($fuserId, SITE_ID);
$basketItems = $basket->getBasketItems();
$item = false;

if($basketItems) {
    foreach($basketItems as $basketItem) {
        if($basketItem->getField('PRODUCT_ID') == $offerId) {
            $item = $basketItem;
            break;
        }
    }
}


if (!$item) {
    echo json_encode(['success' => false, 'error' => 'Товар не найден в корзине']);
    exit;
}

$result = null;

if ($action === 'remove') {
    // Удаляем элемент
    $item->delete();
    $result = $basket->save();
} else {
    // Обновляем количество
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));
    $item->setField('QUANTITY', $quantity);
    $result = $basket->save();
}

if (!$result->isSuccess()) {
    echo json_encode([
        'success' => false,
        'error' => implode('; ', $result->getErrorMessages())
    ]);
} else {
    if ($action === 'remove') {
        echo json_encode([
            'success' => true,
            'action' => 'removed',
            'offerId' => $offerId
        ]);
    } else {
        // Пересчитываем общую сумму (опционально)
        $totalPrice = 0;
        foreach ($basket as $bItem) {
            $totalPrice += $bItem->getFinalPrice();
        }

        echo json_encode([
            'success' => true,
            'action' => 'updated',
            'offerId' => $offerId,
            'quantity' => $item->getQuantity(),
            'totalPrice' => $totalPrice
        ]);
    }
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");