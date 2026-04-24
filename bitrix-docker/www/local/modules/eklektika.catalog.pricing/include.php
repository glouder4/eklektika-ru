<?php

defined('B_PROLOG_INCLUDED') || die();

use Bitrix\Main\Loader;

/**
 * Пол цены, оптовая база, нижняя граница закупки; узкая связь с компанией через
 * OnlineService\Site\Company::getMaxCompanyDiscountPercentForUserGroups (модуль eklektika.company).
 */
Loader::registerAutoLoadClasses(null, [
    \OnlineService\Site\CatalogPriceFloor::class => '/local/modules/eklektika.catalog.pricing/lib/CatalogPriceFloor.php',
    \OnlineService\Site\Config\CatalogPricingConfig::class => '/local/modules/eklektika.catalog.pricing/lib/Config/CatalogPricingConfig.php',
]);
