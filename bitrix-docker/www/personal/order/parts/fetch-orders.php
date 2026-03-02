<?php
/**
 * Получение списка заказов пользователя
 * @param int $userId
 * @param int $limit
 * @return array ['orderIds' => int[], 'orderData' => array]
 */
if (!defined("B_PROLOG_INCLUDED") && !defined("BITRIX_INCLUDED")) {
    return [];
}

function orderPartsFetchOrders(int $userId, int $limit = 50): array {
    $orderIds = [];
    $orderData = [];

    $dbOrders = \CSaleOrder::GetList(
        ['DATE_INSERT' => 'DESC'],
        ['USER_ID' => $userId, 'CANCELED' => 'N'],
        false,
        ['nTopCount' => $limit],
        ['ID', 'DATE_INSERT', 'STATUS_ID', 'PRICE', 'CURRENCY', 'PAYED']
    );

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
            'items'    => [],
        ];
    }

    return ['orderIds' => $orderIds, 'orderData' => $orderData];
}
