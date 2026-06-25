<?php

function catalogListBuildBrandPropertyFilter(string $brandValue, string $brandPropertyCode = 'BRENDY_DLYA_WEB'): array
{
    $brandValue = trim($brandValue);
    if ($brandValue === '') {
        return [];
    }

    return [
        '!PROPERTY_' . $brandPropertyCode => false,
        '=PROPERTY_' . $brandPropertyCode => $brandValue,
    ];
}

/**
 * @return list<string>
 */
function catalogListGetColorFilterPropertyCodes(): array
{
    return ['TSVET', 'COLOR'];
}

function catalogListEscapeLikeValue(string $value): string
{
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
}

function catalogListResolveColorFilterNeedle(mixed $raw): string
{
    if (is_array($raw)) {
        foreach ($raw as $item) {
            $resolved = catalogListResolveColorFilterNeedle($item);
            if ($resolved !== '') {
                return $resolved;
            }
        }

        return '';
    }

    if (!is_scalar($raw)) {
        return '';
    }

    $value = trim((string)$raw);
    if ($value === '') {
        return '';
    }

    if (\Bitrix\Main\Loader::includeModule('iblock')) {
        if (ctype_digit($value)) {
            $enum = \CIBlockPropertyEnum::GetByID((int)$value);
            if (is_array($enum) && !empty($enum['VALUE'])) {
                $enumValue = trim((string)$enum['VALUE']);
                if ($enumValue !== '') {
                    return $enumValue;
                }
            }
        }

        $byXmlId = catalogListResolveColorLabelByXmlId($value);
        if ($byXmlId !== '') {
            return $byXmlId;
        }
    }

    return $value;
}

function catalogListResolveColorLabelByXmlId(string $xmlId): string
{
    $xmlId = trim($xmlId);
    if ($xmlId === '' || !\Bitrix\Main\Loader::includeModule('iblock')) {
        return '';
    }

    $productIblockId = catalogListResolveCatalogProductIblockIdFromFilterContext();
    $iblockIds = [];
    if ($productIblockId > 0) {
        $iblockIds[] = $productIblockId;
        if (\Bitrix\Main\Loader::includeModule('catalog')) {
            $skuInfo = \CCatalogSKU::GetInfoByProductIBlock($productIblockId);
            if (!empty($skuInfo['IBLOCK_ID'])) {
                $iblockIds[] = (int)$skuInfo['IBLOCK_ID'];
            }
        }
    }

    foreach (array_unique($iblockIds) as $iblockId) {
        foreach (catalogListGetColorFilterPropertyCodes() as $propertyCode) {
            $property = \CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => $propertyCode])->Fetch();
            if (!$property || empty($property['ID'])) {
                continue;
            }

            $enum = \CIBlockPropertyEnum::GetList(
                [],
                ['PROPERTY_ID' => (int)$property['ID'], 'XML_ID' => $xmlId]
            )->Fetch();
            if (is_array($enum) && !empty($enum['VALUE'])) {
                $label = trim((string)$enum['VALUE']);
                if ($label !== '') {
                    return $label;
                }
            }
        }
    }

    return '';
}

/**
 * @param array<string, mixed> $valueRow
 */
function catalogListResolveColorFilterNeedleFromSmartFilterValueRow(array $valueRow): string
{
    $label = trim((string)($valueRow['VALUE'] ?? ''));
    if ($label !== '') {
        return catalogListResolveColorFilterNeedle($label);
    }

    foreach (['URL_ID', 'HTML_VALUE', 'HTML_VALUE_ALT', 'FACET_VALUE'] as $key) {
        if (empty($valueRow[$key])) {
            continue;
        }
        $resolved = catalogListResolveColorFilterNeedle($valueRow[$key]);
        if ($resolved !== '') {
            return $resolved;
        }
    }

    return '';
}

/**
 * @return list<string>
 */
function catalogListExtractColorNeedlesFromSmartFilterPath(string $path): array
{
    $path = trim(str_replace('\\', '/', $path), '/');
    if ($path === '') {
        return [];
    }

    $needles = [];
    foreach (explode('/', $path) as $chunk) {
        if (!preg_match('/^(tsvet|color)-is-(.+)$/iu', $chunk, $matches)) {
            continue;
        }
        $resolved = catalogListResolveColorFilterNeedle($matches[2]);
        if ($resolved !== '') {
            $needles[$resolved] = $resolved;
        }
    }

    return array_values($needles);
}

/**
 * @param mixed $raw
 * @return list<string>
 */
