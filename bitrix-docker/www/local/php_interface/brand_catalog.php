<?php

if (!defined('BRAND_CATALOG_IBLOCK_ID')) {
    define('BRAND_CATALOG_IBLOCK_ID', 20);
}
if (!defined('BRAND_CATALOG_IBLOCK_TYPE')) {
    define('BRAND_CATALOG_IBLOCK_TYPE', 'sliders');
}
if (!defined('BRAND_CATALOG_PROPERTY_BRENDY')) {
    define('BRAND_CATALOG_PROPERTY_BRENDY', 'BRENDY_DLYA_WEB');
}
if (!defined('BRAND_CATALOG_PROPERTY_PAGE_TITLE')) {
    define('BRAND_CATALOG_PROPERTY_PAGE_TITLE', 'PAGE_TITLE');
}
if (!defined('BRAND_CATALOG_PROPERTY_META_DESCRIPTION')) {
    define('BRAND_CATALOG_PROPERTY_META_DESCRIPTION', 'META_DESCRIPTION');
}
if (!defined('BRAND_CATALOG_PROPERTY_SEO_TOP')) {
    define('BRAND_CATALOG_PROPERTY_SEO_TOP', 'SEO_DESCRIPTION_TOP');
}
if (!defined('BRAND_CATALOG_PROPERTY_UPPER_DESCRIPTION')) {
    define('BRAND_CATALOG_PROPERTY_UPPER_DESCRIPTION', 'UPPER_DESCRIPTION');
}
if (!defined('BRAND_CATALOG_PROPERTY_SEO_BOTTOM')) {
    define('BRAND_CATALOG_PROPERTY_SEO_BOTTOM', 'SEO_DESCRIPTION_BOTTOM');
}

/**
 * Коды свойств элемента инфоблока брендов (iblock 20).
 * Заполните в админке Bitrix; при отсутствии — fallback из brand_catalog_map.php.
 */
function brandCatalogGetIblockId(): int
{
    return (int)BRAND_CATALOG_IBLOCK_ID;
}

function brandCatalogGetIblockType(): string
{
    return (string)BRAND_CATALOG_IBLOCK_TYPE;
}

function brandCatalogGetBrendyPropertyCode(): string
{
    return (string)BRAND_CATALOG_PROPERTY_BRENDY;
}

function brandCatalogGetPageFolder(string $slug): string
{
    $slug = trim($slug, '/');
    if ($slug === '') {
        return '/';
    }

    return '/' . $slug . '/';
}

/**
 * Есть ли у элемента картинка (поля ИБ или массив из компонента).
 *
 * @param array<string, mixed> $data
 */
function brandCatalogElementHasPicture(array $data): bool
{
    foreach (['PREVIEW_PICTURE', 'DETAIL_PICTURE'] as $key) {
        if (!\array_key_exists($key, $data)) {
            continue;
        }
        $picture = $data[$key];
        if (\is_array($picture)) {
            if ((int) ($picture['ID'] ?? 0) > 0) {
                return true;
            }
            if (\trim((string) ($picture['SRC'] ?? '')) !== '') {
                return true;
            }
            continue;
        }
        if ((int) $picture > 0) {
            return true;
        }
    }

    return false;
}

/**
 * @param array<string, mixed> $item строка bitrix:news.list
 */
function brandCatalogListItemHasPicture(array $item): bool
{
    return brandCatalogElementHasPicture($item);
}

/**
 * URL карточки бренда: свойство LINK или /{CODE}/.
 *
 * @param array<string, mixed> $item
 */
function brandCatalogListItemResolveDetailUrl(array $item, string $slug): string
{
    $link = brandCatalogGetPropertyValue((array) ($item['PROPERTIES'] ?? []), 'LINK');
    if ($link !== '') {
        return $link[0] === '/' ? $link : '/' . \ltrim($link, '/');
    }

    return brandCatalogGetPageFolder($slug);
}

/**
 * Slug'и брендов для списка /brendy/ и urlrewrite: активные, с CODE и картинкой.
 *
 * @return list<string>
 */
function brandCatalogGetEligibleBrandSlugs(): array
{
    static $slugs = null;
    if ($slugs !== null) {
        return $slugs;
    }

    $slugs = [];
    if (\Bitrix\Main\Loader::includeModule('iblock')) {
        $rsElements = \CIBlockElement::GetList(
            ['SORT' => 'ASC', 'NAME' => 'ASC'],
            [
                'IBLOCK_ID' => brandCatalogGetIblockId(),
                'ACTIVE' => 'Y',
                'ACTIVE_DATE' => 'Y',
                '!CODE' => false,
            ],
            false,
            false,
            ['ID', 'CODE', 'PREVIEW_PICTURE', 'DETAIL_PICTURE']
        );

        while ($row = $rsElements->Fetch()) {
            if (!\is_array($row)) {
                continue;
            }
            $code = \trim((string) ($row['CODE'] ?? ''));
            if ($code === '' || !brandCatalogElementHasPicture($row)) {
                continue;
            }
            $slugs[$code] = $code;
        }
    }

    foreach (\array_keys(brandCatalogGetMapFile()) as $mapSlug) {
        $mapSlug = \trim((string) $mapSlug);
        if ($mapSlug !== '') {
            $slugs[$mapSlug] = $mapSlug;
        }
    }

    $slugs = \array_values($slugs);

    return $slugs;
}

/**
 * @return array<string, array<string, mixed>>
 */
function brandCatalogGetMapFile(): array
{
    static $map = null;
    if ($map === null) {
        $path = __DIR__ . '/brand_catalog_map.php';
        $map = is_file($path) ? require $path : [];
        if (!is_array($map)) {
            $map = [];
        }
    }

    return $map;
}

function brandCatalogGetPropertyValue(array $properties, string $code): string
{
    if ($code === '' || !isset($properties[$code])) {
        return '';
    }

    $property = $properties[$code];
    $value = $property['~VALUE'] ?? ($property['VALUE'] ?? '');
    if (is_array($value)) {
        if (array_key_exists('TEXT', $value) || array_key_exists('~TEXT', $value)) {
            return trim((string)($value['~TEXT'] ?? $value['TEXT'] ?? ''));
        }

        $value = implode(', ', array_filter(array_map('strval', $value)));
    }

    return trim((string)$value);
}

/**
 * @param array<string, mixed> $fields
 * @param array<string, mixed> $properties
 * @param array<string, mixed>|null $mapFallback
 * @return array<string, mixed>|null
 */
