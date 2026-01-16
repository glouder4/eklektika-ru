<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

//$this->addExternalCss("/bitrix/css/main/bootstrap.css");

if ($arParams["USE_COMPARE"]=="Y")
{
	$APPLICATION->IncludeComponent(
		"bitrix:catalog.compare.list",
		"",
		[
			"IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
			"IBLOCK_ID" => $arParams["IBLOCK_ID"],
			"NAME" => $arParams["COMPARE_NAME"],
			"DETAIL_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["element"],
			"COMPARE_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["compare"],
			"ACTION_VARIABLE" => (!empty($arParams["ACTION_VARIABLE"]) ? $arParams["ACTION_VARIABLE"] : "action"),
			"PRODUCT_ID_VARIABLE" => $arParams["PRODUCT_ID_VARIABLE"],
			'POSITION_FIXED' => $arParams['COMPARE_POSITION_FIXED'] ?? '',
			'POSITION' => $arParams['COMPARE_POSITION'] ?? ''
		],
		$component,
		["HIDE_ICONS" => "Y"]
	);
}

if (isset($arParams['USE_COMMON_SETTINGS_BASKET_POPUP']) && $arParams['USE_COMMON_SETTINGS_BASKET_POPUP'] == 'Y')
{
	$basketAction = ($arParams['COMMON_ADD_TO_BASKET_ACTION'] ?? '');
}
else
{
	$basketAction = ($arParams['SECTION_ADD_TO_BASKET_ACTION'] ?? '');
}


// Обрабатываем параметры сортировки из URL
$sortField = $arParams["ELEMENT_SORT_FIELD"] ?? "sort";
$sortOrder = $arParams["ELEMENT_SORT_ORDER"] ?? "asc";

// Получаем ID типа цены для сортировки по цене
$priceTypeId = null;
$priceCode = $arParams["~PRICE_CODE"] ?? [];
if (is_array($priceCode) && !empty($priceCode)) {
    if (is_numeric($priceCode[0])) {
        $priceTypeId = intval($priceCode[0]);
    } else {
        // Если передан код, получаем ID
        CModule::IncludeModule("catalog");
        $dbPriceType = CCatalogGroup::GetList(array(), array("NAME" => $priceCode[0]));
        if ($arPriceType = $dbPriceType->Fetch()) {
            $priceTypeId = $arPriceType["ID"];
        }
    }
}

// Если не удалось получить ID типа цены, используем значение по умолчанию
if (!$priceTypeId) {
    $priceTypeId = 1; // Значение по умолчанию
}

// Маппинг значений сортировки из URL на поля Bitrix
$sortFieldMap = array(
    "price" => "CATALOG_PRICE_" . $priceTypeId,
    "pagetitle" => "name",
    "inventory" => "CATALOG_QUANTITY"
);

// Обрабатываем параметры сортировки из GET
if (isset($_GET['sort_field']) && !empty($_GET['sort_field'])) {
    $requestedField = $_GET['sort_field'];
    if (isset($sortFieldMap[$requestedField])) {
        $sortField = $sortFieldMap[$requestedField];
    } elseif (in_array($requestedField, array("name", "sort", "id", "shows", "timestamp_x"))) {
        // Прямое использование стандартных полей Bitrix
        $sortField = $requestedField;
    }
}

if (isset($_GET['sort_order']) && in_array(strtolower($_GET['sort_order']), array("asc", "desc"))) {
    $sortOrder = strtolower($_GET['sort_order']);
}

// Обрабатываем фильтр "Новинки"
$isNovinki = isset($_GET['novinki']) && $_GET['novinki'] == '1';
if ($isNovinki) {
    $sortField = "timestamp_x"; // Сортировка по дате создания
    $sortOrder = "desc";
}

// Обновляем параметры для передачи в компонент
$arParams["ELEMENT_SORT_FIELD"] = $sortField;
$arParams["ELEMENT_SORT_ORDER"] = $sortOrder;

