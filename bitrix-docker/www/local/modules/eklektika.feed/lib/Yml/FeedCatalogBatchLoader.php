<?php

declare(strict_types=1);

namespace OnlineService\Feed\Yml;

use Bitrix\Iblock\ElementPropertyTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Loader;
use OnlineService\Feed\Config\FeedConfig;

/**
 * Пакетная загрузка данных каталога для YML-фида (без N+1).
 * Цены и наличие — через SQL, без модуля catalog (обход sproduction.integration в CLI).
 */
final class FeedCatalogBatchLoader
{
    private const CHUNK_SIZE = 1000;

    /** @var array<int, string> */
    private array $fileSrcCache = [];

    public function assertModules(): void
    {
        if (!Loader::includeModule('iblock')) {
            throw new \RuntimeException('Required Bitrix module iblock is not available.');
        }
    }

    /**
     * Потоковая выборка офферов чанками (без загрузки всего каталога в память).
     *
     * @param callable(list<array<string, mixed>>): void $consumer
     */
    public function iterateOfferChunks(int $chunkSize, callable $consumer): int
    {
        $total = 0;
        $lastId = 0;

        while (true) {
            $chunk = $this->loadOffersChunk($lastId, $chunkSize);
            if ($chunk === []) {
                break;
            }

            $consumer($chunk);
            $total += count($chunk);
            $lastId = (int)($chunk[array_key_last($chunk)]['ID'] ?? $lastId);
        }

        return $total;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadOffersChunk(int $lastId, int $chunkSize): array
    {
        $filter = [
            'IBLOCK_ID' => FeedConfig::OFFERS_IBLOCK_ID,
            'ACTIVE' => 'Y',
            'ACTIVE_DATE' => 'Y',
        ];
        if ($lastId > 0) {
            $filter['>ID'] = $lastId;
        }

        $chunk = [];
        $offerRes = \CIBlockElement::GetList(
            ['ID' => 'ASC'],
            $filter,
            false,
            ['nTopCount' => $chunkSize],
            [
                'ID',
                'IBLOCK_ID',
                'NAME',
                'PREVIEW_PICTURE',
                'DETAIL_PICTURE',
                'PROPERTY_CML2_LINK',
                'PROPERTY_CML2_ARTICLE',
                'PROPERTY_TSVET',
                'PROPERTY_MATERIAL',
            ]
        );

        while ($offer = $offerRes->Fetch()) {
            $chunk[] = $offer;
        }

        return $chunk;
    }

    /**
     * @param list<int> $offerIds
     * @return array<int, int> offerId => productId
     */
    public function loadOfferProductLinkMap(array $offerIds): array
    {
        $offerIds = array_values(array_unique(array_filter(array_map('intval', $offerIds))));
        if ($offerIds === []) {
            return [];
        }

        $propertyMap = $this->loadPropertyCodeMap(FeedConfig::OFFERS_IBLOCK_ID, ['CML2_LINK']);
        if ($propertyMap === []) {
            return [];
        }

        $propertyId = (int)array_key_first($propertyMap);
        if ($propertyId <= 0) {
            return [];
        }

        $result = [];
        foreach (array_chunk($offerIds, self::CHUNK_SIZE) as $chunk) {
            $rows = ElementPropertyTable::getList([
                'select' => ['IBLOCK_ELEMENT_ID', 'VALUE'],
                'filter' => [
                    'IBLOCK_ELEMENT_ID' => $chunk,
                    'IBLOCK_PROPERTY_ID' => $propertyId,
                ],
            ])->fetchAll();

            foreach ($rows as $row) {
                $offerId = (int)($row['IBLOCK_ELEMENT_ID'] ?? 0);
                $productId = (int)($row['VALUE'] ?? 0);
                if ($offerId > 0 && $productId > 0) {
                    $result[$offerId] = $productId;
                }
            }
        }

        return $result;
    }

    /**
     * @param list<int> $productIds
     * @return array<int, array<string, mixed>>
     */
    public function loadProductsByIds(array $productIds): array
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if ($productIds === []) {
            return [];
        }

        $products = [];
        $fileIds = [];
        $previewDetailByProduct = [];

        foreach (array_chunk($productIds, self::CHUNK_SIZE) as $chunk) {
            $elementRes = \CIBlockElement::GetList(
                ['ID' => 'ASC'],
                [
                    'IBLOCK_ID' => FeedConfig::CATALOG_IBLOCK_ID,
                    'ACTIVE' => 'Y',
                    'ACTIVE_DATE' => 'Y',
                    'ID' => $chunk,
                ],
                false,
                false,
                [
                    'ID',
                    'NAME',
                    'CODE',
                    'IBLOCK_ID',
                    'IBLOCK_SECTION_ID',
                    'DETAIL_PAGE_URL',
                    'PREVIEW_PICTURE',
                    'DETAIL_PICTURE',
                    'PROPERTY_BRENDY_DLYA_WEB',
                    'PROPERTY_TSVET',
                    'PROPERTY_MATERIAL',
                ]
            );

            while ($element = $elementRes->Fetch()) {
                $id = (int)($element['ID'] ?? 0);
                if ($id <= 0) {
                    continue;
                }

                $previewDetailByProduct[$id] = [
                    'PREVIEW_PICTURE' => (int)($element['PREVIEW_PICTURE'] ?? 0),
                    'DETAIL_PICTURE' => (int)($element['DETAIL_PICTURE'] ?? 0),
                ];
                foreach (['PREVIEW_PICTURE', 'DETAIL_PICTURE'] as $field) {
                    $fileId = (int)($element[$field] ?? 0);
                    if ($fileId > 0) {
                        $fileIds[] = $fileId;
                    }
                }

                $products[$id] = [
                    'ID' => $id,
                    'NAME' => trim((string)($element['NAME'] ?? '')),
                    'SECTION_ID' => (int)($element['IBLOCK_SECTION_ID'] ?? 0),
                    'DETAIL_PAGE_URL' => self::resolveDetailPageUrl($element),
                    'DESCRIPTION' => '',
                    'PICTURES' => [],
                    'BRAND' => self::extractPropertyDisplayValue(
                        $element['PROPERTY_' . FeedConfig::BRAND_PROPERTY_CODE . '_VALUE'] ?? ''
                    ),
                    'COLOR' => self::extractPropertyDisplayValue(
                        $element['PROPERTY_' . FeedConfig::COLOR_PROPERTY_CODE . '_VALUE'] ?? ''
                    ),
                    'MATERIAL' => self::extractPropertyDisplayValue(
                        $element['PROPERTY_' . FeedConfig::MATERIAL_PROPERTY_CODE . '_VALUE'] ?? ''
                    ),
                ];
            }
        }

        if ($products === []) {
            return $products;
        }

        $loadedIds = array_keys($products);
        $sectionByProduct = $this->loadPrimarySectionIds($loadedIds);
        foreach ($products as $id => &$product) {
            if ((int)($product['SECTION_ID'] ?? 0) <= 0) {
                $product['SECTION_ID'] = (int)($sectionByProduct[$id] ?? 0);
            }
        }
        unset($product);

        $descriptions = $this->loadProductDescriptionsByIds($loadedIds);
        foreach ($descriptions as $productId => $description) {
            if (isset($products[$productId])) {
                $products[$productId]['DESCRIPTION'] = $description;
            }
        }

        $filePropertyMap = $this->loadFilePropertyMap(
            FeedConfig::CATALOG_IBLOCK_ID,
            $loadedIds,
            ['MORE_PHOTO', 'PHOTOS']
        );

        foreach ($filePropertyMap as $props) {
            foreach ($props as $codeFiles) {
                foreach ($codeFiles as $fileId) {
                    $fileIds[] = (int)$fileId;
                }
            }
        }

        $fileSrcMap = $this->resolveFileSrcMap($fileIds);

        foreach ($products as $id => &$product) {
            $srcList = [];
            foreach (['PREVIEW_PICTURE', 'DETAIL_PICTURE'] as $field) {
                $fileId = (int)($previewDetailByProduct[$id][$field] ?? 0);
                if ($fileId > 0 && isset($fileSrcMap[$fileId])) {
                    $srcList[] = $fileSrcMap[$fileId];
                }
            }
            foreach ($filePropertyMap[$id] ?? [] as $codeFiles) {
                foreach ($codeFiles as $fileId) {
                    if (isset($fileSrcMap[$fileId])) {
                        $srcList[] = $fileSrcMap[$fileId];
                    }
                }
            }
            $product['PICTURES'] = array_values(array_unique($srcList));
        }
        unset($product);

        return $products;
    }

