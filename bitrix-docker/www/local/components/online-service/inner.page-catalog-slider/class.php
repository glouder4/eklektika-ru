<?php
namespace OnlineService\SalesHit;

use Bitrix\Main\Loader;
use Bitrix\Catalog\PriceTable;

class PageCatalogSlider extends \CBitrixComponent
{
    private const NO_PHOTO_PATH = '/local/components/online-service/inner.page-catalog-slider/images/no_photo.png';

    private $offersIblockId = null;

    public function onPrepareComponentParams($params)
    {
        $params['IBLOCK_ID'] = (int)($params['IBLOCK_ID'] ?? 0);
        $params['SECTION_ID'] = trim((string)($params['SECTION_ID'] ?? '')); // может быть ID или символьный код
        $params['PROPERTY_CODE'] = (array)($params['PROPERTY_CODE'] ?? []);
        $params['OFFER_PROPERTY_CODE'] = (array)($params['OFFER_PROPERTY_CODE'] ?? []);
        $params['FILTER_OFFER_PROPERTY'] = trim((string)($params['FILTER_OFFER_PROPERTY'] ?? ''));
        $params['FILTER_OFFER_VALUE'] = trim((string)($params['FILTER_OFFER_VALUE'] ?? ''));
        $params['ELEMENT_COUNT'] = (int)($params['ELEMENT_COUNT'] ?? 10);
        $params['CACHE_TIME'] = isset($params['CACHE_TIME']) ? (int)$params['CACHE_TIME'] : 3600;
        $params['PRICE_AD_GROUP_ID'] = (int)($params['PRICE_AD_GROUP_ID'] ?? 3); // Рекламная цена по умолчанию
        return $params;
    }

    public function executeComponent()
    {
        if (!Loader::includeModule('iblock') || !Loader::includeModule('catalog')) {
            ShowError('Требуемые модули не установлены.');
            return;
        }

        if ($this->arParams['IBLOCK_ID'] <= 0) {
            ShowError('Не указан ID инфоблока.');
            return;
        }

        $this->determineOffersIblock();
        if (!$this->offersIblockId) {
            ShowError('Не найден инфоблок торговых предложений.');
            return;
        }

        $this->arResult['ITEMS'] = $this->loadItems();
        $this->includeComponentTemplate();

        global $APPLICATION;
        $templateFolder = $this->getTemplate()->getFolder();
        $APPLICATION->AddHeadScript($templateFolder . '/script.js');
    }

    private function determineOffersIblock()
    {
        $this->offersIblockId = null;
        if (class_exists('CCatalogSku')) {
            $skuInfo = \CCatalogSku::GetInfoByProductIBlock($this->arParams['IBLOCK_ID']);
            $this->offersIblockId = $skuInfo['IBLOCK_ID'] ?? null;
        }
    }

    // === Основной метод — координатор ===
    private function loadItems()
    {
        $cacheKey = $this->buildCacheKey();
        if ($this->readFromCache($cacheKey)) {
            return $this->arResult['ITEMS'];
        }

        $productToOffers = $this->findProductToOffersMap();
        if (empty($productToOffers)) {
            $this->writeToCache($cacheKey, []);
            return [];
        }

        // === Фильтрация по разделу (если задан) ===
        $productIds = array_keys($productToOffers);

        if (!empty($this->arParams['SECTION_ID'])) {
            $productIds = $this->filterProductIdsBySection($productIds, $this->arParams['SECTION_ID']);
            if (empty($productIds)) {
                $this->writeToCache($cacheKey, []);
                return [];
            }
        }

        $productIds = array_slice($productIds, 0, $this->arParams['ELEMENT_COUNT']);
        $items = $this->loadProducts($productIds);
        $items = $this->attachOffersToProducts($items, $productToOffers);

        $this->writeToCache($cacheKey, $items);
        return $items;
    }

    private function filterProductIdsBySection(array $productIds, string $sectionId): array
    {
        // Определяем, число это или строка (символьный код)
        $isNumeric = ctype_digit($sectionId);

        $filteredIds = [];
        $dbItems = \CIBlockElement::GetList(
            [],
            [
                'ID' => $productIds,
                'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
                'ACTIVE' => 'Y',
                $isNumeric ? 'IBLOCK_SECTION_ID' : 'IBLOCK_SECTION_CODE' => $sectionId
            ],
            false,
            false,
            ['ID']
        );

        while ($item = $dbItems->GetNext()) {
            $filteredIds[] = (int)$item['ID'];
        }

        return $filteredIds;
    }

