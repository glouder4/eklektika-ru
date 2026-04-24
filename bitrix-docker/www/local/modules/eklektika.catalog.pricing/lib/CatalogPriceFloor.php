<?php

namespace OnlineService\Site;

use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use OnlineService\Site\Config\CatalogPricingConfig;

/**
 * База для скидок — оптовая цена (CATALOG_GROUP_ID = 2); если у позиции нет типа 2 — резервно тип 1 (BASE в Битрикс).
 * На витрине строка «Рекламная цена» (тип 3): приоритетно нативный GetOptimalPrice только по строкам типа 3 (bypass подмены на опт),
 * коэффициент DISCOUNT_PRICE/BASE_PRICE переносится на оптовую базу; иначе — цепочка CCatalogDiscount / превью Sale;
 * если скидки ядра нет и есть группа скидки компании — процент по группе (Company::getMaxCompanyDiscountPercentForUserGroups);
 * если группы скидки нет — подстановка рекламной цены из прайса (тип 3), без нативного GOP×опт (factor=1); без приравнивания рекламной строки к оптовой базе (зачёркнутая база = цена продажи). Итог не ниже закупочной (тип 4).
 */
final class CatalogPriceFloor
{
    public const ADVERTISING_PRICE_TYPE_ID = CatalogPricingConfig::ADVERTISING_PRICE_TYPE_ID;
    public const PURCHASE_PRICE_TYPE_ID = CatalogPricingConfig::PURCHASE_PRICE_TYPE_ID;

    /**
     * Оптовая цена (ID 2) — основная «базовая» цена проекта для расчёта скидок и зачёркнутой суммы.
     */
    public const BASE_PRICE_TYPE_ID = CatalogPricingConfig::BASE_PRICE_TYPE_ID;

    /**
     * Резерв: BASE (ID 1 в каталоге Битрикс), если оптовой цены нет.
     */
    public const BASE_PRICE_FALLBACK_TYPE_ID = CatalogPricingConfig::BASE_PRICE_FALLBACK_TYPE_ID;

    /**
     * Порядок типов цен для базы скидок и для подстановки в GetOptimalPrice.
     *
     * @return list<int>
     */
    private static function getDiscountBaseCatalogGroupIds(): array
    {
        return [self::BASE_PRICE_TYPE_ID, self::BASE_PRICE_FALLBACK_TYPE_ID];
    }

    /**
     * Включить запись в лог. Выключаем только по явной просьбе после решения проблемы.
     */
    public const DEBUG_LOG_ENABLED = CatalogPricingConfig::DEBUG_LOG_ENABLED;

    /**
     * Доп. трассировка цепочки скидок (много строк на страницу).
     */
    public const DEBUG_TRACE_COUNT_PRICE = CatalogPricingConfig::DEBUG_TRACE_COUNT_PRICE;

    /** @var string|null Путь к файлу лога (кешируется) */
    private static ?string $debugLogPath = null;

    /** @var bool Обработчики уже зарегистрированы (bootstrap) */
    private static bool $handlersRegistered = false;

    /**
     * Пока true — OnGetOptimalPrice сразу return true, чтобы вложенный GetOptimalPrice
     * отработал в ядре с переданным $arPrices (оптовая база), а не снова зашёл в наш обёрточный обработчик.
     */
    private static bool $bypassGetOptimalPriceHandler = false;

    /** @var int|null ID товара/ТП внутри текущего GetOptimalPrice (для OnCountPriceWithDiscount) */
    private static ?int $currentOptimalPriceProductId = null;

    /** @var bool Защита от рекурсии в OnCountPriceWithDiscount */
    private static bool $inCountPriceWithDiscountHandler = false;

    /**
     * Последний результат CCatalogProduct::CountPriceWithDiscount по PRODUCT_ID за запрос
     * (полная маркетинговая цепочка; может быть ниже, чем DISCOUNT_PRICE из GetOptimalPrice).
     *
     * @var array<int, float>
     */
    private static array $lastMarketingPriceByProduct = [];

    /**
     * Вызов из local/php_interface/init.php после подключения кастомных классов — глобально для всего сайта.
     */
    public static function bootstrap(): void
    {
        if (self::$handlersRegistered) {
            return;
        }

        if (self::isAdminSection()) {
            self::$handlersRegistered = true;

            return;
        }

        self::$handlersRegistered = true;

        if (!\function_exists('AddEventHandler')) {
            self::debugLog('bootstrap: AddEventHandler недоступен (слишком ранний вызов?)');
            return;
        }  

        // SORT=1 — раньше сторонних обработчиков, иначе чужой OnGetOptimalPrice может вернуть цену до нашей обёртки
        AddEventHandler('catalog', 'OnGetOptimalPrice', [self::class, 'onGetOptimalPrice'], 1);
        // Цепочка скидок (в т.ч. доп. %), иначе итог может уйти ниже пола после OnGetOptimalPrice
        AddEventHandler('catalog', 'OnCountPriceWithDiscount', [self::class, 'onCountPriceWithDiscount']);

        // Sale: только AddEventHandler с явным путём к файлу — иначе EventManager при send()
        // может получить пустой/некорректный TO_PATH и кинуть Bitrix\Main\IO\InvalidPathException (120).
        AddEventHandler(
            'sale',
            'OnSaleBasketItemBeforeSaved',
            ['\OnlineService\Site\CatalogPriceFloor', 'onSaleBasketItemBeforeSaved'],
            500,
            __FILE__
        );

        self::markCompositeNonCacheableForAuthorizedCatalog();
    }

    /**
     * Раздел администрирования: не подменяем цены/скидки (редактирование заказа, каталог в админке и т.д.).
     */
    private static function isAdminSection(): bool
    {
        return \defined('ADMIN_SECTION') && ADMIN_SECTION === true;
    }

    /**
     * Кастомная логика цен (пол, витрина, мини-корзина, обработчики каталога) — только для авторизованных пользователей.
     */
    public static function isPricingOverrideActive(): bool
    {
        if (self::isAdminSection()) {
            return false;
        }

        global $USER;
        if (!\is_object($USER) || !\method_exists($USER, 'IsAuthorized')) {
            return false;
        }

        return (bool)$USER->IsAuthorized();
    }

    /**
     * Композитное кеширование страниц отдаёт HTML без повторного PHP — result_modifier и sync витрины не выполняются,
     * авторизованный дилер видит «гостевые» цены в разметке. Любой нестандартный query (например os_price_debug=1)
     * даёт промах по кешу и маскурует проблему. Для каталога при активной подмене цен помечаем страницу некешируемой.
     *
     * Вызов: сразу после автозагрузки (например из local/classes/requires.php).
     */
    public static function markCompositeNonCacheableForAuthorizedCatalog(): void
    {
        if (!self::isPricingOverrideActive()) {
            return;
        }
        $uri = isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '';
        if ($uri === '') {
            return;
        }
        $path = (string)\parse_url($uri, PHP_URL_PATH);
        if ($path === '' || \stripos($path, '/catalog/') === false) {
            return;
        }
        if (!\class_exists(\Bitrix\Main\Composite\Page::class)) {
            return;
        }
        try {
            \Bitrix\Main\Composite\Page::getInstance()->markNonCacheable();
        } catch (\Throwable $e) {
            // ранний вызов до инициализации композита — игнорируем
        }
    }

