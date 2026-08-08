<?php
/**
 * Импорт detail_text из upload/categories.sqlite
 * в описание разделов IBLOCK 13 (DESCRIPTION).
 *
 * Правила:
 * - new_url IS NULL / пустой → не читаются (фильтр в reader)
 * - detail_text пустой → не читаются (фильтр в reader)
 * - new_url не /catalog/* → skip
 * - section не найден → skip + log
 *
 * Запуск из корня сайта (DOCUMENT_ROOT):
 *   php local/tools/import-category-detail-text.php --dry-run
 *   php local/tools/import-category-detail-text.php --apply
 *   php local/tools/import-category-detail-text.php --apply --limit=10
 *   php local/tools/import-category-detail-text.php --sqlite=/path/to/categories.sqlite
 */
declare(strict_types=1);

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');
if ($_SERVER['DOCUMENT_ROOT'] === false) {
    fwrite(STDERR, "Cannot resolve DOCUMENT_ROOT\n");
    exit(1);
}

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('CHK_EVENT', false);
define('BX_NO_ACCELERATOR_RESET', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/classes/catalog/CategoryContentSqliteReader.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/classes/catalog/CategoryDetailTextImporter.php';

$options = getopt('', ['dry-run', 'apply', 'limit:', 'sqlite:', 'help']);

if (isset($options['help'])) {
    echo "Usage: php local/tools/import-category-detail-text.php [--dry-run|--apply] [--limit=N] [--sqlite=path]\n";
    echo "  --dry-run   simulate import (default)\n";
    echo "  --apply     write DESCRIPTION to sections\n";
    exit(0);
}

$dryRun = !isset($options['apply']);
$limit = isset($options['limit']) ? max(0, (int)$options['limit']) : null;
if ($limit === 0) {
    $limit = null;
}

$defaultSqlite = $_SERVER['DOCUMENT_ROOT'] . '/upload/categories.sqlite';
$sqlitePath = isset($options['sqlite']) ? (string)$options['sqlite'] : $defaultSqlite;

$logger = static function (string $message): void {
    echo $message . PHP_EOL;
};

try {
    $backend = CategoryContentSqliteReader::resolveBackend();
    if ($backend === '') {
        throw new \RuntimeException(CategoryContentSqliteReader::driverMissingMessage());
    }

    $reader = new CategoryContentSqliteReader($sqlitePath);
    $rows = $reader->fetchDetailTextProcessableRows($limit);

    echo 'SQLite: ' . $sqlitePath . PHP_EOL;
    echo 'Backend: ' . $backend . PHP_EOL;
    echo 'Mode: ' . ($dryRun ? 'dry-run' : 'apply') . PHP_EOL;
    echo 'Target: section DESCRIPTION' . PHP_EOL;
    echo 'Rows to process: ' . count($rows) . PHP_EOL;
    echo str_repeat('-', 60) . PHP_EOL;

    $importer = new CategoryDetailTextImporter($logger);
    $stats = $importer->import($rows, $dryRun);

    echo str_repeat('-', 60) . PHP_EOL;
    echo 'Total:               ' . $stats['total'] . PHP_EOL;
    echo 'Updated/simulated:   ' . $stats['updated'] . PHP_EOL;
    echo 'Skipped non-catalog: ' . $stats['skipped_non_catalog'] . PHP_EOL;
    echo 'Skipped no section:  ' . $stats['skipped_no_section'] . PHP_EOL;
    echo 'Skipped empty:       ' . $stats['skipped_empty'] . PHP_EOL;
    echo 'Errors:              ' . $stats['errors'] . PHP_EOL;

    if ($stats['details'] !== []) {
        echo PHP_EOL . 'Details:' . PHP_EOL;
        foreach ($stats['details'] as $detail) {
            echo $detail . PHP_EOL;
        }
    }

    exit($stats['errors'] > 0 ? 2 : 0);
} catch (\Throwable $e) {
    fwrite(STDERR, 'Fatal: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
