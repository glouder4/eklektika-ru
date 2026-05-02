<?php

/**
 * Входящий HTTP-канал CRM → сайт (mаршрутизация {@see \OnlineService\Sync\FromCrm\InboundGateway}).
 * Канонический путь: {@see \OnlineService\Sync\Config\CrmInboundEndpoint::HTTP_PATH}
 */

use OnlineService\Sync\FromCrm\InboundGateway;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/eklektika_requires.php';

\OnlineService\Sync\InboundSecurity::assertInboundAllowed();

$payload = $_REQUEST;
$contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
if ($contentType !== '' && \stripos($contentType, 'application/json') !== false) {
    $raw = \file_get_contents('php://input');
    if (\is_string($raw) && $raw !== '') {
        $decoded = \json_decode($raw, true);
        if (\json_last_error() === \JSON_ERROR_NONE && \is_array($decoded)) {
            // Query string (sync_token и т.д.) перекрывает одноимённые ключи из JSON.
            $payload = \array_merge($decoded, $_GET);
        }
    }
}

InboundGateway::dispatch($payload);