function brandCatalogNormalizeElementConfig(array $fields, array $properties, ?array $mapFallback = null): ?array
{
    $slug = trim((string)($fields['CODE'] ?? ''));
    if ($slug === '') {
        return null;
    }

    $brandValue = brandCatalogGetPropertyValue($properties, BRAND_CATALOG_PROPERTY_BRENDY);
    if ($brandValue === '' && $mapFallback !== null) {
        $brandValue = trim((string)($mapFallback['BRENDY_DLYA_WEB'] ?? ''));
    }
    if ($brandValue === '') {
        return null;
    }

    $title = trim((string)($fields['NAME'] ?? ''));
    if ($title === '' && $mapFallback !== null) {
        $title = trim((string)($mapFallback['TITLE'] ?? $slug));
    }

    $pageTitle = brandCatalogGetPropertyValue($properties, BRAND_CATALOG_PROPERTY_PAGE_TITLE);
    if ($pageTitle === '' && $mapFallback !== null) {
        $pageTitle = trim((string)($mapFallback['PAGE_TITLE'] ?? ''));
    }

    $description = brandCatalogGetPropertyValue($properties, BRAND_CATALOG_PROPERTY_META_DESCRIPTION);
    if ($description === '' && $mapFallback !== null) {
        $description = trim((string)($mapFallback['DESCRIPTION'] ?? ''));
    }

    $seoTop = brandCatalogGetPropertyValue($properties, BRAND_CATALOG_PROPERTY_UPPER_DESCRIPTION);
    if ($seoTop === '' && $mapFallback !== null) {
        $seoTop = trim((string)($mapFallback['UPPER_DESCRIPTION'] ?? ''));
    }
    if ($seoTop === '') {
        $seoTop = brandCatalogGetPropertyValue($properties, BRAND_CATALOG_PROPERTY_SEO_TOP);
    }
    if ($seoTop === '' && $mapFallback !== null) {
        $seoTop = trim((string)($mapFallback['SEO_DESCRIPTION_TOP'] ?? ''));
    }
    if ($seoTop === '' && trim((string)($fields['PREVIEW_TEXT'] ?? $fields['~PREVIEW_TEXT'] ?? '')) !== '') {
        $seoTop = (string)($fields['~PREVIEW_TEXT'] ?? $fields['PREVIEW_TEXT']);
    }

    $detailText = trim((string)($fields['~DETAIL_TEXT'] ?? $fields['DETAIL_TEXT'] ?? ''));

    $seoBottom = brandCatalogGetPropertyValue($properties, BRAND_CATALOG_PROPERTY_SEO_BOTTOM);
    if ($seoBottom === '' && $mapFallback !== null) {
        $seoBottom = trim((string)($mapFallback['SEO_DESCRIPTION_BOTTOM'] ?? ''));
    }
    if ($seoBottom === '' && $detailText !== '') {
        $seoBottom = $detailText;
    }

    return [
        'SLUG' => $slug,
        'BRENDY_DLYA_WEB' => $brandValue,
        'TITLE' => $title,
        'PAGE_TITLE' => $pageTitle,
        'DESCRIPTION' => $description,
        'SEO_DESCRIPTION_TOP' => $seoTop,
        'UPPER_DESCRIPTION' => $seoTop,
        'SEO_DESCRIPTION_BOTTOM' => $seoBottom,
        'DETAIL_TEXT' => $detailText,
    ];
}

/**
 * @return array<string, array<string, mixed>>
 */
function getBrandCatalogMap(): array
{
    static $map = null;
    if ($map !== null) {
        return $map;
    }

    $map = [];
    $fileMap = brandCatalogGetMapFile();

    if (\Bitrix\Main\Loader::includeModule('iblock')) {
        $rsElements = \CIBlockElement::GetList(
            ['SORT' => 'ASC', 'NAME' => 'ASC'],
            [
                'IBLOCK_ID' => brandCatalogGetIblockId(),
                'ACTIVE' => 'Y',
                'ACTIVE_DATE' => 'Y',
            ],
            false,
            false,
            ['ID', 'IBLOCK_ID', 'NAME', 'CODE', 'DETAIL_TEXT', 'PREVIEW_TEXT']
        );

        while ($elementObject = $rsElements->GetNextElement()) {
            $fields = $elementObject->GetFields();
            $slug = trim((string)($fields['CODE'] ?? ''));
            if ($slug === '') {
                continue;
            }

            $config = brandCatalogNormalizeElementConfig(
                $fields,
                $elementObject->GetProperties(),
                $fileMap[$slug] ?? null
            );
            if ($config !== null) {
                $map[$slug] = $config;
            }
        }
    }

    if ($map === []) {
        $map = $fileMap;
    }

    return $map;
}

function getBrandCatalogConfig(string $slug): ?array
{
    $slug = trim($slug, '/');
    if ($slug === '') {
        return null;
    }

    static $cache = [];
    if (array_key_exists($slug, $cache)) {
        return $cache[$slug];
    }

    $fileMap = brandCatalogGetMapFile();
    $config = null;

    if (\Bitrix\Main\Loader::includeModule('iblock')) {
        $elementObject = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => brandCatalogGetIblockId(),
                '=CODE' => $slug,
                'ACTIVE' => 'Y',
                'ACTIVE_DATE' => 'Y',
            ],
            false,
            false,
            ['ID', 'IBLOCK_ID', 'NAME', 'CODE', 'DETAIL_TEXT', 'PREVIEW_TEXT']
        )->GetNextElement();

        if ($elementObject) {
            $config = brandCatalogNormalizeElementConfig(
                $elementObject->GetFields(),
                $elementObject->GetProperties(),
                $fileMap[$slug] ?? null
            );
        }
    }

    if ($config === null) {
        $config = $fileMap[$slug] ?? null;
    }

    $cache[$slug] = $config;

    return $config;
}

function applyBrandCatalogFilter(string $brandValue, string $filterName = 'arrFilter'): void
{
    require_once __DIR__ . '/catalog_list_item_properties.php';
    $brandFilter = catalogListBuildBrandPropertyFilter($brandValue);
    if ($brandFilter === []) {
        return;
    }

    $brandValue = trim($brandValue);
    $brandFilterName = $filterName . '_brand';
    $_GET[$brandFilterName] = $brandValue;
    $_REQUEST[$brandFilterName] = $brandValue;
    $_GET['set_filter'] = 'y';
    $_REQUEST['set_filter'] = 'y';

    if (!isset($GLOBALS[$filterName]) || !is_array($GLOBALS[$filterName])) {
        $GLOBALS[$filterName] = [];
    }

    $GLOBALS[$filterName] = array_merge($GLOBALS[$filterName], $brandFilter);
}

function brandCatalogIsDebugEnabled(): bool
{
    return isset($_GET['brand_catalog_debug']) && (string)$_GET['brand_catalog_debug'] === 'Y';
}

function brandCatalogDebug(string $label, mixed $data): void
{
    if (!brandCatalogIsDebugEnabled() || !function_exists('pre')) {
        return;
    }

    pre([
        'BLOCK' => $label,
        'URI' => $_SERVER['REQUEST_URI'] ?? '',
        'DATA' => $data,
    ]);
}

function brandCatalogGetSectionFilterControlNames(string $filterName = 'arrFilter'): array
{
    return [
        'parent' => $filterName . '_section_parent',
        'sub' => $filterName . '_section_sub',
    ];
}

function brandCatalogIsSectionFilterControlName(string $filterName, string $key): bool
{
    $controls = brandCatalogGetSectionFilterControlNames($filterName);

    return in_array($key, $controls, true);
}

