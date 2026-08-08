<?php
/**
 * Dry-run: сравнить OG из upload/categories.sqlite с meta og:* на живых страницах (new_url).
 *
 * По умолчанию: og_title + og_description.
 * Сравниваются только теги, которые есть и в SQLite, и на странице
 * (пустой meta на странице не считается diff).
 *
 * Запуск:
 *   php local/tools/check-category-og-diff.php --dry-run
 *   php local/tools/check-category-og-diff.php --dry-run --limit=10
 *   php local/tools/check-category-og-diff.php --dry-run --base-url=https://new.eklektika.ru
 *   php local/tools/check-category-og-diff.php --dry-run --fields=og_title,og_description,og_type
 *   php local/tools/check-category-og-diff.php --dry-run --show-matches
 */
declare(strict_types=1);

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');
if ($_SERVER['DOCUMENT_ROOT'] === false) {
    fwrite(STDERR, "Cannot resolve DOCUMENT_ROOT\n");
    exit(1);
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/classes/catalog/CategoryContentSqliteReader.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/classes/catalog/CategoryOgPageDiffChecker.php';

$options = getopt('', [
    'dry-run',
    'limit:',
    'sqlite:',
    'base-url:',
    'timeout:',
    'fields:',
    'show-matches',
    'include-og-url',
    'help',
]);

if (isset($options['help'])) {
    echo "Usage: php local/tools/check-category-og-diff.php --dry-run [options]\n";
    echo "  --dry-run              required; only reports diffs (no writes)\n";
    echo "  --limit=N              limit SQLite rows\n";
    echo "  --sqlite=path          path to categories.sqlite\n";
    echo "  --base-url=URL         prefix for relative new_url\n";
    echo "  --timeout=SEC          HTTP timeout (default 20)\n";
    echo "  --fields=a,b,c         compare subset (default: og_title,og_description)\n";
    echo "  --include-og-url       also compare og:url\n";
    echo "  --show-matches         also print matching pages\n";
    echo "Note: missing tags on page are skipped; only present tags are compared.\n";
    exit(0);
}

if (!isset($options['dry-run'])) {
    fwrite(STDERR, "Use --dry-run (script is audit-only, no apply mode).\n");
    exit(1);
}

$limit = isset($options['limit']) ? max(0, (int)$options['limit']) : null;
if ($limit === 0) {
    $limit = null;
}

$defaultSqlite = $_SERVER['DOCUMENT_ROOT'] . '/upload/categories.sqlite';
$sqlitePath = isset($options['sqlite']) ? (string)$options['sqlite'] : $defaultSqlite;
$baseUrl = isset($options['base-url']) ? (string)$options['base-url'] : '';
$timeout = isset($options['timeout']) ? max(1, (int)$options['timeout']) : 20;
$onlyDiff = !isset($options['show-matches']);

$fields = CategoryOgPageDiffChecker::COMPARE_FIELDS;
if (isset($options['fields']) && trim((string)$options['fields']) !== '') {
    $fields = array_map('trim', explode(',', (string)$options['fields']));
}
if (isset($options['include-og-url'])) {
    $fields[] = 'og_url';
}
$fields = CategoryOgPageDiffChecker::normalizeFields($fields);

$logger = static function (string $message): void {
    echo $message . PHP_EOL;
};

try {
    $backend = CategoryContentSqliteReader::resolveBackend();
    if ($backend === '') {
        throw new \RuntimeException(CategoryContentSqliteReader::driverMissingMessage());
    }

    $reader = new CategoryContentSqliteReader($sqlitePath);
    $ogColumns = $reader->getOgColumnsPresent();
    if ($ogColumns === []) {
        throw new \RuntimeException(
            CategoryContentSqliteReader::missingOgColumnsMessage($sqlitePath, $ogColumns)
        );
    }

    $rows = $reader->fetchOgProcessableRows($limit);

    echo 'SQLite: ' . $sqlitePath . PHP_EOL;
    echo 'Backend: ' . $backend . PHP_EOL;
    echo 'Mode: dry-run (compare only)' . PHP_EOL;
    echo 'Base URL: ' . ($baseUrl !== '' ? $baseUrl : '(from new_url)') . PHP_EOL;
    echo 'Fields: ' . implode(', ', $fields) . PHP_EOL;
    echo 'Rows: ' . count($rows) . PHP_EOL;
    echo str_repeat('-', 60) . PHP_EOL;

    $checker = new CategoryOgPageDiffChecker($baseUrl, $timeout, 'EklektikaOgDiffBot/1.0', $logger);
    $stats = $checker->compare($rows, $fields, $onlyDiff);

    echo str_repeat('-', 60) . PHP_EOL;
    echo 'Total rows:      ' . $stats['total'] . PHP_EOL;
    echo 'Compared:        ' . $stats['compared'] . PHP_EOL;
    echo 'Matched:         ' . $stats['matched'] . PHP_EOL;
    echo 'Differed:        ' . $stats['differed'] . PHP_EOL;
    echo 'Skipped no OG:   ' . $stats['skipped_no_og'] . PHP_EOL;
    echo 'Skipped no URL:  ' . $stats['skipped_no_url'] . PHP_EOL;
    echo 'Fetch errors:    ' . $stats['fetch_errors'] . PHP_EOL;

    if ($stats['differed'] > 0) {
        echo PHP_EOL . 'Pages with OG diffs: ' . $stats['differed'] . PHP_EOL;
        foreach ($stats['details'] as $detail) {
            if (($detail['status'] ?? '') !== 'diff') {
                continue;
            }
            echo PHP_EOL . 'URL: ' . $detail['url'] . PHP_EOL;
            echo 'ID: ' . $detail['id'] . ' | ' . $detail['menu_title'] . PHP_EOL;
            foreach ($detail['diffs'] as $diff) {
                echo '  ' . $diff['property'] . PHP_EOL;
                echo '    sqlite: ' . $diff['sqlite'] . PHP_EOL;
                echo '    page:   ' . ($diff['page'] !== '' ? $diff['page'] : '<empty>') . PHP_EOL;
            }
        }
    }

    if ($stats['fetch_errors'] > 0) {
        exit(2);
    }

    exit($stats['differed'] > 0 ? 3 : 0);
} catch (\Throwable $e) {
    fwrite(STDERR, 'Fatal: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
