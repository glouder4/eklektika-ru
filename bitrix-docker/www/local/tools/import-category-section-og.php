<?php
/**
 * Импорт OG-шаблонов каталога:
 * - разделы: из upload/categories.sqlite + вычисляемые поля (SEO-маски)
 * - элементы: маски на уровне IBLOCK 13 (наследуются всеми товарами)
 *
 * Разделы — поля dwstroy.opengraph tab catalog:
 * SECTION_OG_TYPE/TITLE/DESCRIPTION/SITE_NAME/IMAGE + computed IMAGE_*, LOCALE
 *
 * Элементы — один раз на инфоблок:
 * ELEMENT_OG_TYPE/TITLE/DESCRIPTION/IMAGE/SITE_NAME/LOCALE + IMAGE_*
 *
 * Запуск:
 *   php local/tools/import-category-section-og.php --dry-run
 *   php local/tools/import-category-section-og.php --apply
 *   php local/tools/import-category-section-og.php --apply --with-elements-iblock
 *   php local/tools/import-category-section-og.php --apply-elements-iblock
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

$catalogOgClassesDir = $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/classes/catalog';
$requiredCatalogOgFiles = [
    'CatalogOgTemplateDefaults.php',
    'CatalogOgIpropertyWriter.php',
    'CategoryUpperDescImporter.php',
    'CategoryContentSqliteReader.php',
    'CategorySectionOgImporter.php',
    'CatalogElementOgIblockImporter.php',
];

foreach ($requiredCatalogOgFiles as $fileName) {
    $path = $catalogOgClassesDir . '/' . $fileName;
    if (!is_file($path)) {
        fwrite(STDERR, 'Missing required file: ' . $path . PHP_EOL);
        fwrite(STDERR, 'Deploy all files from local/php_interface/classes/catalog/ (CatalogOg*.php, Category*.php).' . PHP_EOL);
        exit(1);
    }
    require_once $path;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/catalog_section_path.php';

$options = getopt('', [
    'dry-run',
    'apply',
    'apply-elements-iblock',
    'with-elements-iblock',
    'limit:',
    'sqlite:',
    'help',
]);

if (isset($options['help'])) {
    echo "Usage: php local/tools/import-category-section-og.php [options]\n";
    echo "  --dry-run                 simulate section import (default)\n";
    echo "  --apply                   write section OG templates from SQLite\n";
    echo "  --apply-elements-iblock   write element OG templates on iblock 13 only\n";
    echo "  --with-elements-iblock    with --apply, also set element iblock templates\n";
    echo "  --limit=N                 limit SQLite rows\n";
    echo "  --sqlite=path             path to categories.sqlite\n";
    exit(0);
}

$dryRun = !isset($options['apply']) && !isset($options['apply-elements-iblock']);
$onlyElementsIblock = isset($options['apply-elements-iblock']) && !isset($options['apply']);
$withElementsIblock = isset($options['with-elements-iblock']) || isset($options['apply-elements-iblock']);
$limit = isset($options['limit']) ? max(0, (int)$options['limit']) : null;
if ($limit === 0) {
    $limit = null;
}

$defaultSqlite = $_SERVER['DOCUMENT_ROOT'] . '/upload/categories.sqlite';
$sqlitePath = isset($options['sqlite']) ? (string)$options['sqlite'] : $defaultSqlite;

$logger = static function (string $message): void {
    echo $message . PHP_EOL;
};

$exitCode = 0;

try {
    if (!$onlyElementsIblock) {
        $backend = CategoryContentSqliteReader::resolveBackend();
        if ($backend === '') {
            throw new \RuntimeException(CategoryContentSqliteReader::driverMissingMessage());
        }

        $reader = new CategoryContentSqliteReader($sqlitePath);
        $ogColumns = $reader->getOgColumnsPresent();
        $rows = $reader->fetchOgProcessableRows($limit);

        echo 'SQLite: ' . $sqlitePath . PHP_EOL;
        echo 'Backend: ' . $backend . PHP_EOL;
        echo 'OG columns: ' . implode(', ', $ogColumns) . PHP_EOL;
        echo 'Mode: ' . ($dryRun ? 'dry-run' : 'apply') . PHP_EOL;
        echo 'Rows to process: ' . count($rows) . PHP_EOL;
        echo str_repeat('-', 60) . PHP_EOL;

        $importer = new CategorySectionOgImporter($logger);
        $stats = $importer->import($rows, $dryRun);

        echo str_repeat('-', 60) . PHP_EOL;
        echo 'Sections total:          ' . $stats['total'] . PHP_EOL;
        echo 'Sections updated:        ' . $stats['updated'] . PHP_EOL;
        echo 'Skipped non-catalog:     ' . $stats['skipped_non_catalog'] . PHP_EOL;
        echo 'Skipped no section:      ' . $stats['skipped_no_section'] . PHP_EOL;
        echo 'Skipped empty og:        ' . $stats['skipped_empty_og'] . PHP_EOL;
        echo 'OG image→{=PICTURE}:     ' . ($stats['og_image_to_template'] ?? 0) . PHP_EOL;
        echo 'Section errors:          ' . $stats['errors'] . PHP_EOL;

        if ($stats['details'] !== []) {
            echo PHP_EOL . 'Section details:' . PHP_EOL;
            foreach ($stats['details'] as $detail) {
                echo $detail . PHP_EOL;
            }
        }

        if ($stats['errors'] > 0) {
            $exitCode = 2;
        }
    }

    if ($onlyElementsIblock || $withElementsIblock) {
        echo PHP_EOL . str_repeat('=', 60) . PHP_EOL;
        echo 'Element OG templates (IBLOCK ' . CatalogOgTemplateDefaults::CATALOG_IBLOCK_ID . ')' . PHP_EOL;
        echo str_repeat('-', 60) . PHP_EOL;

        $elementImporter = new CatalogElementOgIblockImporter($logger);
        $elementStats = $elementImporter->apply($dryRun);

        echo str_repeat('-', 60) . PHP_EOL;
        echo 'Element iblock updated:  ' . $elementStats['updated'] . PHP_EOL;
        echo 'Element iblock errors:   ' . $elementStats['errors'] . PHP_EOL;

        if ($elementStats['details'] !== []) {
            echo PHP_EOL . 'Element details:' . PHP_EOL;
            foreach ($elementStats['details'] as $detail) {
                echo $detail . PHP_EOL;
            }
        }

        if ($elementStats['errors'] > 0) {
            $exitCode = 2;
        }
    }

    exit($exitCode);
} catch (\Throwable $e) {
    fwrite(STDERR, 'Fatal: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
