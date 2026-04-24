<?php

defined('B_PROLOG_INCLUDED') || die();

use Bitrix\Main\Loader;

/**
 * Заявки по строкам сделки B24 (kit.productapplications) и перенос в заказ Sale.
 * Зависимость: eklektika.b24.rest (RestClient) должен быть загружен раньше.
 */
Loader::registerAutoLoadClasses(null, [
    \OnlineService\Orders\Applications\DealApplicationsService::class => '/local/modules/eklektika.orders.applications/lib/DealApplicationsService.php',
    \OnlineService\Orders\Applications\Config\DealApplicationsConfig::class => '/local/modules/eklektika.orders.applications/lib/Config/DealApplicationsConfig.php',
]);