// Передаем параметры фильтра из URL в глобальную переменную для компонента каталога
// Делаем это ДО вызова компонентов, чтобы они могли использовать эти параметры
$filterName = $arParams["FILTER_NAME"] ?? "arrFilter";
$preFilterName = "arrPreFilter"; // Имя для предфильтра (поиск по названию)
$stockFilterName = $filterName . "_stock";

// Создаем предфильтр для поиска по названию (параметр q)
if (isset($_GET['q']) && $_GET['q'] !== '') {
    $searchQuery = trim($_GET['q']);
    if (!empty($searchQuery)) {
        CModule::IncludeModule("catalog");
        CModule::IncludeModule("iblock");
        
        $IBLOCK_ID = $arParams["IBLOCK_ID"] ?? 13;
        $OFFER_IBLOCK_ID = 14; // ID инфоблока товарных предложений
        
        // Получаем информацию о связи товарных предложений с товарами
        $arSKU = CCatalogSKU::GetInfoByProductIBlock($IBLOCK_ID);
        $hasOffers = !empty($arSKU) && $arSKU['IBLOCK_ID'] == $OFFER_IBLOCK_ID;
        
        $productIdsFromOffers = array();
        
        // Ищем в товарных предложениях
        if ($hasOffers) {
            $arFilterOffers = array(
                'IBLOCK_ID' => $OFFER_IBLOCK_ID,
                'ACTIVE' => 'Y',
                'ACTIVE_DATE' => 'Y',
                '%NAME' => $searchQuery
            );
            
            $arSelectOffers = array('ID', 'NAME', 'CODE', 'IBLOCK_SECTION_ID', 'IBLOCK_ID');
            if ($arSKU['SKU_PROPERTY_ID'] > 0) {
                $arSelectOffers[] = 'PROPERTY_' . $arSKU['SKU_PROPERTY_ID'];
            }
            
            $rsOffers = CIBlockElement::GetList(
                array('SORT' => 'ASC', 'NAME' => 'ASC'),
                $arFilterOffers,
                false,
                false,
                $arSelectOffers
            );
            
            while ($arOffer = $rsOffers->GetNext()) {
                $linkedProductId = 0;
                
                // Получаем ID связанного товара из свойства
                if ($arSKU['SKU_PROPERTY_ID'] > 0) {
                    $propKey = 'PROPERTY_' . $arSKU['SKU_PROPERTY_ID'] . '_VALUE';
                    if (isset($arOffer[$propKey])) {
                        $linkedProductId = intval($arOffer[$propKey]);
                    }
                }
                
                if ($linkedProductId > 0 && !in_array($linkedProductId, $productIdsFromOffers)) {
                    $productIdsFromOffers[] = $linkedProductId;
                }
            }
        }
        
        // Создаем предфильтр
        // Если есть товары, найденные через предложения, используем фильтр по ID
        if (!empty($productIdsFromOffers)) {
            // Ищем также в основном инфоблоке
            $arFilterProducts = array(
                'IBLOCK_ID' => $IBLOCK_ID,
                'ACTIVE' => 'Y',
                'ACTIVE_DATE' => 'Y',
                '%NAME' => $searchQuery
            );
            
            $rsProducts = CIBlockElement::GetList(
                array(),
                $arFilterProducts,
                false,
                false,
                array('ID')
            );
            
            $productIdsFromProducts = array();
            while ($arProduct = $rsProducts->GetNext()) {
                $productIdsFromProducts[] = intval($arProduct['ID']);
            }
            
            // Объединяем ID товаров из обоих источников
            $allProductIds = array_unique(array_merge($productIdsFromOffers, $productIdsFromProducts));
            
            // Используем фильтр по ID товаров
            $GLOBALS[$preFilterName] = array('ID' => $allProductIds);
        } else {
            // Если товары через предложения не найдены, используем обычный фильтр по названию
            $GLOBALS[$preFilterName] = array('%NAME' => $searchQuery);
        }
    }
}