function brandCatalogIsCustomFilterControlName(string $filterName, string $key): bool
{
    if (brandCatalogIsSectionFilterControlName($filterName, $key)) {
        return true;
    }

    if ($key === $filterName . '_brand' || $key === $filterName . '_stock') {
        return true;
    }

    return (bool)preg_match('/^' . preg_quote($filterName, '/') . '_P\d+_(MIN|MAX)$/', $key);
}

function brandCatalogApplySmartFilterPriceFromRequest(array &$filter, string $filterName): void
{
    foreach ($_GET as $key => $value) {
        if (!preg_match('/^' . preg_quote($filterName, '/') . '_P(\d+)_(MIN|MAX)$/', (string)$key, $matches)) {
            continue;
        }

        $priceTypeId = (int)$matches[1];
        if ($priceTypeId <= 0 || $value === '' || $value === null) {
            continue;
        }

        $priceValue = (float)$value;
        if ($matches[2] === 'MIN') {
            $filter['>=CATALOG_PRICE_' . $priceTypeId] = $priceValue;
        } else {
            $filter['<=CATALOG_PRICE_' . $priceTypeId] = $priceValue;
        }
    }
}

function brandCatalogSanitizeFilterArray(array $filter, string $filterName): array
{
    $sanitized = [];

    foreach ($filter as $key => $value) {
        $key = (string)$key;

        if (brandCatalogIsCustomFilterControlName($filterName, $key)) {
            continue;
        }

        if (in_array($key, ['>', '<', '>=', '<='], true)) {
            continue;
        }

        if (is_array($value) && !preg_match('/^(=PROPERTY_|!PROPERTY_|PROPERTY_|@?ID|SECTION_ID)/', $key)) {
            continue;
        }

        $sanitized[$key] = $value;
    }

    brandCatalogApplySmartFilterPriceFromRequest($sanitized, $filterName);

    return $sanitized;
}

function brandCatalogFinalizeGlobalFilter(string $filterName): void
{
    if (!isset($GLOBALS[$filterName]) || !is_array($GLOBALS[$filterName])) {
        $GLOBALS[$filterName] = [];
    }

    $GLOBALS[$filterName] = brandCatalogSanitizeFilterArray($GLOBALS[$filterName], $filterName);
}

/**
 * Базовый фильтр для построения списка категорий: только бренд/свойства, без цены и раздела.
 *
 * @param array<string, mixed> $prefilterSource
 * @return array<string, mixed>
 */
function brandCatalogGetCategoryBaseElementFilter(int $iblockId, array $prefilterSource): array
{
    $filter = [
        'IBLOCK_ID' => $iblockId,
        'ACTIVE' => 'Y',
        'ACTIVE_DATE' => 'Y',
    ];

    foreach ($prefilterSource as $key => $value) {
        $key = (string)$key;

        if (in_array($key, ['SECTION_ID', 'INCLUDE_SUBSECTIONS', 'IBLOCK_ID', 'ACTIVE', 'ACTIVE_DATE'], true)) {
            continue;
        }

        if (strpos($key, 'CATALOG_PRICE') !== false || strpos($key, 'CATALOG_QUANTITY') !== false) {
            continue;
        }

        if (in_array($key, ['>', '<', '>=', '<='], true) || is_array($value)) {
            continue;
        }

        if (preg_match('/^(=PROPERTY_|!PROPERTY_|PROPERTY_)/', $key)) {
            $filter[$key] = $value;
        }
    }

    return $filter;
}

/**
 * @param array<string, mixed> $elementFilter
 * @return list<array{ID: int, NAME: string}>
 */
function brandCatalogGetSubsectionsWithProductsForParent(
    int $iblockId,
    int $parentId,
    array $elementFilter
): array {
    if ($parentId <= 0 || $iblockId <= 0 || !\Bitrix\Main\Loader::includeModule('iblock')) {
        return [];
    }

    $subsections = [];
    $rsSections = \CIBlockSection::GetList(
        ['NAME' => 'ASC'],
        [
            'IBLOCK_ID' => $iblockId,
            'SECTION_ID' => $parentId,
            'ACTIVE' => 'Y',
            'GLOBAL_ACTIVE' => 'Y',
        ],
        false,
        ['ID', 'NAME']
    );

    while ($section = $rsSections->Fetch()) {
        $childId = (int)($section['ID'] ?? 0);
        if ($childId <= 0) {
            continue;
        }

        $countFilter = array_merge($elementFilter, [
            'SECTION_ID' => $childId,
            'INCLUDE_SUBSECTIONS' => 'Y',
        ]);

        $count = (int)\CIBlockElement::GetList([], $countFilter, []);
        if ($count <= 0) {
            continue;
        }

        $subsections[] = [
            'ID' => $childId,
            'NAME' => (string)($section['NAME'] ?? ''),
        ];
    }

    return $subsections;
}

function brandCatalogGetSelectedSectionFilterIds(string $filterName = 'arrFilter'): array
{
    $controls = brandCatalogGetSectionFilterControlNames($filterName);
    $parentId = isset($_REQUEST[$controls['parent']]) ? (int)$_REQUEST[$controls['parent']] : 0;
    $subId = isset($_REQUEST[$controls['sub']]) ? (int)$_REQUEST[$controls['sub']] : 0;

    if ($subId > 0 && $parentId <= 0) {
        $subId = 0;
    }

    return [
        'parent' => max(0, $parentId),
        'sub' => max(0, $subId),
    ];
}

function brandCatalogResolveActiveSectionFilterId(string $filterName = 'arrFilter'): int
{
    $selected = brandCatalogGetSelectedSectionFilterIds($filterName);

    return $selected['sub'] > 0 ? $selected['sub'] : $selected['parent'];
}

/**
 * @return array<string, mixed>|null
 */
function brandCatalogBuildSectionCatalogFilter(int $sectionId): ?array
{
    if ($sectionId <= 0 || !\Bitrix\Main\Loader::includeModule('iblock')) {
        return null;
    }

    $section = \CIBlockSection::GetList(
        [],
        [
            'ID' => $sectionId,
            'ACTIVE' => 'Y',
            'GLOBAL_ACTIVE' => 'Y',
        ],
        false,
        ['ID'],
        ['nTopCount' => 1]
    )->Fetch();

    if (!$section) {
        return null;
    }

    return [
        'SECTION_ID' => $sectionId,
        'INCLUDE_SUBSECTIONS' => 'Y',
    ];
}

function brandCatalogApplySectionFilterToArray(array $filterArray, string $filterName = 'arrFilter'): array
{
    $sectionId = brandCatalogResolveActiveSectionFilterId($filterName);
    $sectionFilter = brandCatalogBuildSectionCatalogFilter($sectionId);

    if ($sectionFilter === null) {
        return $filterArray;
    }

    return array_merge($filterArray, $sectionFilter);
}

/**
 * @return list<int>
 */
