<?php

use OnlineService\Sync\FromCrm\InboundGateway;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
// Как в local/php_interface/init.php: кастомные модули eklektika.* через local/classes/requires.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/classes/requires.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/sync/bootstrap.php';

\OnlineService\Sync\InboundSecurity::assertInboundAllowed();
InboundGateway::dispatch($_REQUEST);
