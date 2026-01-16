<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/**
 * @var CBitrixComponentTemplate $this
 * @var CatalogSectionComponent $component
 */

$component = $this->getComponent();
$arParams = $component->applyTemplateModifications();

// Отладка: проверяем параметры фильтра
$filterName = $arParams["FILTER_NAME"] ?? "arrFilter";
//pre($arParams, "Catalog section params");
//pre($_GET, "GET in result_modifier");
if (isset($GLOBALS[$filterName]))
{
    //pre($GLOBALS[$filterName], "Filter in result_modifier");
}
// Проверяем что получил компонент
if (isset($arResult["ELEMENT_CNT"]))
{
    //pre($arResult["ELEMENT_CNT"], "Elements count");
}
//pre($arResult["ITEMS"], "Items in result");

// Фильтрация по финальной цене с учетом персональных скидок
if (isset($_GET['f8']) && $_GET['f8'] !== '' && isset($arResult["ITEMS"]) && is_array($arResult["ITEMS"]))
{
    $priceFilter = $_GET['f8'];
    if (preg_match('/^minmax~(\d+(?:\.\d+)?),(\d+(?:\.\d+)?)$/', $priceFilter, $matches))
    {
        $minPrice = floatval($matches[1]);
        $maxPrice = floatval($matches[2]);
        
        CModule::IncludeModule("catalog");
        CModule::IncludeModule("sale");
        
        $priceCode = $arParams["~PRICE_CODE"] ?? [];
        if (empty($priceCode) && is_array($arParams["PRICE_CODE"]))
        {
            $priceCode = $arParams["PRICE_CODE"];
        }
        
        $filteredItems = array();
        foreach ($arResult["ITEMS"] as $item)
        {
            // Получаем финальную цену с учетом скидок
            $finalPrice = null;
            
            if (isset($item["PRICES"]) && is_array($item["PRICES"]))
            {
                // Используем первую доступную цену
                foreach ($item["PRICES"] as $priceKey => $priceData)
                {
                    if (isset($priceData["PRICE_VALUE"]))
                    {
                        $finalPrice = floatval($priceData["PRICE_VALUE"]);
                        break;
                    }
                }
            }
            
            // Если цена не найдена в PRICES, пытаемся получить через CCatalogProduct
            if ($finalPrice === null && isset($item["ID"]))
            {
                $arPrice = CCatalogProduct::GetOptimalPrice(
                    $item["ID"],
                    1,
                    array(), // группа пользователя
                    'N', // только базовая цена
                    array(), // параметры
                    SITE_ID,
                    array() // цены
                );
                
                if (isset($arPrice["PRICE"]["PRICE"]))
                {
                    $finalPrice = floatval($arPrice["PRICE"]["PRICE"]);
                }
            }
            
            // Фильтруем по финальной цене
            if ($finalPrice !== null && $finalPrice >= $minPrice && $finalPrice <= $maxPrice)
            {
                $filteredItems[] = $item;
            }
        }
        
        $arResult["ITEMS"] = $filteredItems;
        $arResult["ELEMENT_CNT"] = count($filteredItems);
    }
}

// Вычисляем минимальную и максимальную цены из текущей выборки для фильтра
if (isset($arResult["ITEMS"]) && is_array($arResult["ITEMS"]) && !empty($arResult["ITEMS"]))
{
    CModule::IncludeModule("catalog");
    CModule::IncludeModule("sale");
    
    $minPrice = null;
    $maxPrice = null;
    
    foreach ($arResult["ITEMS"] as $item)
    {
        $finalPrice = null;
        
        // Получаем финальную цену с учетом скидок
        if (isset($item["PRICES"]) && is_array($item["PRICES"]))
        {
            foreach ($item["PRICES"] as $priceKey => $priceData)
            {
                if (isset($priceData["PRICE_VALUE"]))
                {
                    $finalPrice = floatval($priceData["PRICE_VALUE"]);
                    break;
                }
            }
        }
        
        if ($finalPrice === null && isset($item["ID"]))
        {
            $arPrice = CCatalogProduct::GetOptimalPrice(
                $item["ID"],
                1,
                array(),
                'N',
                array(),
                SITE_ID,
                array()
            );
            
            if (isset($arPrice["PRICE"]["PRICE"]))
            {
                $finalPrice = floatval($arPrice["PRICE"]["PRICE"]);
            }
        }
        
        if ($finalPrice !== null)
        {
            if ($minPrice === null || $finalPrice < $minPrice)
            {
                $minPrice = $finalPrice;
            }
            if ($maxPrice === null || $finalPrice > $maxPrice)
            {
                $maxPrice = $finalPrice;
            }
        }
    }
    
    // Сохраняем значения в глобальной переменной для использования в шаблоне фильтра
    if ($minPrice !== null && $maxPrice !== null)
    {
        $GLOBALS["CATALOG_PRICE_RANGE"] = array(
            "MIN" => floor($minPrice),
            "MAX" => ceil($maxPrice)
        );
    }
}