function brandCatalogGetSectionSubtreeIds(int $iblockId, int $sectionId): array
{
    if ($sectionId <= 0 || !\Bitrix\Main\Loader::includeModule('iblock')) {
        return [];
    }

    $section = \CIBlockSection::GetList(
        [],
        [
            'IBLOCK_ID' => $iblockId,
            'ID' => $sectionId,
            'ACTIVE' => 'Y',
            'GLOBAL_ACTIVE' => 'Y',
        ],
        false,
        ['ID', 'LEFT_MARGIN', 'RIGHT_MARGIN'],
        ['nTopCount' => 1]
    )->Fetch();

    if (!$section) {
        return [];
    }

    $sectionIds = [$sectionId];
    $rsSections = \CIBlockSection::GetList(
        ['LEFT_MARGIN' => 'ASC'],
        [
            'IBLOCK_ID' => $iblockId,
            '>=LEFT_MARGIN' => (int)($section['LEFT_MARGIN'] ?? 0),
            '<=RIGHT_MARGIN' => (int)($section['RIGHT_MARGIN'] ?? 0),
            'ACTIVE' => 'Y',
            'GLOBAL_ACTIVE' => 'Y',
        ],
        false,
        ['ID']
    );

    while ($childSection = $rsSections->Fetch()) {
        $childId = (int)($childSection['ID'] ?? 0);
        if ($childId > 0) {
            $sectionIds[] = $childId;
        }
    }

    return array_values(array_unique($sectionIds));
}

/**
 * @return list<array<string, mixed>>
 */
function brandCatalogGetSectionNavChain(int $iblockId, int $sectionId): array
{
    if ($sectionId <= 0 || !\Bitrix\Main\Loader::includeModule('iblock')) {
        return [];
    }

    $chain = [];
    $navChain = \CIBlockSection::GetNavChain(
        $iblockId,
        $sectionId,
        ['ID', 'NAME', 'DEPTH_LEVEL', 'IBLOCK_SECTION_ID']
    );

    while ($chainItem = $navChain->Fetch()) {
        $chain[] = $chainItem;
    }

    return $chain;
}

/**
 * @return list<int>
 */
function brandCatalogGetProductSectionIds(int $iblockId, array $productIds): array
{
    $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
    if ($iblockId <= 0 || $productIds === [] || !\Bitrix\Main\Loader::includeModule('iblock')) {
        return [];
    }

    $sectionIds = [];

    if (class_exists(\Bitrix\Iblock\SectionElementTable::class)) {
        $sectionRows = \Bitrix\Iblock\SectionElementTable::getList([
            'filter' => ['@IBLOCK_ELEMENT_ID' => $productIds],
            'select' => ['IBLOCK_SECTION_ID'],
        ]);

        while ($sectionRow = $sectionRows->fetch()) {
            $sectionId = (int)($sectionRow['IBLOCK_SECTION_ID'] ?? 0);
            if ($sectionId > 0) {
                $sectionIds[$sectionId] = true;
            }
        }
    }

    $rsElements = \CIBlockElement::GetList(
        [],
        [
            'IBLOCK_ID' => $iblockId,
            'ID' => $productIds,
        ],
        false,
        false,
        ['ID', 'IBLOCK_SECTION_ID']
    );

    while ($element = $rsElements->Fetch()) {
        $sectionId = (int)($element['IBLOCK_SECTION_ID'] ?? 0);
        if ($sectionId > 0) {
            $sectionIds[$sectionId] = true;
        }
    }

    return array_map('intval', array_keys($sectionIds));
}

/**
 * @param list<int> $productIds
 * @return array<int, list<int>>
 */
function brandCatalogBuildProductSectionMap(int $iblockId, array $productIds): array
{
    $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
    if ($iblockId <= 0 || $productIds === [] || !\Bitrix\Main\Loader::includeModule('iblock')) {
        return [];
    }

    $productSectionMap = [];
    foreach ($productIds as $productId) {
        $productSectionMap[$productId] = [];
    }

    if (class_exists(\Bitrix\Iblock\SectionElementTable::class)) {
        $sectionRows = \Bitrix\Iblock\SectionElementTable::getList([
            'filter' => ['@IBLOCK_ELEMENT_ID' => $productIds],
            'select' => ['IBLOCK_ELEMENT_ID', 'IBLOCK_SECTION_ID'],
        ]);

        while ($sectionRow = $sectionRows->fetch()) {
            $productId = (int)($sectionRow['IBLOCK_ELEMENT_ID'] ?? 0);
            $productSectionId = (int)($sectionRow['IBLOCK_SECTION_ID'] ?? 0);
            if ($productId > 0 && $productSectionId > 0) {
                $productSectionMap[$productId][$productSectionId] = $productSectionId;
            }
        }
    }

    $rsElements = \CIBlockElement::GetList(
        [],
        [
            'IBLOCK_ID' => $iblockId,
            'ID' => $productIds,
        ],
        false,
        false,
        ['ID', 'IBLOCK_SECTION_ID']
    );

    while ($element = $rsElements->Fetch()) {
        $productId = (int)($element['ID'] ?? 0);
        $productSectionId = (int)($element['IBLOCK_SECTION_ID'] ?? 0);
        if ($productId > 0 && $productSectionId > 0) {
            $productSectionMap[$productId][$productSectionId] = $productSectionId;
        }
    }

    return $productSectionMap;
}

/**
 * @param list<int> $productIds
 * @return list<int>
 */
function brandCatalogFilterProductIdsBySectionSubtree(int $iblockId, int $sectionId, array $productIds): array
{
    if ($sectionId <= 0 || $productIds === []) {
        return $productIds;
    }

    $allowedSectionIds = array_fill_keys(brandCatalogGetSectionSubtreeIds($iblockId, $sectionId), true);
    if ($allowedSectionIds === []) {
        return [];
    }

    $productSectionMap = brandCatalogBuildProductSectionMap($iblockId, $productIds);
    $matchedIds = [];

    foreach ($productIds as $productId) {
        $productId = (int)$productId;
        if ($productId <= 0) {
            continue;
        }

        foreach ($productSectionMap[$productId] ?? [] as $itemSectionId) {
            if (isset($allowedSectionIds[$itemSectionId])) {
                $matchedIds[] = $productId;
                break;
            }
        }
    }

    return array_values(array_unique($matchedIds));
}

/**
 * @param array<string, mixed> $elementFilter
 * @return list<int>
 */
function brandCatalogGetProductIdsInSectionSubtree(int $iblockId, int $sectionId, array $elementFilter): array
{
    if ($sectionId <= 0 || $iblockId <= 0 || !\Bitrix\Main\Loader::includeModule('iblock')) {
        return [];
    }

    $queryFilter = $elementFilter;
    unset($queryFilter['SECTION_ID'], $queryFilter['INCLUDE_SUBSECTIONS'], $queryFilter['@ID'], $queryFilter['ID']);

    $productIds = [];
    $rsElements = \CIBlockElement::GetList([], $queryFilter, false, false, ['ID']);
    while ($element = $rsElements->Fetch()) {
        $productId = (int)($element['ID'] ?? 0);
        if ($productId > 0) {
            $productIds[] = $productId;
        }
    }

    return brandCatalogFilterProductIdsBySectionSubtree($iblockId, $sectionId, $productIds);
}

