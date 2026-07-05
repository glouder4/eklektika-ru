<?php
declare(strict_types=1);

$repoRoot = dirname(__DIR__, 2);
require $repoRoot . '/local/php_interface/classes/catalog/CategoryContentSqliteReader.php';
$sqlitePath = $repoRoot . '/upload/categories.sqlite';
$label = $argv[1] ?? 'default';

echo "=== {$label} ===\n";
echo 'resolveBackend: ' . (CategoryContentSqliteReader::resolveBackend() ?: '(none)') . "\n";
echo 'pdo_sqlite loaded: ' . (extension_loaded('pdo_sqlite') ? 'yes' : 'no') . "\n";
echo 'SQLite3 class: ' . (class_exists(SQLite3::class, false) ? 'yes' : 'no') . "\n";

try {
    $reader = new CategoryContentSqliteReader($sqlitePath);
    $rows = $reader->fetchProcessableRows();
    echo 'fetchProcessableRows count: ' . count($rows) . "\n";
    echo "status: OK\n";
} catch (Throwable $e) {
    echo 'fetchProcessableRows: ERROR - ' . $e->getMessage() . "\n";
}
