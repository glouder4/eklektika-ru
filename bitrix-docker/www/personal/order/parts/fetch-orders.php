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
    $statusNamesById = [];
    $dbStatus = \CSaleStatus::GetList(['SORT' => 'ASC'], ['LID' => LANGUAGE_ID]);
    while ($statusRow = $dbStatus->Fetch()) {
        $statusId = (string)($statusRow['ID'] ?? '');
        if ($statusId !== '') {
            $statusNamesById[$statusId] = (string)($statusRow['NAME'] ?? $statusId);
        }
    }

    $select = ['ID', 'DATE_INSERT', 'STATUS_ID', 'PRICE', 'CURRENCY', 'PAYED'];

    $appendOrders = static function($dbOrders) use (&$orderIds, &$orderData, $statusNamesById): void {
        while ($order = $dbOrders->Fetch()) {
            $orderId = (int)($order['ID'] ?? 0);
            if ($orderId <= 0 || isset($orderData[$orderId])) {
                continue;
            }
            $orderIds[] = $orderId;

            $properties = [];
            $dbProps = \CSaleOrderPropsValue::GetList(
                ['SORT' => 'ASC', 'ID' => 'ASC'],
                ['ORDER_ID' => $orderId],
                false,
                false,
                ['CODE', 'VALUE']
            );
            while ($prop = $dbProps->Fetch()) {
                $code = (string)($prop['CODE'] ?? '');
                if ($code === '') {
                    continue;
                }
                if (!isset($properties[$code])) {
                    $properties[$code] = [];
                }
                $properties[$code][] = $prop['VALUE'] ?? null;
            }

            $statusId = (string)($order['STATUS_ID'] ?? '');
            $orderData[$orderId] = [
                'id'       => $orderId,
                'date'     => $order['DATE_INSERT'] ?? '',
                'status'   => $statusId,
                'status_name' => $statusNamesById[$statusId] ?? $statusId,
                'price'    => (float)($order['PRICE'] ?? 0),
                'currency' => (string)($order['CURRENCY'] ?? ''),
                'paid'     => ($order['PAYED'] ?? 'N') === 'Y',
                'properties' => $properties,
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