function brandCatalogGetGlobalFilterRestrictedProductIds(array $globalFilter): ?array
{
    if (!empty($globalFilter['@ID']) && is_array($globalFilter['@ID'])) {
        return array_values(array_filter(array_map('intval', $globalFilter['@ID'])));
    }

    if (!array_key_exists('ID', $globalFilter)) {
        return null;
    }

    if (is_array($globalFilter['ID'])) {
        return array_values(array_filter(array_map('intval', $globalFilter['ID'])));
    }

    $id = (int)$globalFilter['ID'];

    return $id === 0 ? [] : [$id];
}

function brandCatalogIsSectionFilterAppliedViaProductIds(array $globalFilter): bool
{
    return brandCatalogGetGlobalFilterRestrictedProductIds($globalFilter) !== null;
}

/**
 * Заменяет SECTION_ID в глобальном фильтре на ID по всем привязкам товара к поддереву раздела.
 */
function brandCatalogApplySectionSubtreeToGlobalFilter(string $filterName, int $iblockId): void
{
    $sectionId = brandCatalogResolveActiveSectionFilterId($filterName);
    if ($sectionId <= 0 || $iblockId <= 0) {
        return;
    }

    if (!isset($GLOBALS[$filterName]) || !is_array($GLOBALS[$filterName])) {
        $GLOBALS[$filterName] = [];
    }

    $elementFilter = array_merge(
        [
            'IBLOCK_ID' => $iblockId,
            'ACTIVE' => 'Y',
            'ACTIVE_DATE' => 'Y',
        ],
        $GLOBALS[$filterName]
    );

    $matchedIds = brandCatalogGetProductIdsInSectionSubtree($iblockId, $sectionId, $elementFilter);

    unset(
        $GLOBALS[$filterName]['SECTION_ID'],
        $GLOBALS[$filterName]['INCLUDE_SUBSECTIONS'],
        $GLOBALS[$filterName]['@ID'],
        $GLOBALS[$filterName]['ID']
    );

    if ($matchedIds === []) {
        $GLOBALS[$filterName]['ID'] = 0;
    } else {
        $GLOBALS[$filterName]['ID'] = $matchedIds;
    }
}

/**
 * @param array<string, mixed> $elementFilter
 * @return list<array{ID: int, NAME: string}>
 */
function brandCatalogGetSubsectionsForParentFromProducts(
    int $iblockId,
    int $parentId,
    array $elementFilter
): array {
    if ($parentId <= 0 || $iblockId <= 0 || !\Bitrix\Main\Loader::includeModule('iblock')) {
        return [];
    }

    $productIds = [];
    $rsElements = \CIBlockElement::GetList([], $elementFilter, false, false, ['ID']);
    while ($element = $rsElements->Fetch()) {
        $productIds[] = (int)$element['ID'];
    }

    if ($productIds === []) {
        return [];
    }

    $productSectionIds = brandCatalogGetProductSectionIds($iblockId, $productIds);
    $subsections = [];

    foreach ($productSectionIds as $sectionId) {
        $chain = brandCatalogGetSectionNavChain($iblockId, $sectionId);
        foreach ($chain as $index => $chainItem) {
            if ((int)($chainItem['ID'] ?? 0) !== $parentId) {
                continue;
            }

            if (!isset($chain[$index + 1])) {
                break;
            }

            $childSection = $chain[$index + 1];
            $childId = (int)($childSection['ID'] ?? 0);
            if ($childId <= 0) {
                break;
            }

            $subsections[$childId] = [
                'ID' => $childId,
                'NAME' => (string)($childSection['NAME'] ?? ''),
            ];
            break;
        }
    }

    if ($subsections === []) {
        return [];
    }

    uasort($subsections, static function (array $a, array $b): int {
        return strnatcasecmp($a['NAME'], $b['NAME']);
    });

    return array_values($subsections);
}

/**
 * @param array<string, mixed> $filter
 */
function brandCatalogCountElements(array $filter): int
{
    if (!\Bitrix\Main\Loader::includeModule('iblock')) {
        return 0;
    }

    return (int)\CIBlockElement::GetList([], $filter, []);
}

/**
 * @return array<string, mixed>
 */
function brandCatalogBuildBrandElementFilter(int $iblockId, string $brandValue): array
{
    require_once __DIR__ . '/catalog_list_item_properties.php';

    return array_merge(
        [
            'IBLOCK_ID' => $iblockId,
            'ACTIVE' => 'Y',
            'ACTIVE_DATE' => 'Y',
        ],
        catalogListBuildBrandPropertyFilter($brandValue)
    );
}

/**
 * @param list<int> $productIds
 * @return list<int>
 */
function brandCatalogFilterProductIdsByTopLevelParent(int $iblockId, int $parentId, array $productIds): array
{
    if ($parentId <= 0 || $productIds === []) {
        return [];
    }

    $matchedIds = [];
    foreach ($productIds as $productId) {
        $productId = (int)$productId;
        if ($productId <= 0) {
            continue;
        }

        foreach (brandCatalogGetProductSectionIds($iblockId, [$productId]) as $sectionId) {
            $chain = brandCatalogGetSectionNavChain($iblockId, $sectionId);
            if ($chain === []) {
                continue;
            }

            if ((int)($chain[0]['ID'] ?? 0) === $parentId) {
                $matchedIds[] = $productId;
                break;
            }
        }
    }

    return array_values(array_unique($matchedIds));
}

/**
 * Диагностика: где реально лежат товары бренда относительно раздела каталога.
 *
 * @param array<string, mixed> $priceFilter
 * @return array<string, mixed>
 */
