<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Bitrix\Sale\Basket;
use Bitrix\Sale\Fuser;

header('Content-Type: application/json; charset=utf-8');

if (!Loader::includeModule('sale')) {
    echo json_encode(['error' => 'Модуль sale не подключен'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Получаем FUSER_ID текущей сессии
    $fuserId = Fuser::getId();

    // Загружаем корзину
    $basket = Basket::loadItemsForFUser($fuserId,'s1');

    $total = 0.0;
    $count = 0;

    /** @var \Bitrix\Sale\BasketItem $item */
    foreach ($basket as $item) {
        $count += $item->getQuantity();
        $total += $item->getFinalPrice();
    }

    $totalRounded = round($total, 2);
    $integerPart = floor($totalRounded);
    $fractionPart = str_pad((int)(($totalRounded - $integerPart) * 100), 2, '0', STR_PAD_LEFT);

    echo json_encode([
        'count' => (int)$count,
        'total' => $totalRounded,
        'formatted' => [$integerPart, $fractionPart]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}