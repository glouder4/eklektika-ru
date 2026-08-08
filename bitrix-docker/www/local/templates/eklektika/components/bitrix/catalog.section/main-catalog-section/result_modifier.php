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

if (!empty($arResult['ITEMS']) && is_array($arResult['ITEMS'])) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/catalog_list_item_properties.php';
    catalogListEnrichItemsBrandProperty(
        $arResult['ITEMS'],
        (int)($arParams['IBLOCK_ID'] ?? 0)
    );

    $filterName = $arParams['FILTER_NAME'] ?? 'arrFilter';
    $itemsCountBeforeFilters = count($arResult['ITEMS']);
    $sampleItemsBefore = array_map(static function (array $item): array {
        return [
            'ID' => (int)($item['ID'] ?? 0),
            'NAME' => (string)($item['NAME'] ?? ''),
            'IBLOCK_SECTION_ID' => (int)($item['IBLOCK_SECTION_ID'] ?? 0),
        ];
    }, array_slice($arResult['ITEMS'], 0, 5));

    $brandFilterName = $filterName . '_brand';
    $activeBrand = trim((string)($_GET[$brandFilterName] ?? ''));
    if ($activeBrand !== '') {
        $arResult['ITEMS'] = catalogListFilterItemsByActiveBrand(
            $arResult['ITEMS'],
            $activeBrand,
            (int)($arParams['IBLOCK_ID'] ?? 0)
        );
        $arResult['ELEMENT_CNT'] = count($arResult['ITEMS']);
    }

    $itemsCountAfterBrand = count($arResult['ITEMS']);

    require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/brand_catalog.php';
    $activeSectionId = brandCatalogResolveActiveSectionFilterId($filterName);
    $componentSectionId = (int)($arParams['SECTION_ID'] ?? 0);
    $request = \Bitrix\Main\Context::getCurrent()->getRequest();
    $globalFilter = $GLOBALS[$filterName] ?? [];
    $restrictedProductIds = brandCatalogGetGlobalFilterRestrictedProductIds($globalFilter);
    $sectionAppliedViaIds = $restrictedProductIds !== null;

    $itemsCountBeforeSection = count($arResult['ITEMS']);
    if ($sectionAppliedViaIds) {
        $allowedMap = array_fill_keys($restrictedProductIds ?? [], true);
        $arResult['ITEMS'] = array_values(array_filter(
            $arResult['ITEMS'],
            static function (array $item) use ($allowedMap): bool {
                return isset($allowedMap[(int)($item['ID'] ?? 0)]);
            }
        ));
        $arResult['ELEMENT_CNT'] = count($restrictedProductIds ?? []);
    } elseif ($activeSectionId > 0) {
        $arResult['ITEMS'] = brandCatalogFilterItemsByActiveSection(
            $arResult['ITEMS'],
            $activeSectionId,
            (int)($arParams['IBLOCK_ID'] ?? 0)
        );
        $arResult['ELEMENT_CNT'] = count($arResult['ITEMS']);
    }

    $shouldPostFilterSection = $activeSectionId > 0 && !$sectionAppliedViaIds;

    brandCatalogDebug('catalog_section_result', [
        'filterName' => $filterName,
        'GLOBALS_filter' => $GLOBALS[$filterName] ?? null,
        'GLOBALS_id_count' => ($debugRestrictedIds = brandCatalogGetGlobalFilterRestrictedProductIds($GLOBALS[$filterName] ?? [])) !== null
            ? count($debugRestrictedIds)
            : null,
        'GET_section' => brandCatalogGetSelectedSectionFilterIds($filterName),
        'activeSectionId' => $activeSectionId,
        'componentSectionId' => $componentSectionId,
        'SHOW_ALL_WO_SECTION' => $arParams['SHOW_ALL_WO_SECTION'] ?? null,
        'BY_LINK' => $arParams['BY_LINK'] ?? null,
        'isAjax' => $request->isAjaxRequest(),
        'sectionAppliedViaIds' => $sectionAppliedViaIds,
        'restrictedProductIds_count' => $restrictedProductIds !== null ? count($restrictedProductIds) : null,
        'shouldPostFilterSection' => $shouldPostFilterSection,
        'counts' => [
            'before_filters' => $itemsCountBeforeFilters,
            'after_brand' => $itemsCountAfterBrand,
            'before_section_post' => $itemsCountBeforeSection,
            'final' => count($arResult['ITEMS']),
            'ELEMENT_CNT' => $arResult['ELEMENT_CNT'] ?? null,
        ],
        'sample_before_filters' => $sampleItemsBefore,
        'sample_final' => array_map(static function (array $item): array {
            return [
                'ID' => (int)($item['ID'] ?? 0),
                'NAME' => (string)($item['NAME'] ?? ''),
                'IBLOCK_SECTION_ID' => (int)($item['IBLOCK_SECTION_ID'] ?? 0),
            ];
        }, array_slice($arResult['ITEMS'], 0, 5)),
        'subtree_ids_count' => $activeSectionId > 0
            ? count(brandCatalogGetSectionSubtreeIds((int)($arParams['IBLOCK_ID'] ?? 0), $activeSectionId))
            : 0,
    ]);

    catalogListReorderItemsOffersForActiveColorFilter($arResult['ITEMS']);
}

// Описание раздела: гарантированно подтягиваем DESCRIPTION из БД
// (иначе при cache hit / урезанном select поле пропадает).
$sectionId = (int)($arResult['ID'] ?? 0);
$iblockId = (int)($arParams['IBLOCK_ID'] ?? 0);
if ($sectionId > 0 && $iblockId > 0 && \Bitrix\Main\Loader::includeModule('iblock')) {
    $sectionRow = CIBlockSection::GetList(
        [],
        ['ID' => $sectionId, 'IBLOCK_ID' => $iblockId],
        false,
        ['ID', 'DESCRIPTION', 'DESCRIPTION_TYPE']
    )->GetNext();

    if (is_array($sectionRow)) {
        $arResult['DESCRIPTION'] = (string)($sectionRow['DESCRIPTION'] ?? '');
        $arResult['~DESCRIPTION'] = (string)($sectionRow['~DESCRIPTION'] ?? $sectionRow['DESCRIPTION'] ?? '');
        $arResult['DESCRIPTION_TYPE'] = (string)($sectionRow['DESCRIPTION_TYPE'] ?? '');
    }
}
