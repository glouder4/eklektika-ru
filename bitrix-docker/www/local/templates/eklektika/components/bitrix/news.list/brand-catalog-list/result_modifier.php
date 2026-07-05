<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/brand_catalog.php';

$items = [];
foreach ((array)($arResult['ITEMS'] ?? []) as $item) {
    if (!is_array($item)) {
        continue;
    }

    $slug = trim((string)($item['CODE'] ?? ''));
    if ($slug === '' || !brandCatalogListItemHasPicture($item)) {
        continue;
    }

    $item['DETAIL_PAGE_URL'] = brandCatalogListItemResolveDetailUrl($item, $slug);
    $items[] = $item;
}

$arResult['ITEMS'] = $items;