    /**
     * Каталог логов: /log от DOCUMENT_ROOT (веб), иначе от корня проекта.
     */
    private static function getLogDirectory(): string
    {
        $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? \rtrim((string)$_SERVER['DOCUMENT_ROOT'], '/\\') : '';
        if ($docRoot !== '') {
            $dir = $docRoot . '/log';
        } else {
            $dir = \dirname(__DIR__, 3) . '/log';
        }
        if (!\is_dir($dir)) {
            @\mkdir($dir, 0755, true);
        }

        return $dir;
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function debugLog(string $message, array $context = []): void
    {
        if (!self::DEBUG_LOG_ENABLED) {
            return;
        }

        if (self::$debugLogPath === null) {
            self::$debugLogPath = self::getLogDirectory() . '/catalog_price_floor.log';
        }

        $line = date('Y-m-d H:i:s') . ' [' . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'cli') . '] '
            . $message;
        if ($context !== []) {
            $line .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        }
        $line .= "\n";

        @file_put_contents(self::$debugLogPath, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Отдельный файл — только тяжёлая трассировка CountPriceWithDiscount (удобно фильтровать).
     *
     * @param array<string, mixed> $context
     */
    private static function debugTraceCountPrice(string $message, array $context = []): void
    {
        if (!self::DEBUG_LOG_ENABLED || !self::DEBUG_TRACE_COUNT_PRICE) {
            return;
        }

        $path = self::getLogDirectory() . '/catalog_price_floor_count_discount.log';
        $line = date('Y-m-d H:i:s') . ' [' . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'cli') . '] '
            . $message;
        if ($context !== []) {
            $line .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        }
        $line .= "\n";
        @\file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
    }

    private static function isDebugFlagEnabled(string $name): bool
    {
        return isset($_GET[$name]) && (string)$_GET[$name] === '1';
    }

    private static function getDebugProductFilter(): int
    {
        return isset($_GET['os_price_debug_product']) ? (int)$_GET['os_price_debug_product'] : 0;
    }

    private static function isDebugEnabledForProduct(int $productId): bool
    {
        if (!self::isDebugFlagEnabled('os_price_debug')) {
            return false;
        }
        $debugProductFilter = self::getDebugProductFilter();

        return $debugProductFilter <= 0 || $productId === $debugProductFilter;
    }

    private static function isDebugBreakdownEnabledForProduct(int $productId): bool
    {
        if (!self::isDebugFlagEnabled('os_price_debug_breakdown')) {
            return false;
        }
        $debugProductFilter = self::getDebugProductFilter();

        return $debugProductFilter <= 0 || $productId === $debugProductFilter;
    }

    /**
     * Округление денежной суммы по правилам валюты (как в Bitrix Sale: {@see \Bitrix\Sale\PriceMaths::roundByFormatCurrency}).
     */
    private static function roundPriceAmountForCurrency(float $price, string $currency): float
    {
        if ($currency === '' || !\is_finite($price)) {
            return $price;
        }
        if (Loader::includeModule('sale') && \class_exists(\Bitrix\Sale\PriceMaths::class)) {
            return \Bitrix\Sale\PriceMaths::roundByFormatCurrency($price, $currency);
        }
        if (Loader::includeModule('currency')) {
            $fmt = \CCurrencyLang::GetCurrencyFormat($currency);
            $decimals = isset($fmt['DECIMALS']) ? (int)$fmt['DECIMALS'] : 2;

            return \round($price, $decimals);
        }

        return \round($price, 4);
    }

    /**
     * Округление по правилам каталога для типа цены (Настройки → Торговый каталог → Типы цен → Округление).
     * Не путать с DECIMALS валюты: {@see roundPriceAmountForCurrency} / {@see \Bitrix\Sale\PriceMaths::roundByFormatCurrency}.
     *
     * @see \Bitrix\Catalog\Product\Price::roundPrice
     */
    private static function roundPriceAmountForCatalogGroup(float $price, string $currency, int $catalogGroupId): float
    {
        if ($currency === '' || !\is_finite($price) || $catalogGroupId <= 0) {
            return $price;
        }
        if (Loader::includeModule('catalog') && \class_exists(\Bitrix\Catalog\Product\Price::class)) {
            $rounded = \Bitrix\Catalog\Product\Price::roundPrice($catalogGroupId, $price, $currency);
            if ($rounded !== null && \is_numeric($rounded)) {
                $out = (float)$rounded;
                if (\is_finite($out)) {
                    return $out;
                }
            }
        }

        return self::roundPriceAmountForCurrency($price, $currency);
    }

    /**
     * Форматирование суммы для витрины: третий параметр true у {@see \CCurrencyLang::CurrencyFormat} —
     * учёт DECIMALS, HIDE_ZERO и FORMAT_STRING валюты (как в публичной части).
     */
    private static function currencyFormatForDisplay(float $amount, string $currency): string
    {
        if (!Loader::includeModule('currency')) {
            return (string)$amount;
        }

        return \CCurrencyLang::CurrencyFormat($amount, $currency, true);
    }

    /**
     * Приведение полей RESULT_PRICE к округлённым значениям и согласованным DISCOUNT/PERCENT.
     *
     * @param array<string, mixed> $rp
     */
    private static function normalizeResultPriceAmountsForCurrency(array &$rp): void
    {
        $currency = (string)($rp['CURRENCY'] ?? '');
        if ($currency === '') {
            return;
        }
        $pt = (int)($rp['PRICE_TYPE_ID'] ?? 0);
        if (isset($rp['DISCOUNT_PRICE'])) {
            $rp['DISCOUNT_PRICE'] = $pt > 0
                ? self::roundPriceAmountForCatalogGroup((float)$rp['DISCOUNT_PRICE'], $currency, $pt)
                : self::roundPriceAmountForCurrency((float)$rp['DISCOUNT_PRICE'], $currency);
        }
        if (isset($rp['BASE_PRICE'])) {
            $baseGroup = ($pt === self::ADVERTISING_PRICE_TYPE_ID) ? self::BASE_PRICE_TYPE_ID : ($pt > 0 ? $pt : 0);
            $rp['BASE_PRICE'] = $baseGroup > 0
                ? self::roundPriceAmountForCatalogGroup((float)$rp['BASE_PRICE'], $currency, $baseGroup)
                : self::roundPriceAmountForCurrency((float)$rp['BASE_PRICE'], $currency);
        }
        if (isset($rp['DISCOUNT_PRICE'], $rp['BASE_PRICE']) && $rp['BASE_PRICE'] < $rp['DISCOUNT_PRICE']) {
            $rp['BASE_PRICE'] = $rp['DISCOUNT_PRICE'];
        }
        if (isset($rp['UNROUND_DISCOUNT_PRICE'])) {
            $rp['UNROUND_DISCOUNT_PRICE'] = $rp['DISCOUNT_PRICE'] ?? $rp['UNROUND_DISCOUNT_PRICE'];
        }
        if (isset($rp['UNROUND_BASE_PRICE'])) {
            $rp['UNROUND_BASE_PRICE'] = $rp['BASE_PRICE'] ?? $rp['UNROUND_BASE_PRICE'];
        }
        if (isset($rp['BASE_PRICE'], $rp['DISCOUNT_PRICE'])) {
            $disc = (float)$rp['DISCOUNT_PRICE'];
            $base = (float)$rp['BASE_PRICE'];
            $rp['DISCOUNT'] = self::roundPriceAmountForCurrency(\max(0.0, $base - $disc), $currency);
            if ($base > 0) {
                $rp['PERCENT'] = \round(100 * (1 - $disc / $base), 2);
            } else {
                $rp['PERCENT'] = 0.0;
            }
        } elseif (isset($rp['DISCOUNT'])) {
            $rp['DISCOUNT'] = self::roundPriceAmountForCurrency((float)$rp['DISCOUNT'], $currency);
        }
    }

    /**
     * Закупочная цена в указанной валюте или null, если пол не задан.
     */
    public static function getPurchaseFloorForProduct(int $productId, string $targetCurrency): ?float
    {
        if ($productId <= 0 || $targetCurrency === '') {
            return null;
        }

        if (!Loader::includeModule('catalog')) {
            self::debugLog('getPurchaseFloor: модуль catalog не загружен', ['productId' => $productId]);
            return null;
        }

        $row = \Bitrix\Catalog\PriceTable::getList([
            'filter' => [
                '=PRODUCT_ID' => $productId,
                '=CATALOG_GROUP_ID' => self::PURCHASE_PRICE_TYPE_ID,
            ],
            'select' => ['PRICE', 'CURRENCY'],
            'limit' => 1,
        ])->fetch();

        if (!$row || !isset($row['PRICE'])) {
            self::debugLog('getPurchaseFloor: нет строки цены типа 4', [
                'productId' => $productId,
                'targetCurrency' => $targetCurrency,
            ]);
            return null;
        }

        $price = (float)$row['PRICE'];
        if ($price <= 0) {
            self::debugLog('getPurchaseFloor: цена <= 0', ['productId' => $productId, 'raw' => $row['PRICE']]);
            return null;
        }

        $currency = (string)$row['CURRENCY'];
        if ($currency === $targetCurrency) {
            return self::roundPriceAmountForCurrency($price, $targetCurrency);
        }

        if (!Loader::includeModule('currency')) {
            self::debugLog('getPurchaseFloor: модуль currency не загружен', [
                'productId' => $productId,
                'needConvert' => $currency . '→' . $targetCurrency,
            ]);
            return null;
        }

        $converted = \CCurrencyRates::ConvertCurrency($price, $currency, $targetCurrency);
        if ($converted === null || $converted === false) {
            self::debugLog('getPurchaseFloor: ошибка конвертации валюты', [
                'productId' => $productId,
                'from' => $currency,
                'to' => $targetCurrency,
                'price' => $price,
            ]);
            return null;
        }

        return self::roundPriceAmountForCurrency((float)$converted, $targetCurrency);
    }

    /**
     * Закупочный пол: у SKU в b_catalog_price часто нет типа 4, тогда берём с родительского товара.
     */
    private static function getPurchaseFloorForProductOrParent(int $productId, string $targetCurrency): ?float
    {
        $floor = self::getPurchaseFloorForProduct($productId, $targetCurrency);
        if ($floor !== null) {
            return $floor;
        }

        if (!Loader::includeModule('catalog') || !\class_exists(\CCatalogSku::class)) {
            return null;
        }

        $skuInfo = \CCatalogSku::GetProductInfo($productId);
        if (!\is_array($skuInfo) || empty($skuInfo['ID'])) {
            return null;
        }

        $parentId = (int)$skuInfo['ID'];
        if ($parentId <= 0 || $parentId === $productId) {
            return null;
        }

        return self::getPurchaseFloorForProduct($parentId, $targetCurrency);
    }

    /**
     * Одна строка цены: PRODUCT_ID + CATALOG_GROUP_ID → сумма в $targetCurrency.
     */
    private static function getCatalogPriceAmountForProductAndGroup(
        int $productId,
        string $targetCurrency,
        int $catalogGroupId
    ): ?float {
        if ($productId <= 0 || $targetCurrency === '' || !Loader::includeModule('catalog')) {
            return null;
        }

        $row = \Bitrix\Catalog\PriceTable::getList([
            'filter' => [
                '=PRODUCT_ID' => $productId,
                '=CATALOG_GROUP_ID' => $catalogGroupId,
            ],
            'select' => ['PRICE', 'CURRENCY'],
            'order' => ['QUANTITY_FROM' => 'ASC', 'ID' => 'ASC'],
            'limit' => 1,
        ])->fetch();

        if (!$row || !isset($row['PRICE'])) {
            return null;
        }

        $price = (float)$row['PRICE'];
        if ($price <= 0) {
            return null;
        }

        $currency = (string)$row['CURRENCY'];
        if ($currency === $targetCurrency) {
            return self::roundPriceAmountForCurrency($price, $targetCurrency);
        }

        if (!Loader::includeModule('currency')) {
            return null;
        }

        $converted = \CCurrencyRates::ConvertCurrency($price, $currency, $targetCurrency);
        if ($converted === null || $converted === false) {
            return null;
        }

        return self::roundPriceAmountForCurrency((float)$converted, $targetCurrency);
    }

    /**
     * База для скидки: оптовая (тип 2), иначе тип 1 — см. константы, в валюте строки.
     */
    public static function getBaseCatalogPriceForProduct(int $productId, string $targetCurrency): ?float
    {
        if ($productId <= 0 || $targetCurrency === '') {
            return null;
        }

        foreach (self::getDiscountBaseCatalogGroupIds() as $catalogGroupId) {
            $amount = self::getCatalogPriceAmountForProductAndGroup($productId, $targetCurrency, $catalogGroupId);
            if ($amount !== null) {
                return $amount;
            }
        }

        return null;
    }

    /**
     * Базовая «Цена» для ТП: при отсутствии у SKU — с родителя (как закупка).
     */
    private static function getBaseCatalogPriceForProductOrParent(int $productId, string $targetCurrency): ?float
    {
        $base = self::getBaseCatalogPriceForProduct($productId, $targetCurrency);
        if ($base !== null) {
            return $base;
        }

        if (!Loader::includeModule('catalog') || !\class_exists(\CCatalogSku::class)) {
            return null;
        }

        $skuInfo = \CCatalogSku::GetProductInfo($productId);
        if (!\is_array($skuInfo) || empty($skuInfo['ID'])) {
            return null;
        }

        $parentId = (int)$skuInfo['ID'];
        if ($parentId <= 0 || $parentId === $productId) {
            return null;
        }

        return self::getBaseCatalogPriceForProduct($parentId, $targetCurrency);
    }

    /**
     * «Рекламная цена» (тип 3) из b_catalog_price: у ТП при отсутствии строки — с родителя.
     */
    private static function getAdvertisingCatalogPriceForProductOrParent(int $productId, string $targetCurrency): ?float
    {
        $p = self::getCatalogPriceAmountForProductAndGroup($productId, $targetCurrency, self::ADVERTISING_PRICE_TYPE_ID);
        if ($p !== null) {
            return $p;
        }

        if (!Loader::includeModule('catalog') || !\class_exists(\CCatalogSku::class)) {
            return null;
        }

        $skuInfo = \CCatalogSku::GetProductInfo($productId);
        if (!\is_array($skuInfo) || empty($skuInfo['ID'])) {
            return null;
        }

        $parentId = (int)$skuInfo['ID'];
        if ($parentId <= 0 || $parentId === $productId) {
            return null;
        }

        return self::getCatalogPriceAmountForProductAndGroup($parentId, $targetCurrency, self::ADVERTISING_PRICE_TYPE_ID);
    }

    /**
     * Рекламная цена (тип 3): как {@see getAdvertisingCatalogPriceForProductOrParent}, плюс повторный проход по ТП/родителю
     * при пустом результате (разные валюты/строки по количеству).
     */
    private static function getAdvertisingCatalogPriceForProductOrParentFlexible(int $productId, string $targetCurrency): ?float
    {
        $p = self::getAdvertisingCatalogPriceForProductOrParent($productId, $targetCurrency);
        if ($p !== null) {
            return $p;
        }
        if ($targetCurrency === '' || !Loader::includeModule('catalog')) {
            return null;
        }
        $ids = [$productId];
        if (\class_exists(\CCatalogSku::class)) {
            $skuInfo = \CCatalogSku::GetProductInfo($productId);
            if (\is_array($skuInfo) && !empty($skuInfo['ID'])) {
                $pid = (int)$skuInfo['ID'];
                if ($pid > 0 && $pid !== $productId) {
                    $ids[] = $pid;
                }
            }
        }
        foreach ($ids as $pid) {
            if ($pid <= 0) {
                continue;
            }
            $res = \Bitrix\Catalog\PriceTable::getList([
                'filter' => [
                    '=PRODUCT_ID' => $pid,
                    '=CATALOG_GROUP_ID' => self::ADVERTISING_PRICE_TYPE_ID,
                ],
                'select' => ['PRICE', 'CURRENCY'],
                'order' => ['QUANTITY_FROM' => 'ASC', 'ID' => 'ASC'],
                'limit' => 1,
            ]);
            if ($row = $res->fetch()) {
                $price = (float)$row['PRICE'];
                if ($price <= 0) {
                    continue;
                }
                $cur = (string)$row['CURRENCY'];
                if ($cur === $targetCurrency) {
                    return self::roundPriceAmountForCurrency($price, $targetCurrency);
                }
                if (Loader::includeModule('currency')) {
                    $converted = \CCurrencyRates::ConvertCurrency($price, $cur, $targetCurrency);
                    if ($converted !== null && $converted !== false) {
                        return self::roundPriceAmountForCurrency((float)$converted, $targetCurrency);
                    }
                }
            }
        }

        return null;
    }

    /**
     * Строки b_catalog_price для базы скидок (оптовая 2, иначе 1) — в GetOptimalPrice, чтобы скидки не шли от типа 3.
     * У ТП цена часто на родителе: подставляем PRODUCT_ID оцениваемого товара в копии строк.
     *
     * @return list<array<string, mixed>>|false
     */
    private static function getArPricesBaseTypeOnlyForOptimal(int $pricedProductId): array|false
    {
        if ($pricedProductId <= 0 || !Loader::includeModule('catalog')) {
            return false;
        }

        foreach (self::getDiscountBaseCatalogGroupIds() as $catalogGroupId) {
            $loadRows = static function (int $pid) use ($catalogGroupId): array {
                $rows = [];
                $res = \Bitrix\Catalog\PriceTable::getList([
                    'filter' => [
                        '=PRODUCT_ID' => $pid,
                        '=CATALOG_GROUP_ID' => $catalogGroupId,
                    ],
                    'select' => ['*'],
                    'order' => ['QUANTITY_FROM' => 'ASC', 'ID' => 'ASC'],
                ]);
                while ($r = $res->fetch()) {
                    $rows[] = $r;
                }

                return $rows;
            };

            $rows = $loadRows($pricedProductId);
            $sourceProductId = $pricedProductId;

            if ($rows === [] && \class_exists(\CCatalogSku::class)) {
                $skuInfo = \CCatalogSku::GetProductInfo($pricedProductId);
                if (\is_array($skuInfo) && !empty($skuInfo['ID'])) {
                    $parentId = (int)$skuInfo['ID'];
                    if ($parentId > 0 && $parentId !== $pricedProductId) {
                        $rows = $loadRows($parentId);
                        $sourceProductId = $parentId;
                    }
                }
            }

            if ($rows === []) {
                continue;
            }

            if ($sourceProductId !== $pricedProductId) {
                foreach ($rows as $i => $r) {
                    $rows[$i] = \array_replace($r, ['PRODUCT_ID' => $pricedProductId]);
                }
            }

            return $rows;
        }

        return false;
    }

    /**
     * Только строки рекламной цены (тип 3) для GetOptimalPrice — ядро само применит маркетинг, привязанный к этому типу.
     * Логика копирования с родителя ТП — как у getArPricesBaseTypeOnlyForOptimal.
     *
     * @return list<array<string, mixed>>|false
     */
    private static function getArPricesAdvertisingTypeOnlyForOptimal(int $pricedProductId): array|false
    {
        if ($pricedProductId <= 0 || !Loader::includeModule('catalog')) {
            return false;
        }

        $catalogGroupId = self::ADVERTISING_PRICE_TYPE_ID;
        $loadRows = static function (int $pid) use ($catalogGroupId): array {
            $rows = [];
            $res = \Bitrix\Catalog\PriceTable::getList([
                'filter' => [
                    '=PRODUCT_ID' => $pid,
                    '=CATALOG_GROUP_ID' => $catalogGroupId,
                ],
                'select' => ['*'],
                'order' => ['QUANTITY_FROM' => 'ASC', 'ID' => 'ASC'],
            ]);
            while ($r = $res->fetch()) {
                $rows[] = $r;
            }

            return $rows;
        };

        $rows = $loadRows($pricedProductId);
        $sourceProductId = $pricedProductId;

        if ($rows === [] && \class_exists(\CCatalogSku::class)) {
            $skuInfo = \CCatalogSku::GetProductInfo($pricedProductId);
            if (\is_array($skuInfo) && !empty($skuInfo['ID'])) {
                $parentId = (int)$skuInfo['ID'];
                if ($parentId > 0 && $parentId !== $pricedProductId) {
                    $rows = $loadRows($parentId);
                    $sourceProductId = $parentId;
                }
            }
        }

        if ($rows === []) {
            return false;
        }

        if ($sourceProductId !== $pricedProductId) {
            foreach ($rows as $i => $r) {
                $rows[$i] = \array_replace($r, ['PRODUCT_ID' => $pricedProductId]);
            }
        }

        return $rows;
    }

    /**
     * Из ответа GetOptimalPrice: только если выбран тип цены «Рекламная» (3), factor = DISCOUNT_PRICE / BASE_PRICE.
     *
     * @param array<string, mixed> $opt
     * @return array{factor: float, ad_base: float, ad_discounted: float}|null
     */
    private static function extractAdvertisingFactorFromOptimalResult(array $opt): ?array
    {
        if (empty($opt['RESULT_PRICE']) || !\is_array($opt['RESULT_PRICE'])) {
            return null;
        }
        $rp = $opt['RESULT_PRICE'];
        if ((int)($rp['PRICE_TYPE_ID'] ?? 0) !== self::ADVERTISING_PRICE_TYPE_ID) {
            return null;
        }
        $adBase = (float)($rp['BASE_PRICE'] ?? 0);
        $adDisc = (float)($rp['DISCOUNT_PRICE'] ?? 0);
        if ($adBase <= 0 || $adDisc <= 0) {
            return null;
        }
        $factor = $adDisc / $adBase;
        if ($factor >= 1.0 - 1e-6) {
            return null;
        }

        return [
            'factor' => \max(0.0, \min(1.0, $factor)),
            'ad_base' => $adBase,
            'ad_discounted' => $adDisc,
        ];
    }

    /**
     * Два вызова ядра с bypass OnGetOptimalPrice (без подмены arPrices на опт):
     * 1) только строки типа 3; 2) все цены из БД — если оптимальной оказалась рекламная со скидкой, переносим factor на опт.
     *
     * @return array{factor: float, ad_base: float, ad_discounted: float, source: 'type3_rows'|'all_prices'}|null
     */
    private static function tryDeriveDiscountFactorFromNativeOptimalOnAdvertisingPriceOnly(
        int $productId,
        array $userGroups,
        string $renewal,
        $siteId,
        $arDiscountCoupons
    ): ?array {
        if (!Loader::includeModule('catalog')) {
            return null;
        }

        $rows = self::getArPricesAdvertisingTypeOnlyForOptimal($productId);
        $optType3Only = null;
        if ($rows !== false && $rows !== []) {
            self::$bypassGetOptimalPriceHandler = true;
            try {
                \CCatalogProduct::setUseDiscount(true);
                $optType3Only = \CCatalogProduct::GetOptimalPrice(
                    $productId,
                    1,
                    $userGroups,
                    $renewal,
                    $rows,
                    $siteId,
                    $arDiscountCoupons
                );
            } finally {
                self::$bypassGetOptimalPriceHandler = false;
            }
            if ($optType3Only !== false && \is_array($optType3Only)) {
                $ext = self::extractAdvertisingFactorFromOptimalResult($optType3Only);
                if ($ext !== null) {
                    return \array_merge($ext, ['source' => 'type3_rows']);
                }
            }
        }

        self::$bypassGetOptimalPriceHandler = true;
        try {
            \CCatalogProduct::setUseDiscount(true);
            $optAll = \CCatalogProduct::GetOptimalPrice(
                $productId,
                1,
                $userGroups,
                $renewal,
                false,
                $siteId,
                $arDiscountCoupons
            );
        } finally {
            self::$bypassGetOptimalPriceHandler = false;
        }

        if ($optAll === false || !\is_array($optAll)) {
            return null;
        }
        $ext = self::extractAdvertisingFactorFromOptimalResult($optAll);
        if ($ext !== null) {
            return \array_merge($ext, ['source' => 'all_prices']);
        }

        return null;
    }

    /**
     * Для os_price_debug: сырые ответы GOP при bypass (без влияния на расчёт витрины).
     *
     * @return array<string, mixed>
     */
    public static function debugProbeNativeAdvertisingGetOptimalPrice(
        int $productId,
        array $userGroups,
        string $renewal,
        $siteId,
        $arDiscountCoupons
    ): array {
        if (!Loader::includeModule('catalog')) {
            return ['error' => 'no_catalog'];
        }
        $rows = self::getArPricesAdvertisingTypeOnlyForOptimal($productId);
        $out = [
            'type3_rows_count' => \is_array($rows) ? \count($rows) : 0,
            'gop_type3_only_RESULT_PRICE' => null,
            'gop_all_prices_RESULT_PRICE' => null,
        ];

        if (\is_array($rows) && $rows !== []) {
            self::$bypassGetOptimalPriceHandler = true;
            try {
                \CCatalogProduct::setUseDiscount(true);
                $o = \CCatalogProduct::GetOptimalPrice(
                    $productId,
                    1,
                    $userGroups,
                    $renewal,
                    $rows,
                    $siteId,
                    $arDiscountCoupons
                );
            } finally {
                self::$bypassGetOptimalPriceHandler = false;
            }
            $out['gop_type3_only_RESULT_PRICE'] = ($o !== false && \is_array($o) && !empty($o['RESULT_PRICE']))
                ? $o['RESULT_PRICE']
                : ($o === false ? 'false' : 'no_RESULT_PRICE');
        }

        self::$bypassGetOptimalPriceHandler = true;
        try {
            \CCatalogProduct::setUseDiscount(true);
            $o2 = \CCatalogProduct::GetOptimalPrice(
                $productId,
                1,
                $userGroups,
                $renewal,
                false,
                $siteId,
                $arDiscountCoupons
            );
        } finally {
            self::$bypassGetOptimalPriceHandler = false;
        }
        $out['gop_all_prices_RESULT_PRICE'] = ($o2 !== false && \is_array($o2) && !empty($o2['RESULT_PRICE']))
            ? $o2['RESULT_PRICE']
            : ($o2 === false ? 'false' : 'no_RESULT_PRICE');

        return $out;
    }

    /**
     * Опции модулей sale/catalog, из‑за которых скидка по типу цены в «Маркетинге» не участвует в GetOptimalPrice.
     * Типично: sale.use_sale_discount_only = Y — каталожные скидки не применяются к расчёту цены каталога (только корзина).
     *
     * @return array<string, string>
     */
    public static function debugBitrixDiscountModeOptions(): array
    {
        if (!\class_exists(\Bitrix\Main\Config\Option::class)) {
            return ['_note' => 'Bitrix\\Main\\Config\\Option недоступен'];
        }
        $out = [];
        $get = static function (string $module, string $name) use (&$out): void {
            try {
                $out[$module . '.' . $name] = (string)\Bitrix\Main\Config\Option::get($module, $name, '');
            } catch (\Throwable $e) {
                $out[$module . '.' . $name] = 'error: ' . $e->getMessage();
            }
        };
        $get('sale', 'use_sale_discount_only');
        $get('sale', 'get_discount_percent_from_base_price');
        $get('catalog', 'get_discount_percent_from_base_price');

        if (($out['sale.use_sale_discount_only'] ?? '') === 'Y') {
            $out['_interpretation'] = 'Включено «использовать только скидки интернет-магазина» — CCatalogProduct::GetOptimalPrice и CCatalogDiscount для витрины не применяют каталожные скидки; 20% из маркетинга каталога не попадёт в GOP. Смотрите настройки модуля sale или дублируйте эффект через правила корзины + отображение из Sale.';
        }

        return $out;
    }

    /**
     * Совместимость вызовов debug-хука: активная отладочная остановка отключена.
     *
     * @param array<string, mixed> $breakdown результат computeAdvertisingWholesaleMarketingBreakdown
     * @param array<string, mixed> $extra
     */
    private static function maybeDebugBreakdownDie(int $productId, string $label, array $breakdown, array $extra = []): void
    {
        if (!self::isDebugBreakdownEnabledForProduct($productId)) {
            return;
        }
        self::debugLog('os_price_debug_breakdown: ' . $label, [
            'productId' => $productId,
            'breakdown' => $breakdown,
            'extra' => $extra,
        ]);
    }

    /**
     * Зачёркнутая база и процент скидки в RESULT_PRICE — от оптовой (2) или резервно типа 1, не от рекламной.
     *
     * @param array<string, mixed> $rp
     */
    private static function syncResultPriceBaseFromCatalogFloor(int $productId, array &$rp): void
    {
        $currency = (string)($rp['CURRENCY'] ?? '');
        if ($currency === '') {
            return;
        }

        $baseRef = self::getBaseCatalogPriceForProductOrParent($productId, $currency);
        if ($baseRef === null || $baseRef <= 0) {
            return;
        }

        $disc = (float)($rp['DISCOUNT_PRICE'] ?? 0);
        $rp['BASE_PRICE'] = $baseRef;
        $rp['DISCOUNT'] = \max(0.0, $baseRef - $disc);
        if ($baseRef > 0) {
            $rp['PERCENT'] = \max(0.0, \round(100 * (1 - $disc / $baseRef), 2));
        } else {
            $rp['PERCENT'] = 0.0;
        }

        self::formatResultPricePrintFields($rp);
    }

    /**
     * Все CATALOG_GROUP_ID, по которым у товара есть строки в b_catalog_price.
     *
     * @return list<int>
     */
    private static function getAllCatalogGroupIdsForProduct(int $productId): array
    {
        if (!Loader::includeModule('catalog') || $productId <= 0) {
            return [];
        }
        $ids = [];
        $res = \Bitrix\Catalog\PriceTable::getList([
            'filter' => ['=PRODUCT_ID' => $productId],
            'select' => ['CATALOG_GROUP_ID'],
        ]);
        while ($r = $res->fetch()) {
            $g = (int)($r['CATALOG_GROUP_ID'] ?? 0);
            if ($g > 0) {
                $ids[$g] = true;
            }
        }

        return \array_keys($ids);
    }

    /**
     * Вызов CCatalogDiscount::GetDiscountByProduct с разным 4-м аргументом (в документации встречается и int, и массив ID типов).
     *
     * @return array|false|null
     */
    private static function invokeGetDiscountByProductWithFourth(
        int $productId,
        array $userGroups,
        string $renewal,
        mixed $fourth,
        $siteArg,
        $couponArg
    ) {
        try {
            $rm = new \ReflectionMethod(\CCatalogDiscount::class, 'GetDiscountByProduct');
            $n = $rm->getNumberOfParameters();
        } catch (\ReflectionException) {
            return false;
        }
        if ($n >= 6) {
            return \CCatalogDiscount::GetDiscountByProduct($productId, $userGroups, $renewal, $fourth, $siteArg, $couponArg);
        }
        if ($n >= 5) {
            return \CCatalogDiscount::GetDiscountByProduct($productId, $userGroups, $renewal, $fourth, $siteArg);
        }
        if ($n === 4) {
            return \CCatalogDiscount::GetDiscountByProduct($productId, $userGroups, $renewal, $fourth);
        }

        return false;
    }

    /**
     * ID строки b_catalog_price (поле ID) для пары товар + тип цены.
     */
    private static function getCatalogPriceRowIdForProductGroup(int $productId, int $catalogGroupId): ?int
    {
        if ($productId <= 0 || $catalogGroupId <= 0 || !Loader::includeModule('catalog')) {
            return null;
        }
        $row = \Bitrix\Catalog\PriceTable::getList([
            'filter' => [
                '=PRODUCT_ID' => $productId,
                '=CATALOG_GROUP_ID' => $catalogGroupId,
            ],
            'select' => ['ID'],
            'limit' => 1,
        ])->fetch();

        return $row && isset($row['ID']) ? (int)$row['ID'] : null;
    }

    /**
     * @return array|false|null
     */
    private static function invokeGetDiscountByPrice(
        int $catalogPriceRowId,
        array $userGroups,
        string $renewal,
        $siteArg,
        $couponArg
    ) {
        if (!\method_exists(\CCatalogDiscount::class, 'GetDiscountByPrice')) {
            return false;
        }
        try {
            $rm = new \ReflectionMethod(\CCatalogDiscount::class, 'GetDiscountByPrice');
            $n = $rm->getNumberOfParameters();
        } catch (\ReflectionException) {
            return false;
        }
        if ($n >= 5) {
            return \CCatalogDiscount::GetDiscountByPrice($catalogPriceRowId, $userGroups, $renewal, $siteArg, $couponArg);
        }
        if ($n === 4) {
            return \CCatalogDiscount::GetDiscountByPrice($catalogPriceRowId, $userGroups, $renewal, $siteArg);
        }
        if ($n === 3) {
            return \CCatalogDiscount::GetDiscountByPrice($catalogPriceRowId, $userGroups, $renewal);
        }
        if ($n === 2) {
            return \CCatalogDiscount::GetDiscountByPrice($catalogPriceRowId, $userGroups);
        }

        return false;
    }

    /**
     * IBLOCK_ID элемента каталога (PRODUCT_ID в b_catalog_product = ID элемента инфоблока).
     */
    private static function resolveCatalogElementIblockId(int $elementId): ?int
    {
        if ($elementId <= 0 || !Loader::includeModule('iblock')) {
            return null;
        }
        $r = \CIBlockElement::GetByID($elementId);
        if ($row = $r->GetNext()) {
            $ib = (int)($row['IBLOCK_ID'] ?? 0);

            return $ib > 0 ? $ib : null;
        }

        return null;
    }

    /**
     * CCatalogDiscount::GetDiscount — скидки для показа на сайте (часть правил маркетинга видна только через этот метод).
     *
     * @param list<int> $catalogGroups
     * @return array|false|null
     */
    private static function invokeGetDiscountForProduct(
        int $productId,
        int $iblockId,
        array $catalogGroups,
        array $userGroups,
        string $renewal,
        $siteArg,
        $couponArg
    ) {
        if (!\method_exists(\CCatalogDiscount::class, 'GetDiscount')) {
            return false;
        }
        try {
            $rm = new \ReflectionMethod(\CCatalogDiscount::class, 'GetDiscount');
            $n = $rm->getNumberOfParameters();
        } catch (\ReflectionException) {
            return false;
        }
        $site = ($siteArg !== false && $siteArg !== null && (string)$siteArg !== '') ? $siteArg : false;
        if ($n >= 9) {
            return \CCatalogDiscount::GetDiscount(
                $productId,
                $iblockId,
                $catalogGroups,
                $userGroups,
                $renewal,
                $site,
                $couponArg,
                true,
                false
            );
        }
        if ($n >= 8) {
            return \CCatalogDiscount::GetDiscount(
                $productId,
                $iblockId,
                $catalogGroups,
                $userGroups,
                $renewal,
                $site,
                $couponArg,
                true
            );
        }
        if ($n >= 7) {
            return \CCatalogDiscount::GetDiscount(
                $productId,
                $iblockId,
                $catalogGroups,
                $userGroups,
                $renewal,
                $site,
                $couponArg
            );
        }
        if ($n >= 6) {
            return \CCatalogDiscount::GetDiscount(
                $productId,
                $iblockId,
                $catalogGroups,
                $userGroups,
                $renewal,
                $site
            );
        }

        return false;
    }

    /**
     * Цепочка catalog PRODUCT_ID для поиска скидок: сама позиция, затем родитель ТП (правила часто висят на основном товаре).
     *
     * @return list<int>
     */
    private static function getCatalogProductIdsForDiscountLookup(int $productId): array
    {
        if ($productId <= 0) {
            return [];
        }
        $ids = [$productId];
        if (Loader::includeModule('catalog') && \class_exists(\CCatalogSku::class)) {
            $info = \CCatalogSku::GetProductInfo($productId);
            if (\is_array($info) && !empty($info['ID'])) {
                $parent = (int)$info['ID'];
                if ($parent > 0 && $parent !== $productId) {
                    $ids[] = $parent;
                }
            }
        }

        return $ids;
    }

    /**
     * Скидки каталога для одного CATALOG_GROUP_ID: GetDiscountByProduct, затем GetDiscountByPrice (ID строки цены), SKU/родитель.
     *
     * @return list<array<string, mixed>>
     */
    private static function fetchCatalogDiscountArraysForProductAndCatalogGroup(
        int $productId,
        array $userGroups,
        string $renewal,
        int $catalogGroupId,
        $siteArg,
        $couponArg
    ): array {
        foreach (self::getCatalogProductIdsForDiscountLookup($productId) as $pid) {
            $allGroups = self::getAllCatalogGroupIdsForProduct($pid);
            if ($allGroups === []) {
                $allGroups = [$catalogGroupId];
            }
            \sort($allGroups);

            $fourthCandidates = [
                $allGroups,
                $catalogGroupId,
                [$catalogGroupId],
            ];

            foreach ($fourthCandidates as $fourth) {
                $res = self::invokeGetDiscountByProductWithFourth(
                    $pid,
                    $userGroups,
                    $renewal,
                    $fourth,
                    $siteArg,
                    $couponArg
                );
                if (\is_array($res) && $res !== []) {
                    return $res;
                }
            }

            $priceRowId = self::getCatalogPriceRowIdForProductGroup($pid, $catalogGroupId);
            if ($priceRowId !== null && $priceRowId > 0) {
                $byPrice = self::invokeGetDiscountByPrice(
                    $priceRowId,
                    $userGroups,
                    $renewal,
                    $siteArg,
                    $couponArg
                );
                if (\is_array($byPrice) && $byPrice !== []) {
                    return $byPrice;
                }
            }
        }

        return [];
    }

    /**
     * Собрать непустой массив скидок каталога для расчёта CountPriceWithDiscount (один тип цены).
     *
     * @return list<array<string, mixed>>
     */
    private static function fetchCatalogDiscountArraysForProduct(
        int $productId,
        array $userGroups,
        string $renewal,
        int $catalogGroupId,
        $siteArg,
        $couponArg
    ): array {
        return self::fetchCatalogDiscountArraysForProductAndCatalogGroup(
            $productId,
            $userGroups,
            $renewal,
            $catalogGroupId,
            $siteArg,
            $couponArg
        );
    }

    /**
     * Порядок типов цен для витрины «опт − маркетинг»: сначала рекламная (3), затем прочие типы у SKU/родителя (кроме закупки),
     * затем опт/база — если правило в админке привязано не к ID 3, но к другому типу, его всё равно найдём.
     *
     * @return list<int>
     */
    private static function buildMarketingDiscountCatalogGroupOrder(int $productId): array
    {
        $order = [];
        $push = static function (int $id) use (&$order): void {
            if ($id > 0 && !\in_array($id, $order, true)) {
                $order[] = $id;
            }
        };

        $push(self::ADVERTISING_PRICE_TYPE_ID);

        foreach (self::getCatalogProductIdsForDiscountLookup($productId) as $pid) {
            $groups = self::getAllCatalogGroupIdsForProduct($pid);
            \sort($groups);
            foreach ($groups as $gid) {
                if ($gid === self::PURCHASE_PRICE_TYPE_ID || $gid === self::ADVERTISING_PRICE_TYPE_ID) {
                    continue;
                }
                $push($gid);
            }
        }

        $push(self::BASE_PRICE_TYPE_ID);
        $push(self::BASE_PRICE_FALLBACK_TYPE_ID);

        return $order;
    }

    /**
     * Подбор правил маркетинга каталога для расчёта от оптовой базы: GetDiscountByProduct + GetDiscountByPrice по цепочке типов,
     * затем CCatalogDiscount::GetDiscount (показ на сайте; зависит от настройки «Только правила корзины»).
     *
     * @return array{rules: list<array<string, mixed>>, matched_catalog_group_id: int|null, matched_via_get_discount: bool}
     */
    private static function fetchCatalogDiscountArraysForWholesaleMarketingDisplay(
        int $productId,
        array $userGroups,
        string $renewal,
        $siteArg,
        $couponArg
    ): array {
        foreach (self::buildMarketingDiscountCatalogGroupOrder($productId) as $catalogGroupId) {
            $rules = self::fetchCatalogDiscountArraysForProductAndCatalogGroup(
                $productId,
                $userGroups,
                $renewal,
                $catalogGroupId,
                $siteArg,
                $couponArg
            );
            if ($rules !== []) {
                return [
                    'rules' => $rules,
                    'matched_catalog_group_id' => $catalogGroupId,
                    'matched_via_get_discount' => false,
                ];
            }
        }

        $iblockId = self::resolveCatalogElementIblockId($productId);
        if ($iblockId !== null && \class_exists(\CCatalogDiscount::class)) {
            $groupList = self::buildMarketingDiscountCatalogGroupOrder($productId);
            $gd = self::invokeGetDiscountForProduct(
                $productId,
                $iblockId,
                $groupList,
                $userGroups,
                $renewal,
                $siteArg,
                $couponArg
            );
            if (\is_array($gd) && $gd !== []) {
                return [
                    'rules' => $gd,
                    'matched_catalog_group_id' => null,
                    'matched_via_get_discount' => true,
                ];
            }
        }

        return [
            'rules' => [],
            'matched_catalog_group_id' => null,
            'matched_via_get_discount' => false,
        ];
    }

    /**
     * GetOptimalPrice отдал рекламную строку без скидки, хотя оптовая база (2/1) выше — типично, если другой обработчик
     * или ядро выбрали тип 3 до расчёта от оптовой цены.
     *
     * @param array<string, mixed> $opt
     */
    private static function shouldRebuildOptimalStuckOnAdvertisingIgnoringWholesale(int $productId, array $opt): bool
    {
        if (empty($opt['RESULT_PRICE']) || !\is_array($opt['RESULT_PRICE'])) {
            return false;
        }
        $rp = $opt['RESULT_PRICE'];
        if ((int)($rp['PRICE_TYPE_ID'] ?? 0) !== self::ADVERTISING_PRICE_TYPE_ID) {
            return false;
        }
        $pct = (float)($rp['PERCENT'] ?? 0);
        if ($pct > 0.02) {
            return false;
        }
        $disc = (float)($rp['DISCOUNT_PRICE'] ?? 0);
        $baseRes = (float)($rp['BASE_PRICE'] ?? 0);
        if (\abs($baseRes - $disc) > 0.0001) {
            return false;
        }
        $currency = (string)($rp['CURRENCY'] ?? '');
        if ($currency === '') {
            return false;
        }
        $wholesale = self::getBaseCatalogPriceForProductOrParent($productId, $currency);
        if ($wholesale === null || $wholesale <= $disc + 0.01) {
            return false;
        }

        return true;
    }

    /**
     * Повторный расчёт цены со скидкой от строк оптовой/резервной базы через CCatalogDiscount + CountPriceWithDiscount
     * (без повторного входа в OnGetOptimalPrice). Результат в формате GetOptimalPrice (только RESULT_PRICE).
     *
     * @param array<string, mixed> $stuckOpt Исходный ответ GetOptimalPrice (для VAT и т.п.)
     * @return array<string, mixed>|null
     */
    private static function rebuildOptimalResultFromWholesaleBase(
        int $productId,
        int $quantity,
        array $userGroups,
        string $renewal,
        $siteId,
        $arDiscountCoupons,
        array $stuckOpt
    ): ?array {
        if (!Loader::includeModule('catalog') || !\class_exists(\CCatalogDiscount::class)) {
            return null;
        }

        $rows = self::getArPricesBaseTypeOnlyForOptimal($productId);
        if ($rows === false || $rows === []) {
            return null;
        }

        $row = $rows[0];
        $currency = (string)($row['CURRENCY'] ?? '');
        $basePrice = (float)($row['PRICE'] ?? 0);
        if ($currency === '' || $basePrice <= 0) {
            return null;
        }

        $catalogGroupId = (int)($row['CATALOG_GROUP_ID'] ?? self::BASE_PRICE_TYPE_ID);
        if ($catalogGroupId <= 0) {
            return null;
        }

        if (!\method_exists(\CCatalogDiscount::class, 'GetDiscountByProduct')) {
            self::debugLog('rebuildOptimal: нет CCatalogDiscount::GetDiscountByProduct', ['productId' => $productId]);
            return null;
        }

        $siteArg = ($siteId !== false && $siteId !== null && (string)$siteId !== '')
            ? (string)$siteId
            : (\defined('SITE_ID') ? (string)SITE_ID : false);
        $couponArg = \is_array($arDiscountCoupons) ? $arDiscountCoupons : false;

        $arDiscounts = self::fetchCatalogDiscountArraysForProduct($productId, $userGroups, $renewal, $catalogGroupId, $siteArg, $couponArg);

        if ($arDiscounts === []) {
            self::debugLog('rebuildOptimal: GetDiscountByProduct вернул пусто (скидка не в каталоге или другой тип правил)', [
                'productId' => $productId,
                'catalogGroupId' => $catalogGroupId,
                'siteArg' => $siteArg,
            ]);
        }

        \CCatalogProduct::setUseDiscount(true);

        self::$currentOptimalPriceProductId = $productId;
        try {
            $raw = \CCatalogProduct::CountPriceWithDiscount($basePrice, $currency, $arDiscounts);
            $discounted = self::normalizeCountPriceWithDiscountResult($raw);
        } finally {
            self::$currentOptimalPriceProductId = null;
        }

        self::$lastMarketingPriceByProduct[$productId] = $discounted;

        $discountAmount = \max(0.0, $basePrice - $discounted);
        $percent = $basePrice > 0 ? \max(0.0, \round(100 * (1 - $discounted / $basePrice), 2)) : 0.0;

        $tpl = \is_array($stuckOpt['RESULT_PRICE'] ?? null) ? $stuckOpt['RESULT_PRICE'] : [];
        $vatRate = (float)($tpl['VAT_RATE'] ?? 0);
        $vatIncluded = $tpl['VAT_INCLUDED'] ?? 'Y';
        if ($vatIncluded !== 'Y' && $vatIncluded !== 'N') {
            $vatIncluded = $vatIncluded ? 'Y' : 'N';
        }

        self::debugLog('rebuildOptimal: пересчёт от оптовой базы', [
            'productId' => $productId,
            'catalogGroupId' => $catalogGroupId,
            'basePrice' => $basePrice,
            'discounted' => $discounted,
            'discountRulesCount' => \count($arDiscounts),
        ]);

        $resultPrice = [
            'PRICE_TYPE_ID' => $catalogGroupId,
            'BASE_PRICE' => $basePrice,
            'DISCOUNT_PRICE' => $discounted,
            'CURRENCY' => $currency,
            'DISCOUNT' => $discountAmount,
            'PERCENT' => $percent,
            'VAT_RATE' => $vatRate,
            'VAT_INCLUDED' => $vatIncluded,
            'UNROUND_BASE_PRICE' => $basePrice,
            'UNROUND_DISCOUNT_PRICE' => $discounted,
            'ID' => (int)($row['ID'] ?? 0),
        ];
        self::normalizeResultPriceAmountsForCurrency($resultPrice);

        return [
            'RESULT_PRICE' => $resultPrice,
        ];
    }

    /**
     * Ядро иногда отдаёт оптимальную цену по типу 1/2 без скидки (BASE ≈ DISCOUNT), хотя в каталоге есть правила.
     * Повторно запрашиваем скидки (см. fetchCatalogDiscountArraysForProduct) и пересчитываем через CountPriceWithDiscount.
     *
     * @param array<string, mixed> $opt
     * @return array<string, mixed>
     */
    private static function maybeApplyCatalogDiscountsWhenFinalEqualsBase(
        int $productId,
        array $userGroups,
        string $renewal,
        $siteId,
        $arDiscountCoupons,
        array $opt
    ): array {
        if (empty($opt['RESULT_PRICE']) || !\is_array($opt['RESULT_PRICE'])) {
            return $opt;
        }
        $rp = &$opt['RESULT_PRICE'];
        $base = (float)($rp['BASE_PRICE'] ?? 0);
        $disc = (float)($rp['DISCOUNT_PRICE'] ?? 0);
        if ($base <= 0 || \abs($base - $disc) > 0.02) {
            return $opt;
        }
        $typeId = (int)($rp['PRICE_TYPE_ID'] ?? 0);
        if ($typeId !== self::BASE_PRICE_TYPE_ID && $typeId !== self::BASE_PRICE_FALLBACK_TYPE_ID) {
            return $opt;
        }
        $currency = (string)($rp['CURRENCY'] ?? '');
        if ($currency === '') {
            return $opt;
        }

        $siteArg = ($siteId !== false && $siteId !== null && (string)$siteId !== '')
            ? $siteId
            : (\defined('SITE_ID') ? (string)SITE_ID : false);
        $couponArg = \is_array($arDiscountCoupons) ? $arDiscountCoupons : false;

        $arDiscounts = self::fetchCatalogDiscountArraysForProduct(
            $productId,
            $userGroups,
            $renewal,
            $typeId,
            $siteArg,
            $couponArg
        );
        if ($arDiscounts === []) {
            return $opt;
        }

        \CCatalogProduct::setUseDiscount(true);

        self::$currentOptimalPriceProductId = $productId;
        try {
            $raw = \CCatalogProduct::CountPriceWithDiscount($base, $currency, $arDiscounts);
            $newDisc = self::normalizeCountPriceWithDiscountResult($raw);
        } finally {
            self::$currentOptimalPriceProductId = null;
        }

        if (!\is_finite($newDisc)) {
            return $opt;
        }
        if ($newDisc >= $base - 0.001 || \abs($newDisc - $disc) < 0.001) {
            return $opt;
        }

        self::$lastMarketingPriceByProduct[$productId] = $newDisc;

        $rp['DISCOUNT_PRICE'] = $newDisc;
        if (\array_key_exists('UNROUND_DISCOUNT_PRICE', $rp)) {
            $rp['UNROUND_DISCOUNT_PRICE'] = $newDisc;
        }
        $rp['DISCOUNT'] = \max(0.0, $base - $newDisc);
        $rp['PERCENT'] = $base > 0 ? \round(100 * (1 - $newDisc / $base), 2) : 0.0;

        self::formatResultPricePrintFields($rp);

        self::debugLog('maybeApplyCatalogDiscounts: применена скидка при BASE≈DISCOUNT', [
            'productId' => $productId,
            'base' => $base,
            'wasDiscount' => $disc,
            'newDiscount' => $newDisc,
            'rulesCount' => \count($arDiscounts),
        ]);

        return $opt;
    }

    /**
     * Оптовая база (2/1): скидки каталога (цепочка типов цен) или, если пусто, превью Sale на временном заказе; затем пол закупки (4).
     * Без группы скидки компании: discount_source catalog_advertising_list — значение типа 3 из прайса (ТП/родитель).
     *
     * @return array{
     *     base: float,
     *     discounted_before_floor: float,
     *     final: float,
     *     rules_count: int,
     *     floor: float|null,
     *     matched_catalog_group_id: int|null,
     *     matched_via_get_discount: bool,
     *     discount_source: string,
     *     sale_preview_unit: float|null,
     *     native_type3: array{factor: float, ad_base: float, ad_discounted: float, source: string}|null,
     *     company_group_percent: float,
     *     company_group_discounted: float|null
     * }|null
     */
    private static function computeAdvertisingWholesaleMarketingBreakdown(
        int $productId,
        array $userGroups,
        string $renewal,
        $siteId,
        $arDiscountCoupons,
        string $currency
    ): ?array {
        if ($currency === '' || !Loader::includeModule('catalog')) {
            return null;
        }

        $base = self::getBaseCatalogPriceForProductOrParent($productId, $currency);
        if ($base === null || $base <= 0) {
            return null;
        }

        $companyPctNoGroup = self::isCatalogPricingUserAuthorized()
            ? Company::getMaxCompanyDiscountPercentForUserGroups($userGroups)
            : 0.0;
        $hasCompanyDiscountTier = $companyPctNoGroup > 0.00001;
        if ($companyPctNoGroup <= 0.00001) {
            $adList = self::getAdvertisingCatalogPriceForProductOrParentFlexible($productId, $currency);
            if ($adList !== null && $adList > 0) {
                $base = self::roundPriceAmountForCatalogGroup($base, $currency, self::BASE_PRICE_TYPE_ID);
                $discountedList = self::roundPriceAmountForCatalogGroup($adList, $currency, self::ADVERTISING_PRICE_TYPE_ID);
                $floorEarly = self::getPurchaseFloorForProductOrParent($productId, $currency);
                $finalEarly = $discountedList;
                if ($floorEarly !== null && $finalEarly < $floorEarly) {
                    $finalEarly = $floorEarly;
                }
                $finalEarly = self::roundPriceAmountForCatalogGroup($finalEarly, $currency, self::ADVERTISING_PRICE_TYPE_ID);
                self::$lastMarketingPriceByProduct[$productId] = $finalEarly;

                $earlyBreakdown = [
                    'base' => $base,
                    'discounted_before_floor' => $discountedList,
                    'final' => $finalEarly,
                    'rules_count' => 0,
                    'floor' => $floorEarly,
                    'matched_catalog_group_id' => null,
                    'matched_via_get_discount' => false,
                    'discount_source' => 'catalog_advertising_list',
                    'sale_preview_unit' => null,
                    'native_type3' => null,
                    'company_group_percent' => 0.0,
                    'company_group_discounted' => null,
                ];
                self::maybeDebugBreakdownDie($productId, 'catalog_advertising_list', $earlyBreakdown, [
                    'currency' => $currency,
                    'adList_flexible' => $adList,
                    'companyPctNoGroup' => $companyPctNoGroup,
                    'hasCompanyDiscountTier' => $hasCompanyDiscountTier,
                ]);

                return $earlyBreakdown;
            }
        }

        $siteArg = ($siteId !== false && $siteId !== null && (string)$siteId !== '')
            ? $siteId
            : (\defined('SITE_ID') ? (string)SITE_ID : false);
        $couponArg = \is_array($arDiscountCoupons) ? $arDiscountCoupons : false;
        $siteIdStr = ($siteArg !== false && $siteArg !== '') ? (string)$siteArg : (\defined('SITE_ID') ? (string)SITE_ID : 's1');

        $discountSource = 'none';
        $salePreviewUnit = null;
        $discounted = $base;
        $nativeType3 = null;

        $emptyPack = [
            'rules' => [],
            'matched_catalog_group_id' => null,
            'matched_via_get_discount' => false,
        ];

        // Без скидочной группы компании нативный GOP по типу 3 даёт factor=1 (× опт) и затирает «Рекламную цену» из прайса.
        $nativeFactor = $hasCompanyDiscountTier
            ? self::tryDeriveDiscountFactorFromNativeOptimalOnAdvertisingPriceOnly(
                $productId,
                $userGroups,
                $renewal,
                $siteId,
                $arDiscountCoupons
            )
            : null;
        if ($nativeFactor !== null) {
            $nativeType3 = $nativeFactor;
            $discounted = self::roundPriceAmountForCatalogGroup($base * $nativeFactor['factor'], $currency, self::ADVERTISING_PRICE_TYPE_ID);
            $discountSource = 'native_optimal_type3';
            $pack = $emptyPack;
            $arDiscounts = [];
        } else {
            $pack = self::fetchCatalogDiscountArraysForWholesaleMarketingDisplay(
                $productId,
                $userGroups,
                $renewal,
                $siteArg,
                $couponArg
            );
            $arDiscounts = $pack['rules'];

            if ($arDiscounts !== []) {
                \CCatalogProduct::setUseDiscount(true);
                self::$currentOptimalPriceProductId = $productId;
                try {
                    $raw = \CCatalogProduct::CountPriceWithDiscount($base, $currency, $arDiscounts);
                    $discounted = self::normalizeCountPriceWithDiscountResult($raw);
                } finally {
                    self::$currentOptimalPriceProductId = null;
                }
                if (!\is_finite($discounted)) {
                    $discounted = $base;
                }
                $discounted = (float)\max(0.0, $discounted);
                $discounted = self::roundPriceAmountForCatalogGroup($discounted, $currency, self::ADVERTISING_PRICE_TYPE_ID);
                $discountSource = 'catalog';
            } else {
                $saleHit = self::computeWholesaleUnitAfterSaleBasketPreview($productId, $currency, $siteIdStr, $base);
                if ($saleHit !== null && \is_finite($saleHit['unit'])) {
                    $salePreviewUnit = (float)$saleHit['unit'];
                    if ($salePreviewUnit > 0 && $salePreviewUnit < $base - 0.001) {
                        $discounted = self::roundPriceAmountForCatalogGroup($salePreviewUnit, $currency, self::ADVERTISING_PRICE_TYPE_ID);
                        $discountSource = 'sale_preview';
                    }
                }
            }
        }

        $companyGroupPercent = 0.0;
        $companyGroupDiscounted = null;
        if ($discountSource !== 'native_optimal_type3') {
            $companyGroupPercent = self::isCatalogPricingUserAuthorized()
                ? Company::getMaxCompanyDiscountPercentForUserGroups($userGroups)
                : 0.0;
            if ($companyGroupPercent > 0 && $companyGroupPercent < 100) {
                $companyGroupDiscounted = self::roundPriceAmountForCatalogGroup($base * (1.0 - $companyGroupPercent / 100.0), $currency, self::ADVERTISING_PRICE_TYPE_ID);
                if (\is_finite($companyGroupDiscounted) && $companyGroupDiscounted >= 0) {
                    $prevDiscounted = $discounted;
                    $discounted = \min($discounted, $companyGroupDiscounted);
                    if (\abs($discounted - $companyGroupDiscounted) < 0.01
                        && $companyGroupDiscounted < $base - 0.001
                        && ($discounted < $prevDiscounted - 0.001 || \abs($prevDiscounted - $base) < 0.001)
                    ) {
                        $discountSource = 'company_group';
                    }
                }
            }
        }

        $discounted = self::roundPriceAmountForCatalogGroup($discounted, $currency, self::ADVERTISING_PRICE_TYPE_ID);

        $floor = self::getPurchaseFloorForProductOrParent($productId, $currency);
        $final = $discounted;
        if ($floor !== null && $final < $floor) {
            $final = $floor;
        }
        $final = self::roundPriceAmountForCatalogGroup($final, $currency, self::ADVERTISING_PRICE_TYPE_ID);

        self::$lastMarketingPriceByProduct[$productId] = $final;

        $fullBreakdown = [
            'base' => self::roundPriceAmountForCatalogGroup($base, $currency, self::BASE_PRICE_TYPE_ID),
            'discounted_before_floor' => $discounted,
            'final' => $final,
            'rules_count' => \count($arDiscounts),
            'floor' => $floor,
            'matched_catalog_group_id' => $pack['matched_catalog_group_id'],
            'matched_via_get_discount' => $pack['matched_via_get_discount'],
            'discount_source' => $discountSource,
            'sale_preview_unit' => $salePreviewUnit,
            'native_type3' => $nativeType3,
            'company_group_percent' => $companyGroupPercent,
            'company_group_discounted' => $companyGroupDiscounted,
        ];
        self::maybeDebugBreakdownDie($productId, 'full_chain', $fullBreakdown, [
            'currency' => $currency,
            'hasCompanyDiscountTier' => $hasCompanyDiscountTier,
        ]);

        return $fullBreakdown;
    }

    /**
     * Временный заказ без сохранения: одна позиция каталога с ценой = оптовая база, расчёт скидок модуля Sale (правила корзины).
     * Нужен, когда маркетинг в админке не попадает в CCatalogDiscount::GetDiscountByProduct.
     *
     * @return array{unit: float, base_line: float}|null
     */
    private static function computeWholesaleUnitAfterSaleBasketPreview(
        int $productId,
        string $currency,
        string $siteId,
        float $wholesaleBaseUnit
    ): ?array {
        if ($wholesaleBaseUnit <= 0 || $currency === '' || !Loader::includeModule('sale') || !Loader::includeModule('catalog')) {
            return null;
        }

        $providerClass = \class_exists(\Bitrix\Catalog\Product\CatalogProvider::class)
            ? \Bitrix\Catalog\Product\CatalogProvider::class
            : 'CCatalogProductProvider';

        $userId = 0;
        if (!empty($GLOBALS['USER']) && \is_object($GLOBALS['USER']) && \method_exists($GLOBALS['USER'], 'GetID')) {
            $userId = (int)$GLOBALS['USER']->GetID();
        }

        try {
            if (!\class_exists(\Bitrix\Sale\Order::class)) {
                return null;
            }

            $order = \Bitrix\Sale\Order::create($siteId, $userId);
            if (\method_exists($order, 'setPersonTypeId')) {
                $personTypeId = 0;
                if (\class_exists(\Bitrix\Sale\Internals\PersonTypeTable::class)) {
                    $ptRow = \Bitrix\Sale\Internals\PersonTypeTable::getList([
                        'filter' => ['=ACTIVE' => 'Y', '=LID' => $siteId],
                        'order' => ['SORT' => 'ASC', 'ID' => 'ASC'],
                        'limit' => 1,
                    ])->fetch();
                    if (\is_array($ptRow) && !empty($ptRow['ID'])) {
                        $personTypeId = (int)$ptRow['ID'];
                    }
                } elseif (\class_exists(\CSalePersonType::class)) {
                    $dbPt = \CSalePersonType::GetList(['SORT' => 'ASC'], ['ACTIVE' => 'Y', 'LID' => $siteId]);
                    if ($ptRow = $dbPt->Fetch()) {
                        $personTypeId = (int)($ptRow['ID'] ?? 0);
                    }
                }
                if ($personTypeId > 0) {
                    $order->setPersonTypeId($personTypeId);
                }
            }
            $basket = $order->getBasket();
            $item = $basket->createItem('catalog', $productId);

            $setFields = $item->setFields([
                'QUANTITY' => 1,
                'CURRENCY' => $currency,
                'LID' => $siteId,
                'PRODUCT_PROVIDER_CLASS' => $providerClass,
            ]);
            if (\is_object($setFields) && \method_exists($setFields, 'isSuccess') && !$setFields->isSuccess()) {
                self::debugLog('saleBasketPreview: setFields', ['errors' => $setFields->getErrorMessages()]);
                return null;
            }

            if (\method_exists($item, 'refreshData')) {
                $ref = $item->refreshData([]);
                if (\is_object($ref) && \method_exists($ref, 'isSuccess') && !$ref->isSuccess()) {
                    self::debugLog('saleBasketPreview: refreshData', ['errors' => $ref->getErrorMessages()]);
                }
            }

            $item->setField('CUSTOM_PRICE', 'Y');
            $item->setField('BASE_PRICE', $wholesaleBaseUnit);
            $item->setField('PRICE', $wholesaleBaseUnit);

            // Не вызываем order->refreshData() здесь — сбрасывает CUSTOM_PRICE у позиции.

            $discount = $order->getDiscount();
            if ($discount === null && \class_exists(\Bitrix\Sale\Discount::class) && \method_exists(\Bitrix\Sale\Discount::class, 'buildFromOrder')) {
                \Bitrix\Sale\Discount::buildFromOrder($order);
                $discount = $order->getDiscount();
            }
            if ($discount !== null && \method_exists($discount, 'calculate')) {
                $calc = $discount->calculate();
                if (\is_object($calc) && \method_exists($calc, 'isSuccess') && !$calc->isSuccess()) {
                    self::debugLog('saleBasketPreview: discount calculate', ['errors' => $calc->getErrorMessages()]);
                }
            }

            $unit = (float)$item->getField('PRICE');
            $baseLine = (float)$item->getField('BASE_PRICE');
            if ($unit <= 0) {
                return null;
            }

            return ['unit' => $unit, 'base_line' => $baseLine];
        } catch (\Throwable $e) {
            self::debugLog('saleBasketPreview: exception', [
                'productId' => $productId,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * RESULT_PRICE для подстановки в «Рекламную цену» на витрине: база опт + маркетинг каталога (поиск правила по цепочке типов цен) + пол закупки.
     *
     * @param array<string, mixed>|false $opt GetOptimalPrice — для VAT в merge.
     * @return array<string, mixed>|null
     */
    private static function buildAdvertisingDisplayResultPriceFromWholesaleAndMarketing(
        int $productId,
        array $userGroups,
        string $renewal,
        $siteId,
        $arDiscountCoupons,
        string $currency,
        $opt
    ): ?array {
        $calc = self::computeAdvertisingWholesaleMarketingBreakdown(
            $productId,
            $userGroups,
            $renewal,
            $siteId,
            $arDiscountCoupons,
            $currency
        );
        if ($calc === null) {
            return null;
        }

        $base = $calc['base'];
        $final = $calc['final'];

        $vatRate = 0.0;
        $vatIncluded = 'Y';
        if ($opt !== false && \is_array($opt) && !empty($opt['RESULT_PRICE']) && \is_array($opt['RESULT_PRICE'])) {
            $tpl = $opt['RESULT_PRICE'];
            $vatRate = (float)($tpl['VAT_RATE'] ?? 0);
            $vi = $tpl['VAT_INCLUDED'] ?? 'Y';
            $vatIncluded = ($vi === 'Y' || $vi === 'N') ? $vi : 'Y';
        }

        // Без группы скидки компании: не показываем скидку «от опта» — база в RESULT_PRICE = итоговая рекламная.
        if (($calc['discount_source'] ?? '') === 'catalog_advertising_list') {
            $base = $final;
            $discountAmount = 0.0;
            $percent = 0.0;
        } else {
            $discountAmount = \max(0.0, $base - $final);
            $percent = $base > 0 ? \round(100 * (1 - $final / $base), 2) : 0.0;
        }

        $resultPrice = [
            'PRICE_TYPE_ID' => self::ADVERTISING_PRICE_TYPE_ID,
            'BASE_PRICE' => $base,
            'DISCOUNT_PRICE' => $final,
            'CURRENCY' => $currency,
            'DISCOUNT' => $discountAmount,
            'PERCENT' => $percent,
            'VAT_RATE' => $vatRate,
            'VAT_INCLUDED' => $vatIncluded,
            'UNROUND_BASE_PRICE' => $base,
            'UNROUND_DISCOUNT_PRICE' => $final,
            'ID' => 0,
        ];
        self::normalizeResultPriceAmountsForCurrency($resultPrice);

        return $resultPrice;
    }

    /**
     * Диагностика мини-корзины: резолв catalog PRODUCT_ID, пол по SKU и родителю, итоговый пол (как в applyFloor).
     *
     * @param array<string, mixed> $item Строка из $arResult['BASKET']['ITEMS']
     * @return array<string, mixed>
     */
    public static function describeBasketSmallItemFloor(array $item, string $currencyCode): array
    {
        $cur = (string)($item['PRICE']['CURRENCY'] ?? $currencyCode);
        if ($cur === '') {
            $cur = $currencyCode;
        }

        $resolvedId = self::resolveCatalogProductIdForBasketSmallItem($item);
        $floorSku = $resolvedId > 0 ? self::getPurchaseFloorForProduct($resolvedId, $cur) : null;

        $parentId = null;
        $floorParent = null;
        if ($resolvedId > 0 && Loader::includeModule('catalog') && \class_exists(\CCatalogSku::class)) {
            $skuInfo = \CCatalogSku::GetProductInfo($resolvedId);
            if (\is_array($skuInfo) && !empty($skuInfo['ID'])) {
                $parentId = (int)$skuInfo['ID'];
                if ($parentId > 0 && $parentId !== $resolvedId) {
                    $floorParent = self::getPurchaseFloorForProduct($parentId, $cur);
                }
            }
        }

        $floorEff = $resolvedId > 0 ? self::getPurchaseFloorForProductOrParent($resolvedId, $cur) : null;
        $unitDisc = (float)($item['PRICE']['DISCOUNT']['VALUE'] ?? 0);

        $reason = 'ok';
        if ($resolvedId <= 0) {
            $reason = 'no_resolved_product_id';
        } elseif ($floorEff === null) {
            $reason = 'no_purchase_floor_type4';
        } elseif ($unitDisc >= $floorEff) {
            $reason = 'unit_discount_not_below_floor';
        } else {
            $reason = 'should_raise_to_floor';
        }

        return [
            'basket_line_id' => $item['ID'] ?? null,
            'raw_PRODUCT_ID' => $item['PRODUCT_ID'] ?? null,
            'resolved_catalog_product_id' => $resolvedId > 0 ? $resolvedId : null,
            'currency_used' => $cur,
            'floor_type4_on_sku' => $floorSku,
            'ccatalogsku_parent_id' => $parentId,
            'floor_type4_on_parent' => $floorParent,
            'floor_effective_or_parent' => $floorEff,
            'unit_discount' => $unitDisc,
            'reason' => $reason,
        ];
    }

    /**
     * Обработчик catalog OnGetOptimalPrice.
     *
     * @param int $intProductID
     * @param int $quantity
     * @param array $arUserGroups
     * @param string $renewal
     * @param array $arPrices
     * @param string|false $siteID
     * @param array|false $arDiscountCoupons
     * @return array|bool
     */
    public static function onGetOptimalPrice(
        $intProductID,
        $quantity,
        $arUserGroups,
        $renewal,
        $arPrices,
        $siteID,
        $arDiscountCoupons
    ) {
        if (!Loader::includeModule('catalog')) {
            self::debugLog('OnGetOptimalPrice: catalog не загружен', ['productId' => $intProductID]);
            return true;
        }

        if (self::$bypassGetOptimalPriceHandler) {
            return true;
        }

        if (!self::isPricingOverrideActive()) {
            return true;
        }

        self::debugLog('OnGetOptimalPrice: вход', [
            'productId' => $intProductID,
            'quantity' => $quantity,
            'siteID' => $siteID,
        ]);

        self::$currentOptimalPriceProductId = (int)$intProductID;
        self::debugLog('OnGetOptimalPrice: выставлен currentOptimalPriceProductId', [
            'productId' => self::$currentOptimalPriceProductId,
        ]);

        self::$bypassGetOptimalPriceHandler = true;
        try {
            // Режим объединённых скидок: учёт правил, которые можно показать в каталоге (см. документацию setUseDiscount / GetOptimalPrice)
            \CCatalogProduct::setUseDiscount(true);

            $pricesForOptimal = $arPrices;
            $baseOnlyRows = self::getArPricesBaseTypeOnlyForOptimal((int)$intProductID);
            if ($baseOnlyRows !== false && $baseOnlyRows !== []) {
                $pricesForOptimal = $baseOnlyRows;
            }
            $pricesArg = (\is_array($pricesForOptimal) && $pricesForOptimal !== [])
                ? $pricesForOptimal
                : false;

            $result = \CCatalogProduct::GetOptimalPrice(
                (int)$intProductID,
                (int)$quantity,
                (array)$arUserGroups,
                (string)$renewal,
                $pricesArg,
                $siteID,
                $arDiscountCoupons
            );

            if ($result === false) {
                self::debugLog('OnGetOptimalPrice: GetOptimalPrice вернул false', ['productId' => $intProductID]);
                return false;
            }

            if (self::shouldRebuildOptimalStuckOnAdvertisingIgnoringWholesale((int)$intProductID, $result)) {
                $reb = self::rebuildOptimalResultFromWholesaleBase(
                    (int)$intProductID,
                    (int)$quantity,
                    (array)$arUserGroups,
                    (string)$renewal,
                    $siteID,
                    $arDiscountCoupons,
                    $result
                );
                if ($reb !== null) {
                    $result = $reb;
                }
            }

            if ($result !== false && \is_array($result)) {
                $result = self::maybeApplyCatalogDiscountsWhenFinalEqualsBase(
                    (int)$intProductID,
                    (array)$arUserGroups,
                    (string)$renewal,
                    $siteID,
                    $arDiscountCoupons,
                    $result
                );
            }

            return self::applyFloorToOptimalPriceResult((int)$intProductID, $result);
        } finally {
            self::$bypassGetOptimalPriceHandler = false;
            unset(self::$lastMarketingPriceByProduct[(int)$intProductID]);
            self::debugLog('OnGetOptimalPrice: сброс currentOptimalPriceProductId (finally)', [
                'hadId' => self::$currentOptimalPriceProductId,
            ]);
            self::$currentOptimalPriceProductId = null;
        }
    }

    /**
     * Цена после всей цепочки скидок каталога; без этого пол 252→260 перекрывается следующей скидкой (напр. 40% → 151.2).
     *
     * @param float|int $price
     * @param array $arDiscounts
     * @return bool|float true — стандартный расчёт; float — итоговая цена
     */
    public static function onCountPriceWithDiscount($price, $currency, $arDiscounts)
    {
        $discountsCount = \is_array($arDiscounts) ? \count($arDiscounts) : 0;

        if (!self::isPricingOverrideActive()) {
            return true;
        }

        if (!Loader::includeModule('catalog')) {
            self::debugTraceCountPrice('CountPriceWithDiscount: ВХОД → пропуск (catalog)', [
                'price' => $price,
                'currency' => $currency,
                'discountsCount' => $discountsCount,
                'currentProductId' => self::$currentOptimalPriceProductId,
                'inRecursion' => self::$inCountPriceWithDiscountHandler,
            ]);
            return true;
        }

        if (self::$inCountPriceWithDiscountHandler) {
            self::debugTraceCountPrice('CountPriceWithDiscount: внутренний вызов → true', [
                'price' => $price,
                'currency' => $currency,
            ]);
            return true;
        }

        // Bitrix часто вызывает CountPriceWithDiscount ДО GetOptimalPrice — currentOptimalPriceProductId ещё пуст.
        $productId = self::$currentOptimalPriceProductId;
        if ($productId === null) {
            $productId = self::resolveProductIdFromDiscounts($arDiscounts);
        }
        if ($productId === null) {
            $productId = self::resolveProductIdFromRequest();
        }
        if ($productId === null) {
            self::debugTraceCountPrice('CountPriceWithDiscount: ВХОД → ПРОПУСК нет productId', [
                'price' => $price,
                'currency' => $currency,
                'discountsCount' => $discountsCount,
                'sampleDiscountKeys' => self::sampleDiscountKeys($arDiscounts),
            ]);
            self::debugLog('CountPriceWithDiscount: пропуск — не удалось определить PRODUCT_ID', [
                'price' => $price,
                'currency' => $currency,
            ]);
            return true;
        }

        self::debugTraceCountPrice('CountPriceWithDiscount: источник productId', [
            'productId' => $productId,
            'fromGetOptimal' => self::$currentOptimalPriceProductId !== null,
        ]);

        $currency = (string)$currency;

        // Внутри GetOptimalPrice ядро может передать в цепочку не оптовую базу — выравниваем старт к базе скидок (2→1)
        if (self::$currentOptimalPriceProductId !== null
            && (int)self::$currentOptimalPriceProductId === $productId
        ) {
            $baseStart = self::getBaseCatalogPriceForProductOrParent($productId, $currency);
            if ($baseStart !== null && $baseStart > 0) {
                $price = $baseStart;
            }
        }

        self::debugTraceCountPrice('CountPriceWithDiscount: ВХОД', [
            'productId' => $productId,
            'price' => $price,
            'currency' => $currency,
            'discountsCount' => $discountsCount,
            'sampleDiscountKeys' => self::sampleDiscountKeys($arDiscounts),
        ]);

        $raw = null;
        self::$inCountPriceWithDiscountHandler = true;
        try {
            $raw = \CCatalogProduct::CountPriceWithDiscount($price, $currency, $arDiscounts);
            $discounted = self::normalizeCountPriceWithDiscountResult($raw);
        } finally {
            self::$inCountPriceWithDiscountHandler = false;
        }

        // Запоминаем итог после всех скидок в этой цепочке (не поднимаем до закупки здесь — иначе ломается следующий маркетинговый шаг)
        self::$lastMarketingPriceByProduct[$productId] = $discounted;

        self::debugTraceCountPrice('CountPriceWithDiscount: после вложенного расчёта (только маркетинг, без пола)', [
            'productId' => $productId,
            'rawType' => $raw === null ? 'null' : \gettype($raw),
            'raw' => \is_array($raw) ? $raw : $raw,
            'discounted' => $discounted,
        ]);

        return $discounted;
    }

    /**
     * PRODUCT_ID из строк скидок (если есть) или по ID цены в каталоге.
     *
     * @param mixed $arDiscounts
     */
    private static function resolveProductIdFromDiscounts($arDiscounts): ?int
    {
        if (!\is_array($arDiscounts) || $arDiscounts === []) {
            return null;
        }

        foreach ($arDiscounts as $row) {
            if (!\is_array($row)) {
                continue;
            }
            foreach (['PRODUCT_ID', 'ELEMENT_ID', 'ITEM_ID', 'IBLOCK_ELEMENT_ID'] as $key) {
                if (!empty($row[$key]) && (int)$row[$key] > 0) {
                    return (int)$row[$key];
                }
            }
            $priceId = $row['PRICE_ID'] ?? $row['CATALOG_PRICE_ID'] ?? null;
            if ($priceId !== null && (int)$priceId > 0 && Loader::includeModule('catalog')) {
                $p = \Bitrix\Catalog\PriceTable::getList([
                    'filter' => ['=ID' => (int)$priceId],
                    'select' => ['PRODUCT_ID'],
                    'limit' => 1,
                ])->fetch();
                if ($p && !empty($p['PRODUCT_ID'])) {
                    return (int)$p['PRODUCT_ID'];
                }
            }
        }

        return null;
    }

    /**
     * Карточка товара: offer=ID ТП, ELEMENT_ID и т.п. (CountPriceWithDiscount вызывается до GetOptimalPrice).
     */
    private static function resolveProductIdFromRequest(): ?int
    {
        $keys = ['offer', 'OFFER_ID', 'ELEMENT_ID', 'element_id', 'PRODUCT_ID', 'product_id'];
        foreach ($keys as $key) {
            if (!empty($_REQUEST[$key]) && (int)$_REQUEST[$key] > 0) {
                return (int)$_REQUEST[$key];
            }
        }

        return null;
    }

    /**
     * @param mixed $arDiscounts
     * @return array<int, string>
     */
    private static function sampleDiscountKeys($arDiscounts): array
    {
        if (!\is_array($arDiscounts) || $arDiscounts === []) {
            return [];
        }
        $first = $arDiscounts[0] ?? null;
        if (!\is_array($first)) {
            return ['_non_array_first'];
        }

        return \array_slice(\array_keys($first), 0, 12);
    }

    /**
     * @param mixed $raw
     */
    private static function normalizeCountPriceWithDiscountResult($raw): float
    {
        if (\is_array($raw)) {
            if (isset($raw['PRICE'])) {
                return (float)$raw['PRICE'];
            }
            if (isset($raw['DISCOUNT_PRICE'])) {
                return (float)$raw['DISCOUNT_PRICE'];
            }

            return 0.0;
        }

        return (float)$raw;
    }

    /**
     * @param array $result Результат CCatalogProduct::GetOptimalPrice
     * @return array
     */
    public static function applyFloorToOptimalPriceResult(int $productId, array $result): array
    {
        if (empty($result['RESULT_PRICE']) || !is_array($result['RESULT_PRICE'])) {
            self::debugLog('applyFloor optimal: нет RESULT_PRICE', [
                'productId' => $productId,
                'keys' => array_keys($result),
            ]);
            return $result;
        }

        $rp = &$result['RESULT_PRICE'];
        if (!isset($rp['DISCOUNT_PRICE'], $rp['CURRENCY'])) {
            self::debugLog('applyFloor optimal: нет DISCOUNT_PRICE или CURRENCY', [
                'productId' => $productId,
                'resultPriceKeys' => array_keys($rp),
            ]);
            return $result;
        }

        $currency = (string)$rp['CURRENCY'];
        self::syncResultPriceBaseFromCatalogFloor($productId, $rp);

        $floor = self::getPurchaseFloorForProductOrParent($productId, $currency);
        if ($floor === null) {
            self::debugLog('applyFloor optimal: пол (закупка) не задан — пропуск', [
                'productId' => $productId,
                'currency' => $currency,
            ]);
            return $result;
        }

        $fromGop = (float)$rp['DISCOUNT_PRICE'];
        $fromChain = self::$lastMarketingPriceByProduct[$productId] ?? null;
        // Берём минимум из цены GOP и последней цепочки CountPriceWithDiscount = фактическая маркетинговая цена после всех скидок
        $marketingFinal = $fromChain !== null ? \min($fromGop, $fromChain) : $fromGop;

        if ($marketingFinal >= $floor) {
            return $result;
        }

        self::debugLog('applyFloor optimal: ПОДНИМАЕМ до пола (после полной маркетинговой цепочки)', [
            'productId' => $productId,
            'fromGetOptimalPrice' => $fromGop,
            'fromDiscountChain' => $fromChain,
            'marketingFinal' => $marketingFinal,
            'floor' => $floor,
            'basePrice' => $rp['BASE_PRICE'] ?? null,
        ]);

        $rp['DISCOUNT_PRICE'] = $floor;

        $baseRef = self::getBaseCatalogPriceForProductOrParent($productId, $currency);
        $basePrice = $baseRef !== null && $baseRef > 0 ? \max($baseRef, $floor) : \max((float)($rp['BASE_PRICE'] ?? 0), $floor);
        $rp['BASE_PRICE'] = $basePrice;

        $discountValue = max(0.0, $basePrice - $floor);
        if (array_key_exists('DISCOUNT', $rp)) {
            $rp['DISCOUNT'] = $discountValue;
        }
        if ($basePrice > 0) {
            $rp['PERCENT'] = round(100 * (1 - $floor / $basePrice), 2);
        } else {
            $rp['PERCENT'] = 0.0;
        }

        self::formatResultPricePrintFields($rp);

        return $result;
    }

    /**
     * @param array $rp Ссылка на RESULT_PRICE
     */
    private static function formatResultPricePrintFields(array &$rp): void
    {
        self::normalizeResultPriceAmountsForCurrency($rp);

        if (!Loader::includeModule('currency')) {
            return;
        }

        $currency = (string)($rp['CURRENCY'] ?? '');
        if ($currency === '') {
            return;
        }

        if (isset($rp['DISCOUNT_PRICE'])) {
            $rp['PRINT_DISCOUNT_PRICE'] = self::currencyFormatForDisplay((float)$rp['DISCOUNT_PRICE'], $currency);
        }
        if (isset($rp['BASE_PRICE'])) {
            $rp['PRINT_BASE_PRICE'] = self::currencyFormatForDisplay((float)$rp['BASE_PRICE'], $currency);
        }
        if (isset($rp['DISCOUNT'])) {
            $rp['PRINT_DISCOUNT'] = self::currencyFormatForDisplay((float)$rp['DISCOUNT'], $currency);
        }
    }

    /**
     * Обработчик sale OnSaleBasketItemBeforeSaved — поднимает цену строки до закупочного пола.
     *
     * Через EventManager передаётся Bitrix\Main\Event; через AddEventHandler (классический API) —
     * сразу Bitrix\Sale\BasketItem.
     *
     * @param Event|\Bitrix\Sale\BasketItem $eventOrItem
     */
    public static function onSaleBasketItemBeforeSaved($eventOrItem): void
    {
        if (!Loader::includeModule('sale') || !Loader::includeModule('catalog')) {
            self::debugLog('BasketItemBeforeSaved: sale или catalog не загружен');
            return;
        }

        if ($eventOrItem instanceof Event) {
            $item = $eventOrItem->getParameter('ENTITY');
        } elseif ($eventOrItem instanceof \Bitrix\Sale\BasketItem) {
            $item = $eventOrItem;
        } else {
            self::debugLog('BasketItemBeforeSaved: неизвестный аргумент', [
                'class' => \is_object($eventOrItem) ? \get_class($eventOrItem) : \gettype($eventOrItem),
            ]);
            return;
        }

        if (!$item instanceof \Bitrix\Sale\BasketItem) {
            self::debugLog('BasketItemBeforeSaved: ENTITY не BasketItem', [
                'class' => \is_object($item) ? \get_class($item) : \gettype($item),
            ]);
            return;
        }

        if (!self::isPricingOverrideActive()) {
            return;
        }

        self::applyFloorToBasketItem($item);
    }

    private static function applyFloorToBasketItem(\Bitrix\Sale\BasketItem $item): void
    {
        if (method_exists($item, 'isBundle') && $item->isBundle()) {
            self::debugLog('basket: пропуск (bundle)');
            return;
        }

        $productId = (int)$item->getField('PRODUCT_ID');
        if ($productId <= 0) {
            self::debugLog('basket: PRODUCT_ID пустой');
            return;
        }

        $currency = (string)$item->getField('CURRENCY');
        if ($currency === '') {
            self::debugLog('basket: CURRENCY пустой', ['productId' => $productId]);
            return;
        }

        // Для оформления/сохранения заказа применяем ту же маркетинговую цену, что и в витрине/корзине.
        // Иначе в заказ может уйти "оптовая" цена без скидки при корректном визуальном отображении.
        $groups = self::getCurrentUserGroupArrayForPricing();
        $siteId = \defined('SITE_ID') ? SITE_ID : null;
        $calc = self::computeAdvertisingWholesaleMarketingBreakdown($productId, $groups, 'N', $siteId, false, $currency);

        if ($calc !== null && (float)($calc['base'] ?? 0) > 0) {
            $newUnit = (float)($calc['final'] ?? 0);
            if ($newUnit > 0) {
                $fullUnit = (($calc['discount_source'] ?? '') === 'catalog_advertising_list')
                    ? $newUnit
                    : \max((float)($calc['base'] ?? 0), $newUnit);

                self::debugLog('basket: применяем marketing/floor цену', [
                    'productId' => $productId,
                    'basketId' => $item->getId(),
                    'oldPrice' => (float)$item->getField('PRICE'),
                    'newPrice' => $newUnit,
                    'newBase' => $fullUnit,
                    'discountSource' => $calc['discount_source'] ?? null,
                ]);

                $item->setField('CUSTOM_PRICE', 'Y');
                $item->setField('PRICE', $newUnit);
                $item->setField('BASE_PRICE', $fullUnit);

                return;
            }
        }

        $floor = self::getPurchaseFloorForProductOrParent($productId, $currency);
        if ($floor === null) {
            self::debugLog('basket: пол не задан', ['productId' => $productId, 'currency' => $currency]);
            return;
        }

        $price = (float)$item->getField('PRICE');
        if ($price >= $floor) {
            return;
        }

        self::debugLog('basket: ПОДНИМАЕМ PRICE до пола', [
            'productId' => $productId,
            'wasPrice' => $price,
            'floor' => $floor,
            'basketId' => $item->getId(),
        ]);

        $item->setField('CUSTOM_PRICE', 'Y');
        $item->setField('PRICE', $floor);

        $basePrice = (float)$item->getField('BASE_PRICE');
        if ($basePrice > 0 && $basePrice < $floor) {
            $item->setField('BASE_PRICE', $floor);
        }
    }

    /**
     * @see isPricingOverrideActive()
     */
    private static function isCatalogPricingUserAuthorized(): bool
    {
        return self::isPricingOverrideActive();
    }

    /**
     * Публичный фасад для получения процента скидки компании по группам пользователя.
     * Fail-safe: любые проблемы контракта Company приводят к 0.0 без фатала.
     *
     * @param array<int|string> $userGroupIds
     */
    public static function getCompanyDiscountPercentForUserGroups(array $userGroupIds): float
    {
        if ($userGroupIds === []) {
            return 0.0;
        }

        if (!\class_exists(Company::class)) {
            return 0.0;
        }

        try {
            $percent = (float) Company::getMaxCompanyDiscountPercentForUserGroups($userGroupIds);
        } catch (\Throwable $e) {
            self::debugLog('company discount percent fallback: Company contract unavailable', [
                'error' => $e->getMessage(),
            ]);

            return 0.0;
        }

        if (!\is_finite($percent) || $percent <= 0.0) {
            return 0.0;
        }

        return $percent;
    }

    /**
     * Публичный фасад для текущего авторизованного пользователя.
     */
    public static function getCurrentUserCompanyDiscountPercent(): float
    {
        if (!self::isCatalogPricingUserAuthorized()) {
            return 0.0;
        }

        return self::getCompanyDiscountPercentForUserGroups(self::getCurrentUserGroupArrayForPricing());
    }

    /**
     * @return list<int|string>
     */
    private static function getCurrentUserGroupArrayForPricing(): array
    {
        global $USER;
        if (\is_object($USER) && \method_exists($USER, 'GetUserGroupArray')) {
            $g = $USER->GetUserGroupArray();

            return \is_array($g) && $g !== [] ? $g : [2];
        }

        return [2];
    }

    /**
     * Единый пересчёт полей строки корзины (mutator) по цене за единицу и зачёркнутой базе.
     *
     * @param array<string, mixed> $rowData
     */
    private static function applyBasketRowRenderDerivedUnitPricing(array &$rowData, float $newUnit, float $fullUnit, string $currency): void
    {
        $qty = (float)($rowData['QUANTITY'] ?? 1);
        if ($qty <= 0) {
            return;
        }

        $discUnit = \max(0.0, $fullUnit - $newUnit);

        $rowData['PRICE'] = $newUnit;
        $rowData['FULL_PRICE'] = $fullUnit;
        $rowData['DISCOUNT_PRICE'] = $discUnit;
        $rowData['SHOW_DISCOUNT_PRICE'] = $discUnit > 0.00001;

        $rowData['PRICE_FORMATED'] = self::currencyFormatForDisplay($newUnit, $currency);
        $rowData['FULL_PRICE_FORMATED'] = self::currencyFormatForDisplay($fullUnit, $currency);
        $rowData['DISCOUNT_PRICE_FORMATED'] = self::currencyFormatForDisplay($discUnit, $currency);

        if ($fullUnit > 0.00001) {
            $pctRaw = 100 * (1 - $newUnit / $fullUnit);
            $rowData['DISCOUNT_PRICE_PERCENT'] = \round($pctRaw, 2);
        } else {
            $pctRaw = 0.0;
            $rowData['DISCOUNT_PRICE_PERCENT'] = 0.0;
        }
        $pct = (float)$rowData['DISCOUNT_PRICE_PERCENT'];
        if ($pct <= 0.00001) {
            $rowData['DISCOUNT_PRICE_PERCENT_FORMATED'] = '0%';
        } else {
            // Только визуал: цены не меняем, округляем процент для стикера/колонки «Скидка».
            $rowData['DISCOUNT_PRICE_PERCENT_FORMATED'] = (string)(int)\round($pctRaw) . '%';
        }

        $sumLine = \Bitrix\Sale\PriceMaths::roundPrecision($newUnit * $qty);
        $sumFullLine = \Bitrix\Sale\PriceMaths::roundPrecision($fullUnit * $qty);
        $sumDiscLine = \Bitrix\Sale\PriceMaths::roundPrecision($sumFullLine - $sumLine);

        $rowData['SUM_PRICE'] = $sumLine;
        $rowData['SUM_PRICE_FORMATED'] = self::currencyFormatForDisplay($sumLine, $currency);
        $rowData['SUM_FULL_PRICE'] = $sumFullLine;
        $rowData['SUM_FULL_PRICE_FORMATED'] = self::currencyFormatForDisplay($sumFullLine, $currency);
        $rowData['SUM_DISCOUNT_PRICE'] = $sumDiscLine;
        $rowData['SUM_DISCOUNT_PRICE_FORMATED'] = self::currencyFormatForDisplay($sumDiscLine, $currency);

        self::syncBasketRowDiscountColumnList($rowData);
    }

    /**
     * Данные строки для mustache (mutator шаблона sale.basket.basket): как на витрине (опт + маркетинг/группа + пол);
     * если расчёт недоступен — только подъём до закупки при цене ниже пола.
     *
     * @param array<string, mixed> $rowData
     */
    public static function applyFloorToBasketRowRenderData(array &$rowData): void
    {
        if (!Loader::includeModule('catalog') || !Loader::includeModule('currency') || !Loader::includeModule('sale')) {
            return;
        }

        if (!self::isPricingOverrideActive()) {
            return;
        }

        $productId = (int)($rowData['PRODUCT_ID'] ?? 0);
        if ($productId <= 0) {
            return;
        }

        $currency = (string)($rowData['CURRENCY'] ?? '');
        if ($currency === '') {
            return;
        }

        $qty = (float)($rowData['QUANTITY'] ?? 1);
        if ($qty <= 0) {
            return;
        }

        $groups = self::getCurrentUserGroupArrayForPricing();
        $siteId = \defined('SITE_ID') ? SITE_ID : null;
        $calc = self::computeAdvertisingWholesaleMarketingBreakdown($productId, $groups, 'N', $siteId, false, $currency);
        if ($calc !== null && (float)$calc['base'] > 0) {
            $newUnit = (float)$calc['final'];
            $fullUnit = (($calc['discount_source'] ?? '') === 'catalog_advertising_list')
                ? $newUnit
                : \max((float)$calc['base'], $newUnit);
            self::applyBasketRowRenderDerivedUnitPricing($rowData, $newUnit, $fullUnit, $currency);

            return;
        }

        $floor = self::getPurchaseFloorForProductOrParent($productId, $currency);
        if ($floor === null) {
            return;
        }

        $unitPrice = (float)($rowData['PRICE'] ?? 0);
        if ($unitPrice >= $floor) {
            return;
        }

        $newUnit = $floor;

        // Скидка и экономия в корзине — от оптовой базы (BASE_PRICE_TYPE_ID = 2), не от рекламной FULL в строке.
        $baseRef = self::getBaseCatalogPriceForProductOrParent($productId, $currency);
        if ($baseRef !== null && $baseRef > 0) {
            $fullUnit = \max($baseRef, $newUnit);
        } else {
            $fullUnit = (float)($rowData['FULL_PRICE'] ?? 0);
            if ($fullUnit < $newUnit) {
                $fullUnit = $newUnit;
            }
        }

        self::applyBasketRowRenderDerivedUnitPricing($rowData, $newUnit, $fullUnit, $currency);
    }

    /**
     * Все строки b_catalog_price по товару (для отладки корзины).
     *
     * @return list<array<string, mixed>>
     */
    public static function debugCatalogPriceRowsForProduct(int $productId): array
    {
        if (!Loader::includeModule('catalog') || $productId <= 0) {
            return [];
        }
        $out = [];
        $res = \Bitrix\Catalog\PriceTable::getList([
            'filter' => ['=PRODUCT_ID' => $productId],
            'select' => ['CATALOG_GROUP_ID', 'PRICE', 'CURRENCY'],
            'order' => ['CATALOG_GROUP_ID' => 'ASC'],
        ]);
        while ($r = $res->fetch()) {
            $out[] = $r;
        }

        return $out;
    }

    /**
     * Mutator строит COLUMN_LIST из исходной строки до applyFloor — обновляем/убираем колонку «Скидка».
     *
     * @param array<string, mixed> $rowData
     */
    private static function syncBasketRowDiscountColumnList(array &$rowData): void
    {
        if (empty($rowData['COLUMN_LIST']) || !\is_array($rowData['COLUMN_LIST'])) {
            return;
        }

        $percent = (float)($rowData['DISCOUNT_PRICE_PERCENT'] ?? 0);
        $formatted = (string)($rowData['DISCOUNT_PRICE_PERCENT_FORMATED'] ?? '');

        $newList = [];
        foreach ($rowData['COLUMN_LIST'] as $col) {
            if (!\is_array($col) || ($col['CODE'] ?? '') !== 'DISCOUNT') {
                $newList[] = $col;
                continue;
            }
            $col['VALUE'] = $percent <= 0.00001 ? '0%' : $formatted;
            $newList[] = $col;
        }
        $rowData['COLUMN_LIST'] = $newList;
    }

    /**
     * После правок строк mutator — пересчитать итоги корзины для JS (TOTAL_RENDER_DATA).
     *
     * @param array<string, mixed> $result
     */
    public static function recalculateBasketResultTotalsAfterFloor(array &$result): void
    {
        if (!Loader::includeModule('currency') || !Loader::includeModule('sale')) {
            return;
        }

        if (!self::isPricingOverrideActive()) {
            return;
        }

        if (empty($result['BASKET_ITEM_RENDER_DATA']) || !\is_array($result['BASKET_ITEM_RENDER_DATA'])) {
            return;
        }

        $currency = (string)($result['CURRENCY'] ?? '');
        if ($currency === '') {
            return;
        }

        $sumAll = 0.0;
        $sumFullAll = 0.0;
        foreach ($result['BASKET_ITEM_RENDER_DATA'] as $line) {
            $sumAll += (float)($line['SUM_PRICE'] ?? 0);
            $sumFullAll += (float)($line['SUM_FULL_PRICE'] ?? 0);
        }

        $result['allSum'] = \Bitrix\Sale\PriceMaths::roundPrecision($sumAll);
        $result['allSum_FORMATED'] = self::currencyFormatForDisplay((float)$result['allSum'], $currency);

        $discAll = \max(0.0, \Bitrix\Sale\PriceMaths::roundPrecision($sumFullAll - $sumAll));
        $result['DISCOUNT_PRICE_ALL'] = $discAll;
        if (isset($result['DISCOUNT_PRICE_FORMATED'])) {
            $result['DISCOUNT_PRICE_FORMATED'] = self::currencyFormatForDisplay($discAll, $currency);
        }

        if (\array_key_exists('PRICE_WITHOUT_DISCOUNT', $result)) {
            $result['PRICE_WITHOUT_DISCOUNT'] = self::currencyFormatForDisplay(
                \Bitrix\Sale\PriceMaths::roundPrecision($sumFullAll),
                $currency
            );
        }
    }

    /**
     * Каталожный PRODUCT_ID для строки мини-корзины Intec (часто в шаблоне только ID строки корзины).
     *
     * @param array<string, mixed> $item
     */
    private static function resolveCatalogProductIdForBasketSmallItem(array $item): int
    {
        $from = [
            $item['PRODUCT_ID'] ?? null,
            $item['productId'] ?? null,
            $item['PRODUCT']['ID'] ?? null,
            $item['ELEMENT_ID'] ?? null,
        ];
        foreach ($from as $v) {
            if ($v !== null && (int)$v > 0) {
                return (int)$v;
            }
        }

        if (!empty($item['ID']) && Loader::includeModule('sale') && \class_exists(\Bitrix\Sale\Internals\BasketTable::class)) {
            $row = \Bitrix\Sale\Internals\BasketTable::getList([
                'filter' => ['=ID' => (int)$item['ID']],
                'select' => ['PRODUCT_ID'],
                'limit' => 1,
            ])->fetch();
            if ($row && !empty($row['PRODUCT_ID'])) {
                return (int)$row['PRODUCT_ID'];
            }
        }

        // intec.universe sale.basket.small: в ITEMS часто подставляют ID элемента каталога (ТП), а не PK b_sale_basket.
        if (!empty($item['ID']) && Loader::includeModule('catalog')) {
            $candidate = (int)$item['ID'];
            if ($candidate > 0 && \CCatalogProduct::GetByID($candidate) !== false) {
                return $candidate;
            }
        }

        return 0;
    }

    /**
     * @param array<string, mixed> $item
     */
    private static function applyFloorToBasketSmallItemRow(array &$item, string $defaultCurrency): void
    {
        $productId = self::resolveCatalogProductIdForBasketSmallItem($item);
        if ($productId <= 0) {
            return;
        }

        $cur = (string)($item['PRICE']['CURRENCY'] ?? $defaultCurrency);
        if ($cur === '') {
            $cur = $defaultCurrency;
        }

        $floor = self::getPurchaseFloorForProductOrParent($productId, $cur);
        if ($floor === null) {
            return;
        }

        $unitDisc = (float)($item['PRICE']['DISCOUNT']['VALUE'] ?? 0);
        if ($unitDisc >= $floor) {
            return;
        }

        $newUnit = $floor;

        // Зачёркнутая цена в шапке — от оптовой базы (BASE_PRICE_TYPE_ID), иначе совпадает с новой и span скрыт.
        $baseRef = self::getBaseCatalogPriceForProductOrParent($productId, $cur);
        if ($baseRef !== null && $baseRef > 0) {
            $fullUnit = \max($baseRef, $newUnit);
        } else {
            $fullUnit = (float)($item['PRICE']['BASE']['VALUE'] ?? 0);
            if ($fullUnit < $newUnit) {
                $fullUnit = $newUnit;
            }
        }

        $item['PRICE']['DISCOUNT']['VALUE'] = $newUnit;
        $item['PRICE']['BASE']['VALUE'] = $fullUnit;

        if (Loader::includeModule('currency')) {
            $item['PRICE']['DISCOUNT']['DISPLAY'] = self::currencyFormatForDisplay($newUnit, $cur);
            $item['PRICE']['BASE']['DISPLAY'] = self::currencyFormatForDisplay($fullUnit, $cur);
        }
    }

    /**
     * Мини-корзина: подставить отображаемые цены как на витрине (опт + группа/маркетинг + пол).
     *
     * @param array<string, mixed> $item
     */
    private static function syncBasketSmallItemDisplayFromAdvertisingBreakdown(array &$item, string $defaultCurrency): bool
    {
        if (!Loader::includeModule('catalog') || !Loader::includeModule('currency')) {
            return false;
        }

        $productId = self::resolveCatalogProductIdForBasketSmallItem($item);
        if ($productId <= 0) {
            return false;
        }

        $cur = (string)($item['PRICE']['CURRENCY'] ?? $defaultCurrency);
        if ($cur === '') {
            $cur = $defaultCurrency;
        }
        if ($cur === '') {
            return false;
        }

        $groups = self::getCurrentUserGroupArrayForPricing();
        $siteId = \defined('SITE_ID') ? SITE_ID : null;
        $calc = self::computeAdvertisingWholesaleMarketingBreakdown($productId, $groups, 'N', $siteId, false, $cur);
        if ($calc === null || (float)$calc['base'] <= 0) {
            return false;
        }

        $newUnit = (float)$calc['final'];
        $fullUnit = (($calc['discount_source'] ?? '') === 'catalog_advertising_list')
            ? $newUnit
            : \max((float)$calc['base'], $newUnit);

        if (!isset($item['PRICE']) || !\is_array($item['PRICE'])) {
            return false;
        }
        if (!isset($item['PRICE']['DISCOUNT']) || !\is_array($item['PRICE']['DISCOUNT'])) {
            $item['PRICE']['DISCOUNT'] = [];
        }
        if (!isset($item['PRICE']['BASE']) || !\is_array($item['PRICE']['BASE'])) {
            $item['PRICE']['BASE'] = [];
        }

        $item['PRICE']['DISCOUNT']['VALUE'] = $newUnit;
        $item['PRICE']['BASE']['VALUE'] = $fullUnit;
        $item['PRICE']['DISCOUNT']['DISPLAY'] = self::currencyFormatForDisplay($newUnit, $cur);
        $item['PRICE']['BASE']['DISPLAY'] = self::currencyFormatForDisplay($fullUnit, $cur);

        return true;
    }

    /**
     * Мини-корзина (intec.universe sale.basket.small): $arResult['BASKET']['ITEMS'] и суммы в шапке.
     *
     * @param array<string, mixed> $arResult
     */
    public static function syncSaleBasketSmallDisplay(array &$arResult): void
    {
        if (!Loader::includeModule('catalog') || !Loader::includeModule('sale') || !Loader::includeModule('currency')) {
            return;
        }

        if (!self::isPricingOverrideActive()) {
            return;
        }

        $currencyCode = (string)($arResult['CURRENCY']['CODE'] ?? '');
        if ($currencyCode === '' && isset($arResult['CURRENCY']) && \is_string($arResult['CURRENCY'])) {
            $currencyCode = $arResult['CURRENCY'];
        }
        if ($currencyCode === '' && !empty($arResult['BASKET']['ITEMS'][0]['PRICE']['CURRENCY'])) {
            $currencyCode = (string)$arResult['BASKET']['ITEMS'][0]['PRICE']['CURRENCY'];
        }
        if ($currencyCode === '') {
            return;
        }

        if (!empty($arResult['BASKET']['ITEMS']) && \is_array($arResult['BASKET']['ITEMS'])) {
            foreach ($arResult['BASKET']['ITEMS'] as &$item) {
                if (!self::syncBasketSmallItemDisplayFromAdvertisingBreakdown($item, $currencyCode)) {
                    self::applyFloorToBasketSmallItemRow($item, $currencyCode);
                }
            }
            unset($item);
        }

        if (!empty($arResult['DELAYED']['ITEMS']) && \is_array($arResult['DELAYED']['ITEMS'])) {
            foreach ($arResult['DELAYED']['ITEMS'] as &$item) {
                if (!self::syncBasketSmallItemDisplayFromAdvertisingBreakdown($item, $currencyCode)) {
                    self::applyFloorToBasketSmallItemRow($item, $currencyCode);
                }
            }
            unset($item);
        }

        if (empty($arResult['BASKET']['ITEMS']) || !\is_array($arResult['BASKET']['ITEMS'])) {
            return;
        }

        $sumDisc = 0.0;
        $sumBase = 0.0;
        foreach ($arResult['BASKET']['ITEMS'] as $it) {
            $q = (float)($it['QUANTITY']['VALUE'] ?? $it['QUANTITY'] ?? 0);
            if ($q <= 0) {
                continue;
            }
            $sumDisc += \Bitrix\Sale\PriceMaths::roundPrecision((float)($it['PRICE']['DISCOUNT']['VALUE'] ?? 0) * $q);
            $sumBase += \Bitrix\Sale\PriceMaths::roundPrecision((float)($it['PRICE']['BASE']['VALUE'] ?? 0) * $q);
        }

        if (!isset($arResult['BASKET']['SUM'])) {
            $arResult['BASKET']['SUM'] = [];
        }
        if (!isset($arResult['BASKET']['SUM']['DISCOUNT'])) {
            $arResult['BASKET']['SUM']['DISCOUNT'] = [];
        }
        if (!isset($arResult['BASKET']['SUM']['BASE'])) {
            $arResult['BASKET']['SUM']['BASE'] = [];
        }

        $arResult['BASKET']['SUM']['DISCOUNT']['VALUE'] = $sumDisc;
        $arResult['BASKET']['SUM']['DISCOUNT']['DISPLAY'] = self::currencyFormatForDisplay($sumDisc, $currencyCode);

        $arResult['BASKET']['SUM']['BASE']['VALUE'] = $sumBase;
        $arResult['BASKET']['SUM']['BASE']['DISPLAY'] = self::currencyFormatForDisplay($sumBase, $currencyCode);
    }

    /**
     * Валюта для compute/buildAdvertising: у неавторизованного иногда пусто у ITEM_PRICES[0], иначе compute() → null
     * и в рекламную строку попадает оптовый RESULT_PRICE из GetOptimalPrice.
     *
     * @param array<string, mixed> $offer
     */
    private static function resolveCatalogOfferCurrencyForAdvertising(array $offer): string
    {
        if (!empty($offer['ITEM_PRICES']) && \is_array($offer['ITEM_PRICES'])) {
            foreach ($offer['ITEM_PRICES'] as $ip) {
                if (!\is_array($ip)) {
                    continue;
                }
                $c = (string)($ip['CURRENCY'] ?? '');
                if ($c !== '') {
                    return $c;
                }
            }
        }
        if (!empty($offer['MIN_PRICE']['CURRENCY'])) {
            $c = (string)$offer['MIN_PRICE']['CURRENCY'];
            if ($c !== '') {
                return $c;
            }
        }
        if (!empty($offer['PRICES']) && \is_array($offer['PRICES'])) {
            foreach ($offer['PRICES'] as $pr) {
                if (!\is_array($pr)) {
                    continue;
                }
                $c = (string)($pr['CURRENCY'] ?? '');
                if ($c !== '') {
                    return $c;
                }
            }
        }
        if (Loader::includeModule('currency') && \class_exists(\CCurrency::class)) {
            $c = (string)\CCurrency::GetBaseCurrency();
            if ($c !== '') {
                return $c;
            }
        }

        return '';
    }

    /**
     * Один SKU (ТП) или товар без ТП: PRICES / ITEM_PRICES / MIN_PRICE с GetOptimalPrice;
     * тип 3 (рекламная): при скидке ниже закупки — закупка, иначе цена со скидкой от оптовой базы (2 / резерв 1).
     *
     * @param array<string, mixed> $offer
     */
    public static function syncCatalogSkuOfferDisplayFromOptimal(array &$offer): void
    {
        if (!Loader::includeModule('catalog')) {
            return;
        }

        global $USER;
        $groups = [2];
        if (\is_object($USER) && \method_exists($USER, 'GetUserGroupArray')) {
            $g = $USER->GetUserGroupArray();
            if (\is_array($g) && $g !== []) {
                $groups = $g;
            }
        }

        $siteId = \defined('SITE_ID') ? SITE_ID : null;

        $productId = (int)($offer['ID'] ?? 0);
        if ($productId <= 0) {
            return;
        }

        $priceDebug = self::isDebugEnabledForProduct($productId);

        $debugBefore = null;
        if ($priceDebug) {
            $debugBefore = [
                'MIN_PRICE' => $offer['MIN_PRICE'] ?? null,
                'ITEM_PRICE_type_3' => null,
                'PRICES_snapshot_before_sync' => self::debugExtractAdvertisingPricesRowsSnapshot($offer),
            ];
            if (!empty($offer['ITEM_PRICES']) && \is_array($offer['ITEM_PRICES'])) {
                foreach ($offer['ITEM_PRICES'] as $ip) {
                    if ((int)($ip['PRICE_TYPE_ID'] ?? $ip['PRICE_ID'] ?? 0) === self::ADVERTISING_PRICE_TYPE_ID) {
                        $debugBefore['ITEM_PRICE_type_3'] = $ip;
                        break;
                    }
                }
            }
        }

        \CCatalogProduct::setUseDiscount(true);
        $opt = \CCatalogProduct::GetOptimalPrice($productId, 1, $groups, 'N', false, $siteId, false);
        if ($opt !== false && self::shouldRebuildOptimalStuckOnAdvertisingIgnoringWholesale($productId, $opt)) {
            $reb = self::rebuildOptimalResultFromWholesaleBase(
                $productId,
                1,
                $groups,
                'N',
                $siteId,
                false,
                $opt
            );
            if ($reb !== null) {
                $opt = $reb;
            }
        }

        if ($opt !== false && \is_array($opt)) {
            $opt = self::maybeApplyCatalogDiscountsWhenFinalEqualsBase(
                $productId,
                $groups,
                'N',
                $siteId,
                false,
                $opt
            );
        }

        $currencyForAdvertising = self::resolveCatalogOfferCurrencyForAdvertising($offer);

        $rpForAdvertisingMerge = self::buildAdvertisingDisplayResultPriceFromWholesaleAndMarketing(
            $productId,
            $groups,
            'N',
            $siteId,
            false,
            $currencyForAdvertising,
            $opt
        );

        $optimalMerged = false;
        if ($rpForAdvertisingMerge !== null) {
            $optimalMerged = true;
            if (!empty($offer['MIN_PRICE']) && \is_array($offer['MIN_PRICE'])) {
                self::mergeResultPriceIntoMinPrice($offer['MIN_PRICE'], $rpForAdvertisingMerge);
            }
            if (!empty($offer['ITEM_PRICES']) && \is_array($offer['ITEM_PRICES'])) {
                foreach ($offer['ITEM_PRICES'] as &$ip) {
                    $pt = (int)($ip['PRICE_TYPE_ID'] ?? $ip['PRICE_ID'] ?? 0);
                    if ($pt === self::ADVERTISING_PRICE_TYPE_ID) {
                        self::mergeResultPriceIntoItemPriceRow($ip, $rpForAdvertisingMerge);
                    }
                }
                unset($ip);
            }
            self::mergeAdvertisingResultPriceIntoOfferPricesArray($offer, $rpForAdvertisingMerge);
        } elseif (
            $opt !== false
            && !empty($opt['RESULT_PRICE'])
            && \is_array($opt['RESULT_PRICE'])
            && (int)($opt['RESULT_PRICE']['PRICE_TYPE_ID'] ?? 0) === self::ADVERTISING_PRICE_TYPE_ID
        ) {
            $optimalMerged = true;
            $rpOpt = $opt['RESULT_PRICE'];
            if (!empty($offer['MIN_PRICE']) && \is_array($offer['MIN_PRICE'])) {
                self::mergeResultPriceIntoMinPrice($offer['MIN_PRICE'], $rpOpt);
            }
            if (!empty($offer['ITEM_PRICES']) && \is_array($offer['ITEM_PRICES'])) {
                foreach ($offer['ITEM_PRICES'] as &$ip) {
                    $pt = (int)($ip['PRICE_TYPE_ID'] ?? $ip['PRICE_ID'] ?? 0);
                    if ($pt === self::ADVERTISING_PRICE_TYPE_ID) {
                        self::mergeResultPriceIntoItemPriceRow($ip, $rpOpt);
                    }
                }
                unset($ip);
            }
            self::mergeAdvertisingResultPriceIntoOfferPricesArray($offer, $rpOpt);
        }

        self::clampAdvertisingCatalogPriceRows($productId, $offer, $optimalMerged);

        if ($priceDebug) {
            $currency = '';
            if (!empty($offer['ITEM_PRICES']) && \is_array($offer['ITEM_PRICES'])) {
                $currency = (string)($offer['ITEM_PRICES'][0]['CURRENCY'] ?? '');
            }
            if ($currency === '' && !empty($offer['MIN_PRICE']['CURRENCY'])) {
                $currency = (string)$offer['MIN_PRICE']['CURRENCY'];
            }

            $debugCatalogDiscount = [
                'rules_count' => 0,
                'trial_CountPriceWithDiscount' => null,
                'RESULT_PRICE_TYPE_ID_used' => null,
                'lookup_product_ids' => self::getCatalogProductIdsForDiscountLookup($productId),
            ];
            if ($opt !== false && \is_array($opt) && !empty($opt['RESULT_PRICE']) && \is_array($opt['RESULT_PRICE'])) {
                $rpDbg = $opt['RESULT_PRICE'];
                $siteArgDbg = ($siteId !== false && $siteId !== null && (string)$siteId !== '')
                    ? $siteId
                    : (\defined('SITE_ID') ? (string)SITE_ID : false);
                $typeIdDbg = (int)($rpDbg['PRICE_TYPE_ID'] ?? self::BASE_PRICE_TYPE_ID);
                $debugCatalogDiscount['RESULT_PRICE_TYPE_ID_used'] = $typeIdDbg;
                $curDbg = (string)($rpDbg['CURRENCY'] ?? $currency);
                $baseDbg = (float)($rpDbg['BASE_PRICE'] ?? 0);
                $rulesDbg = self::fetchCatalogDiscountArraysForProduct(
                    $productId,
                    $groups,
                    'N',
                    $typeIdDbg,
                    $siteArgDbg,
                    false
                );
                $debugCatalogDiscount['rules_count'] = \count($rulesDbg);
                if ($rulesDbg !== [] && $curDbg !== '' && $baseDbg > 0) {
                    \CCatalogProduct::setUseDiscount(true);
                    self::$currentOptimalPriceProductId = $productId;
                    try {
                        $rawDbg = \CCatalogProduct::CountPriceWithDiscount($baseDbg, $curDbg, $rulesDbg);
                        $debugCatalogDiscount['trial_CountPriceWithDiscount'] = self::normalizeCountPriceWithDiscountResult($rawDbg);
                    } finally {
                        self::$currentOptimalPriceProductId = null;
                    }
                }
            }

            $baseResolved = ($currency !== '') ? self::getBaseCatalogPriceForProductOrParent($productId, $currency) : null;
            $baseOptovaya2 = ($currency !== '')
                ? self::getCatalogPriceAmountForProductAndGroup($productId, $currency, self::BASE_PRICE_TYPE_ID)
                : null;
            $baseBitrix1 = ($currency !== '')
                ? self::getCatalogPriceAmountForProductAndGroup($productId, $currency, self::BASE_PRICE_FALLBACK_TYPE_ID)
                : null;
            $floor4 = ($currency !== '') ? self::getPurchaseFloorForProductOrParent($productId, $currency) : null;
            $type3After = null;
            if (!empty($offer['ITEM_PRICES']) && \is_array($offer['ITEM_PRICES'])) {
                foreach ($offer['ITEM_PRICES'] as $ip) {
                    if ((int)($ip['PRICE_TYPE_ID'] ?? $ip['PRICE_ID'] ?? 0) === self::ADVERTISING_PRICE_TYPE_ID) {
                        $type3After = $ip;
                        break;
                    }
                }
            }
            self::debugLog('os_price_debug: syncCatalogSkuOfferDisplayFromOptimal', [
                'productId' => $productId,
                'currency' => $currency,
                'debug_before' => $debugBefore,
                'debug_after' => [
                    'MIN_PRICE' => $offer['MIN_PRICE'] ?? null,
                    'ITEM_PRICE_type_3' => $type3After,
                    'PRICES_snapshot_after_sync' => self::debugExtractAdvertisingPricesRowsSnapshot($offer),
                ],
                'catalog_discount_probe' => $debugCatalogDiscount,
                'base_resolved' => $baseResolved,
                'base_optovaya_type_2' => $baseOptovaya2,
                'base_fallback_type_1' => $baseBitrix1,
                'purchase_floor_type_4' => $floor4,
                'optimal_merged' => $optimalMerged,
                'native_gop_probe' => self::debugProbeNativeAdvertisingGetOptimalPrice(
                    $productId,
                    $groups,
                    'N',
                    $siteId,
                    false
                ),
                'bitrix_discount_mode' => self::debugBitrixDiscountModeOptions(),
            ]);
        }
    }

    /**
     * После расчёта компонента catalog.element: массивы PRICES / ITEM_PRICES / MIN_PRICE
     * могут не совпадать с GetOptimalPrice (хук уже поднимает цену до закупки).
     * Синхронизируем отображение карточки с актуальным оптимальным расчётом и полом по типу 3.
     *
     * @param array<string, mixed> $arResult
     */
    public static function syncCatalogElementDisplayFromOptimal(array &$arResult): void
    {
        if (!Loader::includeModule('catalog')) {
            return;
        }

        if (!self::isPricingOverrideActive()) {
            return;
        }

        if (!empty($arResult['OFFERS']) && \is_array($arResult['OFFERS'])) {
            foreach ($arResult['OFFERS'] as &$arOffer) {
                self::syncCatalogSkuOfferDisplayFromOptimal($arOffer);
            }
            unset($arOffer);

            $arPricesAgg = null;
            $arMinAgg = null;
            foreach ($arResult['OFFERS'] as &$arOffer) {
                if (!empty($arOffer['ITEM_PRICES'])) {
                    if ($arPricesAgg === null || (float)$arPricesAgg[0]['PRICE'] > (float)$arOffer['ITEM_PRICES'][0]['PRICE']) {
                        $arPricesAgg = $arOffer['ITEM_PRICES'];
                    }
                }
                if (!empty($arOffer['MIN_PRICE'])) {
                    if ($arMinAgg === null || (float)$arMinAgg['DISCOUNT_VALUE'] > (float)$arOffer['MIN_PRICE']['DISCOUNT_VALUE']) {
                        $arMinAgg = $arOffer['MIN_PRICE'];
                    }
                }
            }
            unset($arOffer);
            $arResult['ITEM_PRICES'] = $arPricesAgg;
            $arResult['MIN_PRICE'] = $arMinAgg;
        } else {
            self::syncCatalogSkuOfferDisplayFromOptimal($arResult);
        }
    }

    /**
     * Раздел каталога (catalog.section): для каждого элемента списка те же правки, что в карточке.
     *
     * @param array<string, mixed> $arResult
     */
    public static function syncCatalogSectionItemsDisplayFromOptimal(array &$arResult): void
    {
        if (!Loader::includeModule('catalog')) {
            return;
        }

        if (!self::isPricingOverrideActive()) {
            return;
        }

        if (empty($arResult['ITEMS']) || !\is_array($arResult['ITEMS'])) {
            return;
        }

        foreach ($arResult['ITEMS'] as &$arItem) {
            if (!empty($arItem['OFFERS']) && \is_array($arItem['OFFERS'])) {
                foreach ($arItem['OFFERS'] as &$arOffer) {
                    self::syncCatalogSkuOfferDisplayFromOptimal($arOffer);
                }
                unset($arOffer);

                $arPrices = null;
                $arPrice = null;
                foreach ($arItem['OFFERS'] as &$arOffer) {
                    if (!empty($arOffer['ITEM_PRICES'])) {
                        if ($arPrices === null || (float)$arPrices[0]['PRICE'] > (float)$arOffer['ITEM_PRICES'][0]['PRICE']) {
                            $arPrices = $arOffer['ITEM_PRICES'];
                            $arItem['CATALOG_MEASURE_RATIO'] = $arOffer['CATALOG_MEASURE_RATIO'];
                            $arItem['CATALOG_MEASURE_NAME'] = $arOffer['CATALOG_MEASURE_NAME'];
                        }
                    }
                    if (!empty($arOffer['MIN_PRICE'])) {
                        if ($arPrice === null || (float)$arPrice['DISCOUNT_VALUE'] > (float)$arOffer['MIN_PRICE']['DISCOUNT_VALUE']) {
                            $arPrice = $arOffer['MIN_PRICE'];
                        }
                    }
                }
                unset($arOffer);

                $arItem['MIN_PRICE'] = $arPrice;
                $arItem['ITEM_PRICES'] = $arPrices;
            } else {
                self::syncCatalogSkuOfferDisplayFromOptimal($arItem);
            }
        }
        unset($arItem);
    }

    /**
     * @param array<string, mixed> $minPrice
     * @param array<string, mixed> $rp
     */
    private static function mergeResultPriceIntoMinPrice(array &$minPrice, array $rp): void
    {
        if (!Loader::includeModule('currency')) {
            return;
        }
        $currency = (string)($rp['CURRENCY'] ?? $minPrice['CURRENCY'] ?? '');
        if ($currency === '') {
            return;
        }
        $pt = (int)($rp['PRICE_TYPE_ID'] ?? 0);
        $disc = $pt > 0
            ? self::roundPriceAmountForCatalogGroup((float)($rp['DISCOUNT_PRICE'] ?? 0), $currency, $pt)
            : self::roundPriceAmountForCurrency((float)($rp['DISCOUNT_PRICE'] ?? 0), $currency);
        $baseGroup = ($pt === self::ADVERTISING_PRICE_TYPE_ID) ? self::BASE_PRICE_TYPE_ID : ($pt > 0 ? $pt : 0);
        $base = $baseGroup > 0
            ? self::roundPriceAmountForCatalogGroup((float)($rp['BASE_PRICE'] ?? 0), $currency, $baseGroup)
            : self::roundPriceAmountForCurrency((float)($rp['BASE_PRICE'] ?? 0), $currency);
        if ($base < $disc) {
            $base = $disc;
        }
        $diff = self::roundPriceAmountForCurrency(\max(0.0, $base - $disc), $currency);
        $percent = isset($rp['PERCENT']) ? (float)$rp['PERCENT'] : ($base > 0 ? \round(100 * (1 - $disc / $base), 2) : 0.0);

        $minPrice['DISCOUNT_VALUE'] = $disc;
        $minPrice['VALUE'] = $base;
        $minPrice['CURRENCY'] = $currency;
        $minPrice['PRINT_DISCOUNT_VALUE'] = self::currencyFormatForDisplay($disc, $currency);
        if (isset($minPrice['PRINT_VALUE'])) {
            $minPrice['PRINT_VALUE'] = self::currencyFormatForDisplay($base, $currency);
        }
        if (isset($minPrice['DISCOUNT_DIFF_PERCENT'])) {
            $minPrice['DISCOUNT_DIFF_PERCENT'] = $percent;
        }
        if (\array_key_exists('DISCOUNT_DIFF', $minPrice)) {
            $minPrice['DISCOUNT_DIFF'] = $diff;
        }
        if (\array_key_exists('PRINT_DISCOUNT_DIFF', $minPrice)) {
            $minPrice['PRINT_DISCOUNT_DIFF'] = self::currencyFormatForDisplay($diff, $currency);
        }

        $vatRate = (float)($rp['VAT_RATE'] ?? 0);
        $vatIncluded = (($rp['VAT_INCLUDED'] ?? 'Y') === 'Y');
        if ($vatIncluded) {
            $bNo = $vatRate > 0 ? $base / (1 + $vatRate) : $base;
            $dNo = $vatRate > 0 ? $disc / (1 + $vatRate) : $disc;
            $minPrice['VALUE_VAT'] = $base;
            $minPrice['DISCOUNT_VALUE_VAT'] = $disc;
            $minPrice['VALUE_NOVAT'] = $bNo;
            $minPrice['DISCOUNT_VALUE_NOVAT'] = $dNo;
            $minPrice['VATRATE_VALUE'] = $base - $bNo;
            $minPrice['DISCOUNT_VATRATE_VALUE'] = $disc - $dNo;
        } else {
            $minPrice['VALUE_NOVAT'] = $base;
            $minPrice['DISCOUNT_VALUE_NOVAT'] = $disc;
            $bVat = $vatRate > 0 ? $base * (1 + $vatRate) : $base;
            $dVat = $vatRate > 0 ? $disc * (1 + $vatRate) : $disc;
            $minPrice['VALUE_VAT'] = $bVat;
            $minPrice['DISCOUNT_VALUE_VAT'] = $dVat;
            $minPrice['VATRATE_VALUE'] = $bVat - $base;
            $minPrice['DISCOUNT_VATRATE_VALUE'] = $dVat - $disc;
        }
        if (\array_key_exists('ROUND_VALUE_VAT', $minPrice)) {
            $minPrice['ROUND_VALUE_VAT'] = $minPrice['VALUE_VAT'];
        }
        if (\array_key_exists('ROUND_VALUE_NOVAT', $minPrice)) {
            $minPrice['ROUND_VALUE_NOVAT'] = $minPrice['VALUE_NOVAT'];
        }
        if (\array_key_exists('ROUND_VATRATE_VALUE', $minPrice)) {
            $minPrice['ROUND_VATRATE_VALUE'] = $minPrice['VATRATE_VALUE'];
        }
        if (\array_key_exists('UNROUND_DISCOUNT_VALUE', $minPrice)) {
            $minPrice['UNROUND_DISCOUNT_VALUE'] = $disc;
        }
        if (\array_key_exists('PRINT_VALUE_VAT', $minPrice)) {
            $minPrice['PRINT_VALUE_VAT'] = self::currencyFormatForDisplay((float)$minPrice['VALUE_VAT'], $currency);
        }
        if (\array_key_exists('PRINT_VALUE_NOVAT', $minPrice)) {
            $minPrice['PRINT_VALUE_NOVAT'] = self::currencyFormatForDisplay((float)$minPrice['VALUE_NOVAT'], $currency);
        }
        if (\array_key_exists('PRINT_DISCOUNT_VALUE_VAT', $minPrice)) {
            $minPrice['PRINT_DISCOUNT_VALUE_VAT'] = self::currencyFormatForDisplay((float)$minPrice['DISCOUNT_VALUE_VAT'], $currency);
        }
        if (\array_key_exists('PRINT_DISCOUNT_VALUE_NOVAT', $minPrice)) {
            $minPrice['PRINT_DISCOUNT_VALUE_NOVAT'] = self::currencyFormatForDisplay((float)$minPrice['DISCOUNT_VALUE_NOVAT'], $currency);
        }
        if (\array_key_exists('PRINT_VATRATE_VALUE', $minPrice)) {
            $minPrice['PRINT_VATRATE_VALUE'] = self::currencyFormatForDisplay((float)$minPrice['VATRATE_VALUE'], $currency);
        }
        if (\array_key_exists('PRINT_DISCOUNT_VATRATE_VALUE', $minPrice)) {
            $minPrice['PRINT_DISCOUNT_VATRATE_VALUE'] = self::currencyFormatForDisplay((float)$minPrice['DISCOUNT_VATRATE_VALUE'], $currency);
        }
    }

    /**
     * ID строки b_catalog_price для типа 3 из ITEM_PRICES — в $offer['PRICES'] в PRICE_ID иногда лежит этот ID, а не 3.
     *
     * @param array<string, mixed> $offer
     */
    private static function resolveAdvertisingCatalogPriceRowIdFromItemPrices(array $offer): ?int
    {
        if (empty($offer['ITEM_PRICES']) || !\is_array($offer['ITEM_PRICES'])) {
            return null;
        }
        foreach ($offer['ITEM_PRICES'] as $ip) {
            if (!\is_array($ip)) {
                continue;
            }
            $pt = (int)($ip['PRICE_TYPE_ID'] ?? $ip['PRICE_ID'] ?? 0);
            if ($pt === self::ADVERTISING_PRICE_TYPE_ID) {
                $rid = (int)($ip['ID'] ?? 0);

                return $rid > 0 ? $rid : null;
            }
        }

        return null;
    }

    /**
     * CODE рекламной строки из ITEM_PRICES — ключ в $offer['PRICES'] может быть «Рекламная цена», а не ID строки.
     *
     * @param array<string, mixed> $offer
     */
    private static function resolveAdvertisingPriceCodeFromItemPrices(array $offer): ?string
    {
        if (empty($offer['ITEM_PRICES']) || !\is_array($offer['ITEM_PRICES'])) {
            return null;
        }
        foreach ($offer['ITEM_PRICES'] as $ip) {
            if (!\is_array($ip)) {
                continue;
            }
            $pt = (int)($ip['PRICE_TYPE_ID'] ?? $ip['PRICE_ID'] ?? 0);
            if ($pt === self::ADVERTISING_PRICE_TYPE_ID) {
                $c = (string)($ip['CODE'] ?? '');
                if ($c !== '') {
                    return $c;
                }
            }
        }

        return null;
    }

    /**
     * Строка массива PRICES (старый формат компонента) — рекламная цена (тип 3).
     *
     * @param array<string, mixed> $pr
     */
    private static function catalogPriceRowIsAdvertisingType(
        array $pr,
        ?int $advertisingCatalogPriceRowId,
        ?string $advertisingCode = null,
        mixed $pricesArrayKey = null
    ): bool {
        $typeFromRow = (int)($pr['PRICE_TYPE_ID'] ?? $pr['CATALOG_GROUP_ID'] ?? 0);
        if ($typeFromRow === self::ADVERTISING_PRICE_TYPE_ID) {
            return true;
        }
        $pid = (int)($pr['PRICE_ID'] ?? 0);
        if ($pid === self::ADVERTISING_PRICE_TYPE_ID) {
            return true;
        }
        if ($advertisingCatalogPriceRowId !== null && $advertisingCatalogPriceRowId > 0) {
            if ($pid === $advertisingCatalogPriceRowId) {
                return true;
            }
            if ((int)($pr['ID'] ?? 0) === $advertisingCatalogPriceRowId) {
                return true;
            }
        }
        if ($advertisingCode !== null && $advertisingCode !== '') {
            $c = (string)($pr['CODE'] ?? '');
            if ($c !== '' && $c === $advertisingCode) {
                return true;
            }
            if ($pricesArrayKey !== null && (string)$pricesArrayKey === $advertisingCode) {
                return true;
            }
        }

        return false;
    }

    /**
     * Отладка: все строки PRICES с признаком «рекламная» и ключевыми полями (для os_price_debug).
     *
     * @param array<string, mixed> $offer
     * @return array<string, mixed>
     */
    private static function debugExtractAdvertisingPricesRowsSnapshot(array $offer): array
    {
        $adRowId = self::resolveAdvertisingCatalogPriceRowIdFromItemPrices($offer);
        $adCode = self::resolveAdvertisingPriceCodeFromItemPrices($offer);
        if (empty($offer['PRICES']) || !\is_array($offer['PRICES'])) {
            return [
                'ad_row_id_from_ITEM_PRICES' => $adRowId,
                'ad_code_from_ITEM_PRICES' => $adCode,
                'note' => 'PRICES empty or missing',
            ];
        }
        $rows = [];
        foreach ($offer['PRICES'] as $k => $pr) {
            if (!\is_array($pr)) {
                continue;
            }
            $isAd = self::catalogPriceRowIsAdvertisingType($pr, $adRowId, $adCode, $k);
            $rows[] = [
                'array_key' => $k,
                'is_advertising_row' => $isAd,
                'PRICE_ID' => $pr['PRICE_ID'] ?? null,
                'ID' => $pr['ID'] ?? null,
                'PRICE_TYPE_ID' => $pr['PRICE_TYPE_ID'] ?? null,
                'CATALOG_GROUP_ID' => $pr['CATALOG_GROUP_ID'] ?? null,
                'CODE' => $pr['CODE'] ?? null,
                'PRINT_PRICE' => $pr['PRINT_PRICE'] ?? null,
                'PRINT_DISCOUNT_VALUE' => $pr['PRINT_DISCOUNT_VALUE'] ?? null,
                'PRICE' => $pr['PRICE'] ?? null,
                'DISCOUNT_VALUE' => $pr['DISCOUNT_VALUE'] ?? null,
            ];
        }

        return [
            'ad_row_id_from_ITEM_PRICES' => $adRowId,
            'ad_code_from_ITEM_PRICES' => $adCode,
            'rows' => $rows,
        ];
    }

    /**
     * Шаблоны Intec (purchase/price.php и др.) при OFFERS берут строки из $offer['PRICES'], не из ITEM_PRICES.
     *
     * @param array<string, mixed> $offer
     * @param array<string, mixed> $rp RESULT_PRICE-подобный массив
     */
    private static function mergeAdvertisingResultPriceIntoOfferPricesArray(array &$offer, array $rp): void
    {
        if (empty($offer['PRICES']) || !\is_array($offer['PRICES'])) {
            return;
        }
        $adRowId = self::resolveAdvertisingCatalogPriceRowIdFromItemPrices($offer);
        $adCode = self::resolveAdvertisingPriceCodeFromItemPrices($offer);
        foreach ($offer['PRICES'] as $priceKey => &$pr) {
            if (!\is_array($pr)) {
                continue;
            }
            if (!self::catalogPriceRowIsAdvertisingType($pr, $adRowId, $adCode, $priceKey)) {
                continue;
            }
            self::mergeResultPriceIntoItemPriceRow($pr, $rp);
        }
        unset($pr);
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $rp
     */
    private static function mergeResultPriceIntoItemPriceRow(array &$row, array $rp): void
    {
        if (!Loader::includeModule('currency')) {
            return;
        }
        $currency = (string)($rp['CURRENCY'] ?? $row['CURRENCY'] ?? '');
        if ($currency === '') {
            return;
        }
        $pt = (int)($rp['PRICE_TYPE_ID'] ?? 0);
        $disc = $pt > 0
            ? self::roundPriceAmountForCatalogGroup((float)($rp['DISCOUNT_PRICE'] ?? 0), $currency, $pt)
            : self::roundPriceAmountForCurrency((float)($rp['DISCOUNT_PRICE'] ?? 0), $currency);
        $baseGroup = ($pt === self::ADVERTISING_PRICE_TYPE_ID) ? self::BASE_PRICE_TYPE_ID : ($pt > 0 ? $pt : 0);
        $base = $baseGroup > 0
            ? self::roundPriceAmountForCatalogGroup((float)($rp['BASE_PRICE'] ?? 0), $currency, $baseGroup)
            : self::roundPriceAmountForCurrency((float)($rp['BASE_PRICE'] ?? 0), $currency);
        if ($base < $disc) {
            $base = $disc;
        }
        $discountAmount = self::roundPriceAmountForCurrency(\max(0.0, $base - $disc), $currency);
        $percent = isset($rp['PERCENT']) ? (float)$rp['PERCENT'] : ($base > 0 ? \round(100 * (1 - $disc / $base), 2) : 0.0);

        $oldPrice = (float)($row['PRICE'] ?? 0);
        $oldBaseRow = (float)($row['BASE_PRICE'] ?? 0);
        $oldRatioPrice = (float)($row['RATIO_PRICE'] ?? $oldPrice);
        $oldRatioBase = (float)($row['RATIO_BASE_PRICE'] ?? $oldBaseRow);
        $kP = $oldPrice > 0 ? $oldRatioPrice / $oldPrice : 1.0;
        $kB = $oldBaseRow > 0 ? $oldRatioBase / $oldBaseRow : ($oldPrice > 0 ? $oldRatioPrice / $oldPrice : 1.0);

        $row['PRICE'] = $disc;
        $row['BASE_PRICE'] = $base;
        if (\array_key_exists('UNROUND_PRICE', $row)) {
            $row['UNROUND_PRICE'] = $disc;
        }
        if (\array_key_exists('UNROUND_BASE_PRICE', $row)) {
            $row['UNROUND_BASE_PRICE'] = $base;
        }
        $row['DISCOUNT'] = $discountAmount;
        $row['PERCENT'] = $percent;
        $row['CURRENCY'] = $currency;
        $row['PRINT_PRICE'] = self::currencyFormatForDisplay($disc, $currency);
        if (\array_key_exists('PRINT_BASE_PRICE', $row)) {
            $row['PRINT_BASE_PRICE'] = self::currencyFormatForDisplay($base, $currency);
        }
        if (\array_key_exists('PRINT_DISCOUNT', $row)) {
            $row['PRINT_DISCOUNT'] = self::currencyFormatForDisplay($discountAmount, $currency);
        }
        if (\array_key_exists('RATIO_PRICE', $row)) {
            $rawRp = $disc * $kP;
            $row['RATIO_PRICE'] = $pt === self::ADVERTISING_PRICE_TYPE_ID
                ? self::roundPriceAmountForCatalogGroup($rawRp, $currency, self::ADVERTISING_PRICE_TYPE_ID)
                : self::roundPriceAmountForCurrency($rawRp, $currency);
        }
        if (\array_key_exists('RATIO_BASE_PRICE', $row)) {
            $rawRb = $base * $kB;
            $row['RATIO_BASE_PRICE'] = $pt === self::ADVERTISING_PRICE_TYPE_ID
                ? self::roundPriceAmountForCatalogGroup($rawRb, $currency, self::BASE_PRICE_TYPE_ID)
                : self::roundPriceAmountForCurrency($rawRb, $currency);
        }
        if (\array_key_exists('PRINT_RATIO_PRICE', $row)) {
            $ratioP = \array_key_exists('RATIO_PRICE', $row)
                ? (float)$row['RATIO_PRICE']
                : ($pt === self::ADVERTISING_PRICE_TYPE_ID
                    ? self::roundPriceAmountForCatalogGroup($disc * $kP, $currency, self::ADVERTISING_PRICE_TYPE_ID)
                    : self::roundPriceAmountForCurrency($disc * $kP, $currency));
            $row['PRINT_RATIO_PRICE'] = self::currencyFormatForDisplay($ratioP, $currency);
        }
        if (\array_key_exists('PRINT_RATIO_BASE_PRICE', $row)) {
            $ratioB = \array_key_exists('RATIO_BASE_PRICE', $row)
                ? (float)$row['RATIO_BASE_PRICE']
                : ($pt === self::ADVERTISING_PRICE_TYPE_ID
                    ? self::roundPriceAmountForCatalogGroup($base * $kB, $currency, self::BASE_PRICE_TYPE_ID)
                    : self::roundPriceAmountForCurrency($base * $kB, $currency));
            $row['PRINT_RATIO_BASE_PRICE'] = self::currencyFormatForDisplay($ratioB, $currency);
        }
        if (\array_key_exists('RATIO_DISCOUNT', $row)) {
            $rawRd = \max(0.0, $base * $kB - $disc * $kP);
            $row['RATIO_DISCOUNT'] = $pt === self::ADVERTISING_PRICE_TYPE_ID
                ? self::roundPriceAmountForCatalogGroup($rawRd, $currency, self::ADVERTISING_PRICE_TYPE_ID)
                : self::roundPriceAmountForCurrency($rawRd, $currency);
        }
        if (\array_key_exists('PRINT_RATIO_DISCOUNT', $row)) {
            $ratioD = \array_key_exists('RATIO_DISCOUNT', $row)
                ? (float)$row['RATIO_DISCOUNT']
                : ($pt === self::ADVERTISING_PRICE_TYPE_ID
                    ? self::roundPriceAmountForCatalogGroup(\max(0.0, $base * $kB - $disc * $kP), $currency, self::ADVERTISING_PRICE_TYPE_ID)
                    : self::roundPriceAmountForCurrency(\max(0.0, $base * $kB - $disc * $kP), $currency));
            $row['PRINT_RATIO_DISCOUNT'] = self::currencyFormatForDisplay($ratioD, $currency);
        }
    }

    /**
     * Рекламная цена (тип 3) на витрине после merge с GetOptimalPrice:
     * если цена со скидкой < закупки (тип 4) → показываем закупку; иначе цена со скидкой (= оптовая база − сумма скидки).
     * Без успешного optimal — только подъём до пола (старое поведение).
     *
     * @param array<string, mixed> $offer
     */
    private static function clampAdvertisingCatalogPriceRows(int $productId, array &$offer, bool $optimalMerged = false): void
    {
        if (!empty($offer['PRICES']) && \is_array($offer['PRICES'])) {
            $adRowId = self::resolveAdvertisingCatalogPriceRowIdFromItemPrices($offer);
            $adCode = self::resolveAdvertisingPriceCodeFromItemPrices($offer);
            foreach ($offer['PRICES'] as $priceKey => &$pr) {
                if (!\is_array($pr)) {
                    continue;
                }
                if (!self::catalogPriceRowIsAdvertisingType($pr, $adRowId, $adCode, $priceKey)) {
                    continue;
                }
                if ($optimalMerged) {
                    self::syncAdvertisingCatalogPricesArrayRowDerived($productId, $pr);
                } else {
                    self::clampCatalogPricesArrayRowToFloorOnly($productId, $pr);
                }
            }
            unset($pr);
        }
        if (!empty($offer['ITEM_PRICES']) && \is_array($offer['ITEM_PRICES'])) {
            foreach ($offer['ITEM_PRICES'] as &$ip) {
                $pt = (int)($ip['PRICE_TYPE_ID'] ?? $ip['PRICE_ID'] ?? 0);
                if ($pt === self::ADVERTISING_PRICE_TYPE_ID) {
                    if ($optimalMerged) {
                        self::syncAdvertisingItemPriceRowDerived($productId, $ip);
                    } else {
                        self::clampItemPriceRowToFloorOnly($productId, $ip);
                    }
                }
            }
            unset($ip);
        }
    }

    /**
     * Цена продажи для строки «Рекламная»; зачёркнутая база = оптовая (2/1), если авторизован и есть группа скидки компании;
     * гость и пользователь без такой группы — база = цена продажи (без приравнивания к опту).
     *
     * @return array{sell: float, base: float}|null
     */
    private static function deriveAdvertisingSellAndBaseFromOptimal(
        int $productId,
        string $currency,
        float $discountedFromOptimal,
        float $fallbackBase
    ): ?array {
        if ($currency === '' || $productId <= 0) {
            return null;
        }

        $floor = self::getPurchaseFloorForProductOrParent($productId, $currency);

        $sell = $discountedFromOptimal;
        if ($floor !== null && $sell < $floor) {
            $sell = $floor;
        }

        if (!self::isCatalogPricingUserAuthorized()) {
            $sell = self::roundPriceAmountForCatalogGroup($sell, $currency, self::ADVERTISING_PRICE_TYPE_ID);

            return ['sell' => $sell, 'base' => $sell];
        }

        $groups = self::getCurrentUserGroupArrayForPricing();
        $companyPct = Company::getMaxCompanyDiscountPercentForUserGroups($groups);
        if ($companyPct <= 0.00001) {
            $sell = self::roundPriceAmountForCatalogGroup($sell, $currency, self::ADVERTISING_PRICE_TYPE_ID);

            return ['sell' => $sell, 'base' => $sell];
        }

        $baseRef = self::getBaseCatalogPriceForProductOrParent($productId, $currency);

        $base = ($baseRef !== null && $baseRef > 0) ? $baseRef : ($fallbackBase > 0 ? $fallbackBase : $sell);
        if ($base < $sell) {
            $base = $sell;
        }

        $sell = self::roundPriceAmountForCatalogGroup($sell, $currency, self::ADVERTISING_PRICE_TYPE_ID);
        $base = self::roundPriceAmountForCatalogGroup($base, $currency, self::BASE_PRICE_TYPE_ID);
        if ($base < $sell) {
            $base = $sell;
        }

        return ['sell' => $sell, 'base' => $base];
    }

    /**
     * ITEM_PRICES: рекламная строка после merge с RESULT_PRICE.
     *
     * @param array<string, mixed> $row
     */
    private static function syncAdvertisingItemPriceRowDerived(int $productId, array &$row): void
    {
        $currency = (string)($row['CURRENCY'] ?? '');
        if ($currency === '') {
            return;
        }

        $discounted = (float)($row['PRICE'] ?? 0);
        $fallbackBase = (float)($row['BASE_PRICE'] ?? 0);
        $derived = self::deriveAdvertisingSellAndBaseFromOptimal($productId, $currency, $discounted, $fallbackBase);
        if ($derived === null) {
            return;
        }

        $sell = $derived['sell'];
        $base = $derived['base'];
        $discountAmount = self::roundPriceAmountForCurrency(\max(0.0, $base - $sell), $currency);
        $percent = $base > 0 ? \max(0.0, \round(100 * (1 - $sell / $base), 2)) : 0.0;

        $row['PRICE'] = $sell;
        $row['BASE_PRICE'] = $base;
        $row['DISCOUNT'] = $discountAmount;
        $row['PERCENT'] = $percent;

        if (!Loader::includeModule('currency')) {
            return;
        }
        $row['PRINT_PRICE'] = self::currencyFormatForDisplay($sell, $currency);
        if (\array_key_exists('PRINT_BASE_PRICE', $row)) {
            $row['PRINT_BASE_PRICE'] = self::currencyFormatForDisplay($base, $currency);
        }
        if (\array_key_exists('PRINT_DISCOUNT', $row)) {
            $row['PRINT_DISCOUNT'] = self::currencyFormatForDisplay($discountAmount, $currency);
        }
    }

    /**
     * PRICES[] (старый формат): та же логика для типа 3.
     *
     * @param array<string, mixed> $row
     */
    private static function syncAdvertisingCatalogPricesArrayRowDerived(int $productId, array &$row): void
    {
        $currency = (string)($row['CURRENCY'] ?? '');
        if ($currency === '') {
            return;
        }

        // После merge в PRICES актуальна PRICE (как в ITEM_PRICES); DISCOUNT_VALUE может остаться старой из каталога.
        $discounted = (float)($row['PRICE'] ?? $row['DISCOUNT_VALUE'] ?? 0);
        $fallbackBase = (float)($row['BASE_PRICE'] ?? $row['VALUE_VAT'] ?? $row['VALUE'] ?? 0);
        $derived = self::deriveAdvertisingSellAndBaseFromOptimal($productId, $currency, $discounted, $fallbackBase);
        if ($derived === null) {
            return;
        }

        $sell = $derived['sell'];
        $base = $derived['base'];
        $discountAmount = self::roundPriceAmountForCurrency(\max(0.0, $base - $sell), $currency);
        $percent = $base > 0 ? \max(0.0, \round(100 * (1 - $sell / $base), 2)) : 0.0;

        if (\array_key_exists('PRICE', $row)) {
            $row['PRICE'] = $sell;
        }
        if (\array_key_exists('BASE_PRICE', $row)) {
            $row['BASE_PRICE'] = $base;
        }
        $row['DISCOUNT_VALUE'] = $sell;
        $row['DISCOUNT_VALUE_VAT'] = $sell;
        $row['DISCOUNT_VALUE_NOVAT'] = $sell;
        $row['VALUE_VAT'] = $base;
        $row['VALUE_NOVAT'] = $base;
        $row['VALUE'] = $base;
        $row['DISCOUNT_DIFF'] = $discountAmount;
        $row['PERCENT'] = $percent;
        $row['DISCOUNT_DIFF_PERCENT'] = $percent;

        if (!Loader::includeModule('currency')) {
            return;
        }
        $row['PRINT_DISCOUNT_VALUE'] = self::currencyFormatForDisplay($sell, $currency);
        $row['PRINT_PRICE'] = $row['PRINT_DISCOUNT_VALUE'];
        $row['PRINT_VALUE_VAT'] = self::currencyFormatForDisplay($base, $currency);
        $row['PRINT_DISCOUNT_DIFF'] = self::currencyFormatForDisplay($discountAmount, $currency);
        if (\array_key_exists('PRINT_BASE_PRICE', $row)) {
            $row['PRINT_BASE_PRICE'] = self::currencyFormatForDisplay($base, $currency);
        }
        if (\array_key_exists('PRINT_DISCOUNT', $row)) {
            $row['PRINT_DISCOUNT'] = self::currencyFormatForDisplay($discountAmount, $currency);
        }
    }

    /**
     * Только пол закупки — если optimal не подтянули (сырой каталог).
     *
     * @param array<string, mixed> $row
     */
    private static function clampCatalogPricesArrayRowToFloorOnly(int $productId, array &$row): void
    {
        $currency = (string)($row['CURRENCY'] ?? '');
        if ($currency === '') {
            return;
        }
        $floor = self::getPurchaseFloorForProductOrParent($productId, $currency);
        if ($floor === null) {
            return;
        }
        $disc = (float)($row['DISCOUNT_VALUE'] ?? $row['PRICE'] ?? 0);
        if ($disc >= $floor) {
            return;
        }

        $base = (float)($row['VALUE_VAT'] ?? $row['VALUE'] ?? 0);
        if ($base < $floor) {
            $base = $floor;
        }
        $floor = self::roundPriceAmountForCatalogGroup($floor, $currency, self::ADVERTISING_PRICE_TYPE_ID);
        $base = self::roundPriceAmountForCatalogGroup($base, $currency, self::BASE_PRICE_TYPE_ID);
        if ($base < $floor) {
            $base = $floor;
        }
        $discountAmount = self::roundPriceAmountForCurrency(\max(0.0, $base - $floor), $currency);
        $percent = $base > 0 ? \round(100 * (1 - $floor / $base), 2) : 0.0;

        $row['DISCOUNT_VALUE'] = $floor;
        $row['DISCOUNT_VALUE_VAT'] = $floor;
        $row['DISCOUNT_VALUE_NOVAT'] = $floor;
        $row['VALUE_VAT'] = $base;
        $row['VALUE_NOVAT'] = $base;
        $row['VALUE'] = $base;
        $row['DISCOUNT_DIFF'] = $discountAmount;
        $row['PERCENT'] = $percent;
        $row['DISCOUNT_DIFF_PERCENT'] = $percent;

        if (!Loader::includeModule('currency')) {
            return;
        }
        $row['PRINT_DISCOUNT_VALUE'] = self::currencyFormatForDisplay($floor, $currency);
        $row['PRINT_PRICE'] = $row['PRINT_DISCOUNT_VALUE'];
        $row['PRINT_VALUE_VAT'] = self::currencyFormatForDisplay($base, $currency);
        $row['PRINT_DISCOUNT_DIFF'] = self::currencyFormatForDisplay($discountAmount, $currency);
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function clampItemPriceRowToFloorOnly(int $productId, array &$row): void
    {
        $currency = (string)($row['CURRENCY'] ?? '');
        if ($currency === '') {
            return;
        }
        $floor = self::getPurchaseFloorForProductOrParent($productId, $currency);
        if ($floor === null) {
            return;
        }
        $price = (float)($row['PRICE'] ?? 0);
        if ($price >= $floor) {
            return;
        }
        $base = (float)($row['BASE_PRICE'] ?? 0);
        if ($base < $floor) {
            $base = $floor;
        }
        $floor = self::roundPriceAmountForCatalogGroup($floor, $currency, self::ADVERTISING_PRICE_TYPE_ID);
        $base = self::roundPriceAmountForCatalogGroup($base, $currency, self::BASE_PRICE_TYPE_ID);
        if ($base < $floor) {
            $base = $floor;
        }
        $discountAmount = self::roundPriceAmountForCurrency(\max(0.0, $base - $floor), $currency);
        $percent = $base > 0 ? \round(100 * (1 - $floor / $base), 2) : 0.0;

        $row['PRICE'] = $floor;
        $row['BASE_PRICE'] = $base;
        $row['DISCOUNT'] = $discountAmount;
        $row['PERCENT'] = $percent;

        if (!Loader::includeModule('currency')) {
            return;
        }
        $row['PRINT_PRICE'] = self::currencyFormatForDisplay($floor, $currency);
        if (\array_key_exists('PRINT_BASE_PRICE', $row)) {
            $row['PRINT_BASE_PRICE'] = self::currencyFormatForDisplay($base, $currency);
        }
        if (\array_key_exists('PRINT_DISCOUNT', $row)) {
            $row['PRINT_DISCOUNT'] = self::currencyFormatForDisplay($discountAmount, $currency);
        }
    }
}
