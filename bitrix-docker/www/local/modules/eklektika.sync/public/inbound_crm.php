<?php

/**
 * Входящий HTTP-канал CRM → сайт (mаршрутизация {@see \OnlineService\Sync\FromCrm\InboundGateway}).
 * Канонический путь: {@see \OnlineService\Sync\Config\CrmInboundEndpoint::HTTP_PATH}
 */

use OnlineService\Sync\FromCrm\InboundGateway;
use OnlineService\Sync\InboundRequestParser;

// Тело запроса — до prolog_before (на POST Bitrix может прочитать php://input).
InboundRequestParser::captureRawBodyFromInput();

// Гарантия JSON-ответа даже при фатале (E_ERROR) до/во время dispatch.
// Это защищает n8n HTTP Request node от обращения к undefined response/body.
$__inboundResponseSent = false;
\register_shutdown_function(static function () use (&$__inboundResponseSent) {
    if ($__inboundResponseSent) {
        return;
    }
    $err = \error_get_last();
    if (!$err || !\is_array($err)) {
        return;
    }
    $type = (int)($err['type'] ?? 0);
    // Обрабатываем только фатальные типы.
    $fatalTypes = [\E_ERROR, \E_PARSE, \E_CORE_ERROR, \E_COMPILE_ERROR, \E_USER_ERROR];
    if (!\in_array($type, $fatalTypes, true)) {
        return;
    }
    if (!\headers_sent()) {
        \http_response_code(500);
        \header('Content-Type: application/json; charset=UTF-8');
    }
    echo \json_encode([
        'success' => 0,
        'error' => 'fatal_error',
        'message' => 'Inbound handler fatal error',
        'data' => [],
        'fatal' => [
            'type' => $type,
            'message' => (string)($err['message'] ?? ''),
            'file' => (string)($err['file'] ?? ''),
            'line' => (int)($err['line'] ?? 0),
        ],
    ], \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);
});

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/eklektika_requires.php';

\OnlineService\Sync\InboundSecurity::assertInboundAllowed();

$payload = InboundRequestParser::parse();

InboundGateway::dispatch($payload);
$__inboundResponseSent = true;
