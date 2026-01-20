<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
use Bitrix\Sale;

if (!Loader::includeModule('sale')) {
    echo json_encode(['error' => 'Модуль sale не загружен']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['ajax_basket'] ?? '') !== 'Y') {
    http_response_code(400);
    echo json_encode(['error' => 'Неверный запрос']);
    exit;
}

$offerId = (int)($_POST['offerId'] ?? 0);
$quantity = max(1, (int)($_POST['quantity'] ?? 1));

if ($offerId <= 0) {
    echo json_encode(['error' => 'Некорректный ID']);
    exit;
}

// Загружаем корзину
$fuserId = Sale\Fuser::getId();
$basket = Sale\Basket::loadItemsForFUser($fuserId, SITE_ID);

// Ищем элемент
$item = $basket->getExistsItem('catalog', $offerId);

if (!$item) {
    echo json_encode(['error' => 'Товар не найден в корзине']);
    exit;
}

// Обновляем количество
$item->setField('QUANTITY', $quantity);

// Сохраняем
$result = $basket->save();

if (!$result->isSuccess()) {
    echo json_encode([
        'success' => false,
        'error' => implode('; ', $result->getErrorMessages())
    ]);
} else {
    // Пересчитываем общую сумму
    $totalPrice = 0;
    foreach ($basket as $bItem) {
        $totalPrice += $bItem->getFinalPrice();
    }

    echo json_encode([
        'success' => true,
        'quantity' => $item->getQuantity(),
        'offerId' => $offerId,
    ]);
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");