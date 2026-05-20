<?php

defined('B_PROLOG_INCLUDED') || die();

use Bitrix\Main\Loader;

/**
 * Канал CRM ↔ сайт (inbound/outbound helpers), конфиг sync, трассировка.
 * Без установщика Bitrix — только автозагрузка классов и ядро конфигурации.
 */
Loader::registerAutoLoadClasses(null, [
    \OnlineService\Sync\SyncInboundLog::class => '/local/modules/eklektika.sync/lib/SyncInboundLog.php',
    \OnlineService\Sync\SyncTrace::class => '/local/modules/eklektika.sync/lib/SyncTrace.php',
    \OnlineService\Sync\SyncPrimitiveBreakpoint::class => '/local/modules/eklektika.sync/lib/SyncPrimitiveBreakpoint.php',
    \OnlineService\Sync\InboundSecurity::class => '/local/modules/eklektika.sync/lib/InboundSecurity.php',
    \OnlineService\Sync\InboundRequestParser::class => '/local/modules/eklektika.sync/lib/InboundRequestParser.php',
    \OnlineService\Sync\FromCrm\InboundGateway::class => '/local/modules/eklektika.sync/lib/from-crm/InboundGateway.php',
    \OnlineService\Sync\FromCrm\CrmInboundUfMap::class => '/local/modules/eklektika.sync/lib/from-crm/CrmInboundUfMap.php',
    \OnlineService\Sync\ToCrm\OutboundUpdateContactPayload::class => '/local/modules/eklektika.sync/lib/to-crm/OutboundUpdateContactPayload.php',
    \OnlineService\Sync\Config\CrmInboundEndpoint::class => '/local/modules/eklektika.sync/lib/Config/CrmInboundEndpoint.php',
]);

require_once __DIR__ . '/lib/SyncKernelBootstrap.php';