function brandCatalogBuildSectionDiagnostics(
    int $iblockId,
    int $sectionId,
    string $brandValue,
    array $priceFilter = []
): array {
    if ($sectionId <= 0 || $iblockId <= 0 || !\Bitrix\Main\Loader::includeModule('iblock')) {
        return [];
    }

    $brandFilter = brandCatalogBuildBrandElementFilter($iblockId, $brandValue);
    $brandWithPrice = array_merge($brandFilter, $priceFilter);

    $portobelloIds = [];
    $rsElements = \CIBlockElement::GetList([], $brandFilter, false, false, ['ID']);
    while ($element = $rsElements->Fetch()) {
        $portobelloIds[] = (int)$element['ID'];
    }

    $portobelloWithPriceIds = [];
    $rsElements = \CIBlockElement::GetList([], $brandWithPrice, false, false, ['ID']);
    while ($element = $rsElements->Fetch()) {
        $portobelloWithPriceIds[] = (int)$element['ID'];
    }

    $topParentDistribution = [];
    foreach ($portobelloIds as $productId) {
        foreach (brandCatalogGetProductSectionIds($iblockId, [$productId]) as $productSectionId) {
            $chain = brandCatalogGetSectionNavChain($iblockId, $productSectionId);
            if ($chain === []) {
                continue;
            }

            $topId = (int)($chain[0]['ID'] ?? 0);
            $topName = (string)($chain[0]['NAME'] ?? '');
            if ($topId <= 0) {
                continue;
            }

            if (!isset($topParentDistribution[$topId])) {
                $topParentDistribution[$topId] = [
                    'ID' => $topId,
                    'NAME' => $topName,
                    'COUNT' => 0,
                ];
            }

            $topParentDistribution[$topId]['COUNT']++;
            break;
        }
    }

    uasort($topParentDistribution, static function (array $a, array $b): int {
        return $b['COUNT'] <=> $a['COUNT'];
    });

    return [
        'all_brands_in_section_sql' => brandCatalogCountElements([
            'IBLOCK_ID' => $iblockId,
            'ACTIVE' => 'Y',
            'ACTIVE_DATE' => 'Y',
            'SECTION_ID' => $sectionId,
            'INCLUDE_SUBSECTIONS' => 'Y',
        ]),
        'brand_total' => count($portobelloIds),
        'brand_with_price' => count($portobelloWithPriceIds),
        'brand_in_section_subtree' => count(brandCatalogFilterProductIdsBySectionSubtree($iblockId, $sectionId, $portobelloIds)),
        'brand_in_section_subtree_with_price' => count(
            brandCatalogFilterProductIdsBySectionSubtree($iblockId, $sectionId, $portobelloWithPriceIds)
        ),
        'brand_top_parent_eq_section' => count(
            brandCatalogFilterProductIdsByTopLevelParent($iblockId, $sectionId, $portobelloIds)
        ),
        'brand_top_parent_eq_section_with_price' => count(
            brandCatalogFilterProductIdsByTopLevelParent($iblockId, $sectionId, $portobelloWithPriceIds)
        ),
        'brand_top_parent_distribution' => array_values($topParentDistribution),
    ];
}

/**
 * @param list<array<string, mixed>> $items
 * @return list<array<string, mixed>>
 */
function brandCatalogFilterItemsByActiveSection(array $items, int $sectionId, int $iblockId): array
{
    if ($sectionId <= 0 || $items === []) {
        return $items;
    }

    $productIds = [];
    foreach ($items as $item) {
        $productId = (int)($item['ID'] ?? 0);
        if ($productId > 0) {
            $productIds[] = $productId;
        }
    }

    if ($productIds === []) {
        return [];
    }

    $matchedIds = array_fill_keys(
        brandCatalogFilterProductIdsBySectionSubtree($iblockId, $sectionId, $productIds),
        true
    );

    if ($matchedIds === []) {
        return [];
    }

    $filteredItems = [];
    foreach ($items as $item) {
        $productId = (int)($item['ID'] ?? 0);
        if ($productId > 0 && isset($matchedIds[$productId])) {
            $filteredItems[] = $item;
        }
    }

    return $filteredItems;
}

/**
 * Пересчитывает ELEMENT_COUNT смарт-фильтра с учётом кастомного фильтра категории (раздел/subtree).
 *
 * @param array<string, mixed> $arResult
 * @param array<string, mixed> $arParams
 * @return array<string, mixed>
 */
function brandCatalogRecalculateSmartFilterElementCount(array $arResult, array $arParams, string $filterName = 'arrFilter'): array
{
    if (($arParams['SHOW_CATEGORY_FILTER'] ?? 'N') !== 'Y') {
        return $arResult;
    }

    $iblockId = (int)($arParams['IBLOCK_ID'] ?? 0);
    $sectionId = brandCatalogResolveActiveSectionFilterId($filterName);
    if ($iblockId <= 0 || $sectionId <= 0) {
        return $arResult;
    }

    brandCatalogFinalizeGlobalFilter($filterName);

    $elementFilter = array_merge(
        [
            'IBLOCK_ID' => $iblockId,
            'ACTIVE' => 'Y',
            'ACTIVE_DATE' => 'Y',
        ],
        is_array($GLOBALS[$filterName] ?? null) ? $GLOBALS[$filterName] : []
    );

    foreach (['SECTION_ID', 'INCLUDE_SUBSECTIONS', 'ID', '@ID'] as $filterKey) {
        unset($elementFilter[$filterKey]);
    }

    $elementFilter = brandCatalogSanitizeFilterArray($elementFilter, $filterName);
    $arResult['ELEMENT_COUNT'] = count(
        brandCatalogGetProductIdsInSectionSubtree($iblockId, $sectionId, $elementFilter)
    );

    return $arResult;
}

function brandCatalogGetSeoDescriptionHtml(?array $brandConfig, string $position): string
{
    if ($brandConfig === null) {
        return '';
    }

    if ($position === 'top') {
        $html = trim((string)($brandConfig['UPPER_DESCRIPTION'] ?? $brandConfig['SEO_DESCRIPTION_TOP'] ?? ''));
    } else {
        $html = trim((string)($brandConfig['SEO_DESCRIPTION_BOTTOM'] ?? $brandConfig['DETAIL_TEXT'] ?? ''));
    }

    return $html;
}

function brandCatalogRenderSeoDescription(?array $brandConfig, string $position): void
{
    $html = brandCatalogGetSeoDescriptionHtml($brandConfig, $position);
    if ($html === '') {
        return;
    }
    ?>
    <div class="content brand-catalog-seo brand-catalog-seo--<?= htmlspecialcharsbx($position) ?>">
        <?= $html ?>
    </div>
    <?php
}

/**
 * @return array<string, mixed>
 */
function brandCatalogBuildCategoryElementFilter(
    int $iblockId,
    string $filterName = 'arrFilter',
    string $prefilterName = ''
): array {
    brandCatalogFinalizeGlobalFilter($filterName);

    $categoryElementFilter = [
        'IBLOCK_ID' => $iblockId,
        'ACTIVE' => 'Y',
        'ACTIVE_DATE' => 'Y',
    ];

    $prefilterSource = [];
    if ($prefilterName !== '' && isset($GLOBALS[$prefilterName]) && is_array($GLOBALS[$prefilterName])) {
        $prefilterSource = $GLOBALS[$prefilterName];
    } elseif (isset($GLOBALS[$filterName]) && is_array($GLOBALS[$filterName])) {
        $prefilterSource = $GLOBALS[$filterName];
    }

    foreach ($prefilterSource as $prefilterKey => $prefilterValue) {
        if (in_array($prefilterKey, ['SECTION_ID', 'INCLUDE_SUBSECTIONS'], true)) {
            continue;
        }
        $categoryElementFilter[$prefilterKey] = $prefilterValue;
    }

    return brandCatalogGetCategoryBaseElementFilter($iblockId, $categoryElementFilter);
}

/**
 * @param array{
 *     SHOW?: bool,
 *     PARENT?: array{CURRENT?: int, VALUES?: list<array{ID: int, NAME: string}>},
 *     SUB?: array{SHOW?: bool, CURRENT?: int, VALUES?: list<array{ID: int, NAME: string}>}
 * } $categoryFilter
 * @return list<array{ID: int, NAME: string, TYPE: string}>
 */
