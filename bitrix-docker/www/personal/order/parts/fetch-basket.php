<?php
/**
 * Получение позиций корзины по списку заказов
 * @param int[] $orderIds
 * @return array ['basketItems' => array, 'productIds' => int[]]
 */
if (!defined("B_PROLOG_INCLUDED") && !defined("BITRIX_INCLUDED")) {
    return [];
}

function orderPartsFetchBasket(array $orderIds): array {
    $productIds = [];
    $basketItems = [];

    $dbBasket = \CSaleBasket::GetList(
        ['ID' => 'ASC'],
        ['ORDER_ID' => $orderIds],
        false,
        false,
        ['ID', 'ORDER_ID', 'PRODUCT_ID', 'NAME', 'QUANTITY', 'PRICE', 'CURRENCY', 'BASE_PRICE', 'DISCOUNT_PRICE']
    );

    while ($item = $dbBasket->Fetch()) {
        $productId = (int)$item['PRODUCT_ID'];
        if ($productId > 0) {
            $productIds[] = $productId;
        }
        $basketItems[] = $item;
    }

    return ['basketItems' => $basketItems, 'productIds' => array_unique($productIds)];
}
