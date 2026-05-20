<?php
    function normalizeCatalogRedirectPath($requestPath): string
    {
        $requestPath = (string)$requestPath;
        if ($requestPath !== '' && strpos(basename($requestPath), '.') === false) {
            return rtrim($requestPath, '/') . '/';
        }

        return $requestPath;
    }

    function handleOldCatalogRedirects(): void
    {
        // Редиректы старых URL категорий каталога (до загрузки остального)
        $oldCatalogRedirects = require __DIR__ . '/old_catalog_redirects.php';
        $requestPath = (string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $pathKey = normalizeCatalogRedirectPath($requestPath);

        if (!isset($oldCatalogRedirects[$pathKey]) && !isset($oldCatalogRedirects[$requestPath])) {
            return;
        }

        $target = $oldCatalogRedirects[$pathKey] ?? $oldCatalogRedirects[$requestPath];
        if ($target !== '') {
            \LocalRedirect($target, true);
            return;
        }

        include $_SERVER['DOCUMENT_ROOT'] . '/404.php';
        die;
    }

    function defineB24WebhookConstants(array $b24IntegrationConfig): void
    {
        if (!defined('B24_REST_WEBHOOK_MAIN')) {
            $mainWebhook = (string)($b24IntegrationConfig['rest_webhook_main'] ?? '');
            if ($mainWebhook === '' && defined('B24_REST_WEBHOOK')) {
                // Legacy compatibility: B24_REST_WEBHOOK can contain full URL with /rest/1/{token}.
                $legacyWebhookUrl = (string)B24_REST_WEBHOOK;
                if (preg_match('~/rest/\d+/([^/]+)~', $legacyWebhookUrl, $m)) {
                    $mainWebhook = (string)($m[1] ?? '');
                }
            }
            if ($mainWebhook !== '') {
                define('B24_REST_WEBHOOK_MAIN', trim($mainWebhook));
            }
        }

        if (!defined('B24_REST_WEBHOOK_KIT')) {
            $kitWebhook = (string)($b24IntegrationConfig['rest_webhook_kit'] ?? '');
            if ($kitWebhook !== '') {
                define('B24_REST_WEBHOOK_KIT', trim($kitWebhook));
            }
        }
    }

    /**
     * Единая точка: php_interface/b24_integration_config.php (без .env / getenv).
     *
     * @return array{use_test_portal?: bool, base_url?: string, rest_webhook_main?: string, rest_webhook_kit?: string}
     */
    function loadB24IntegrationConfig(): array
    {
        $defaults = [
            'use_test_portal' => false,
            'base_url' => '',
            'rest_webhook_main' => '',
            'rest_webhook_kit' => '',
        ];
        $path = __DIR__ . '/b24_integration_config.php';
        if (!is_file($path)) {
            return $defaults;
        }
        $loaded = require $path;

        return is_array($loaded) ? array_merge($defaults, $loaded) : $defaults;
    }

    function normalizeB24PortalBaseUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        return rtrim($url, '/') . '/';
    }

    /** URL_B24: `base_url` из {@see loadB24IntegrationConfig()}, иначе прод по умолчанию. */
    function resolveUrlB24Constant(array $b24IntegrationConfig): string
    {
        $fromFile = normalizeB24PortalBaseUrl((string)($b24IntegrationConfig['base_url'] ?? ''));
        if ($fromFile !== '') {
            return $fromFile;
        }

        return 'https://bitrix.eklektika.ru/';
    }

    /**
     * Полный URL входящего вебхука (совместимость с кодом, что читает константу B24_REST_WEBHOOK целиком).
     * Сборка: base_url (или тот же дефолт, что и URL_B24) + /rest/1/{rest_webhook_main}; иначе захардкоженный fallback.
     */
    function resolveB24RestWebhookUrlConstant(array $b24IntegrationConfig): string
    {
        $token = trim((string)($b24IntegrationConfig['rest_webhook_main'] ?? ''));
        $base = rtrim((string)($b24IntegrationConfig['base_url'] ?? ''), '/');
        if ($base === '') {
            $base = rtrim(resolveUrlB24Constant($b24IntegrationConfig), '/');
        }
        if ($token !== '' && $base !== '') {
            return rtrim($base . '/rest/1/' . $token, '/');
        }

        return 'https://bitrix.eklektika.ru/rest/1/t4iml4wdy10uqefs';
    }

    handleOldCatalogRedirects();

    $b24IntegrationConfig = loadB24IntegrationConfig();

    if (!defined('B24_USE_TEST_PORTAL')) {
        define('B24_USE_TEST_PORTAL', !empty($b24IntegrationConfig['use_test_portal']));
    }

    if (!defined('URL_B24')) {
        define('URL_B24', resolveUrlB24Constant($b24IntegrationConfig));
    }

    if (!defined('B24_REST_WEBHOOK')) {
        define('B24_REST_WEBHOOK', resolveB24RestWebhookUrlConstant($b24IntegrationConfig));
    }

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? (string)$_SERVER['HTTP_HOST'] : 'localhost';

    define('SITE_URL', $protocol . '://' . $host);

    defineB24WebhookConstants($b24IntegrationConfig);

    require_once __DIR__ . '/eklektika_requires.php'; // Подключение кастомных модулей eklektika.*

    require_once __DIR__ . '/classes/sale/OrderJsonNaneseniyaProperty.php';
    \OnlineService\Sale\OrderJsonNaneseniyaProperty::ensureMaxLength();

    if (class_exists(\OnlineService\Site\CatalogPriceFloor::class)) {
        \OnlineService\Site\CatalogPriceFloor::bootstrap();
    }

    function pre($o) {

        $bt = debug_backtrace();
        $bt = $bt[0];
        $dRoot = $_SERVER["DOCUMENT_ROOT"];
        $dRoot = str_replace("/", "\\", $dRoot);
        $bt["file"] = str_replace($dRoot, "", $bt["file"]);
        $dRoot = str_replace("\\", "/", $dRoot);
        $bt["file"] = str_replace($dRoot, "", $bt["file"]);
        ?>
        <div style='font-size:9pt; color:#000; background:#fff; border:1px dashed #000;text-align: left!important;'>
            <div style='padding:3px 5px; background:#99CCFF; font-weight:bold;'>File: <?= $bt["file"] ?> [<?= $bt["line"] ?>]</div>
            <pre style='padding:5px;'><? print_r($o) ?></pre>
        </div>
        <?php
    }

