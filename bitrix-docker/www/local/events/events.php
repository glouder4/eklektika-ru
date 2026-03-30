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
    'OnBeforeUserAdd',
    [SyncEventHandlers::class, 'onBeforeUserAdd']
);
$eventManager->addEventHandlerCompatible(
    'main',
    'OnAfterUserAdd',
    [SyncEventHandlers::class, 'onAfterUserAdd']
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
