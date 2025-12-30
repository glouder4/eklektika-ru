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