\Bitrix\Main\EventManager::getInstance()->addEventHandler('main', 'OnEpilog', 'onCatalogSeoTitle');

function onCatalogSeoTitle(): void
{
        $offerId = false;
        $path = (string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        if (preg_match('#/offer/(\d+)/?$#', $path, $m)) {
            $offerId = (int)$m[1];
        }

        if (!$offerId) {
            return;
        }

        // Подключаем модули
        \Bitrix\Main\Loader::includeModule('iblock');
        \Bitrix\Main\Loader::includeModule('catalog');

        // Получаем предложение
        $offer = \CIBlockElement::GetList(
            [],
            ['ID' => $offerId, 'ACTIVE' => 'Y'],
            false,
            ['nTopCount' => 1],
            ['ID', 'NAME', 'IBLOCK_ID', 'PROPERTY_TSVET', 'PROPERTY_ARTIKUL_POSTAVSHCHIKA']
        )->Fetch();

        if (!$offer) {
            return;
        }

        // Получаем настройки SEO для элемента
        $seoTemplates = new \Bitrix\Iblock\InheritedProperty\ElementValues(14,$offerId);
        $values = $seoTemplates->getValues();


        global $APPLICATION;
        $APPLICATION->SetTitle($values['ELEMENT_PAGE_TITLE']);
        $APPLICATION->SetPageProperty('description', $values['ELEMENT_META_DESCRIPTION']);
        $APPLICATION->SetPageProperty("title", $values['ELEMENT_META_TITLE']);
}

/**
 * Возвращает все типы цен и их значения для товара или предложения по ID
 * @param int $id ID товара или торгового предложения
 * @return array Массив цен [['ID','PRICE','PRICE_VALUE','CURRENCY','PRICE_TYPE_ID','CATALOG_GROUP_NAME'], ...]
 */
function getCatalogPrices($id)
{
    CModule::IncludeModule("catalog");
    $prices = [];
    $id = (int)$id;
    if (!$id) {
        return $prices;
    }
    $rsPrices = CPrice::GetList([], ["PRODUCT_ID" => $id]);
    while ($price = $rsPrices->Fetch()) {
        $priceVal = (float)$price["PRICE"];
        $prices[] = [
            "ID"                 => (int)$price["ID"],
            "PRICE"              => $priceVal,
            "PRICE_VALUE"        => $priceVal,
            "CURRENCY"           => $price["CURRENCY"],
            "PRICE_TYPE_ID"      => (int)$price["CATALOG_GROUP_ID"],
            "CATALOG_GROUP_NAME" => $price["CATALOG_GROUP_NAME"] ?? "",
        ];
    }
    if (!empty($prices)) {
        $priceTypeIds = array_unique(array_column($prices, "PRICE_TYPE_ID"));
        $priceTypes = [];
        $rsGroups = CCatalogGroup::GetList([], ["ID" => $priceTypeIds]);
        while ($group = $rsGroups->Fetch()) {
            $priceTypes[$group["ID"]] = $group["NAME"];
        }
        foreach ($prices as &$p) {
            $p["CATALOG_GROUP_NAME"] = $priceTypes[$p["PRICE_TYPE_ID"]] ?? "";
        }
        unset($p);
    }
    return $prices;
}

/**
 * Возвращает основную цену, старую цену и скидку между двумя типами цен.
 * Если заполнена только одна из цен — используется она, скидка = 0.
 *
 * @param int $productId ID товара или торгового предложения
 * @param int|null $mainPriceTypeId Тип цены **продажи** ({@see CatalogPricingConfig::PURCHASE_PRICE_TYPE_ID}), по которой реально продаём.
 *                                   Если null — берётся мин. цена среди строк.
 * @param int|null $oldPriceTypeId Тип цены для зачёркнутой базы (обычно опт {@see CatalogPricingConfig::BASE_PRICE_TYPE_ID}). Если null — макс. цена.
 * @return array|null ['MAIN','OLD','DISCOUNT','CURRENCY'] или null, если нет цен вообще.
 *                     Только если задана скидочная группа (pct > 0): MAIN пересчитывается как max(опт × (1−pct/100), рекламная),
 *                     OLD для процента = оптовая база (тип 2). Иначе строки прайза не пересчитываются.
 *                     Если цена типа {@see \OnlineService\Site\Config\CatalogPricingConfig::ADVERTISING_PRICE_TYPE_ID} выше
 *                     цены типа {@see \OnlineService\Site\Config\CatalogPricingConfig::BASE_PRICE_FALLBACK_TYPE_ID},
 *                     MAIN и OLD приравниваются к цене продажи (PURCHASE), скидка 0% (без визуальной «оптовой» пары).
 */
function getCatalogPriceDiscount($productId, $mainPriceTypeId = null, $oldPriceTypeId = null)
{
    $prices = getCatalogPrices($productId);
    if (empty($prices)) {
        return null;
    }
    $mainPriceData = null;
    $oldPriceData = null;
    $wholesalePriceData = null;
    $advertisingPriceData = null;
    $fallbackPriceData = null;

    foreach ($prices as $p) {
        $typeId = (int)$p['PRICE_TYPE_ID'];
        if ($mainPriceTypeId !== null && $typeId === (int)$mainPriceTypeId) {
            $mainPriceData = $p;
        }
        if ($oldPriceTypeId !== null && $typeId === (int)$oldPriceTypeId) {
            $oldPriceData = $p;
        }
        if ($typeId === \OnlineService\Site\Config\CatalogPricingConfig::BASE_PRICE_TYPE_ID) {
            $wholesalePriceData = $p;
        }
        if ($typeId === \OnlineService\Site\Config\CatalogPricingConfig::ADVERTISING_PRICE_TYPE_ID) {
            $advertisingPriceData = $p;
        }
        if ($typeId === \OnlineService\Site\Config\CatalogPricingConfig::BASE_PRICE_FALLBACK_TYPE_ID) {
            $fallbackPriceData = $p;
        }
    }

    if ($mainPriceTypeId === null || $oldPriceTypeId === null) {
        $priceValues = array_column($prices, 'PRICE');
        $mainPriceData = ['PRICE' => min($priceValues), 'CURRENCY' => $prices[0]['CURRENCY'] ?? 'RUB'];
        $oldPriceData = ['PRICE' => max($priceValues)];
    }

    $mainPrice = $mainPriceData ? (float)$mainPriceData['PRICE'] : null;
    $oldPrice = $oldPriceData ? (float)$oldPriceData['PRICE'] : null;

    $currency = ($mainPriceData ?? $oldPriceData)['CURRENCY'] ?? 'RUB';

    $skipCompanyTier = false;
    if (
        $mainPrice !== null
        && $mainPriceTypeId !== null
        && (int)$mainPriceTypeId === \OnlineService\Site\Config\CatalogPricingConfig::PURCHASE_PRICE_TYPE_ID
        && $advertisingPriceData !== null
        && $fallbackPriceData !== null
        && $mainPriceData !== null
    ) {
        $adVal = (float)$advertisingPriceData['PRICE'];
        $fbVal = (float)$fallbackPriceData['PRICE'];
        if ($adVal > $fbVal) {
            $oldPrice = $mainPrice;
            $skipCompanyTier = true;
        }
    }

    if (!$skipCompanyTier
        && $mainPrice !== null
        && $mainPriceTypeId !== null
        && (int)$mainPriceTypeId === \OnlineService\Site\Config\CatalogPricingConfig::PURCHASE_PRICE_TYPE_ID
        && \class_exists(\OnlineService\Site\CatalogPriceFloor::class)
        && $wholesalePriceData !== null
    ) {
        $wholesaleBase = (float)$wholesalePriceData['PRICE'];
        $pct = \OnlineService\Site\CatalogPriceFloor::getCurrentUserCompanyDiscountPercent();
        if ($pct > 0.00001) {
            $ad = ($advertisingPriceData !== null) ? (float)$advertisingPriceData['PRICE'] : null;
            $mainPrice = \OnlineService\Site\CatalogPriceFloor::computeShowcaseMainPriceCompanyTierVsAdvertising(
                $wholesaleBase,
                ($ad !== null && $ad > 0.0) ? $ad : null,
                $currency
            );
            $oldPrice = $wholesaleBase;
        }
    }

    $result = [
        'MAIN'      => $mainPrice,
        'OLD'       => $oldPrice,
        'DISCOUNT'  => 0,
        'CURRENCY'  => $currency,
    ];

    if ($mainPrice !== null && $oldPrice !== null && $oldPrice > 0 && $oldPrice > $mainPrice) {
        $result['DISCOUNT'] = round((($oldPrice - $mainPrice) / $oldPrice) * 100, 1);
    }

    return $result;
}