function brandCatalogGetCategoryMenuSections(array $categoryFilter): array
{
    if (empty($categoryFilter['SHOW'])) {
        return [];
    }

    $currentParent = (int)($categoryFilter['PARENT']['CURRENT'] ?? 0);
    if ($currentParent > 0 && !empty($categoryFilter['SUB']['SHOW'])) {
        $sections = [];
        foreach ((array)($categoryFilter['SUB']['VALUES'] ?? []) as $section) {
            if (!is_array($section)) {
                continue;
            }
            $section['TYPE'] = 'sub';
            $sections[] = $section;
        }

        return $sections;
    }

    $sections = [];
    foreach ((array)($categoryFilter['PARENT']['VALUES'] ?? []) as $section) {
        if (!is_array($section)) {
            continue;
        }
        $section['TYPE'] = 'parent';
        $sections[] = $section;
    }

    return $sections;
}

function brandCatalogBuildCategoryMenuUrl(
    string $pageFolder,
    string $filterName,
    string $sectionType,
    int $sectionId,
    int $currentParentId = 0
): string {
    $params = $_GET;
    $controls = brandCatalogGetSectionFilterControlNames($filterName);

    if ($sectionType === 'parent') {
        if ($sectionId > 0) {
            $params[$controls['parent']] = $sectionId;
            unset($params[$controls['sub']]);
        } else {
            unset($params[$controls['parent']], $params[$controls['sub']]);
        }
    } elseif ($sectionId > 0) {
        if ($currentParentId > 0) {
            $params[$controls['parent']] = $currentParentId;
        }
        $params[$controls['sub']] = $sectionId;
    }

    $params['set_filter'] = 'y';

    return rtrim($pageFolder, '/') . '/?' . http_build_query($params);
}

/**
 * @param array{
 *     SHOW?: bool,
 *     PARENT?: array{CURRENT?: int, VALUES?: list<array{ID: int, NAME: string}>},
 *     SUB?: array{SHOW?: bool, CURRENT?: int, VALUES?: list<array{ID: int, NAME: string}>}
 * } $categoryFilter
 */
function brandCatalogRenderCategoryMenuFilter(
    array $categoryFilter,
    string $pageFolder,
    string $filterName = 'arrFilter'
): void {
    $sections = brandCatalogGetCategoryMenuSections($categoryFilter);
    if ($sections === []) {
        return;
    }

    $currentParent = (int)($categoryFilter['PARENT']['CURRENT'] ?? 0);
    $currentSub = (int)($categoryFilter['SUB']['CURRENT'] ?? 0);
    $isSubMode = $currentParent > 0 && !empty($categoryFilter['SUB']['SHOW']);
    ?>
    <div class="cats-menu-filter brand-cats-menu-filter">
        <div class="row">
            <div class="col-sm-12">
                <ul class="category">
                    <?php foreach ($sections as $section): ?>
                        <?php
                        $sectionId = (int)($section['ID'] ?? 0);
                        $sectionType = (string)($section['TYPE'] ?? 'parent');
                        if ($sectionId <= 0) {
                            continue;
                        }

                        $isActive = $isSubMode
                            ? $sectionId === $currentSub
                            : $sectionId === $currentParent;
                        $sectionUrl = brandCatalogBuildCategoryMenuUrl(
                            $pageFolder,
                            $filterName,
                            $sectionType,
                            $sectionId,
                            $currentParent
                        );
                        ?>
                        <li<?= $isActive ? ' class="is-active"' : '' ?>>
                            <a
                                href="<?= htmlspecialcharsbx($sectionUrl) ?>"
                                data-brand-category-id="<?= $sectionId ?>"
                                data-brand-category-type="<?= htmlspecialcharsbx($sectionType) ?>"
                            ><?= htmlspecialcharsbx((string)($section['NAME'] ?? '')) ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <?php
}

/**
 * @param array<string, mixed> $elementFilter
 * @return array{
 *     SHOW: bool,
 *     PARENT: array{CONTROL_NAME: string, CURRENT: int, VALUES: list<array{ID: int, NAME: string}>},
 *     SUB: array{CONTROL_NAME: string, CURRENT: int, SHOW: bool, VALUES: list<array{ID: int, NAME: string}>}
 * }
 */
function brandCatalogCollectCategoryFilterData(
    int $iblockId,
    array $elementFilter,
    string $filterName = 'arrFilter'
): array {
    $controls = brandCatalogGetSectionFilterControlNames($filterName);
    $selected = brandCatalogGetSelectedSectionFilterIds($filterName);

    $empty = [
        'SHOW' => false,
        'PARENT' => [
            'CONTROL_NAME' => $controls['parent'],
            'CURRENT' => $selected['parent'],
            'VALUES' => [],
        ],
        'SUB' => [
            'CONTROL_NAME' => $controls['sub'],
            'CURRENT' => $selected['sub'],
            'SHOW' => false,
            'VALUES' => [],
        ],
    ];

    if ($iblockId <= 0 || !\Bitrix\Main\Loader::includeModule('iblock')) {
        return $empty;
    }

    $productIds = [];
    $rsElements = \CIBlockElement::GetList([], $elementFilter, false, false, ['ID']);
    while ($element = $rsElements->Fetch()) {
        $productIds[] = (int)$element['ID'];
    }

    if ($productIds === []) {
        return $empty;
    }

    $productSectionIds = brandCatalogGetProductSectionIds($iblockId, $productIds);
    if ($productSectionIds === []) {
        return $empty;
    }

    $parentSections = [];
    $subSectionsByParent = [];

    foreach ($productSectionIds as $sectionId) {
        $chain = brandCatalogGetSectionNavChain($iblockId, $sectionId);
        if ($chain === []) {
            continue;
        }

        $topSection = $chain[0];
        $topId = (int)($topSection['ID'] ?? 0);
        if ($topId <= 0) {
            continue;
        }

        $parentSections[$topId] = [
            'ID' => $topId,
            'NAME' => (string)($topSection['NAME'] ?? ''),
        ];

        foreach ($chain as $index => $chainItem) {
            $ancestorId = (int)($chainItem['ID'] ?? 0);
            if ($ancestorId <= 0 || !isset($chain[$index + 1])) {
                continue;
            }

            $childSection = $chain[$index + 1];
            $childId = (int)($childSection['ID'] ?? 0);
            if ($childId <= 0) {
                continue;
            }

            if (!isset($subSectionsByParent[$ancestorId])) {
                $subSectionsByParent[$ancestorId] = [];
            }

            $subSectionsByParent[$ancestorId][$childId] = [
                'ID' => $childId,
                'NAME' => (string)($childSection['NAME'] ?? ''),
            ];
        }
    }

    if ($parentSections === []) {
        return $empty;
    }

    uasort($parentSections, static function (array $a, array $b): int {
        return strnatcasecmp($a['NAME'], $b['NAME']);
    });

    $parentValues = array_values($parentSections);
    $allowedParentIds = array_column($parentValues, 'ID');
    $currentParent = in_array($selected['parent'], $allowedParentIds, true) ? $selected['parent'] : 0;

    $subValues = [];
    if ($currentParent > 0) {
        $subValues = brandCatalogGetSubsectionsForParentFromProducts(
            $iblockId,
            $currentParent,
            brandCatalogGetCategoryBaseElementFilter($iblockId, $elementFilter)
        );
    }

    $allowedSubIds = array_column($subValues, 'ID');
    $currentSub = in_array($selected['sub'], $allowedSubIds, true) ? $selected['sub'] : 0;

    brandCatalogDebug('collect_category_filter', [
        'productIds_count' => count($productIds),
        'productSectionIds_count' => count($productSectionIds),
        'productSectionIds_sample' => array_slice($productSectionIds, 0, 30),
        'parentSections_count' => count($parentSections),
        'subSectionsByParent_25' => $subSectionsByParent[25] ?? ($subSectionsByParent[$currentParent] ?? null),
        'subSectionsByParent_keys' => array_keys($subSectionsByParent),
        'elementFilter' => $elementFilter,
        'selected' => $selected,
    ]);

    return [
        'SHOW' => true,
        'PARENT' => [
            'CONTROL_NAME' => $controls['parent'],
            'CURRENT' => $currentParent,
            'VALUES' => $parentValues,
        ],
        'SUB' => [
            'CONTROL_NAME' => $controls['sub'],
            'CURRENT' => $currentSub,
            'SHOW' => $currentParent > 0 && $subValues !== [],
            'VALUES' => $subValues,
        ],
    ];
}

