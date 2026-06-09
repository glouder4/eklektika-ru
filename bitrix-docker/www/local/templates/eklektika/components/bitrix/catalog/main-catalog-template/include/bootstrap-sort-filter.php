<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
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
$stockFilterName = $filterName . "_stock";
$brandFilterName = $filterName . "_brand";
$brandPropertyCode = "BRENDY_DLYA_WEB";

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

    // Обрабатываем фильтр по бренду
    if (isset($_GET[$brandFilterName]) && $_GET[$brandFilterName] !== '') {
        $brandValue = trim((string)$_GET[$brandFilterName]);
        if ($brandValue !== '') {
            $filterArray["=PROPERTY_" . $brandPropertyCode] = $brandValue;
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
        $GLOBALS[$filterName] = $filterArray;
    }
}
