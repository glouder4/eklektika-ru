<?php
/**
 * Получение данных по товарам (URL, артикул)
 * @param int[] $productIds
 * @param int $catalogIblockId
 * @param int $catalogOffersIblockId
 * @return array [productId => ['detail_url' => string, 'artikul' => string]]
 */
if (!defined("B_PROLOG_INCLUDED") && !defined("BITRIX_INCLUDED")) {
    return [];
}

function orderPartsFetchProductInfo(array $productIds, int $catalogIblockId, int $catalogOffersIblockId): array {
    $productInfoMap = [];

    foreach ($productIds as $productId) {
        $catalogProduct = \CCatalogProduct::GetList(
            [],
            ['ID' => $productId],
            false,
            false,
            ['ID', 'TYPE', 'PARENT_PRODUCT_ID']
        )->Fetch();

        if (!$catalogProduct) {
            $productInfoMap[$productId] = ['detail_url' => '', 'artikul' => '', 'xml_id' => (string)$productId];
            continue;
        }

        $isOffer = ($catalogProduct['TYPE'] == \CCatalogProduct::TYPE_OFFER);
        $res = \CIBlockElement::GetByID($productId);
        $row = $res->GetNext();
        $url = $row ? ($row["DETAIL_PAGE_URL"] ?? '') : '';

        $xmlId = '';
        if ($isOffer) {
            $parentId = (int)($catalogProduct['PARENT_PRODUCT_ID'] ?? 0);
            if ($parentId > 0) {
                $parentRow = \CIBlockElement::GetByID($parentId)->Fetch();
                $xmlId = trim((string)($parentRow['XML_ID'] ?? ''));
            }
        }
        if ($xmlId === '' && $row) {
            $xmlId = trim((string)($row['XML_ID'] ?? ''));
        }
        if ($xmlId === '') {
            $xmlId = (string)$productId;
        }

        $propertyIblockId = $isOffer ? $catalogOffersIblockId : $catalogIblockId;
        $prop = \CIBlockElement::GetProperty(
            $propertyIblockId,
            $productId,
            ['sort' => 'asc'],
            ['CODE' => 'ARTIKUL_POSTAVSHCHIKA']
        )->Fetch();
        $artikul = $prop ? ($prop['VALUE'] ?? '') : '';

        $productInfoMap[$productId] = ['detail_url' => $url, 'artikul' => $artikul, 'xml_id' => $xmlId];
    }

    return $productInfoMap;
}
