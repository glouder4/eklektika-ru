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

    $select = ['ID', 'DATE_INSERT', 'STATUS_ID', 'PRICE', 'CURRENCY', 'PAYED'];

    $appendOrders = static function($dbOrders) use (&$orderIds, &$orderData): void {
        while ($order = $dbOrders->Fetch()) {
            $orderId = (int)($order['ID'] ?? 0);
            if ($orderId <= 0 || isset($orderData[$orderId])) {
                continue;
            }
            $orderIds[] = $orderId;
            $orderData[$orderId] = [
                'id'       => $orderId,
                'date'     => $order['DATE_INSERT'] ?? '',
                'status'   => $order['STATUS_ID'] ?? '',
                'price'    => (float)($order['PRICE'] ?? 0),
                'currency' => (string)($order['CURRENCY'] ?? ''),
                'paid'     => ($order['PAYED'] ?? 'N') === 'Y',
                'items'    => [],
            ];
        }
    };

    // Основной сценарий: корректные заказы с USER_ID.
    $dbOrders = \CSaleOrder::GetList(
        ['DATE_INSERT' => 'DESC'],
        ['USER_ID' => $userId, 'CANCELED' => 'N'],
        false,
        ['nTopCount' => $limit],
        $select
    );
    $appendOrders($dbOrders);

    // Fallback: если по USER_ID пусто (или есть «битые» заказы), пробуем CREATED_BY.
    if (\count($orderIds) < $limit) {
        $dbOrders2 = \CSaleOrder::GetList(
            ['DATE_INSERT' => 'DESC'],
            ['CREATED_BY' => $userId, 'CANCELED' => 'N'],
            false,
            ['nTopCount' => $limit],
            $select
        );
        $appendOrders($dbOrders2);
    }

    return ['orderIds' => $orderIds, 'orderData' => $orderData];
}
