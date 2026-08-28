<?php

defined('B_PROLOG_INCLUDED') || die();

use Bitrix\Main\Loader;

/**
 * Контент сайта: настройки страницы (Page editor), поисковый bootstrap.
 * Зависимости: ядро Bitrix, iblock (в рантайме внутри PageSettings).
 */
Loader::registerAutoLoadClasses(null, [
    \OnlineService\Site\PageSettings::class => '/local/modules/eklektika.site/lib/PageSettings.php',
    \OnlineService\Site\MinOrderAmount::class => '/local/modules/eklektika.site/lib/MinOrderAmount.php',
    \OnlineService\Site\SearchIndexingBootstrap::class => '/local/modules/eklektika.site/lib/SearchIndexingBootstrap.php',
    \OnlineService\Site\Config\SiteModuleConfig::class => '/local/modules/eklektika.site/lib/Config/SiteModuleConfig.php',
]);

require_once __DIR__ . '/lib/PageEditorGlobalFunctions.php';

\OnlineService\Site\SearchIndexingBootstrap::register();

\Bitrix\Main\Loader::registerAutoLoadClasses(null, [
    '\OnlineService\Classes\Handlers\Search\Stemming' => '/local/php_interface/classes/handlers/search/stemming.php',
]);
$osSearchEventManager = \Bitrix\Main\EventManager::getInstance();
$osSearchEventManager->addEventHandler(
    'search',
    'BeforeIndex',
    ['\OnlineService\Classes\Handlers\Search\Stemming', 'BeforeIndexHandler']
);