// Если есть параметры фильтра в URL, но нет set_filter=y, добавляем его
if (isset($_GET) && is_array($_GET)) {
    $filterArray = array();
    $hasFilterParams = false;
    foreach ($_GET as $key => $value) {
        if (strpos($key, $filterName) === 0) {
            $filterArray[$key] = $value;
            $hasFilterParams = true;
        }
    }

    // Обрабатываем фильтр по остаткам
    if (isset($_GET[$stockFilterName]) && $_GET[$stockFilterName] !== '') {
        $stockValue = intval($_GET[$stockFilterName]);
        if ($stockValue > 0) {
            // Добавляем фильтр по остаткам: товары с остатком >= указанного значения
            $filterArray[">=CATALOG_QUANTITY"] = $stockValue;
            $hasFilterParams = true;
        }
    }

    // Обрабатываем фильтр по цене (формат: minmax~min,max)
    if (isset($_GET['f8']) && $_GET['f8'] !== '') {
        $priceFilter = $_GET['f8'];
        if (preg_match('/^minmax~(\d+(?:\.\d+)?),(\d+(?:\.\d+)?)$/', $priceFilter, $matches)) {
            $minPrice = floatval($matches[1]);
            $maxPrice = floatval($matches[2]);

            // Получаем ID типа цены из параметров (обычно первый тип цены)
            $priceCode = $arParams["~PRICE_CODE"] ?? [];
            if (is_array($priceCode) && !empty($priceCode)) {
                // Получаем ID типа цены
                $priceTypeId = null;
                if (is_numeric($priceCode[0])) {
                    $priceTypeId = intval($priceCode[0]);
                } else {
                    // Если передан код, получаем ID
                    CModule::IncludeModule("catalog");
                    $dbPriceType = CCatalogGroup::GetList(array(), array("NAME" => $priceCode[0]));
                    if ($arPriceType = $dbPriceType->Fetch()) {
                        $priceTypeId = $arPriceType["ID"];
                    }
                }

                if ($priceTypeId) {
                    // Фильтруем по финальной цене с учетом скидок
                    // Используем специальный фильтр для финальной цены
                    $filterArray[">=CATALOG_PRICE_" . $priceTypeId] = $minPrice;
                    $filterArray["<=CATALOG_PRICE_" . $priceTypeId] = $maxPrice;
                    $hasFilterParams = true;
                }
            }
        }
    }
    
    // Если есть параметры фильтра, но нет set_filter=y, добавляем его
    if ($hasFilterParams && !isset($_GET['set_filter'])) {
        $_GET['set_filter'] = 'y';
        $_REQUEST['set_filter'] = 'y';
    }
    
    // Создаем глобальную переменную с именем фильтра
    if (!empty($filterArray)) {
        // Если глобальный фильтр уже существует, мержим с ним (но приоритет у новых значений)
        if (isset($GLOBALS[$filterName]) && is_array($GLOBALS[$filterName])) {
            $GLOBALS[$filterName] = array_merge($GLOBALS[$filterName], $filterArray);
        } else {
            $GLOBALS[$filterName] = $filterArray;
        }
    }
}

