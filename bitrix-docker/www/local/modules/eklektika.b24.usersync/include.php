<?php

defined('B_PROLOG_INCLUDED') || die();

use Bitrix\Main\Loader;

/**
 * Домен синхронизации пользователя ↔ контакт Bitrix24 (без установщика).
 * Зависимость: модуль eklektika.b24.rest (RestClient / Request) должен быть загружен раньше.
 */

Loader::registerAutoLoadClasses(null, [
    \OnlineService\Sync\FromCrm\CrmInboundUfMap::class => '/local/sync/from-crm/CrmInboundUfMap.php',
    \OnlineService\B24\UserSync\UserSyncBootstrap::class => '/local/modules/eklektika.b24.usersync/lib/UserSyncBootstrap.php',
    \OnlineService\B24\UserSync\ContactAjaxFacade::class => '/local/modules/eklektika.b24.usersync/lib/ContactAjaxFacade.php',
    \OnlineService\B24\UserSync\Config\RegisterUserCompanyConfig::class => '/local/modules/eklektika.b24.usersync/lib/Config/RegisterUserCompanyConfig.php',
    \OnlineService\B24\UserSync\Config\UserSyncConfig::class => '/local/modules/eklektika.b24.usersync/lib/Config/UserSyncConfig.php',
    \OnlineService\B24\RegisterUserCompany::class => '/local/modules/eklektika.b24.usersync/lib/RegisterUserCompany.php',
    \OnlineService\B24\User::class => '/local/modules/eklektika.b24.usersync/lib/User.php',
]);

// Регистрация user-sync событий централизована через local/events/events.php (SyncEventHandlers).
