<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/sync/bootstrap.php';

\OnlineService\Sync\InboundSecurity::assertInboundAllowed();
\OnlineService\Sync\FromCrm\InboundGateway::dispatch($_REQUEST);
