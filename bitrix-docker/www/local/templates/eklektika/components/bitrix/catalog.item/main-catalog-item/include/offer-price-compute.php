<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Ожидает в области видимости: $offer (элемент оффера с ключом ID).
 * Задаёт: $offerPriceUi — массив для вывода цен (согласован с getCatalogPriceDiscount в init.php).
 *
 * @var array $offer
 */
$offerPriceUi = [
    'valid' => false,
    'showDiscount' => false,
    'discountPercent' => 0.0,
    'mainInt' => '0',
    'mainFrac' => '00',
    'oldInt' => '0',
    'oldFrac' => '00',
];

$offerId = isset($offer['ID']) ? (int)$offer['ID'] : 0;
if ($offerId <= 0) {
    return;
}

// Рекламная цена должна быть видна только рекламным агентам.
$canShowAdvertisingPrice = catalogCanShowAdvertisingPrice();

$mainPriceTypeId = $canShowAdvertisingPrice ? 3 : 2; // 2) оптовая
$oldPriceTypeId = $canShowAdvertisingPrice ? 2 : 2;  // для скидки (старой цены) — для оптовой пары нет, старое=новое

$offersPrice = getCatalogPriceDiscount($offerId, $mainPriceTypeId, $oldPriceTypeId);
if (!is_array($offersPrice) || !array_key_exists('MAIN', $offersPrice) || $offersPrice['MAIN'] === null) {
    return;
}

$discountPercent = (float)($offersPrice['DISCOUNT'] ?? 0);
$showDiscount = $discountPercent > 0.0001;
$mainPrice = (float)$offersPrice['MAIN'];
$oldPrice = isset($offersPrice['OLD']) && $offersPrice['OLD'] !== null ? (float)$offersPrice['OLD'] : $mainPrice;

[$mainInt, $mainFrac] = explode('.', number_format($mainPrice, 2, '.', ''));
[$oldInt, $oldFrac] = explode('.', number_format($oldPrice, 2, '.', ''));

$offerPriceUi = [
    'valid' => true,
    'showDiscount' => $showDiscount,
    'discountPercent' => $discountPercent,
    'mainInt' => $mainInt,
    'mainFrac' => $mainFrac,
    'oldInt' => $oldInt,
    'oldFrac' => $oldFrac,
];
