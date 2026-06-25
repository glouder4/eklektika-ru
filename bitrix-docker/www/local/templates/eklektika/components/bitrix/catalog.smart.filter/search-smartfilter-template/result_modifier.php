<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Iblock\SectionPropertyTable;

if (isset($arParams["TEMPLATE_THEME"]) && !empty($arParams["TEMPLATE_THEME"]))
{
	$arAvailableThemes = array();
	$dir = trim(preg_replace("'[\\\\/]+'", "/", __DIR__."/themes/"));
	if (is_dir($dir) && $directory = opendir($dir))
	{
		while (($file = readdir($directory)) !== false)
		{
			if ($file != "." && $file != ".." && is_dir($dir.$file))
				$arAvailableThemes[] = $file;
		}
		closedir($directory);
	}

	if ($arParams["TEMPLATE_THEME"] == "site")
	{
		$solution = COption::GetOptionString("main", "wizard_solution", "", SITE_ID);
		if ($solution == "eshop")
		{
			$templateId = COption::GetOptionString("main", "wizard_template_id", "eshop_bootstrap", SITE_ID);
			$templateId = (preg_match("/^eshop_adapt/", $templateId)) ? "eshop_adapt" : $templateId;
			$theme = COption::GetOptionString("main", "wizard_".$templateId."_theme_id", "blue", SITE_ID);
			$arParams["TEMPLATE_THEME"] = (in_array($theme, $arAvailableThemes)) ? $theme : "blue";
		}
	}
	else
	{
		$arParams["TEMPLATE_THEME"] = (in_array($arParams["TEMPLATE_THEME"], $arAvailableThemes)) ? $arParams["TEMPLATE_THEME"] : "blue";
	}
}
else
{
	$arParams["TEMPLATE_THEME"] = "blue";
}

$arParams["FILTER_VIEW_MODE"] = (isset($arParams["FILTER_VIEW_MODE"]) && mb_strtoupper($arParams["FILTER_VIEW_MODE"]) == "HORIZONTAL") ? "HORIZONTAL" : "VERTICAL";
$arParams["POPUP_POSITION"] = (isset($arParams["POPUP_POSITION"]) && in_array($arParams["POPUP_POSITION"], array("left", "right"))) ? $arParams["POPUP_POSITION"] : "left";

// Проверка наличия хотя бы одного доступного фильтра
$arResult["HAS_AVAILABLE_FILTERS"] = false;

if (isset($arResult["ITEMS"]) && is_array($arResult["ITEMS"])) {
    foreach ($arResult["ITEMS"] as $key => $arItem) {
        // Пропускаем элементы без значений
        if (empty($arItem["VALUES"])) {
            continue;
        }

        // Проверка для цен
        if (isset($arItem["PRICE"])) {
            if (isset($arItem["VALUES"]["MAX"]["VALUE"]) && isset($arItem["VALUES"]["MIN"]["VALUE"])) {
                if ($arItem["VALUES"]["MAX"]["VALUE"] - $arItem["VALUES"]["MIN"]["VALUE"] > 0) {
                    $arResult["HAS_AVAILABLE_FILTERS"] = true;
                    break;
                }
            }
            continue;
        }

        // Проверка для NUMBERS_WITH_SLIDER
        if (
            isset($arItem["DISPLAY_TYPE"]) 
            && $arItem["DISPLAY_TYPE"] === SectionPropertyTable::NUMBERS_WITH_SLIDER
        ) {
            if (isset($arItem["VALUES"]["MAX"]["VALUE"]) && isset($arItem["VALUES"]["MIN"]["VALUE"])) {
                if ($arItem["VALUES"]["MAX"]["VALUE"] - $arItem["VALUES"]["MIN"]["VALUE"] > 0) {
                    $arResult["HAS_AVAILABLE_FILTERS"] = true;
                    break;
                }
            }
            continue;
        }

        // Проверка для остальных типов фильтров - есть ли хотя бы одно доступное значение
        $haveAvailableElements = (bool)array_filter($arItem["VALUES"], function($ar) { 
            return isset($ar["DISABLED"]) && $ar["DISABLED"] != 1; 
        });

        if ($haveAvailableElements) {
            $arResult["HAS_AVAILABLE_FILTERS"] = true;
            break;
        }
    }
}

$filterName = $arParams["FILTER_NAME"] ?? "arrFilter";
$brandPropertyCode = "BRENDY_DLYA_WEB";
$stockFilterName = $filterName . "_stock";
$brandFilterName = $filterName . "_brand";

$arResult["STOCK_FILTER"] = array(
    "MIN" => 0,
    "MAX" => 0,
    "CURRENT" => "",
    "CONTROL_NAME" => $stockFilterName,
    "SHOW" => false,
);
$arResult["BRAND_FILTER"] = array(
    "PROPERTY_CODE" => $brandPropertyCode,
    "CONTROL_NAME" => $brandFilterName,
    "VALUES" => array(),
    "CURRENT" => "",
    "SHOW" => false,
);

if (isset($_GET[$stockFilterName]) && $_GET[$stockFilterName] !== "") {
    $arResult["STOCK_FILTER"]["CURRENT"] = (int)$_GET[$stockFilterName];
}

