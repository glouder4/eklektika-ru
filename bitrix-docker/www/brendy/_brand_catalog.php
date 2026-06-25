<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/brand_catalog.php';

/** @global CMain $APPLICATION */

$brandCatalogSlug = trim((string)($brandCatalogSlug ?? $_REQUEST['ELEMENT_CODE'] ?? ''), '/');
$brandConfig = getBrandCatalogConfig($brandCatalogSlug);

if ($brandConfig === null) {
    if (class_exists(\Bitrix\Iblock\Component\Tools::class)) {
        \Bitrix\Iblock\Component\Tools::process404('', true, true, true);
    }
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
    return;
}
$brandValue = trim((string)($brandConfig['BRENDY_DLYA_WEB'] ?? ''));
$brandTitle = trim((string)($brandConfig['TITLE'] ?? $brandCatalogSlug));
$pageFolder = brandCatalogGetPageFolder($brandCatalogSlug);

applyBrandCatalogFilter($brandValue);

$APPLICATION->SetTitle($brandTitle);
if (!empty($brandConfig['PAGE_TITLE'])) {
    $APPLICATION->SetPageProperty('title', (string)$brandConfig['PAGE_TITLE']);
} else {
    $APPLICATION->SetPageProperty('title', $brandTitle . ' — каталог товаров');
}
if (!empty($brandConfig['DESCRIPTION'])) {
    $APPLICATION->SetPageProperty('description', (string)$brandConfig['DESCRIPTION']);
}

$arParams = getBrandCatalogDefaultParams($pageFolder);
$arResult = [
    'FOLDER' => $pageFolder,
    'URL_TEMPLATES' => [
        'smart_filter' => 'filter/#SMART_FILTER_PATH#/apply/',
    ],
    'VARIABLES' => [
        'SECTION_ID' => 0,
        'SECTION_CODE' => '',
        'SMART_FILTER_PATH' => '',
    ],
];

$templateIncludePath = $_SERVER['DOCUMENT_ROOT']
    . '/local/templates/eklektika/components/bitrix/catalog/main-catalog-template/include/';
?>
<div class="category">
<?php
brandCatalogRenderSeoDescription($brandConfig, 'top');

$brandCategoryFilter = brandCatalogCollectCategoryFilterData(
    (int)$arParams['IBLOCK_ID'],
    brandCatalogBuildCategoryElementFilter(
        (int)$arParams['IBLOCK_ID'],
        $arParams['FILTER_NAME'],
        $arParams['FILTER_NAME']
    ),
    $arParams['FILTER_NAME']
);
brandCatalogRenderCategoryMenuFilter(
    $brandCategoryFilter,
    $pageFolder,
    $arParams['FILTER_NAME']
);

include $templateIncludePath . 'bootstrap-sort-filter.php';

$APPLICATION->IncludeComponent(
    'bitrix:catalog.smart.filter',
    'catalog-smartfilter-tamplate',
    [
        'IBLOCK_TYPE' => $arParams['IBLOCK_TYPE'],
        'IBLOCK_ID' => $arParams['IBLOCK_ID'],
        'SECTION_ID' => 0,
        'SECTION_CODE' => '',
        'FILTER_NAME' => $arParams['FILTER_NAME'],
        'PRICE_CODE' => $arParams['PRICE_CODE'],
        'CACHE_TYPE' => $arParams['CACHE_TYPE'],
        'CACHE_TIME' => $arParams['CACHE_TIME'],
        'CACHE_GROUPS' => $arParams['CACHE_GROUPS'],
        'SAVE_IN_SESSION' => 'N',
        'FILTER_VIEW_MODE' => $arParams['FILTER_VIEW_MODE'],
        'XML_EXPORT' => 'N',
        'SECTION_TITLE' => 'NAME',
        'SECTION_DESCRIPTION' => 'DESCRIPTION',
        'HIDE_NOT_AVAILABLE' => $arParams['HIDE_NOT_AVAILABLE'],
        'TEMPLATE_THEME' => $arParams['TEMPLATE_THEME'],
        'CONVERT_CURRENCY' => $arParams['CONVERT_CURRENCY'],
        'SEF_MODE' => 'N',
        'SEF_RULE' => $pageFolder . 'filter/#SMART_FILTER_PATH#/apply/',
        'SMART_FILTER_PATH' => '',
        'PAGER_PARAMS_NAME' => $arParams['PAGER_PARAMS_NAME'],
        'INSTANT_RELOAD' => $arParams['INSTANT_RELOAD'],
        'HIDE_BRAND_FILTER' => 'Y',
        'SHOW_CATEGORY_FILTER' => 'Y',
        'PREFILTER_NAME' => $arParams['FILTER_NAME'],
    ],
    false,
    ['HIDE_ICONS' => 'Y']
);

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/catalog_list_item_properties.php';
$GLOBALS['CATALOG_PRODUCT_IBLOCK_ID'] = (int)($arParams['IBLOCK_ID'] ?? 0);
catalogListApplyColorSubstringFilterToGlobal($arParams['FILTER_NAME']);

