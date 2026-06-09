<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Sale;
use OnlineService\Catalog\NanesenieOptionsResolver;

$arResult['NANESENIE_OPTIONS'] = NanesenieOptionsResolver::getAllOptions();
$arResult['NANESENIE_BY_OFFER'] = [];

if (!Loader::includeModule('sale')) {
    return;
}

$basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), SITE_ID);
foreach ($basket as $basketItem) {
    $offerId = (int)$basketItem->getProductId();
    if ($offerId <= 0) {
        continue;
    }

    $arResult['NANESENIE_BY_OFFER'][$offerId] = NanesenieOptionsResolver::extractSelectedFromPropertyCollection(
        $basketItem->getPropertyCollection()
    );
}
