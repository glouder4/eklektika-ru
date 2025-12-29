<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/**
 * @var CBitrixComponentTemplate $this
 * @var CatalogSectionComponent $component
 */

$component = $this->getComponent();
$arParams = $component->applyTemplateModifications();

// Отладка: проверяем параметры фильтра
$filterName = $arParams["FILTER_NAME"] ?? "arrFilter";
pre($arParams, "Catalog section params");
pre($_GET, "GET in result_modifier");
if (isset($GLOBALS[$filterName]))
{
    pre($GLOBALS[$filterName], "Filter in result_modifier");
}
// Проверяем что получил компонент
if (isset($arResult["ELEMENT_CNT"]))
{
    pre($arResult["ELEMENT_CNT"], "Elements count");
}
pre($arResult["ITEMS"], "Items in result");