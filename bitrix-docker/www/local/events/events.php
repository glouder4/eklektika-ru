<?php

use OnlineService\Events\SyncEventHandlers;

$eventManager = \Bitrix\Main\EventManager::getInstance();

$eventManager->addEventHandlerCompatible(
    'main',
    'OnBeforeUserDelete',
    [SyncEventHandlers::class, 'onBeforeUserDelete']
);
$eventManager->addEventHandlerCompatible(
    'main',
    'OnBeforeUserRegister',
    [SyncEventHandlers::class, 'onBeforeUserRegister']
);
$eventManager->addEventHandlerCompatible(
    'main',
    'OnAfterUserRegister',
    [SyncEventHandlers::class, 'onAfterUserRegister']
);
$eventManager->addEventHandlerCompatible(
    'main',
    'OnAfterUserUpdate',
    [SyncEventHandlers::class, 'onAfterUserUpdate']
);

\Bitrix\Main\Loader::registerAutoLoadClasses(null, [
    '\OnlineService\Classes\Handlers\Search\Stemming' => '/local/php_interface/classes/handlers/search/stemming.php',
]);

$eventManager->addEventHandler(
    'search',
    'BeforeIndex',
    ['\OnlineService\Classes\Handlers\Search\Stemming', 'BeforeIndexHandler']
);
