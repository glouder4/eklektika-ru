<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

if (!defined('URL_B24')) {
    define('URL_B24', 'https://bitrix.eklektika.ru/');
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/sync/bootstrap.php';

\OnlineService\Sync\InboundSecurity::assertInboundAllowed();
\OnlineService\Sync\FromCrm\InboundGateway::dispatch($_REQUEST);