    /**
     * @param list<int> $productIds
     * @return array<int, string>
     */
    public function loadProductDescriptionsByIds(array $productIds): array
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if ($productIds === []) {
            return [];
        }

        $descriptions = [];
        foreach (array_chunk($productIds, self::CHUNK_SIZE) as $chunk) {
            $elementRes = \CIBlockElement::GetList(
                ['ID' => 'ASC'],
                [
                    'IBLOCK_ID' => FeedConfig::CATALOG_IBLOCK_ID,
                    'ID' => $chunk,
                ],
                false,
                false,
                ['ID', 'PREVIEW_TEXT', 'DETAIL_TEXT']
            );

            while ($element = $elementRes->Fetch()) {
                $id = (int)($element['ID'] ?? 0);
                if ($id <= 0) {
                    continue;
                }
                $descriptions[$id] = self::normalizeDescription(
                    (string)($element['DETAIL_TEXT'] ?? ''),
                    (string)($element['PREVIEW_TEXT'] ?? '')
                );
            }
        }

        return $descriptions;
    }

    public static function normalizeDescription(string $detailText, string $previewText): string
    {
        $text = trim(strip_tags($detailText !== '' ? $detailText : $previewText));
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    /**
     * @return array<int, list<array{PRICE: float, CURRENCY: string, CATALOG_GROUP_ID: int}>>
     */
    public function loadPricesForProductIds(array $productIds): array
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if ($productIds === []) {
            return [];
        }

        $db = $this->getDbConnection();
        $result = [];

        foreach (array_chunk($productIds, self::CHUNK_SIZE) as $chunk) {
            $ids = implode(',', $chunk);
            $query = '
                SELECT PRODUCT_ID, PRICE, CURRENCY, CATALOG_GROUP_ID
                FROM b_catalog_price
                WHERE PRODUCT_ID IN (' . $ids . ')
            ';
            $rs = $db->Query($query);
            while ($row = $rs->Fetch()) {
                $productId = (int)($row['PRODUCT_ID'] ?? 0);
                if ($productId <= 0) {
                    continue;
                }
                $result[$productId][] = [
                    'PRICE' => (float)($row['PRICE'] ?? 0),
                    'CURRENCY' => (string)($row['CURRENCY'] ?? 'RUB'),
                    'CATALOG_GROUP_ID' => (int)($row['CATALOG_GROUP_ID'] ?? 0),
                ];
            }
        }

        return $result;
    }

    /**
     * @return array<int, array{AVAILABLE: string, QUANTITY: float, QUANTITY_TRACE: string}>
     */
    public function loadCatalogProducts(array $productIds): array
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if ($productIds === []) {
            return [];
        }

        $db = $this->getDbConnection();
        $result = [];

        foreach (array_chunk($productIds, self::CHUNK_SIZE) as $chunk) {
            $ids = implode(',', $chunk);
            $query = '
                SELECT ID, AVAILABLE, QUANTITY, QUANTITY_TRACE
                FROM b_catalog_product
                WHERE ID IN (' . $ids . ')
            ';
            $rs = $db->Query($query);
            while ($row = $rs->Fetch()) {
                $id = (int)($row['ID'] ?? 0);
                if ($id <= 0) {
                    continue;
                }
                $result[$id] = [
                    'AVAILABLE' => (string)($row['AVAILABLE'] ?? 'N'),
                    'QUANTITY' => (float)($row['QUANTITY'] ?? 0),
                    'QUANTITY_TRACE' => (string)($row['QUANTITY_TRACE'] ?? 'N'),
                ];
            }
        }

        return $result;
    }

    /**
     * @param list<int> $elementIds
     * @param list<string> $codes
     * @return array<int, array<string, list<int>>>
     */
    public function loadFilePropertyMap(int $iblockId, array $elementIds, array $codes): array
    {
        $elementIds = array_values(array_unique(array_filter(array_map('intval', $elementIds))));
        $codes = array_values(array_filter(array_map('strval', $codes)));
        if ($elementIds === [] || $codes === []) {
            return [];
        }

        $propertyMap = $this->loadPropertyCodeMap($iblockId, $codes);
        if ($propertyMap === []) {
            return [];
        }

        $propertyIds = array_keys($propertyMap);
        $result = [];

        foreach (array_chunk($elementIds, self::CHUNK_SIZE) as $chunk) {
            $rows = ElementPropertyTable::getList([
                'select' => ['IBLOCK_ELEMENT_ID', 'IBLOCK_PROPERTY_ID', 'VALUE'],
                'filter' => [
                    'IBLOCK_ELEMENT_ID' => $chunk,
                    'IBLOCK_PROPERTY_ID' => $propertyIds,
                ],
            ])->fetchAll();

            foreach ($rows as $row) {
                $elementId = (int)$row['IBLOCK_ELEMENT_ID'];
                $propertyId = (int)$row['IBLOCK_PROPERTY_ID'];
                $fileId = (int)$row['VALUE'];
                $code = $propertyMap[$propertyId] ?? '';
                if ($elementId <= 0 || $code === '' || $fileId <= 0) {
                    continue;
                }
                $result[$elementId][$code][] = $fileId;
            }
        }

        return $result;
    }

    /**
     * @param list<int> $fileIds
     * @return array<int, string>
     */
    public function resolveFileSrcMap(array $fileIds): array
    {
        $missing = [];
        foreach ($fileIds as $fileId) {
            $fileId = (int)$fileId;
            if ($fileId <= 0) {
                continue;
            }
            if (!isset($this->fileSrcCache[$fileId])) {
                $missing[] = $fileId;
            }
        }

        $missing = array_values(array_unique($missing));
        if ($missing !== []) {
            $this->loadFileSrcFromDatabase($missing);

            foreach ($missing as $fileId) {
                if (isset($this->fileSrcCache[$fileId])) {
                    continue;
                }
                $src = trim((string)\CFile::GetPath($fileId));
                if ($src !== '') {
                    $this->fileSrcCache[$fileId] = $src;
                }
            }
        }

        $map = [];
        foreach ($fileIds as $fileId) {
            $fileId = (int)$fileId;
            if ($fileId > 0 && isset($this->fileSrcCache[$fileId])) {
                $map[$fileId] = $this->fileSrcCache[$fileId];
            }
        }

        return $map;
    }

    /**
     * @param list<int> $fileIds
     */
    private function loadFileSrcFromDatabase(array $fileIds): void
    {
        $fileIds = array_values(array_unique(array_filter(array_map('intval', $fileIds))));
        if ($fileIds === []) {
            return;
        }

        $db = $this->getDbConnection();
        foreach (array_chunk($fileIds, self::CHUNK_SIZE) as $chunk) {
            $ids = implode(',', $chunk);
            $query = '
                SELECT ID, SUBDIR, FILE_NAME
                FROM b_file
                WHERE ID IN (' . $ids . ')
            ';
            $rs = $db->Query($query);
            while ($row = $rs->Fetch()) {
                $id = (int)($row['ID'] ?? 0);
                $src = self::buildFileSrc(
                    (string)($row['SUBDIR'] ?? ''),
                    (string)($row['FILE_NAME'] ?? '')
                );
                if ($id > 0 && $src !== '') {
                    $this->fileSrcCache[$id] = $src;
                }
            }
        }
    }

    public static function buildFileSrc(string $subdir, string $fileName): string
    {
        $fileName = trim($fileName);
        if ($fileName === '') {
            return '';
        }

        $subdir = trim(str_replace('\\', '/', $subdir), '/');
        if ($subdir !== '') {
            return '/' . $subdir . '/' . $fileName;
        }

        return '/' . $fileName;
    }

    public function isAvailable(array $catalogProduct): bool
    {
        if (($catalogProduct['AVAILABLE'] ?? 'N') !== 'Y') {
            return false;
        }

        $quantity = (float)($catalogProduct['QUANTITY'] ?? 0);
        $quantityTrace = ($catalogProduct['QUANTITY_TRACE'] ?? 'N') === 'Y';

        return !($quantityTrace && $quantity <= 0);
    }

    /**
     * @param list<string> $codes
     * @return array<int, string>
     */
    private function loadPropertyCodeMap(int $iblockId, array $codes): array
    {
        $rows = PropertyTable::getList([
            'select' => ['ID', 'CODE'],
            'filter' => [
                'IBLOCK_ID' => $iblockId,
                'CODE' => $codes,
            ],
        ])->fetchAll();

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['ID']] = (string)$row['CODE'];
        }

        return $map;
    }

    /**
     * @param list<int> $productIds
     * @return array<int, int>
     */
    public function loadPrimarySectionIds(array $productIds): array
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if ($productIds === []) {
            return [];
        }

        $db = $this->getDbConnection();
        $result = [];

        foreach (array_chunk($productIds, self::CHUNK_SIZE) as $chunk) {
            $ids = implode(',', $chunk);
            $query = '
                SELECT IBLOCK_ELEMENT_ID, IBLOCK_SECTION_ID
                FROM b_iblock_section_element
                WHERE IBLOCK_ELEMENT_ID IN (' . $ids . ')
                ORDER BY IBLOCK_ELEMENT_ID ASC, IBLOCK_SECTION_ID ASC
            ';
            $rs = $db->Query($query);
            while ($row = $rs->Fetch()) {
                $elementId = (int)($row['IBLOCK_ELEMENT_ID'] ?? 0);
                $sectionId = (int)($row['IBLOCK_SECTION_ID'] ?? 0);
                if ($elementId > 0 && $sectionId > 0 && !isset($result[$elementId])) {
                    $result[$elementId] = $sectionId;
                }
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $element
     */
    public static function resolveDetailPageUrl(array $element): string
    {
        $url = trim((string)($element['DETAIL_PAGE_URL'] ?? ''));
        if ($url !== '' && strpos($url, '#') === false) {
            return self::normalizeSiteDirInUrl($url);
        }

        static $detailUrlTemplate = null;
        if ($detailUrlTemplate === null) {
            $iblock = \CIBlock::GetArrayByID(FeedConfig::CATALOG_IBLOCK_ID);
            $detailUrlTemplate = (string)($iblock['DETAIL_PAGE_URL'] ?? '/catalog/#SECTION_CODE_PATH#/#ELEMENT_CODE#/');
        }

        $template = $url !== '' ? $url : $detailUrlTemplate;
        $elementId = (int)($element['ID'] ?? 0);
        $elementCode = trim((string)($element['CODE'] ?? ''));
        $sectionId = (int)($element['IBLOCK_SECTION_ID'] ?? 0);
        $sectionCodePath = self::resolveSectionCodePath($sectionId);
        $sectionCode = $sectionCodePath !== '' ? basename(str_replace('\\', '/', $sectionCodePath)) : '';

        $built = strtr($template, [
            '#SITE_DIR#' => defined('SITE_DIR') ? (string)SITE_DIR : '/',
            '#SERVER_NAME#' => defined('SITE_SERVER_NAME') ? (string)SITE_SERVER_NAME : '',
            '#ELEMENT_ID#' => (string)$elementId,
            '#ELEMENT_CODE#' => $elementCode,
            '#SECTION_ID#' => (string)$sectionId,
            '#SECTION_CODE#' => $sectionCode,
            '#SECTION_CODE_PATH#' => $sectionCodePath,
        ]);

        if ($sectionCodePath === '') {
            $built = preg_replace('#(/catalog)/+/#', '$1/', $built) ?? $built;
        }

        $built = self::normalizeSiteDirInUrl($built);
        if ($built !== '' && strpos($built, '#') === false) {
            return $built;
        }

        if ($elementCode === '') {
            return '';
        }

        if ($sectionCodePath !== '') {
            return self::normalizeSiteDirInUrl('/catalog/' . $sectionCodePath . '/' . $elementCode . '/');
        }

        return self::normalizeSiteDirInUrl('/catalog/' . $elementCode . '/');
    }

    public static function resolveSectionCodePath(int $sectionId): string
    {
        if ($sectionId <= 0) {
            return '';
        }

        static $cache = [];
        if (isset($cache[$sectionId])) {
            return $cache[$sectionId];
        }

        $codes = [];
        $sectionRes = \CIBlockSection::GetNavChain(
            FeedConfig::CATALOG_IBLOCK_ID,
            $sectionId,
            ['ID', 'CODE']
        );
        while ($section = $sectionRes->GetNext()) {
            $code = trim((string)($section['CODE'] ?? ''));
            if ($code !== '') {
                $codes[] = $code;
            }
        }

        $cache[$sectionId] = implode('/', $codes);

        return $cache[$sectionId];
    }

    public static function normalizeSiteDirInUrl(string $url): string
    {
        $siteDir = defined('SITE_DIR') ? (string)SITE_DIR : '/';
        $url = str_replace('#SITE_DIR#', $siteDir !== '' ? $siteDir : '/', $url);

        return preg_replace('#/+#', '/', $url) ?? $url;
    }

    private function getDbConnection(): \CDatabase
    {
        global $DB;
        if (!($DB instanceof \CDatabase)) {
            throw new \RuntimeException('Database connection is not available.');
        }

        return $DB;
    }

    /**
     * @param list<string> $values
     */
    public static function joinPropertyValues(array $values): string
    {
        $values = array_values(array_unique(array_filter(array_map(
            static fn($v): string => trim((string)$v),
            $values
        ))));

        return implode(', ', $values);
    }

    /**
     * @param mixed $propertyValue
     */
    public static function extractPropertyDisplayValue($propertyValue): string
    {
        if (is_array($propertyValue)) {
            if (isset($propertyValue['VALUE'])) {
                return trim((string)$propertyValue['VALUE']);
            }

            return self::joinPropertyValues($propertyValue);
        }

        return trim((string)$propertyValue);
    }
}
