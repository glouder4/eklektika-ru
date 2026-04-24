<?php

/**
 * ST-09/ST-10: единая и безопасная цепочка bootstrap для eklektika.* модулей.
 * Порядок загрузки зафиксирован по зависимостям:
 * b24.rest -> company -> catalog.pricing -> site -> catalog.import -> orders.applications -> b24.usersync
 */ 
function requireEklektikaModuleInclude(string $moduleId): bool
{
    $moduleIncludePath = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/' . $moduleId . '/include.php';

    if (is_file($moduleIncludePath)) {
        require_once $moduleIncludePath;
        return true;
    }

    // Не прерываем bootstrap, чтобы сохранить поведение легаси.
    trigger_error('Failed to include module include.php: ' . $moduleId, E_USER_WARNING);

    return false;
}

requireEklektikaModuleInclude('eklektika.b24.rest');
// User (eklektika.b24.usersync) extends OnlineService\B24\Request — при сбое include rest класс может не объявиться (runtime: «Request not found»).
$eklektikaB24RestRequestPaths = [
    __DIR__ . '/../modules/eklektika.b24.rest/lib/Request.php',
];
if (!empty($_SERVER['DOCUMENT_ROOT'])) {
    $eklektikaB24RestRequestPaths[] = rtrim((string) $_SERVER['DOCUMENT_ROOT'], '/\\') . '/local/modules/eklektika.b24.rest/lib/Request.php';
}
if (!\class_exists(\OnlineService\B24\Request::class, false)) {
    foreach ($eklektikaB24RestRequestPaths as $eklektikaB24RestRequestPath) {
        if (\is_file($eklektikaB24RestRequestPath)) {
            require_once $eklektikaB24RestRequestPath;
            break;
        }
    }
}
requireEklektikaModuleInclude('eklektika.company');
requireEklektikaModuleInclude('eklektika.catalog.pricing');
requireEklektikaModuleInclude('eklektika.site');
requireEklektikaModuleInclude('eklektika.catalog.import');
requireEklektikaModuleInclude('eklektika.orders.applications');
requireEklektikaModuleInclude('eklektika.b24.usersync');

if (class_exists(\OnlineService\Site\CatalogPriceFloor::class)) {
    \OnlineService\Site\CatalogPriceFloor::markCompositeNonCacheableForAuthorizedCatalog();
}

$GLOBALS['OS_BREADCRUMBS_ADD_CONTAINER'] = 'Y';

// EKLEKTIKA_SYNC_CONFIG + SyncTrace; до этого sync_debug в config.local.php не влиял на ЛК/ ajax (bootstrap не грузился).
$eklektikaSyncBootstrap = __DIR__ . '/../sync/bootstrap.php';
if (is_file($eklektikaSyncBootstrap)) {
    require_once $eklektikaSyncBootstrap;
}

// Регистрация main: OnAfterUserUpdate / OnAfterUserAdd и т.д. (SyncEventHandlers) — без этого обработчики в local/events/events.php никогда не вешались.
$eklektikaLocalEvents = __DIR__ . '/../events/requires.php';
if (is_file($eklektikaLocalEvents)) {
    require_once $eklektikaLocalEvents;
}

// Ограничение цены по закупке: CatalogPriceFloor::bootstrap() в local/php_interface/init.php
// Модификация индексирования: SearchIndexingBootstrap в eklektika.site/include.php