    // === 1. Кэш ===
    private function buildCacheKey()
    {
        return md5(serialize([
            'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
            'SECTION_ID' => $this->arParams['SECTION_ID'],
            'OFFERS_IBLOCK_ID' => $this->offersIblockId,
            'FILTER_PROP' => $this->arParams['FILTER_OFFER_PROPERTY'],
            'FILTER_VAL' => $this->arParams['FILTER_OFFER_VALUE'],
            'OFFER_PROPS' => $this->arParams['OFFER_PROPERTY_CODE'],
            'ITEM_PROPS' => $this->arParams['PROPERTY_CODE'],
            'COUNT' => $this->arParams['ELEMENT_COUNT'],
            'PRICE_AD_GROUP_ID' => $this->arParams['PRICE_AD_GROUP_ID'] ?? 3,
        ]));
    }

    private function readFromCache($cacheKey)
    {
        global $CACHE_MANAGER;
        if ($this->arParams['CACHE_TIME'] > 0 && $CACHE_MANAGER->Read($this->arParams['CACHE_TIME'], $cacheKey)) {
            $this->arResult['ITEMS'] = $CACHE_MANAGER->Get($cacheKey);
            return true;
        }
        return false;
    }

    private function writeToCache($cacheKey, $data)
    {
        global $CACHE_MANAGER;
        if ($this->arParams['CACHE_TIME'] > 0) {
            $CACHE_MANAGER->Set($cacheKey, $data);
        }
    }

    // === 2. Найти соответствие товар ↔ предложения (по фильтру) ===
    private function findProductToOffersMap()
    {
        $offerFilter = [
            'IBLOCK_ID' => $this->offersIblockId,
            'ACTIVE' => 'Y'
        ];

        if (!empty($this->arParams['FILTER_OFFER_PROPERTY']) && !empty($this->arParams['FILTER_OFFER_VALUE'])) {
            $propCode = trim($this->arParams['FILTER_OFFER_PROPERTY']);
            $offerFilter['PROPERTY_' . $propCode] = $this->arParams['FILTER_OFFER_VALUE'];
        }

        $productToOffers = [];
        $dbOffers = \CIBlockElement::GetList(
            [],
            $offerFilter,
            false,
            ['nTopCount' => 1000],
            ['ID', 'PROPERTY_CML2_LINK']
        );

        while ($offer = $dbOffers->GetNext()) {
            $productId = (int)($offer['PROPERTY_CML2_LINK_VALUE'] ?? 0);
            if ($productId > 0) {
                if (!isset($productToOffers[$productId])) {
                    $productToOffers[$productId] = [];
                }
                $productToOffers[$productId][] = (int)$offer['ID'];
            }
        }

        return $productToOffers;
    }

    // === 3. Загрузить товары ===
    private function loadProducts(array $productIds)
    {
        if (empty($productIds)) return [];

        $arSelect = ['ID', 'NAME', 'CODE', 'DETAIL_PAGE_URL', 'PREVIEW_PICTURE'];
        foreach ($this->arParams['PROPERTY_CODE'] as $code) {
            $code = trim($code);
            if ($code !== '') {
                $arSelect[] = 'PROPERTY_' . $code;
            }
        }

        $items = [];
        $itemIndex = [];
        $dbItems = \CIBlockElement::GetList(
            ['SORT' => 'ASC'],
            [
                'ID' => $productIds,
                'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
                'ACTIVE' => 'Y'
            ],
            false,
            false,
            $arSelect
        );

        while ($item = $dbItems->GetNext()) {
            $item['PROPERTIES'] = $this->extractProperties($item, $this->arParams['PROPERTY_CODE']);
            $items[] = $item;
            $itemIndex[$item['ID']] = count($items) - 1;
        }

        // После загрузки товаров
        foreach ($items as &$item) {
            $item['PREVIEW_PICTURE_URL'] = !empty($item['PREVIEW_PICTURE'])
                ? \CFile::GetPath($item['PREVIEW_PICTURE'])
                : self::NO_PHOTO_PATH;
        }
        unset($item);

        return $items;
    }