include $templateIncludePath . 'sorting-bar.php';

$elementTemplate = 'CARD';
if (isset($_GET['cat_view']) && (int)$_GET['cat_view'] === 2) {
    $elementTemplate = 'LINE';
}

$filterName = $arParams['FILTER_NAME'];
if (!isset($GLOBALS[$filterName]) || !is_array($GLOBALS[$filterName])) {
    $GLOBALS[$filterName] = [];
}
$advertisingPriceTypeId = (int)\OnlineService\Site\Config\CatalogPricingConfig::ADVERTISING_PRICE_TYPE_ID;
if ($advertisingPriceTypeId > 0) {
    $minPriceKey = '>=CATALOG_PRICE_' . $advertisingPriceTypeId;
    $maxPriceKey = '<=CATALOG_PRICE_' . $advertisingPriceTypeId;
    if (
        !isset($GLOBALS[$filterName][$minPriceKey])
        && !isset($GLOBALS[$filterName][$maxPriceKey])
    ) {
        $GLOBALS[$filterName]['>CATALOG_PRICE_' . $advertisingPriceTypeId] = 0;
    }
}

brandCatalogFinalizeGlobalFilter($filterName);

$catalogSectionId = brandCatalogResolveActiveSectionFilterId($filterName);
$iblockId = (int)$arParams['IBLOCK_ID'];

$debugElementFilter = array_merge(
    ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y'],
    $GLOBALS[$filterName] ?? []
);
$debugFilterWithoutSection = $debugElementFilter;
unset(
    $debugFilterWithoutSection['SECTION_ID'],
    $debugFilterWithoutSection['INCLUDE_SUBSECTIONS'],
    $debugFilterWithoutSection['@ID'],
    $debugFilterWithoutSection['ID']
);

$priceFilterKeys = [];
foreach ($GLOBALS[$filterName] ?? [] as $filterKey => $filterValue) {
    if (strpos((string)$filterKey, 'CATALOG_PRICE') !== false) {
        $priceFilterKeys[(string)$filterKey] = $filterValue;
    }
}

brandCatalogDebug('brand_catalog_before_section', [
    'brandValue' => $brandValue,
    'catalogSectionId' => $catalogSectionId,
    'GLOBALS_arrFilter' => $GLOBALS[$filterName] ?? null,
    'GET' => [
        'section' => brandCatalogGetSelectedSectionFilterIds($filterName),
        'brand' => $_GET[$filterName . '_brand'] ?? null,
    ],
    'test_counts' => $catalogSectionId > 0 ? array_merge([
        'brand_price_only' => brandCatalogCountElements($debugFilterWithoutSection),
        'section_sql' => brandCatalogCountElements(array_merge($debugFilterWithoutSection, [
            'SECTION_ID' => $catalogSectionId,
            'INCLUDE_SUBSECTIONS' => 'Y',
        ])),
        'section_bindings' => count(brandCatalogGetProductIdsInSectionSubtree(
            $iblockId,
            $catalogSectionId,
            $debugFilterWithoutSection
        )),
    ], brandCatalogBuildSectionDiagnostics(
        $iblockId,
        $catalogSectionId,
        $brandValue,
        $priceFilterKeys
    )) : null,
    'subtree_25_count' => $catalogSectionId > 0
        ? count(brandCatalogGetSectionSubtreeIds($iblockId, $catalogSectionId))
        : 0,
    'subtree_25_sample' => $catalogSectionId > 0
        ? array_slice(brandCatalogGetSectionSubtreeIds($iblockId, $catalogSectionId), 0, 20)
        : [],
]);

if ($catalogSectionId > 0) {
    brandCatalogApplySectionSubtreeToGlobalFilter($filterName, $iblockId);
    brandCatalogFinalizeGlobalFilter($filterName);
}

brandCatalogDebug('brand_catalog_after_section_ids', [
    'catalogSectionId' => $catalogSectionId,
    'GLOBALS_arrFilter' => $GLOBALS[$filterName] ?? null,
    'GLOBALS_id_count' => ($ids = brandCatalogGetGlobalFilterRestrictedProductIds($GLOBALS[$filterName] ?? [])) !== null
        ? count($ids)
        : null,
]);

