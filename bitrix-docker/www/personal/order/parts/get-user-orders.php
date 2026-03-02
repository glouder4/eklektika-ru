<?php
/**
 * Получение списка заказов пользователя с позициями
 * @param int $userId
 * @return array
 */
if (!defined("B_PROLOG_INCLUDED") && !defined("BITRIX_INCLUDED")) {
    return [];
}

require_once __DIR__ . '/fetch-orders.php';
require_once __DIR__ . '/fetch-basket.php';
require_once __DIR__ . '/fetch-product-info.php';
require_once __DIR__ . '/assemble-orders.php';

function getUserOrders(int $userId): array {
    if (!\Bitrix\Main\Loader::includeModule('sale') || !\Bitrix\Main\Loader::includeModule('iblock')) {
        return [];
    }

    $config = require __DIR__ . '/config.php'; // require (не _once) — нужен возврат массива
    $catalogIblockId = $config['catalog_iblock_id'];
    $catalogOffersIblockId = $config['catalog_offers_iblock_id'];
    $maxOrders = $config['max_orders'];

    try {
        $step1 = orderPartsFetchOrders($userId, $maxOrders);
        $orderIds = $step1['orderIds'];
        $orderData = $step1['orderData'];

        if (empty($orderIds)) {
            return [];
        }

        $step2 = orderPartsFetchBasket($orderIds);
        $basketItems = $step2['basketItems'];
        $productIds = $step2['productIds'];

        $productInfoMap = [];
        if (!empty($productIds)) {
            $productInfoMap = orderPartsFetchProductInfo($productIds, $catalogIblockId, $catalogOffersIblockId);
        }

        return orderPartsAssembleOrders($orderData, $basketItems, $productInfoMap);
    } catch (Exception $e) {
        return [];
    }
}
