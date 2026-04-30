<?php

/**
 * Входящий HTTP-канал CRM → сайт (mаршрутизация {@see \OnlineService\Sync\FromCrm\InboundGateway}).
 * Канонический путь: {@see \OnlineService\Sync\Config\CrmInboundEndpoint::HTTP_PATH}
 */

use OnlineService\Sync\FromCrm\InboundGateway;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/eklektika_requires.php';

\OnlineService\Sync\InboundSecurity::assertInboundAllowed();
InboundGateway::dispatch($_REQUEST);