$APPLICATION->IncludeComponent(
    'bitrix:catalog.section',
    'main-catalog-section',
    [
        'ELEMENT_TEMPLATE' => $elementTemplate,
        'IBLOCK_TYPE' => $arParams['IBLOCK_TYPE'],
        'IBLOCK_ID' => $arParams['IBLOCK_ID'],
        'ELEMENT_SORT_FIELD' => $arParams['ELEMENT_SORT_FIELD'],
        'ELEMENT_SORT_ORDER' => $arParams['ELEMENT_SORT_ORDER'],
        'ELEMENT_SORT_FIELD2' => $arParams['ELEMENT_SORT_FIELD2'],
        'ELEMENT_SORT_ORDER2' => $arParams['ELEMENT_SORT_ORDER2'],
        'PROPERTY_CODE' => ['BRENDY_DLYA_WEB', 'MATERIAL', 'RAZMERY'],
        'PROPERTY_CODE_MOBILE' => $arParams['LIST_PROPERTY_CODE_MOBILE'],
        'META_KEYWORDS' => $arParams['LIST_META_KEYWORDS'],
        'META_DESCRIPTION' => $arParams['LIST_META_DESCRIPTION'],
        'BROWSER_TITLE' => $arParams['LIST_BROWSER_TITLE'],
        'SET_BROWSER_TITLE' => 'N',
        'SET_META_KEYWORDS' => 'N',
        'SET_META_DESCRIPTION' => 'N',
        'SET_LAST_MODIFIED' => $arParams['SET_LAST_MODIFIED'],
        'INCLUDE_SUBSECTIONS' => $arParams['INCLUDE_SUBSECTIONS'],
        'BASKET_URL' => $arParams['BASKET_URL'],
        'ACTION_VARIABLE' => $arParams['ACTION_VARIABLE'],
        'PRODUCT_ID_VARIABLE' => $arParams['PRODUCT_ID_VARIABLE'],
        'SECTION_ID_VARIABLE' => $arParams['SECTION_ID_VARIABLE'],
        'PRODUCT_QUANTITY_VARIABLE' => $arParams['PRODUCT_QUANTITY_VARIABLE'],
        'PRODUCT_PROPS_VARIABLE' => $arParams['PRODUCT_PROPS_VARIABLE'],
        'FILTER_NAME' => $arParams['FILTER_NAME'],
        'CACHE_TYPE' => 'N',
        'CACHE_TIME' => $arParams['CACHE_TIME'],
        'CACHE_FILTER' => 'Y',
        'CACHE_GROUPS' => $arParams['CACHE_GROUPS'],
        'SET_TITLE' => $arParams['SET_TITLE'],
        'MESSAGE_404' => $arParams['MESSAGE_404'],
        'SET_STATUS_404' => $arParams['SET_STATUS_404'],
        'SHOW_404' => $arParams['SHOW_404'],
        'FILE_404' => $arParams['FILE_404'],
        'DISPLAY_COMPARE' => $arParams['USE_COMPARE'],
        'PAGE_ELEMENT_COUNT' => $arParams['PAGE_ELEMENT_COUNT'],
        'LINE_ELEMENT_COUNT' => $arParams['LINE_ELEMENT_COUNT'],
        'PRICE_CODE' => $arParams['PRICE_CODE'],
        'USE_PRICE_COUNT' => 'N',
        'SHOW_PRICE_COUNT' => '1',
        'PRICE_VAT_INCLUDE' => 'Y',
        'USE_PRODUCT_QUANTITY' => $arParams['USE_PRODUCT_QUANTITY'],
        'ADD_PROPERTIES_TO_BASKET' => $arParams['ADD_PROPERTIES_TO_BASKET'],
        'PARTIAL_PRODUCT_PROPERTIES' => $arParams['PARTIAL_PRODUCT_PROPERTIES'],
        'PRODUCT_PROPERTIES' => $arParams['PRODUCT_PROPERTIES'],
        'DISPLAY_PROPERTIES' => ['COLOR', 'ARTIKUL_POSTAVSHCHIKA', 'TSVET', 'BRENDY_DLYA_WEB', 'MATERIAL', 'RAZMERY'],
        'DISPLAY_TOP_PAGER' => $arParams['DISPLAY_TOP_PAGER'],
        'DISPLAY_BOTTOM_PAGER' => $arParams['DISPLAY_BOTTOM_PAGER'],
        'PAGER_TITLE' => $arParams['PAGER_TITLE'],
        'PAGER_SHOW_ALWAYS' => $arParams['PAGER_SHOW_ALWAYS'],
        'PAGER_TEMPLATE' => $arParams['PAGER_TEMPLATE'],
        'PAGER_DESC_NUMBERING' => 'N',
        'PAGER_DESC_NUMBERING_CACHE_TIME' => '36000',
        'PAGER_SHOW_ALL' => 'N',
        'PAGER_BASE_LINK_ENABLE' => 'N',
        'LAZY_LOAD' => $arParams['LAZY_LOAD'],
        'LOAD_ON_SCROLL' => $arParams['LOAD_ON_SCROLL'],
        'OFFERS_CART_PROPERTIES' => [],
        'OFFERS_FIELD_CODE' => $arParams['LIST_OFFERS_FIELD_CODE'],
        'OFFERS_PROPERTY_CODE' => array_values(array_unique(array_merge(
            is_array($arParams['LIST_OFFERS_PROPERTY_CODE'] ?? null) ? $arParams['LIST_OFFERS_PROPERTY_CODE'] : [],
            ['ARTIKUL_POSTAVSHCHIKA', 'TSVET', 'MATERIAL', 'RAZMERY']
        ))),
        'OFFERS_SORT_FIELD' => $arParams['OFFERS_SORT_FIELD'],
        'OFFERS_SORT_ORDER' => $arParams['OFFERS_SORT_ORDER'],
        'OFFERS_SORT_FIELD2' => $arParams['OFFERS_SORT_FIELD2'],
        'OFFERS_SORT_ORDER2' => $arParams['OFFERS_SORT_ORDER2'],
        'OFFERS_LIMIT' => $arParams['LIST_OFFERS_LIMIT'],
        'SECTION_ID' => 0,
        'SECTION_CODE' => '',
        'SHOW_ALL_WO_SECTION' => 'Y',
        'SECTION_URL' => $arParams['SECTION_URL'],
        'DETAIL_URL' => $arParams['DETAIL_URL'],
        'USE_MAIN_ELEMENT_SECTION' => $arParams['USE_MAIN_ELEMENT_SECTION'],
        'CONVERT_CURRENCY' => $arParams['CONVERT_CURRENCY'],
        'HIDE_NOT_AVAILABLE' => $arParams['HIDE_NOT_AVAILABLE'],
        'HIDE_NOT_AVAILABLE_OFFERS' => $arParams['HIDE_NOT_AVAILABLE_OFFERS'],
        'LABEL_PROP' => $arParams['LABEL_PROP'],
        'LABEL_PROP_MOBILE' => $arParams['LABEL_PROP_MOBILE'],
        'LABEL_PROP_POSITION' => $arParams['LABEL_PROP_POSITION'],
        'ADD_PICT_PROP' => $arParams['ADD_PICT_PROP'],
        'PRODUCT_DISPLAY_MODE' => $arParams['PRODUCT_DISPLAY_MODE'],
        'PRODUCT_BLOCKS_ORDER' => $arParams['LIST_PRODUCT_BLOCKS_ORDER'],
        'PRODUCT_ROW_VARIANTS' => $arParams['LIST_PRODUCT_ROW_VARIANTS'],
        'ENLARGE_PRODUCT' => $arParams['LIST_ENLARGE_PRODUCT'],
        'SHOW_SLIDER' => $arParams['LIST_SHOW_SLIDER'],
        'SLIDER_INTERVAL' => $arParams['LIST_SLIDER_INTERVAL'],
        'SLIDER_PROGRESS' => $arParams['LIST_SLIDER_PROGRESS'],
        'OFFER_ADD_PICT_PROP' => $arParams['OFFER_ADD_PICT_PROP'],
        'OFFER_TREE_PROPS' => $arParams['OFFER_TREE_PROPS'],
        'PRODUCT_SUBSCRIPTION' => $arParams['PRODUCT_SUBSCRIPTION'],
        'SHOW_DISCOUNT_PERCENT' => $arParams['SHOW_DISCOUNT_PERCENT'],
        'SHOW_OLD_PRICE' => $arParams['SHOW_OLD_PRICE'],
        'MESS_BTN_BUY' => 'Купить',
        'MESS_BTN_ADD_TO_BASKET' => 'В корзину',
        'MESS_BTN_SUBSCRIBE' => 'Подписаться',
        'MESS_BTN_DETAIL' => 'Подробнее',
        'MESS_NOT_AVAILABLE' => 'Нет в наличии',
        'ADD_SECTIONS_CHAIN' => 'N',
        'ADD_TO_BASKET_ACTION' => $arParams['SECTION_ADD_TO_BASKET_ACTION'],
        'SHOW_CLOSE_POPUP' => $arParams['COMMON_SHOW_CLOSE_POPUP'],
        'COMPATIBLE_MODE' => $arParams['COMPATIBLE_MODE'],
        'DISABLE_INIT_JS_IN_COMPONENT' => $arParams['DISABLE_INIT_JS_IN_COMPONENT'],
    ],
    false
);

brandCatalogRenderSeoDescription($brandConfig, 'bottom');

include $templateIncludePath . 'sorting-url-script.php';
?>
</div>
<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
