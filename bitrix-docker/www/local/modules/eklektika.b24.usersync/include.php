<?php

defined('B_PROLOG_INCLUDED') || die();

use Bitrix\Main\Loader;

/**
 * Домен синхронизации пользователя ↔ контакт Bitrix24 (без установщика).
 * Зависимость: модуль eklektika.b24.rest (RestClient / Request) должен быть загружен раньше.
 */

Loader::registerAutoLoadClasses(null, [
    \OnlineService\Events\SyncEventHandlers::class => '/local/modules/eklektika.b24.usersync/lib/SyncEventHandlers.php',
    \OnlineService\B24\UserSync\UserSyncBootstrap::class => '/local/modules/eklektika.b24.usersync/lib/UserSyncBootstrap.php',
    \OnlineService\B24\UserSync\ContactAjaxFacade::class => '/local/modules/eklektika.b24.usersync/lib/ContactAjaxFacade.php',
    \OnlineService\B24\UserSync\Config\RegisterUserCompanyConfig::class => '/local/modules/eklektika.b24.usersync/lib/Config/RegisterUserCompanyConfig.php',
    \OnlineService\B24\UserSync\Config\UserSyncConfig::class => '/local/modules/eklektika.b24.usersync/lib/Config/UserSyncConfig.php',
    \OnlineService\B24\RegisterUserCompany::class => '/local/modules/eklektika.b24.usersync/lib/RegisterUserCompany.php',
    \OnlineService\B24\User::class => '/local/modules/eklektika.b24.usersync/lib/User.php',
]);

// Регистрация user-sync событий (main/*) — рядом с доменом usersync, без отдельной папки local/events.
$osEventManager = \Bitrix\Main\EventManager::getInstance();
$osEventManager->addEventHandlerCompatible(
    'main',
    'OnBeforeUserDelete',
    [\OnlineService\Events\SyncEventHandlers::class, 'onBeforeUserDelete']
);
$osEventManager->addEventHandlerCompatible(
    'main',
    'OnBeforeUserAdd',
    [\OnlineService\Events\SyncEventHandlers::class, 'onBeforeUserAdd']
);
$osEventManager->addEventHandlerCompatible(
    'main',
    'OnAfterUserAdd',
    [\OnlineService\Events\SyncEventHandlers::class, 'onAfterUserAdd']
);
$osEventManager->addEventHandlerCompatible(
    'main',
    'OnAfterUserUpdate',
    [\OnlineService\Events\SyncEventHandlers::class, 'onAfterUserUpdate']
);