$APPLICATION->IncludeComponent(
    "bitrix:catalog.smart.filter",
    "search-smartfilter-template",
    array(
        "IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
        "IBLOCK_ID" => $arParams["IBLOCK_ID"],
        "SECTION_ID" => $arResult["VARIABLES"]["SECTION_ID"],
        "SECTION_CODE" => $arResult["VARIABLES"]["SECTION_CODE"],
        "PREFILTER_NAME" => $preFilterName,
        "FILTER_NAME" => $arParams["FILTER_NAME"],
        "PRICE_CODE" => $arParams["~PRICE_CODE"],
        "CACHE_TYPE" => $arParams["CACHE_TYPE"],
        "CACHE_TIME" => $arParams["CACHE_TIME"],
        "CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
        "SAVE_IN_SESSION" => "N",
        "FILTER_VIEW_MODE" => $arParams["FILTER_VIEW_MODE"],
        "XML_EXPORT" => "N",
        "SECTION_TITLE" => "-",
        "SECTION_DESCRIPTION" => "DESCRIPTION",
        'HIDE_NOT_AVAILABLE' => $arParams["HIDE_NOT_AVAILABLE"],
        "TEMPLATE_THEME" => $arParams["TEMPLATE_THEME"],
        'CONVERT_CURRENCY' => $arParams['CONVERT_CURRENCY'],
        'CURRENCY_ID' => $arParams['CURRENCY_ID'],
        "SEF_MODE" => $arParams['SEF_MODE'], // Отключаем SEF для фильтра, чтобы работали GET параметры
        "SEF_RULE" => $arResult["FOLDER"] . $arResult["URL_TEMPLATES"]["smart_filter"],
        "SMART_FILTER_PATH" => $arResult["VARIABLES"]["SMART_FILTER_PATH"],
        "PAGER_PARAMS_NAME" => $arParams["PAGER_PARAMS_NAME"],
        "INSTANT_RELOAD" => $arParams["INSTANT_RELOAD"],
    ),
    $component,
    array('HIDE_ICONS' => 'Y')
);


// Обрабатываем параметр cat_view для определения шаблона отображения
$elementTemplate = "CARD"; // По умолчанию краткий вид (карточки)
if (isset($_GET['cat_view']) && intval($_GET['cat_view']) == 2) {
    $elementTemplate = "LINE"; // Детальный вид (список)
}

