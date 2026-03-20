<?php
    // Редиректы старых URL категорий каталога (до загрузки остального)
    $oldCatalogRedirects = require __DIR__ . '/old_catalog_redirects.php';
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $pathKey = $requestPath;
    if ($pathKey !== '' && strpos(basename($pathKey), '.') === false) {
        $pathKey = rtrim($pathKey, '/') . '/';
    }
    if (isset($oldCatalogRedirects[$pathKey]) || isset($oldCatalogRedirects[$requestPath])) {
        $target = $oldCatalogRedirects[$pathKey] ?? $oldCatalogRedirects[$requestPath];
        if ($target !== '') {
            \LocalRedirect($target, true);
        } else {
            include $_SERVER['DOCUMENT_ROOT'] . '/404.php';
            die;
        }
    }

    require_once __DIR__.'/../crm/requires.php';
    require_once __DIR__.'/../classes/requires.php'; // Подключение кастомных обработчиков

    $protocol = (!empty($_SERVER['HTTPS'])) ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST']; //preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']); // Убираем порт

    define('SITE_URL',$protocol . '://' . $host);


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
        <?
    }

\Bitrix\Main\EventManager::getInstance()->addEventHandler('main', 'OnEpilog', 'onCatalogSeoTitle');

function onCatalogSeoTitle(): void
{
        $offerId = false;
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if (preg_match('#/offer/(\d+)/?$#', $path, $m)) {
            $offerId = (int)$m[1];
        }

        if (!$offerId) return;

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

        if (!$offer) return;

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
 * @param int|null $mainPriceTypeId ID типа цены основной (текущей) цены (CATALOG_GROUP_ID). Если null — берётся мин. цена
 * @param int|null $oldPriceTypeId ID типа цены старой цены (CATALOG_GROUP_ID). Если null — берётся макс. цена
 * @return array|null ['MAIN','OLD','DISCOUNT','CURRENCY'] или null, если нет цен вообще
 */
function getCatalogPriceDiscount($productId, $mainPriceTypeId = null, $oldPriceTypeId = null)
{
    $prices = getCatalogPrices($productId);
    if (empty($prices)) {
        return null;
    }
    $mainPriceData = null;
    $oldPriceData = null;

    if ($mainPriceTypeId !== null && $oldPriceTypeId !== null) {
        foreach ($prices as $p) {
            if ((int)$p['PRICE_TYPE_ID'] === (int)$mainPriceTypeId) {
                $mainPriceData = $p;
            }
            if ((int)$p['PRICE_TYPE_ID'] === (int)$oldPriceTypeId) {
                $oldPriceData = $p;
            }
        }
        $useTypeIds = true;
    } else {
        $priceValues = array_column($prices, 'PRICE');
        $mainPriceData = ['PRICE' => min($priceValues), 'CURRENCY' => $prices[0]['CURRENCY'] ?? 'RUB'];
        $oldPriceData = ['PRICE' => max($priceValues)];
        $useTypeIds = false;
    }

        $mainPrice = $mainPriceData ? (float)$mainPriceData['PRICE'] : null;
    $oldPrice = $oldPriceData ? (float)$oldPriceData['PRICE'] : null;

    $currency = ($mainPriceData ?? $oldPriceData)['CURRENCY'] ?? 'RUB';

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
