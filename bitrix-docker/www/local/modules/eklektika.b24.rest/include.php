<?php

defined('B_PROLOG_INCLUDED') || die();

use Bitrix\Main\Loader;

/**
 * Транспорт REST к Bitrix24 без установщика (минимальный модуль ST-02 / MODULE-LAYOUT.md).
 */
Loader::registerAutoLoadClasses(null, [
    \OnlineService\B24\RestClient::class => '/local/modules/eklektika.b24.rest/lib/RestClient.php',
    \OnlineService\B24\Request::class => '/local/modules/eklektika.b24.rest/lib/Request.php',
    \OnlineService\B24\Config\RestTransportConfig::class => '/local/modules/eklektika.b24.rest/lib/Config/RestTransportConfig.php',
]);

require_once __DIR__ . '/lib/LegacyGlobalB24.php';