if (isset($_GET[$brandFilterName]) && $_GET[$brandFilterName] !== "") {
    $arResult["BRAND_FILTER"]["CURRENT"] = trim((string)$_GET[$brandFilterName]);
}

if (\Bitrix\Main\Loader::includeModule("catalog") && \Bitrix\Main\Loader::includeModule("iblock")) {
    $iblockId = (int)($arParams["IBLOCK_ID"] ?? 0);

    if ($iblockId > 0) {
        $elementFilter = array(
            "IBLOCK_ID" => $iblockId,
            "ACTIVE" => "Y",
            "ACTIVE_DATE" => "Y",
        );

        $sectionId = (int)($arParams["SECTION_ID"] ?? 0);
        if ($sectionId > 0) {
            $elementFilter["SECTION_ID"] = $sectionId;
            $elementFilter["INCLUDE_SUBSECTIONS"] = "Y";
        }

        $prefilterName = $arParams["PREFILTER_NAME"] ?? "";
        if ($prefilterName !== "" && isset($GLOBALS[$prefilterName]) && is_array($GLOBALS[$prefilterName])) {
            $elementFilter = array_merge($elementFilter, $GLOBALS[$prefilterName]);
        }

        $brandElementFilter = $elementFilter;
        $brandElementFilter["!PROPERTY_" . $brandPropertyCode] = false;

        $brandValues = array();
        $rsBrands = CIBlockElement::GetList(
            array("PROPERTY_" . $brandPropertyCode => "ASC"),
            $brandElementFilter,
            array("PROPERTY_" . $brandPropertyCode),
            false,
            array("PROPERTY_" . $brandPropertyCode)
        );

        while ($brandRow = $rsBrands->Fetch()) {
            $brandValue = trim((string)($brandRow["PROPERTY_" . $brandPropertyCode . "_VALUE"] ?? ""));
            if ($brandValue !== "") {
                $brandValues[] = $brandValue;
            }
        }

        $brandValues = array_values(array_unique($brandValues));
        sort($brandValues, SORT_STRING | SORT_FLAG_CASE);

        if (!empty($brandValues)) {
            $arResult["BRAND_FILTER"]["VALUES"] = $brandValues;
            $arResult["BRAND_FILTER"]["SHOW"] = true;

            if (
                $arResult["BRAND_FILTER"]["CURRENT"] !== ""
                && !in_array($arResult["BRAND_FILTER"]["CURRENT"], $brandValues, true)
            ) {
                $arResult["BRAND_FILTER"]["CURRENT"] = "";
            }

            if (!$arResult["HAS_AVAILABLE_FILTERS"]) {
                $arResult["HAS_AVAILABLE_FILTERS"] = true;
            }
        }

        $productIds = array();
        $rsElements = CIBlockElement::GetList(array(), $elementFilter, false, false, array("ID"));
        while ($element = $rsElements->Fetch()) {
            $productIds[] = (int)$element["ID"];
        }

        if (!empty($productIds)) {
            $catalogProductIds = $productIds;
            $skuInfo = CCatalogSKU::GetInfoByProductIBlock($iblockId);

            if (!empty($skuInfo["IBLOCK_ID"])) {
                $offerFilter = array(
                    "IBLOCK_ID" => (int)$skuInfo["IBLOCK_ID"],
                    "ACTIVE" => "Y",
                    "ACTIVE_DATE" => "Y",
                    "PROPERTY_" . $skuInfo["SKU_PROPERTY_ID"] => $productIds,
                );

                $rsOffers = CIBlockElement::GetList(array(), $offerFilter, false, false, array("ID"));
                while ($offer = $rsOffers->Fetch()) {
                    $catalogProductIds[] = (int)$offer["ID"];
                }
            }

            $catalogProductIds = array_values(array_unique($catalogProductIds));
            $maxQuantity = 0.0;

            $quantityList = \Bitrix\Catalog\ProductTable::getList(array(
                "select" => array("QUANTITY"),
                "filter" => array("@ID" => $catalogProductIds),
            ));

            while ($quantityRow = $quantityList->fetch()) {
                $quantity = (float)$quantityRow["QUANTITY"];
                if ($quantity > $maxQuantity) {
                    $maxQuantity = $quantity;
                }
            }

            if ($maxQuantity > 0) {
                $arResult["STOCK_FILTER"]["MAX"] = (int)floor($maxQuantity);
                $arResult["STOCK_FILTER"]["SHOW"] = true;

                if (!$arResult["HAS_AVAILABLE_FILTERS"]) {
                    $arResult["HAS_AVAILABLE_FILTERS"] = true;
                }
            }
        }
    }
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/catalog_list_item_properties.php';
$GLOBALS['CATALOG_PRODUCT_IBLOCK_ID'] = (int)($arParams['IBLOCK_ID'] ?? 0);
catalogListSetActiveColorFilterNeedles(
    catalogListCollectActiveColorNeedlesFromSmartFilterItems(
        (array)($arResult['ITEMS'] ?? []),
        (string)($arParams['SMART_FILTER_PATH'] ?? $GLOBALS['CATALOG_SMART_FILTER_PATH'] ?? '')
    )
);

