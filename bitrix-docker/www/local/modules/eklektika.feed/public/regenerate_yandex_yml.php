<?php

/**
 * HTTP-запуск регенерации YML-фида (для ручного вызова / внешнего cron через curl).
 *
 * GET/POST ?token=... — токен из local/php_interface/feed_integration_config.php
 */

use OnlineService\Feed\Bootstrap\FeedCliBootstrap;
use OnlineService\Feed\Config\FeedIntegrationConfig;
use OnlineService\Feed\Yml\YandexYmlFeedRegenerator;

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/modules/eklektika.feed/lib/Bootstrap/FeedCliBootstrap.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/modules/eklektika.feed/lib/Config/FeedIntegrationConfig.php';

header('Content-Type: application/json; charset=UTF-8');

$expectedToken = FeedIntegrationConfig::getRegenerateToken();
$providedToken = trim((string)($_REQUEST['token'] ?? ''));

if ($expectedToken === '' || !hash_equals($expectedToken, $providedToken)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'forbidden',
        'message' => 'Invalid or missing token',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    FeedCliBootstrap::init((string)$_SERVER['DOCUMENT_ROOT']);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'bootstrap_failed',
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

@set_time_limit(0);
@ignore_user_abort(true);

try {
    $result = (new YandexYmlFeedRegenerator())->regenerate();
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'generation_failed',
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
