#!/usr/bin/env php
<?php

/**
 * CLI: регенерация YML-фида для Яндекс Директ (cron).
 *
 * Пример:
 *   php local/modules/eklektika.feed/tools/regenerate_yandex_yml.php
 *   php local/modules/eklektika.feed/tools/regenerate_yandex_yml.php --site-base-url=https://new.eklektika.ru
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

$documentRoot = realpath(__DIR__ . '/../../../..');
if ($documentRoot === false) {
    fwrite(STDERR, "Cannot resolve DOCUMENT_ROOT\n");
    exit(1);
}

require_once __DIR__ . '/../lib/Bootstrap/FeedCliBootstrap.php';

use OnlineService\Feed\Bootstrap\FeedCliBootstrap;
use OnlineService\Feed\Yml\FeedGenerationProgress;
use OnlineService\Feed\Yml\YandexYmlFeedRegenerator;

$siteBaseUrl = null;
$quiet = false;
foreach (array_slice($argv, 1) as $arg) {
    if (strpos($arg, '--site-base-url=') === 0) {
        $siteBaseUrl = substr($arg, strlen('--site-base-url='));
        continue;
    }
    if ($arg === '--quiet' || $arg === '-q') {
        $quiet = true;
    }
}

try {
    FeedCliBootstrap::init($documentRoot);
} catch (\Throwable $e) {
    fwrite(STDERR, json_encode([
        'success' => false,
        'error' => 'bootstrap_failed',
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL);
    exit(1);
}

@set_time_limit(0);
@ignore_user_abort(true);

$progress = $quiet
    ? null
    : new FeedGenerationProgress(static function (string $line): void {
        fwrite(STDERR, $line . PHP_EOL);
    });

if ($progress !== null) {
    $progress->step('Bootstrap OK (SITE_ID=' . (defined('SITE_ID') ? SITE_ID : '?') . ')');
}

try {
    $result = (new YandexYmlFeedRegenerator($documentRoot, $siteBaseUrl, $progress))->regenerate();
    fwrite(STDOUT, json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL);
    exit(0);
} catch (\Throwable $e) {
    fwrite(STDERR, json_encode([
        'success' => false,
        'error' => 'generation_failed',
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL);
    exit(1);
}