function catalogListNormalizeFilterValuesToList(mixed $raw): array
{
    $values = is_array($raw) ? $raw : [$raw];
    $out = [];

    foreach ($values as $value) {
        $resolved = catalogListResolveColorFilterNeedle($value);
        if ($resolved !== '') {
            $out[$resolved] = $resolved;
        }
    }

    return array_values($out);
}

function catalogListApplyColorSubstringFilterToGlobal(string $filterName = 'arrFilter'): void
{
    if ($filterName === '') {
        return;
    }

    $needles = catalogListGetActiveColorFilterNeedles();
    if ($needles === []) {
        return;
    }

    if (!isset($GLOBALS[$filterName]) || !is_array($GLOBALS[$filterName])) {
        $GLOBALS[$filterName] = [];
    }

    $productIblockId = catalogListResolveCatalogProductIblockIdFromFilterContext();
    if ($productIblockId <= 0) {
        return;
    }

    $productIds = catalogListFindProductIdsByColorNeedles($needles, $productIblockId);
    if ($productIds === []) {
        return;
    }

    $GLOBALS[$filterName] = catalogListStripColorPropertyConditionsFromFilter($GLOBALS[$filterName]);
    unset($GLOBALS[$filterName]['FACET_OPTIONS']);
    $GLOBALS[$filterName] = catalogListMergeProductIdFilter($GLOBALS[$filterName], $productIds);
}

function catalogListSetActiveColorFilterNeedles(array $needles): void
{
    $normalized = [];
    foreach ($needles as $needle) {
        $resolved = catalogListResolveColorFilterNeedle($needle);
        if ($resolved !== '') {
            $normalized[$resolved] = $resolved;
        }
    }

    if ($normalized === []) {
        unset($GLOBALS['CATALOG_ACTIVE_COLOR_FILTERS']);
        return;
    }

    $GLOBALS['CATALOG_ACTIVE_COLOR_FILTERS'] = array_values($normalized);
}

/**
 * @return list<string>
 */
function catalogListGetActiveColorFilterNeedles(): array
{
    if (!empty($GLOBALS['CATALOG_ACTIVE_COLOR_FILTERS']) && is_array($GLOBALS['CATALOG_ACTIVE_COLOR_FILTERS'])) {
        return catalogListNormalizeFilterValuesToList($GLOBALS['CATALOG_ACTIVE_COLOR_FILTERS']);
    }

    $fromPath = catalogListExtractColorNeedlesFromSmartFilterPath(
        (string)($GLOBALS['CATALOG_SMART_FILTER_PATH'] ?? '')
    );
    if ($fromPath !== []) {
        return $fromPath;
    }

    return catalogListExtractColorNeedlesFromFilterArray(
        isset($GLOBALS['arrFilter']) && is_array($GLOBALS['arrFilter']) ? $GLOBALS['arrFilter'] : []
    );
}

/**
 * @param array<int|string, mixed> $filterItems
 * @return list<string>
 */
function catalogListCollectActiveColorNeedlesFromSmartFilterItems(array $filterItems, string $smartFilterPath = ''): array
{
    $colorCodes = array_fill_keys(catalogListGetColorFilterPropertyCodes(), true);
    $needles = [];
    $smartFilterPath = trim(str_replace('\\', '/', $smartFilterPath), '/');

    foreach ($filterItems as $filterItem) {
        if (!is_array($filterItem)) {
            continue;
        }

        $code = strtoupper(trim((string)($filterItem['CODE'] ?? '')));
        if ($code === '' || !isset($colorCodes[$code])) {
            continue;
        }

        foreach ((array)($filterItem['VALUES'] ?? []) as $valueRow) {
            if (!is_array($valueRow)) {
                continue;
            }

            $isChecked = !empty($valueRow['CHECKED']);
            if (!$isChecked && $smartFilterPath !== '') {
                foreach (['URL_ID', 'HTML_VALUE', 'HTML_VALUE_ALT'] as $pathKey) {
                    $pathToken = trim((string)($valueRow[$pathKey] ?? ''));
                    if ($pathToken !== '' && stripos($smartFilterPath, $pathToken) !== false) {
                        $isChecked = true;
                        break;
                    }
                }
            }
            if (!$isChecked) {
                continue;
            }

            $label = catalogListResolveColorFilterNeedleFromSmartFilterValueRow($valueRow);
            if ($label !== '') {
                $needles[$label] = $label;
            }
        }
    }

    return array_values($needles);
}

