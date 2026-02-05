<?php
    require_once __DIR__.'/../crm/requires.php';

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