    // === 4. Подгрузить предложения и прикрепить к товарам ===
    private function attachOffersToProducts(array $items, array $productToOffers): array
    {
        $offerIds = $this->collectOfferIds($productToOffers);
        if (empty($offerIds)) {
            return $items;
        }

        $itemById = $this->indexItemsById($items);
        $offersById = $this->loadOffersWithBaseData($offerIds);

        if ($this->arParams['OFFER_GET_PRICES'] === 'Y') {
            $this->enrichOffersWithPrices($offersById);
            $this->enrichOffersWithFinalPrice($offersById);
        }

        $this->enrichOffersWithRealQuantity($offersById);
        $this->attachOffersToItems($offersById, $itemById);

        return $items;
    }

// === 1. Собрать ID предложений ===
    private function collectOfferIds(array $productToOffers): array
    {
        $offerIds = [];
        foreach ($productToOffers as $offers) {
            $offerIds = array_merge($offerIds, $offers);
        }
        return array_unique($offerIds);
    }

// === 2. Индексировать товары по ID ===
    private function indexItemsById(array &$items): array
    {
        $indexed = [];
        foreach ($items as &$item) {
            $indexed[$item['ID']] = &$item;
        }
        unset($item);
        return $indexed;
    }

// === 3. Загрузить предложения с базовыми данными ===
    private function loadOffersWithBaseData(array $offerIds): array
    {
        // === Загружаем названия свойств инфоблока предложений ===
        $offerPropertyNames = [];
        $dbProps = \CIBlockProperty::GetList(
            ['SORT' => 'ASC'],
            ['IBLOCK_ID' => $this->offersIblockId, 'ACTIVE' => 'Y']
        );
        while ($prop = $dbProps->Fetch()) {
            if (!empty($prop['CODE'])) {
                $offerPropertyNames[$prop['CODE']] = $prop['NAME'];
            }
        }

        // === Формируем выборку ===
        $arOfferSelect = $this->buildOfferSelectFields();

        $dbOffers = \CIBlockElement::GetList(
            [],
            [
                'ID' => $offerIds,
                'IBLOCK_ID' => $this->offersIblockId,
                'ACTIVE' => 'Y'
            ],
            false,
            false,
            $arOfferSelect
        );

        $offersById = [];
        while ($offer = $dbOffers->GetNext()) {
            $offerId = (int)$offer['ID'];

            // Картинка
            $offer['PREVIEW_PICTURE_URL'] = !empty($offer['PREVIEW_PICTURE'])
                ? \CFile::GetPath($offer['PREVIEW_PICTURE'])
                : self::NO_PHOTO_PATH;

            // Каталоговые поля
            $offer['CATALOG_QUANTITY'] = $offer['CATALOG_QUANTITY'] ?? 0;
            $offer['CATALOG_AVAILABLE'] = $offer['CATALOG_AVAILABLE'] ?? 'N';

            // Свойства с NAME
            $offer['PROPERTIES'] = $this->extractProperties(
                $offer,
                $this->arParams['OFFER_PROPERTY_CODE'],
                $offerPropertyNames
            );

            $offersById[$offerId] = $offer;
        }

        return $offersById;
    }

// === 4. Формируем список полей для запроса предложений ===
    private function buildOfferSelectFields(): array
    {
        $fields = ['PROPERTY_CML2_LINK'];

        $baseFields = (array)($this->arParams['OFFER_BASE_FIELDS'] ?? []);
        foreach ($baseFields as $field) {
            $field = trim($field);
            if ($field !== '' && !in_array($field, $fields)) {
                $fields[] = $field;
            }
        }

        foreach ($this->arParams['OFFER_PROPERTY_CODE'] as $code) {
            $code = trim($code);
            if ($code !== '') {
                $fields[] = 'PROPERTY_' . $code;
            }
        }

        return $fields;
    }

// === 5. Добавить базовые цены ===
    private function enrichOffersWithPrices(array &$offersById): void
    {
        if (empty($offersById)) return;

        $offerIds = array_keys($offersById);
        $priceList = \Bitrix\Catalog\PriceTable::getList([
            'select' => ['PRODUCT_ID', 'PRICE', 'CURRENCY', 'CATALOG_GROUP_ID'],
            'filter' => ['PRODUCT_ID' => $offerIds]
        ])->fetchAll();

        $pricesByOfferId = [];
        foreach ($priceList as $price) {
            $pid = (int)$price['PRODUCT_ID'];
            $pricesByOfferId[$pid][] = [
                'PRICE' => $price['PRICE'],
                'CURRENCY' => $price['CURRENCY'],
                'CATALOG_GROUP_ID' => $price['CATALOG_GROUP_ID']
            ];
        }

        // Офферы без цен — пробуем цены родительского товара (PRODUCT_ID в каталоге = ID товара)
        $productIdsToFetch = [];
        foreach ($offersById as $offerId => $offer) {
            if (empty($pricesByOfferId[$offerId])) {
                $productId = (int)($offer['PROPERTY_CML2_LINK_VALUE'] ?? 0);
                if ($productId > 0) {
                    $productIdsToFetch[$productId][] = $offerId;
                }
            }
        }

        if (!empty($productIdsToFetch)) {
            $productIds = array_keys($productIdsToFetch);
            $productPriceList = \Bitrix\Catalog\PriceTable::getList([
                'select' => ['PRODUCT_ID', 'PRICE', 'CURRENCY', 'CATALOG_GROUP_ID'],
                'filter' => ['PRODUCT_ID' => $productIds]
            ])->fetchAll();

            foreach ($productPriceList as $price) {
                $productId = (int)$price['PRODUCT_ID'];
                $priceRow = [
                    'PRICE' => $price['PRICE'],
                    'CURRENCY' => $price['CURRENCY'],
                    'CATALOG_GROUP_ID' => $price['CATALOG_GROUP_ID']
                ];
                foreach ($productIdsToFetch[$productId] ?? [] as $offerId) {
                    $pricesByOfferId[$offerId][] = $priceRow;
                }
            }
        }

        $adGroupId = $this->arParams['PRICE_AD_GROUP_ID'] ?: 3;
        foreach ($offersById as $offerId => &$offer) {
            $offer['PRICES'] = $pricesByOfferId[$offerId] ?? [];
            $offer['BASE_PRICE'] = null;
            $offer['AD_PRICE'] = null;
            $offer['CURRENCY'] = 'RUB';

            foreach ($offer['PRICES'] as $p) {
                $groupId = (int)$p['CATALOG_GROUP_ID'];
                if ($groupId === 1) {
                    $offer['BASE_PRICE'] = (float)$p['PRICE'];
                    $offer['CURRENCY'] = $p['CURRENCY'];
                }
                if ($groupId === $adGroupId) {
                    $offer['AD_PRICE'] = (float)$p['PRICE'];
                }
            }
        }
        unset($offer);
    }

// === 6. Добавить финальную цену: Рекламная (ID=3) при наличии, иначе базовая (ID=1) ===
    private function enrichOffersWithFinalPrice(array &$offersById): void
    {
        if (empty($offersById)) return;

        foreach ($offersById as &$offer) {
            $price = ($offer['AD_PRICE'] ?? null) !== null
                ? (float)$offer['AD_PRICE']
                : ($offer['BASE_PRICE'] ?? 0);

            $offer['FINAL_PRICE'] = [
                'DISCOUNT_PRICE' => $price,
                'DISCOUNT' => 0,
                'PERCENT' => 0,
                0 => ['DISCOUNT_PRICE' => $price, 'DISCOUNT' => 0, 'PERCENT' => 0],
            ];
        }
        unset($offer);
    }

// === 7. Определяем группы текущего пользователя ===
    private function getCurrentUserGroups(): array
    {
        if (isset($_SESSION['SESS_AUTH']['USER_GROUP_ID'])) {
            return array_merge([2], $_SESSION['SESS_AUTH']['USER_GROUP_ID']);
        }
        return [1, 2]; // гости + зарегистрированные
    }

// === 8. Добавить реальный остаток (игнорируя QUANTITY_TRACE) ===
    private function enrichOffersWithRealQuantity(array &$offersById): void
    {
        if (empty($offersById)) return;

        $quantityList = \Bitrix\Catalog\ProductTable::getList([
            'select' => ['ID', 'QUANTITY'],
            'filter' => ['ID' => array_keys($offersById)]
        ])->fetchAll();

        $quantityById = [];
        foreach ($quantityList as $q) {
            $quantityById[$q['ID']] = (float)$q['QUANTITY'];
        }

        foreach ($offersById as $offerId => &$offer) {
            $offer['REAL_QUANTITY'] = $quantityById[$offerId] ?? 0;
        }
        unset($offer);
    }

// === 9. Привязать предложения к товарам ===
    private function attachOffersToItems(array $offersById, array &$itemById): void
    {
        foreach ($offersById as $offer) {
            $productId = (int)($offer['PROPERTY_CML2_LINK_VALUE'] ?? 0);
            if (isset($itemById[$productId])) {
                if (!isset($itemById[$productId]['OFFERS'])) {
                    $itemById[$productId]['OFFERS'] = [];
                }
                $itemById[$productId]['OFFERS'][] = $offer;
            }
        }
    }

    // === 5. Универсальный метод извлечения свойств ===
    private function extractProperties(array $element, array $propertyCodes, array $propertyNames = []): array
    {
        $props = [];
        foreach ($propertyCodes as $code) {
            $code = trim($code);
            if ($code === '') continue;

            $valueKey = 'PROPERTY_' . $code . '_VALUE';
            $value = $element[$valueKey] ?? '';

            // Пропускаем пустые значения
            if ($value === '' || $value === null) {
                continue;
            }

            // Для множественных свойств: если массив пустой
            if (is_array($value) && empty($value)) {
                continue;
            }

            $props[$code] = [
                'VALUE' => $value,
                'CODE' => $code,
                'NAME' => $propertyNames[$code] ?? $code
            ];
        }
        return $props;
    }
}