<?php
/**
 * Сборка заказов: добавление позиций в orderData
 * @param array $orderData
 * @param array $basketItems
 * @param array $productInfoMap
 * @return array
 */
if (!defined("B_PROLOG_INCLUDED") && !defined("BITRIX_INCLUDED")) {
    return [];
}

function orderPartsAssembleOrders(array $orderData, array $basketItems, array $productInfoMap): array {
    foreach ($basketItems as $item) {
        $orderId = (int)$item['ORDER_ID'];
        $productId = (int)$item['PRODUCT_ID'];

        if (!isset($orderData[$orderId])) {
            continue;
        }

        $finalPrice = (float)($item['PRICE'] ?? 0);
        $quantity = (float)($item['QUANTITY'] ?? 1);
        $lineTotal = $finalPrice * $quantity;
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
            'detail_url'     => $info['detail_url'],
            'properties'     => ['ARTIKUL_POSTAVSHCHIKA' => $info['artikul']],
        ];
    }

    return array_values($orderData);
}