function getBrandCatalogDefaultParams(string $pageFolder): array
{
    return [
        'IBLOCK_TYPE' => 'catalog',
        'IBLOCK_ID' => '13',
        'FILTER_NAME' => 'arrFilter',
        'FILTER_VIEW_MODE' => 'VERTICAL',
        'CACHE_TYPE' => 'A',
        'CACHE_TIME' => '36000000',
        'CACHE_GROUPS' => 'Y',
        'HIDE_NOT_AVAILABLE' => 'L',
        'HIDE_NOT_AVAILABLE_OFFERS' => 'Y',
        'PRICE_CODE' => ['Оптовая цена', 'Рекламная цена'],
        'CONVERT_CURRENCY' => 'N',
        'TEMPLATE_THEME' => 'blue',
        'SEF_MODE' => 'N',
        'PAGER_PARAMS_NAME' => 'arrPager',
        'INSTANT_RELOAD' => 'Y',
        'ELEMENT_SORT_FIELD' => 'sort',
        'ELEMENT_SORT_ORDER' => 'asc',
        'ELEMENT_SORT_FIELD2' => 'id',
        'ELEMENT_SORT_ORDER2' => 'desc',
        'INCLUDE_SUBSECTIONS' => 'Y',
        'BASKET_URL' => '/personal/basket.php',
        'ACTION_VARIABLE' => 'action',
        'PRODUCT_ID_VARIABLE' => 'id',
        'SECTION_ID_VARIABLE' => 'SECTION_ID',
        'PRODUCT_QUANTITY_VARIABLE' => 'quantity',
        'PRODUCT_PROPS_VARIABLE' => 'prop',
        'PAGE_ELEMENT_COUNT' => '24',
        'LINE_ELEMENT_COUNT' => '3',
        'DISPLAY_TOP_PAGER' => 'N',
        'DISPLAY_BOTTOM_PAGER' => 'Y',
        'PAGER_TITLE' => 'Товары',
        'PAGER_SHOW_ALWAYS' => 'N',
        'PAGER_TEMPLATE' => 'catalog-navigation',
        'LAZY_LOAD' => 'Y',
        'LOAD_ON_SCROLL' => 'N',
        'USE_COMPARE' => 'N',
        'USE_PRODUCT_QUANTITY' => 'N',
        'ADD_PROPERTIES_TO_BASKET' => 'Y',
        'PARTIAL_PRODUCT_PROPERTIES' => 'Y',
        'PRODUCT_PROPERTIES' => [],
        'OFFERS_SORT_FIELD' => 'sort',
        'OFFERS_SORT_ORDER' => 'asc',
        'OFFERS_SORT_FIELD2' => 'id',
        'OFFERS_SORT_ORDER2' => 'desc',
        'LIST_OFFERS_LIMIT' => 20,
        'LIST_OFFERS_FIELD_CODE' => ['ID', 'NAME'],
        'LIST_OFFERS_PROPERTY_CODE' => ['CODE'],
        'LIST_PROPERTY_CODE' => [],
        'LIST_PROPERTY_CODE_MOBILE' => [],
        'LIST_META_KEYWORDS' => '-',
        'LIST_META_DESCRIPTION' => '-',
        'LIST_BROWSER_TITLE' => '-',
        'SET_TITLE' => 'N',
        'SET_LAST_MODIFIED' => 'N',
        'SET_STATUS_404' => 'N',
        'SHOW_404' => 'N',
        'MESSAGE_404' => '',
        'FILE_404' => '',
        'USE_MAIN_ELEMENT_SECTION' => 'Y',
        'PRODUCT_DISPLAY_MODE' => 'Y',
        'ADD_PICT_PROP' => '-',
        'LABEL_PROP' => '',
        'LABEL_PROP_MOBILE' => '',
        'LABEL_PROP_POSITION' => '',
        'OFFER_ADD_PICT_PROP' => '-',
        'OFFER_TREE_PROPS' => [],
        'PRODUCT_SUBSCRIPTION' => 'Y',
        'SHOW_DISCOUNT_PERCENT' => 'N',
        'DISCOUNT_PERCENT_POSITION' => '',
        'SHOW_OLD_PRICE' => 'N',
        'SHOW_MAX_QUANTITY' => 'N',
        'LIST_PRODUCT_BLOCKS_ORDER' => 'price,props,sku,quantityLimit,quantity,buttons',
        'LIST_PRODUCT_ROW_VARIANTS' => "[{'VARIANT':'2','BIG_DATA':false},{'VARIANT':'2','BIG_DATA':false},{'VARIANT':'2','BIG_DATA':false},{'VARIANT':'2','BIG_DATA':false},{'VARIANT':'2','BIG_DATA':false},{'VARIANT':'2','BIG_DATA':false}]",
        'LIST_ENLARGE_PRODUCT' => 'STRICT',
        'LIST_SHOW_SLIDER' => 'Y',
        'LIST_SLIDER_INTERVAL' => '3000',
        'LIST_SLIDER_PROGRESS' => 'N',
        'SECTION_ADD_TO_BASKET_ACTION' => 'ADD',
        'COMMON_SHOW_CLOSE_POPUP' => 'N',
        'COMPARE_NAME' => 'CATALOG_COMPARE_LIST',
        'USE_ENHANCED_ECOMMERCE' => 'N',
        'DATA_LAYER_NAME' => '',
        'BRAND_PROPERTY' => '',
        'COMPATIBLE_MODE' => 'N',
        'DISABLE_INIT_JS_IN_COMPONENT' => 'N',
        'SECTION_BACKGROUND_IMAGE' => '-',
        'BRAND_PAGE_FOLDER' => $pageFolder,
        'DETAIL_URL' => '/catalog/#SECTION_CODE_PATH#/#ELEMENT_CODE#/',
        'SECTION_URL' => '/catalog/#SECTION_CODE_PATH#/',
    ];
}