/**
 * @param array<string|int, mixed> $filter
 * @return list<string>
 */
function catalogListExtractColorNeedlesFromFilterArray(array $filter): array
{
    $colorPropertyCodes = catalogListGetColorFilterPropertyCodes();
    $colorPropertyMap = array_fill_keys($colorPropertyCodes, true);
    $needles = [];

    foreach ($filter as $key => $value) {
        if ($key === 'OFFERS' && is_array($value)) {
            $needles = array_merge($needles, catalogListExtractColorNeedlesFromFilterArray($value));
            continue;
        }

        if (!is_string($key)) {
            continue;
        }

        $propertyCode = catalogListResolveColorPropertyCodeFromFilterKey($key, $colorPropertyMap);
        if ($propertyCode === null) {
            continue;
        }

        foreach (catalogListNormalizeFilterValuesToList($value) as $needle) {
            $needles[$needle] = $needle;
        }
    }

    return array_values($needles);
}

/**
 * @param array<string, bool> $colorPropertyMap
 */
function catalogListResolveColorPropertyCodeFromFilterKey(string $key, array $colorPropertyMap): ?string
{
    if (!preg_match('/^=?\\?PROPERTY_(.+)$/u', $key, $matches)) {
        if (!preg_match('/^=?PROPERTY_(.+)$/u', $key, $matches)) {
            return null;
        }
    }

    $suffix = (string)($matches[1] ?? '');
    if ($suffix === '') {
        return null;
    }

    if (isset($colorPropertyMap[$suffix])) {
        return $suffix;
    }

    if (ctype_digit($suffix) && \Bitrix\Main\Loader::includeModule('iblock')) {
        static $propertyCodeById = [];
        $propertyId = (int)$suffix;
        if (!isset($propertyCodeById[$propertyId])) {
            $property = \CIBlockProperty::GetByID($propertyId)->Fetch();
            $propertyCodeById[$propertyId] = strtoupper(trim((string)($property['CODE'] ?? '')));
        }

        $code = $propertyCodeById[$propertyId];
        if ($code !== '' && isset($colorPropertyMap[$code])) {
            return $code;
        }
    }

    return null;
}

function catalogListResolveCatalogProductIblockIdFromFilterContext(): int
{
    if (!empty($GLOBALS['CATALOG_PRODUCT_IBLOCK_ID'])) {
        return (int)$GLOBALS['CATALOG_PRODUCT_IBLOCK_ID'];
    }

    return 13;
}

/**
 * @param list<string> $needles
 * @return list<int>
 */
function catalogListFindProductIdsByColorNeedles(array $needles, int $productIblockId): array
{
    $needles = catalogListNormalizeFilterValuesToList($needles);
    if ($needles === [] || $productIblockId <= 0 || !\Bitrix\Main\Loader::includeModule('iblock')) {
        return [];
    }

    $productIds = [];
    $propertyCodes = catalogListGetColorFilterPropertyCodes();

    foreach ($needles as $needle) {
        foreach ($propertyCodes as $propertyCode) {
            foreach (catalogListFindElementIdsByPropertySubstring($productIblockId, $propertyCode, $needle) as $elementId) {
                $productIds[$elementId] = true;
            }
        }
    }

    if (\Bitrix\Main\Loader::includeModule('catalog')) {
        $skuInfo = \CCatalogSKU::GetInfoByProductIBlock($productIblockId);
        $offerIblockId = (int)($skuInfo['IBLOCK_ID'] ?? 0);
        $linkPropertyId = (int)($skuInfo['SKU_PROPERTY_ID'] ?? 0);
        if ($offerIblockId > 0 && $linkPropertyId > 0) {
            foreach ($needles as $needle) {
                foreach ($propertyCodes as $propertyCode) {
                    foreach (catalogListFindElementIdsByPropertySubstring($offerIblockId, $propertyCode, $needle) as $offerId) {
                        $parentId = catalogListResolveOfferParentProductId($offerId, $linkPropertyId);
                        if ($parentId > 0) {
                            $productIds[$parentId] = true;
                        }
                    }
                }
            }
        }
    }

    return array_map('intval', array_keys($productIds));
}

