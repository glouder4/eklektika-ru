<?php
namespace OnlineService\SalesHit;

use Bitrix\Main\Loader;

class SalesHitComponent extends \CBitrixComponent
{
    private $offersIblockId = null;

    public function onPrepareComponentParams($params)
    {
        $params['IBLOCK_ID'] = (int)($params['IBLOCK_ID'] ?? 0);
        $params['PROPERTY_CODE'] = (array)($params['PROPERTY_CODE'] ?? []);
        $params['OFFER_PROPERTY_CODE'] = (array)($params['OFFER_PROPERTY_CODE'] ?? []);
        $params['FILTER_OFFER_PROPERTY'] = trim((string)($params['FILTER_OFFER_PROPERTY'] ?? ''));
        $params['FILTER_OFFER_VALUE'] = trim((string)($params['FILTER_OFFER_VALUE'] ?? ''));
        $params['ELEMENT_COUNT'] = (int)($params['ELEMENT_COUNT'] ?? 10);
        $params['CACHE_TIME'] = isset($params['CACHE_TIME']) ? (int)$params['CACHE_TIME'] : 3600;
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

        $productIds = array_slice(array_keys($productToOffers), 0, $this->arParams['ELEMENT_COUNT']);
        $items = $this->loadProducts($productIds);
        $items = $this->attachOffersToProducts($items, $productToOffers);

        $this->writeToCache($cacheKey, $items);
        return $items;
    }

    // === 1. Кэш ===
    private function buildCacheKey()
    {
        return md5(serialize([
            'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
            'OFFERS_IBLOCK_ID' => $this->offersIblockId,
            'FILTER_PROP' => $this->arParams['FILTER_OFFER_PROPERTY'],
            'FILTER_VAL' => $this->arParams['FILTER_OFFER_VALUE'],
            'OFFER_PROPS' => $this->arParams['OFFER_PROPERTY_CODE'],
            'ITEM_PROPS' => $this->arParams['PROPERTY_CODE'],
            'COUNT' => $this->arParams['ELEMENT_COUNT']
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
            if (!empty($item['PREVIEW_PICTURE'])) {
                $item['PREVIEW_PICTURE_URL'] = \CFile::GetPath($item['PREVIEW_PICTURE']);
            }
        }
        unset($item);

        return $items;
    }

    // === 4. Подгрузить предложения и прикрепить к товарам ===
    private function attachOffersToProducts(array $items, array $productToOffers)
    {
        // Собираем все ID предложений
        $offerIds = [];
        foreach ($productToOffers as $offers) {
            $offerIds = array_merge($offerIds, $offers);
        }
        $offerIds = array_unique($offerIds);

        if (empty($offerIds)) return $items;

        // Индексируем товары по ID
        $itemById = [];
        foreach ($items as &$item) {
            $itemById[$item['ID']] = &$item;
        }
        unset($item);

        // Загружаем предложения
        // Базовые поля предложений
        $arOfferSelect = ['PROPERTY_CML2_LINK']; // обязательно для связи

        $baseFields = (array)($this->arParams['OFFER_BASE_FIELDS'] ?? []);
        foreach ($baseFields as $field) {
            $field = trim($field);
            if ($field !== '' && !in_array($field, $arOfferSelect)) {
                $arOfferSelect[] = $field;
            }
        }

// Свойства предложений
        foreach ($this->arParams['OFFER_PROPERTY_CODE'] as $code) {
            $code = trim($code);
            if ($code !== '') {
                $arOfferSelect[] = 'PROPERTY_' . $code;
            }
        }

        $dbOffersFull = \CIBlockElement::GetList(
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

        while ($offer = $dbOffersFull->GetNext()) {
            $productId = (int)($offer['PROPERTY_CML2_LINK_VALUE'] ?? 0);
            if (isset($itemById[$productId])) {
                // Преобразуем PREVIEW_PICTURE в URL сразу
                if (!empty($offer['PREVIEW_PICTURE'])) {
                    $offer['PREVIEW_PICTURE_URL'] = \CFile::GetPath($offer['PREVIEW_PICTURE']);
                }

                if (!isset($itemById[$productId]['OFFERS'])) {
                    $itemById[$productId]['OFFERS'] = [];
                }
                $offer['PROPERTIES'] = $this->extractProperties($offer, $this->arParams['OFFER_PROPERTY_CODE']);
                $itemById[$productId]['OFFERS'][] = $offer;
            }
        }

        return $items;
    }

    // === 5. Универсальный метод извлечения свойств ===
    private function extractProperties(array $element, array $propertyCodes): array
    {
        $props = [];
        foreach ($propertyCodes as $code) {
            $code = trim($code);
            if ($code === '') continue;
            $valueKey = 'PROPERTY_' . $code . '_VALUE';
            $props[$code] = [
                'VALUE' => $element[$valueKey] ?? '',
                'CODE' => $code
            ];
        }
        return $props;
    }
}