<?php
$GLOBALS['OG_TAGS'] = [
    'title' => 'Каталог производителей. Сувенирная продукция популярных брендов - купить оптом',
    'description' => 'Компания Эклектика предлагает подарочную сувенирную продукцию оптом с нанесением ваших логотипов. Доставка по России. Оптовые цены.. ☎ 8(800) 777-4723',
];
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$APPLICATION->SetPageProperty('title', 'Каталог производителей. Сувенирная продукция популярных брендов - купить оптом');
$APPLICATION->SetPageProperty('description', 'Компания Эклектика предлагает подарочную сувенирную продукцию оптом с нанесением ваших логотипов. Доставка по России. Оптовые цены.. ☎ 8(800) 777-4723');
$APPLICATION->SetTitle('Бренды');

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/brand_catalog.php';

$brandListIblockId = function_exists('brandCatalogGetIblockId') ? brandCatalogGetIblockId() : 20;
$brandListIblockType = function_exists('brandCatalogGetIblockType') ? brandCatalogGetIblockType() : 'sliders';
$brandListBrendyProperty = function_exists('brandCatalogGetBrendyPropertyCode')
    ? brandCatalogGetBrendyPropertyCode()
    : 'BRENDY_DLYA_WEB';

$APPLICATION->IncludeComponent(
    'bitrix:news.list',
    'brand-catalog-list',
    [
        'IBLOCK_TYPE' => $brandListIblockType,
        'IBLOCK_ID' => (string)$brandListIblockId,
        'NEWS_COUNT' => '100',
        'SORT_BY1' => 'SORT',
        'SORT_ORDER1' => 'ASC',
        'SORT_BY2' => 'NAME',
        'SORT_ORDER2' => 'ASC',
        'FILTER_NAME' => '',
        'FIELD_CODE' => ['NAME', 'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'CODE'],
        'PROPERTY_CODE' => [
            $brandListBrendyProperty,
        ],
        'CHECK_DATES' => 'Y',
        'DETAIL_URL' => '/#ELEMENT_CODE#/',
        'AJAX_MODE' => 'N',
        'CACHE_TYPE' => 'A',
        'CACHE_TIME' => '36000000',
        'CACHE_FILTER' => 'N',
        'CACHE_GROUPS' => 'Y',
        'SET_TITLE' => 'N',
        'SET_BROWSER_TITLE' => 'N',
        'SET_META_KEYWORDS' => 'N',
        'SET_META_DESCRIPTION' => 'N',
        'SET_LAST_MODIFIED' => 'N',
        'INCLUDE_IBLOCK_INTO_CHAIN' => 'N',
        'ADD_SECTIONS_CHAIN' => 'N',
        'HIDE_LINK_WHEN_NO_DETAIL' => 'N',
        'PARENT_SECTION' => '',
        'PARENT_SECTION_CODE' => '',
        'INCLUDE_SUBSECTIONS' => 'N',
        'DISPLAY_DATE' => 'N',
        'DISPLAY_NAME' => 'N',
        'DISPLAY_PICTURE' => 'N',
        'DISPLAY_PREVIEW_TEXT' => 'N',
        'DISPLAY_TOP_PAGER' => 'N',
        'DISPLAY_BOTTOM_PAGER' => 'N',
    ],
    false
);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