// Параметры фильтра уже установлены выше, перед вызовом компонента умного фильтра
$intSectionID = $APPLICATION->IncludeComponent(
    "bitrix:catalog.section",
    "search-catalog-section",
    array(
        "ELEMENT_TEMPLATE" => $elementTemplate,
        "IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
        "IBLOCK_ID" => $arParams["IBLOCK_ID"],
        "ELEMENT_SORT_FIELD" => $arParams["ELEMENT_SORT_FIELD"],
        "ELEMENT_SORT_ORDER" => $arParams["ELEMENT_SORT_ORDER"],
        "ELEMENT_SORT_FIELD2" => $arParams["ELEMENT_SORT_FIELD2"],
        "ELEMENT_SORT_ORDER2" => $arParams["ELEMENT_SORT_ORDER2"],
        "PROPERTY_CODE" => $arParams["LIST_PROPERTY_CODE"],
        "PROPERTY_CODE_MOBILE" => $arParams["LIST_PROPERTY_CODE_MOBILE"],
        "META_KEYWORDS" => $arParams["LIST_META_KEYWORDS"],
        "META_DESCRIPTION" => $arParams["LIST_META_DESCRIPTION"],
        "BROWSER_TITLE" => $arParams["LIST_BROWSER_TITLE"],
        "SET_LAST_MODIFIED" => $arParams["SET_LAST_MODIFIED"],
        "INCLUDE_SUBSECTIONS" => $arParams["INCLUDE_SUBSECTIONS"],
        "BASKET_URL" => $arParams["BASKET_URL"],
        "ACTION_VARIABLE" => $arParams["ACTION_VARIABLE"],
        "PRODUCT_ID_VARIABLE" => $arParams["PRODUCT_ID_VARIABLE"],
        "SECTION_ID_VARIABLE" => $arParams["SECTION_ID_VARIABLE"],
        "PRODUCT_QUANTITY_VARIABLE" => $arParams["PRODUCT_QUANTITY_VARIABLE"],
        "PRODUCT_PROPS_VARIABLE" => $arParams["PRODUCT_PROPS_VARIABLE"],
        "FILTER_NAME" => $arParams["FILTER_NAME"],
        "CACHE_TYPE" => $arParams["CACHE_TYPE"],
        "CACHE_TIME" => $arParams["CACHE_TIME"],
        "CACHE_FILTER" => "N", // Отключаем кеш фильтра для корректной работы
        "CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
        "SET_TITLE" => $arParams["SET_TITLE"],
        "MESSAGE_404" => $arParams["~MESSAGE_404"],
        "SET_STATUS_404" => $arParams["SET_STATUS_404"],
        "SHOW_404" => $arParams["SHOW_404"],
        "FILE_404" => $arParams["FILE_404"],
        "DISPLAY_COMPARE" => $arParams["USE_COMPARE"],
        "PAGE_ELEMENT_COUNT" => $arParams["PAGE_ELEMENT_COUNT"],
        "LINE_ELEMENT_COUNT" => $arParams["LINE_ELEMENT_COUNT"],
        "PRICE_CODE" => $arParams["~PRICE_CODE"],
        "USE_PRICE_COUNT" => $arParams["USE_PRICE_COUNT"],
        "SHOW_PRICE_COUNT" => $arParams["SHOW_PRICE_COUNT"],

        "PRICE_VAT_INCLUDE" => $arParams["PRICE_VAT_INCLUDE"],
        "USE_PRODUCT_QUANTITY" => $arParams['USE_PRODUCT_QUANTITY'],
        "ADD_PROPERTIES_TO_BASKET" => (isset($arParams["ADD_PROPERTIES_TO_BASKET"]) ? $arParams["ADD_PROPERTIES_TO_BASKET"] : ''),
        "PARTIAL_PRODUCT_PROPERTIES" => (isset($arParams["PARTIAL_PRODUCT_PROPERTIES"]) ? $arParams["PARTIAL_PRODUCT_PROPERTIES"] : ''),
        "PRODUCT_PROPERTIES" => (isset($arParams["PRODUCT_PROPERTIES"]) ? $arParams["PRODUCT_PROPERTIES"] : []),

        "DISPLAY_TOP_PAGER" => $arParams["DISPLAY_TOP_PAGER"],
        "DISPLAY_BOTTOM_PAGER" => $arParams["DISPLAY_BOTTOM_PAGER"],
        "PAGER_TITLE" => $arParams["PAGER_TITLE"],
        "PAGER_SHOW_ALWAYS" => $arParams["PAGER_SHOW_ALWAYS"],
        "PAGER_TEMPLATE" => $arParams["PAGER_TEMPLATE"],
        "PAGER_DESC_NUMBERING" => $arParams["PAGER_DESC_NUMBERING"],
        "PAGER_DESC_NUMBERING_CACHE_TIME" => $arParams["PAGER_DESC_NUMBERING_CACHE_TIME"],
        "PAGER_SHOW_ALL" => $arParams["PAGER_SHOW_ALL"],
        "PAGER_BASE_LINK_ENABLE" => $arParams["PAGER_BASE_LINK_ENABLE"],
        "PAGER_BASE_LINK" => $arParams["PAGER_BASE_LINK"],
        "PAGER_PARAMS_NAME" => $arParams["PAGER_PARAMS_NAME"],
        "LAZY_LOAD" => $arParams["LAZY_LOAD"],
        "MESS_BTN_LAZY_LOAD" => $arParams["~MESS_BTN_LAZY_LOAD"],
        "LOAD_ON_SCROLL" => $arParams["LOAD_ON_SCROLL"],

        "OFFERS_CART_PROPERTIES" => (isset($arParams["OFFERS_CART_PROPERTIES"]) ? $arParams["OFFERS_CART_PROPERTIES"] : []),
        "OFFERS_FIELD_CODE" => $arParams["LIST_OFFERS_FIELD_CODE"],
        "OFFERS_PROPERTY_CODE" => (isset($arParams["LIST_OFFERS_PROPERTY_CODE"]) ? $arParams["LIST_OFFERS_PROPERTY_CODE"] : []),
        "OFFERS_SORT_FIELD" => $arParams["OFFERS_SORT_FIELD"],
        "OFFERS_SORT_ORDER" => $arParams["OFFERS_SORT_ORDER"],
        "OFFERS_SORT_FIELD2" => $arParams["OFFERS_SORT_FIELD2"],
        "OFFERS_SORT_ORDER2" => $arParams["OFFERS_SORT_ORDER2"],
        "OFFERS_LIMIT" => (isset($arParams["LIST_OFFERS_LIMIT"]) ? $arParams["LIST_OFFERS_LIMIT"] : 0),

        "SECTION_ID" => $arResult["VARIABLES"]["SECTION_ID"],
        "SECTION_CODE" => $arResult["VARIABLES"]["SECTION_CODE"],
        "SECTION_URL" => $arResult["FOLDER"] . $arResult["URL_TEMPLATES"]["section"],
        "DETAIL_URL" => $arResult["FOLDER"] . $arResult["URL_TEMPLATES"]["element"],
        "USE_MAIN_ELEMENT_SECTION" => $arParams["USE_MAIN_ELEMENT_SECTION"],
        'CONVERT_CURRENCY' => $arParams['CONVERT_CURRENCY'],
        'CURRENCY_ID' => $arParams['CURRENCY_ID'],
        'HIDE_NOT_AVAILABLE' => $arParams["HIDE_NOT_AVAILABLE"],
        'HIDE_NOT_AVAILABLE_OFFERS' => $arParams["HIDE_NOT_AVAILABLE_OFFERS"],

        'LABEL_PROP' => $arParams['LABEL_PROP'],
        'LABEL_PROP_MOBILE' => $arParams['LABEL_PROP_MOBILE'],
        'LABEL_PROP_POSITION' => $arParams['LABEL_PROP_POSITION'],
        'ADD_PICT_PROP' => $arParams['ADD_PICT_PROP'],
        'PRODUCT_DISPLAY_MODE' => $arParams['PRODUCT_DISPLAY_MODE'],
        'PRODUCT_BLOCKS_ORDER' => $arParams['LIST_PRODUCT_BLOCKS_ORDER'],
        'PRODUCT_ROW_VARIANTS' => $arParams['LIST_PRODUCT_ROW_VARIANTS'],
        'ENLARGE_PRODUCT' => $arParams['LIST_ENLARGE_PRODUCT'],
        'ENLARGE_PROP' => isset($arParams['LIST_ENLARGE_PROP']) ? $arParams['LIST_ENLARGE_PROP'] : '',
        'SHOW_SLIDER' => $arParams['LIST_SHOW_SLIDER'],
        'SLIDER_INTERVAL' => isset($arParams['LIST_SLIDER_INTERVAL']) ? $arParams['LIST_SLIDER_INTERVAL'] : '',
        'SLIDER_PROGRESS' => isset($arParams['LIST_SLIDER_PROGRESS']) ? $arParams['LIST_SLIDER_PROGRESS'] : '',

        'OFFER_ADD_PICT_PROP' => $arParams['OFFER_ADD_PICT_PROP'],
        'OFFER_TREE_PROPS' => (isset($arParams['OFFER_TREE_PROPS']) ? $arParams['OFFER_TREE_PROPS'] : []),
        'PRODUCT_SUBSCRIPTION' => $arParams['PRODUCT_SUBSCRIPTION'],
        'SHOW_DISCOUNT_PERCENT' => $arParams['SHOW_DISCOUNT_PERCENT'],
        'DISCOUNT_PERCENT_POSITION' => $arParams['DISCOUNT_PERCENT_POSITION'],
        'SHOW_OLD_PRICE' => $arParams['SHOW_OLD_PRICE'],
        'SHOW_MAX_QUANTITY' => $arParams['SHOW_MAX_QUANTITY'],
        'MESS_SHOW_MAX_QUANTITY' => (isset($arParams['~MESS_SHOW_MAX_QUANTITY']) ? $arParams['~MESS_SHOW_MAX_QUANTITY'] : ''),
        'RELATIVE_QUANTITY_FACTOR' => (isset($arParams['RELATIVE_QUANTITY_FACTOR']) ? $arParams['RELATIVE_QUANTITY_FACTOR'] : ''),
        'MESS_RELATIVE_QUANTITY_MANY' => (isset($arParams['~MESS_RELATIVE_QUANTITY_MANY']) ? $arParams['~MESS_RELATIVE_QUANTITY_MANY'] : ''),
        'MESS_RELATIVE_QUANTITY_FEW' => (isset($arParams['~MESS_RELATIVE_QUANTITY_FEW']) ? $arParams['~MESS_RELATIVE_QUANTITY_FEW'] : ''),
        'MESS_BTN_BUY' => (isset($arParams['~MESS_BTN_BUY']) ? $arParams['~MESS_BTN_BUY'] : ''),
        'MESS_BTN_ADD_TO_BASKET' => (isset($arParams['~MESS_BTN_ADD_TO_BASKET']) ? $arParams['~MESS_BTN_ADD_TO_BASKET'] : ''),
        'MESS_BTN_SUBSCRIBE' => (isset($arParams['~MESS_BTN_SUBSCRIBE']) ? $arParams['~MESS_BTN_SUBSCRIBE'] : ''),
        'MESS_BTN_DETAIL' => (isset($arParams['~MESS_BTN_DETAIL']) ? $arParams['~MESS_BTN_DETAIL'] : ''),
        'MESS_NOT_AVAILABLE' => $arParams['~MESS_NOT_AVAILABLE'] ?? '',
        'MESS_NOT_AVAILABLE_SERVICE' => $arParams['~MESS_NOT_AVAILABLE_SERVICE'] ?? '',
        'MESS_BTN_COMPARE' => (isset($arParams['~MESS_BTN_COMPARE']) ? $arParams['~MESS_BTN_COMPARE'] : ''),

        'USE_ENHANCED_ECOMMERCE' => (isset($arParams['USE_ENHANCED_ECOMMERCE']) ? $arParams['USE_ENHANCED_ECOMMERCE'] : ''),
        'DATA_LAYER_NAME' => (isset($arParams['DATA_LAYER_NAME']) ? $arParams['DATA_LAYER_NAME'] : ''),
        'BRAND_PROPERTY' => (isset($arParams['BRAND_PROPERTY']) ? $arParams['BRAND_PROPERTY'] : ''),

        'TEMPLATE_THEME' => (isset($arParams['TEMPLATE_THEME']) ? $arParams['TEMPLATE_THEME'] : ''),
        "ADD_SECTIONS_CHAIN" => "N",
        'ADD_TO_BASKET_ACTION' => $basketAction,
        'SHOW_CLOSE_POPUP' => isset($arParams['COMMON_SHOW_CLOSE_POPUP']) ? $arParams['COMMON_SHOW_CLOSE_POPUP'] : '',
        'COMPARE_PATH' => $arResult['FOLDER'] . $arResult['URL_TEMPLATES']['compare'],
        'COMPARE_NAME' => $arParams['COMPARE_NAME'],
        'USE_COMPARE_LIST' => 'Y',
        'BACKGROUND_IMAGE' => (isset($arParams['SECTION_BACKGROUND_IMAGE']) ? $arParams['SECTION_BACKGROUND_IMAGE'] : ''),
        'COMPATIBLE_MODE' => (isset($arParams['COMPATIBLE_MODE']) ? $arParams['COMPATIBLE_MODE'] : ''),
        'DISABLE_INIT_JS_IN_COMPONENT' => (isset($arParams['DISABLE_INIT_JS_IN_COMPONENT']) ? $arParams['DISABLE_INIT_JS_IN_COMPONENT'] : '')
    ),
    $component
);
$GLOBALS['CATALOG_CURRENT_SECTION_ID'] = $intSectionID;


unset($basketAction);