function catalogListFindElementIdsByPropertySubstring(int $iblockId, string $propertyCode, string $needle): array
{
    $needle = catalogListResolveColorFilterNeedle($needle);
    if ($iblockId <= 0 || $propertyCode === '' || $needle === '') {
        return [];
    }

    if (!\Bitrix\Main\Loader::includeModule('iblock')) {
        return [];
    }

    $property = \CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => $propertyCode])->Fetch();
    if (!$property || empty($property['ID'])) {
        return [];
    }

    $propertyId = (int)$property['ID'];
    $propertyType = (string)($property['PROPERTY_TYPE'] ?? 'S');

    if ($propertyType === 'L') {
        $enumIds = catalogListFindEnumIdsByValueSubstring($propertyId, $needle);
        if ($enumIds === []) {
            return [];
        }

        return catalogListFindElementIdsByEnumIdsSql($iblockId, $propertyId, $enumIds);
    }

    $ids = catalogListFindElementIdsByPropertySubstringSql($iblockId, $propertyId, $needle);
    if ($ids !== []) {
        return $ids;
    }

    return catalogListFindElementIdsByPropertySubstringScan($iblockId, $propertyCode, $needle);
}

/**
 * @return list<int>
 */
function catalogListFindElementIdsByPropertySubstringSql(int $iblockId, int $propertyId, string $needle): array
{
    $connection = \Bitrix\Main\Application::getConnection();
    $helper = $connection->getSqlHelper();
    $like = $helper->forSql('%' . $needle . '%');
    $ids = [];

    $singleTable = 'b_iblock_element_prop_s' . $iblockId;
    if ($connection->isTableExists($singleTable)) {
        $sql = '
            SELECT DISTINCT T.IBLOCK_ELEMENT_ID AS ID
            FROM ' . $singleTable . ' T
            INNER JOIN b_iblock_element BE ON BE.ID = T.IBLOCK_ELEMENT_ID
            WHERE T.IBLOCK_PROPERTY_ID = ' . $propertyId . '
              AND BE.IBLOCK_ID = ' . $iblockId . "
              AND BE.ACTIVE = 'Y'
              AND T.VALUE LIKE '" . $like . "'
        ";
        $result = $connection->query($sql);
        while ($row = $result->fetch()) {
            $id = (int)($row['ID'] ?? 0);
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
    }

    if ($ids === [] && $connection->isTableExists('b_iblock_element_property')) {
        $sql = '
            SELECT DISTINCT BEP.IBLOCK_ELEMENT_ID AS ID
            FROM b_iblock_element_property BEP
            INNER JOIN b_iblock_element BE ON BE.ID = BEP.IBLOCK_ELEMENT_ID
            WHERE BEP.IBLOCK_PROPERTY_ID = ' . $propertyId . '
              AND BE.IBLOCK_ID = ' . $iblockId . "
              AND BE.ACTIVE = 'Y'
              AND BEP.VALUE LIKE '" . $like . "'
        ";
        $result = $connection->query($sql);
        while ($row = $result->fetch()) {
            $id = (int)($row['ID'] ?? 0);
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
    }

    $multiTable = 'b_iblock_element_prop_m' . $iblockId;
    if ($ids === [] && $connection->isTableExists($multiTable)) {
        $sql = '
            SELECT DISTINCT T.IBLOCK_ELEMENT_ID AS ID
            FROM ' . $multiTable . ' T
            INNER JOIN b_iblock_element BE ON BE.ID = T.IBLOCK_ELEMENT_ID
            WHERE T.IBLOCK_PROPERTY_ID = ' . $propertyId . '
              AND BE.IBLOCK_ID = ' . $iblockId . "
              AND BE.ACTIVE = 'Y'
              AND T.VALUE LIKE '" . $like . "'
        ";
        $result = $connection->query($sql);
        while ($row = $result->fetch()) {
            $id = (int)($row['ID'] ?? 0);
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
    }

    return array_values($ids);
}

/**
 * @return list<int>
 */
function catalogListFindElementIdsByPropertySubstringScan(int $iblockId, string $propertyCode, string $needle): array
{
    $needleLower = mb_strtolower($needle);
    $ids = [];
    $rs = \CIBlockElement::GetList(
        ['ID' => 'ASC'],
        [
            'IBLOCK_ID' => $iblockId,
            'ACTIVE' => 'Y',
            '!PROPERTY_' . $propertyCode => false,
        ],
        false,
        false,
        ['ID']
    );

    while ($row = $rs->Fetch()) {
        $elementId = (int)($row['ID'] ?? 0);
        if ($elementId <= 0) {
            continue;
        }

        $value = mb_strtolower(catalogListFetchElementPropertyValue($elementId, $iblockId, $propertyCode));
        if ($value !== '' && mb_strpos($value, $needleLower) !== false) {
            $ids[$elementId] = $elementId;
        }
    }

    return array_values($ids);
}

/**
 * @param array<string|int, mixed> $filter
 */
function catalogListIsNestedCatalogFilterArray(array $filter): bool
{
    foreach ($filter as $key => $value) {
        if ($key === 'LOGIC' || $key === 'LOGIC_FILTER') {
            return true;
        }
        if ($key === 'OFFERS' && is_array($value)) {
            return true;
        }
        if (is_string($key) && strncmp($key, 'PROPERTY_', 9) === 0) {
            return true;
        }
        if (is_string($key) && strncmp($key, '=PROPERTY_', 10) === 0) {
            return true;
        }
        if (is_string($key) && strncmp($key, '?PROPERTY_', 10) === 0) {
            return true;
        }
        if (is_string($key) && strncmp($key, '%PROPERTY_', 10) === 0) {
            return true;
        }
        if (is_numeric($key) && is_array($value)) {
            return true;
        }
    }

    return false;
}

function catalogListResolveOfferParentProductId(int $offerId, int $linkPropertyId = 0): int
{
    if ($offerId <= 0) {
        return 0;
    }

    if (\Bitrix\Main\Loader::includeModule('catalog')) {
        $productInfo = \CCatalogSKU::GetProductInfo($offerId);
        if (is_array($productInfo) && !empty($productInfo['ID'])) {
            return (int)$productInfo['ID'];
        }
    }

    if ($linkPropertyId <= 0) {
        return 0;
    }

    $row = \CIBlockElement::GetProperty(0, $offerId, [], ['ID' => $linkPropertyId])->Fetch();

    return (int)($row['VALUE'] ?? 0);
}

/**
 * @return list<int>
 */
function catalogListFindEnumIdsByValueSubstring(int $propertyId, string $needle): array
{
    if ($propertyId <= 0 || $needle === '') {
        return [];
    }

    $needleLower = mb_strtolower($needle);
    $ids = [];
    $rs = \CIBlockPropertyEnum::GetList(['SORT' => 'ASC'], ['PROPERTY_ID' => $propertyId]);
    while ($enum = $rs->Fetch()) {
        $label = mb_strtolower(trim((string)($enum['VALUE'] ?? '')));
        if ($label !== '' && mb_strpos($label, $needleLower) !== false) {
            $ids[] = (int)$enum['ID'];
        }
    }

    return array_values(array_unique($ids));
}

/**
 * @param list<int> $enumIds
 * @return list<int>
 */
function catalogListFindElementIdsByEnumIdsSql(int $iblockId, int $propertyId, array $enumIds): array
{
    if ($iblockId <= 0 || $propertyId <= 0 || $enumIds === []) {
        return [];
    }

    $connection = \Bitrix\Main\Application::getConnection();
    $ids = [];
    $enumSql = implode(',', array_map('intval', $enumIds));

    $queries = [];
    $singleTable = 'b_iblock_element_prop_s' . $iblockId;
    if ($connection->isTableExists($singleTable)) {
        $queries[] = '
            SELECT DISTINCT T.IBLOCK_ELEMENT_ID AS ID
            FROM ' . $singleTable . ' T
            INNER JOIN b_iblock_element BE ON BE.ID = T.IBLOCK_ELEMENT_ID
            WHERE T.IBLOCK_PROPERTY_ID = ' . $propertyId . '
              AND BE.IBLOCK_ID = ' . $iblockId . "
              AND BE.ACTIVE = 'Y'
              AND T.VALUE IN (" . $enumSql . ')
        ';
    }
    if ($connection->isTableExists('b_iblock_element_property')) {
        $queries[] = '
            SELECT DISTINCT BEP.IBLOCK_ELEMENT_ID AS ID
            FROM b_iblock_element_property BEP
            INNER JOIN b_iblock_element BE ON BE.ID = BEP.IBLOCK_ELEMENT_ID
            WHERE BEP.IBLOCK_PROPERTY_ID = ' . $propertyId . '
              AND BE.IBLOCK_ID = ' . $iblockId . "
              AND BE.ACTIVE = 'Y'
              AND BEP.VALUE IN (" . $enumSql . ')
        ';
    }

    foreach ($queries as $sql) {
        $result = $connection->query($sql);
        while ($row = $result->fetch()) {
            $id = (int)($row['ID'] ?? 0);
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
    }

    return array_values($ids);
}

/**
 * @param array<string|int, mixed> $filter
 * @return array<string|int, mixed>
 */
function catalogListStripColorPropertyConditionsFromFilter(array $filter): array
{
    $colorPropertyMap = array_fill_keys(catalogListGetColorFilterPropertyCodes(), true);
    $out = [];

    foreach ($filter as $key => $value) {
        if ($key === 'LOGIC' || $key === 'LOGIC_FILTER') {
            $out[$key] = $value;
            continue;
        }

        if ($key === 'OFFERS' && is_array($value)) {
            $strippedOffers = catalogListStripColorPropertyConditionsFromFilter($value);
            if ($strippedOffers !== []) {
                $out[$key] = $strippedOffers;
            }
            continue;
        }

        if (is_string($key) && catalogListResolveColorPropertyCodeFromFilterKey($key, $colorPropertyMap) !== null) {
            continue;
        }

        if (is_array($value) && catalogListIsNestedCatalogFilterArray($value)) {
            $nested = catalogListStripColorPropertyConditionsFromFilter($value);
            if ($nested !== []) {
                $out[$key] = $nested;
            }
            continue;
        }

        $out[$key] = $value;
    }

    return $out;
}

/**
 * @param array<string|int, mixed> $filter
 * @param list<int> $productIds
 * @return array<string|int, mixed>
 */
function catalogListMergeProductIdFilter(array $filter, array $productIds): array
{
    $filter['ID'] = $productIds === [] ? 0 : $productIds;

    return $filter;
}

if (!function_exists('buildBrandCatalogPropertyFilter')) {
    function buildBrandCatalogPropertyFilter(string $brandValue, string $brandPropertyCode = 'BRENDY_DLYA_WEB'): array
    {
        return catalogListBuildBrandPropertyFilter($brandValue, $brandPropertyCode);
    }
}

function catalogListFetchElementPropertyValue(int $elementId, int $iblockId, string $propertyCode): string
{
    if (!\Bitrix\Main\Loader::includeModule('iblock') || $elementId <= 0 || $iblockId <= 0 || $propertyCode === '') {
        return '';
    }

    $values = [];
    $propertyResult = \CIBlockElement::GetProperty(
        $iblockId,
        $elementId,
        'sort',
        'asc',
        ['CODE' => $propertyCode]
    );

    while ($property = $propertyResult->Fetch()) {
        $value = $property['VALUE_ENUM'] ?? ($property['~VALUE'] ?? ($property['VALUE'] ?? ''));
        if (is_array($value)) {
            $value = implode(', ', array_filter(array_map('strval', $value)));
        }

        $value = trim((string)$value);
        if ($value !== '') {
            $values[] = $value;
        }
    }

    return implode(', ', array_unique($values));
}

function catalogListGetCardDisplayPropertyCodes(): array
{
    return [
        'ARTIKUL_POSTAVSHCHIKA',
        'TSVET',
        'BRENDY_DLYA_WEB',
        'MATERIAL',
        'RAZMERY',
    ];
}

function catalogListGetPropertyDisplayName(string $propertyCode): string
{
    static $names = [
        'ARTIKUL_POSTAVSHCHIKA' => 'Артикул поставщика',
        'TSVET' => 'Цвет',
        'COLOR' => 'Цвет',
        'BRENDY_DLYA_WEB' => 'Бренд',
        'MATERIAL' => 'Материал',
        'RAZMERY' => 'Размеры',
    ];

    return $names[$propertyCode] ?? $propertyCode;
}

function catalogListLoadBrandPropertyValues(array $productIds, int $iblockId, string $brandPropertyCode = 'BRENDY_DLYA_WEB'): array
{
    $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
    if ($iblockId <= 0 || $productIds === []) {
        return [];
    }

    $brandByProductId = [];
    foreach ($productIds as $productId) {
        $brandValue = catalogListLoadProductPropertyValue($productId, $iblockId, $brandPropertyCode);
        if ($brandValue !== '') {
            $brandByProductId[$productId] = $brandValue;
        }
    }

    return $brandByProductId;
}

function catalogListItemHasProperty(array $item, string $propertyCode): bool
{
    $properties = is_array($item['PROPERTIES'] ?? null) ? $item['PROPERTIES'] : [];
    $property = $properties[$propertyCode] ?? null;
    if (!is_array($property)) {
        return false;
    }

    foreach (['VALUE', '~VALUE', 'VALUE_ENUM'] as $key) {
        $value = $property[$key] ?? null;
        if ($value === null || $value === '' || $value === false) {
            continue;
        }
        if (!is_array($value) || $value !== []) {
            return true;
        }
    }

    return false;
}

function catalogListItemHasBrandProperty(array $item, string $brandPropertyCode = 'BRENDY_DLYA_WEB'): bool
{
    return catalogListItemHasProperty($item, $brandPropertyCode);
}

function catalogListLoadProductPropertyValue(int $elementId, int $iblockId, string $propertyCode): string
{
    static $cache = [];

    $elementId = (int)$elementId;
    $iblockId = (int)$iblockId;
    if ($elementId <= 0 || $iblockId <= 0 || $propertyCode === '') {
        return '';
    }

    $cacheKey = $iblockId . ':' . $elementId . ':' . $propertyCode;
    if (!array_key_exists($cacheKey, $cache)) {
        $cache[$cacheKey] = catalogListFetchElementPropertyValue($elementId, $iblockId, $propertyCode);
    }

    return $cache[$cacheKey];
}

function catalogListResolveItemPropertyValue(array $item, string $propertyCode): string
{
    if (catalogListItemHasProperty($item, $propertyCode)) {
        $property = $item['PROPERTIES'][$propertyCode];
        $value = $property['VALUE_ENUM'] ?? ($property['~VALUE'] ?? ($property['VALUE'] ?? ''));
        if (is_array($value)) {
            $value = implode(', ', array_filter(array_map('strval', $value)));
        }

        $value = trim((string)$value);
        if ($value !== '') {
            return $value;
        }
    }

    $displayProperties = is_array($item['DISPLAY_PROPERTIES'] ?? null) ? $item['DISPLAY_PROPERTIES'] : [];
    if (!empty($displayProperties[$propertyCode])) {
        $displayProperty = $displayProperties[$propertyCode];
        $value = $displayProperty['DISPLAY_VALUE'] ?? ($displayProperty['VALUE'] ?? '');
        if (is_array($value)) {
            $value = implode(', ', array_filter(array_map('strval', $value)));
        }

        $value = trim((string)$value);
        if ($value !== '') {
            return $value;
        }
    }

    return catalogListLoadProductPropertyValue(
        (int)($item['ID'] ?? 0),
        (int)($item['IBLOCK_ID'] ?? 0),
        $propertyCode
    );
}

function catalogListResolveItemBrandValue(array $item, string $brandPropertyCode = 'BRENDY_DLYA_WEB'): string
{
    return catalogListResolveItemPropertyValue($item, $brandPropertyCode);
}

function catalogListEnrichItemsBrandProperty(array &$items, int $iblockId, string $brandPropertyCode = 'BRENDY_DLYA_WEB'): void
{
    if ($items === []) {
        return;
    }

    $missingProductIds = [];
    foreach ($items as $item) {
        $productId = (int)($item['ID'] ?? 0);
        if ($productId <= 0 || catalogListItemHasBrandProperty($item, $brandPropertyCode)) {
            continue;
        }
        $missingProductIds[] = $productId;
    }

    if ($missingProductIds === []) {
        return;
    }

    $brandByProductId = catalogListLoadBrandPropertyValues($missingProductIds, $iblockId, $brandPropertyCode);
    if ($brandByProductId === []) {
        return;
    }

    foreach ($items as $key => $item) {
        $productId = (int)($item['ID'] ?? 0);
        if ($productId <= 0 || empty($brandByProductId[$productId])) {
            continue;
        }

        if (!isset($items[$key]['PROPERTIES']) || !is_array($items[$key]['PROPERTIES'])) {
            $items[$key]['PROPERTIES'] = [];
        }

        $brandValue = $brandByProductId[$productId];
        $items[$key]['PROPERTIES'][$brandPropertyCode] = [
            'NAME' => 'Бренд',
            'CODE' => $brandPropertyCode,
            'VALUE' => $brandValue,
            '~VALUE' => $brandValue,
            'VALUE_ENUM' => $brandValue,
        ];
    }
}

function catalogListLoadProductBrandValue(int $productId, int $iblockId, string $brandPropertyCode = 'BRENDY_DLYA_WEB'): string
{
    return catalogListLoadProductPropertyValue($productId, $iblockId, $brandPropertyCode);
}

function catalogListFilterItemsByActiveBrand(array $items, string $expectedBrand, int $iblockId, string $brandPropertyCode = 'BRENDY_DLYA_WEB'): array
{
    $expectedBrand = trim($expectedBrand);
    if ($expectedBrand === '' || $items === []) {
        return $items;
    }

    $filteredItems = [];
    foreach ($items as $item) {
        $brandValue = catalogListResolveItemBrandValue($item, $brandPropertyCode);
        if ($brandValue === '' && $iblockId > 0) {
            $brandValue = catalogListLoadProductBrandValue(
                (int)($item['ID'] ?? 0),
                $iblockId,
                $brandPropertyCode
            );
        }

        if ($brandValue !== '' && strcasecmp($brandValue, $expectedBrand) === 0) {
            $filteredItems[] = $item;
        }
    }

    return $filteredItems;
}

function catalogListValueMatchesColorNeedles(string $value, array $needles): bool
{
    $valueLower = mb_strtolower(trim($value));
    if ($valueLower === '' || $needles === []) {
        return false;
    }

    foreach ($needles as $needle) {
        $needleLower = mb_strtolower(catalogListResolveColorFilterNeedle($needle));
        if ($needleLower !== '' && mb_strpos($valueLower, $needleLower) !== false) {
            return true;
        }
    }

    return false;
}

function catalogListOfferMatchesColorNeedles(array $offer, array $item, array $needles): bool
{
    if ($needles === []) {
        return false;
    }

    foreach (catalogListGetColorFilterPropertyCodes() as $propertyCode) {
        $value = catalogListResolveItemPropertyValue($offer, $propertyCode);
        if ($value === '') {
            $value = catalogListResolveItemPropertyValue($item, $propertyCode);
        }

        if (catalogListValueMatchesColorNeedles($value, $needles)) {
            return true;
        }
    }

    return false;
}

function catalogListFindOfferIndexByActiveColorFilter(array $item): int
{
    $needles = catalogListGetActiveColorFilterNeedles();
    if ($needles === [] || empty($item['OFFERS']) || !is_array($item['OFFERS'])) {
        return 0;
    }

    foreach ($item['OFFERS'] as $index => $offer) {
        if (!is_array($offer)) {
            continue;
        }

        if (catalogListOfferMatchesColorNeedles($offer, $item, $needles)) {
            return (int)$index;
        }
    }

    return 0;
}

function catalogListReorderItemOffersForActiveColorFilter(array &$item): void
{
    if (catalogListGetActiveColorFilterNeedles() === [] || empty($item['OFFERS']) || !is_array($item['OFFERS'])) {
        return;
    }

    $index = catalogListFindOfferIndexByActiveColorFilter($item);
    if ($index <= 0) {
        return;
    }

    $selectedOffer = $item['OFFERS'][$index];
    unset($item['OFFERS'][$index]);
    $item['OFFERS'] = array_values(array_merge([$selectedOffer], $item['OFFERS']));
}

/**
 * @param array<int, array<string, mixed>> $items
 */
function catalogListReorderItemsOffersForActiveColorFilter(array &$items): void
{
    if (catalogListGetActiveColorFilterNeedles() === [] || $items === []) {
        return;
    }

    foreach ($items as $key => $item) {
        if (!is_array($item)) {
            continue;
        }

        catalogListReorderItemOffersForActiveColorFilter($items[$key]);
    }
}

function catalogItemBuildOfferDisplayProperties(array $item, array $offer, ?array $propertyCodes = null): array
{
    $displayProperties = is_array($offer['DISPLAY_PROPERTIES'] ?? null) ? $offer['DISPLAY_PROPERTIES'] : [];
    $propertyCodes = $propertyCodes ?? catalogListGetCardDisplayPropertyCodes();

    foreach ($propertyCodes as $propertyCode) {
        $propertyCode = (string)$propertyCode;
        if ($propertyCode === '') {
            continue;
        }

        if (!empty($displayProperties[$propertyCode])) {
            continue;
        }

        $propertyValue = catalogListResolveItemPropertyValue($offer, $propertyCode);
        if ($propertyValue === '') {
            $propertyValue = catalogListResolveItemPropertyValue($item, $propertyCode);
        }

        if ($propertyValue === '') {
            continue;
        }

        $displayProperties[$propertyCode] = [
            'CODE' => $propertyCode,
            'NAME' => catalogListGetPropertyDisplayName($propertyCode),
            'VALUE' => $propertyValue,
            'DISPLAY_VALUE' => $propertyValue,
        ];
    }

    return $displayProperties;
}

function catalogItemMergeOfferDisplayProperties(array $item, array $offer, string $brandPropertyCode = 'BRENDY_DLYA_WEB'): array
{
    return catalogItemBuildOfferDisplayProperties($item, $offer);
}
