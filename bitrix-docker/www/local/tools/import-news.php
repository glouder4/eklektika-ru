<?php
/**
 * Импорт новостей из upload/news.sqlite в IBLOCK 16.
 *
 * Источник: парсинг https://eklektika.ru/novosti/
 * Картинки лежат в upload/news_images/ (не копируются).
 * В SQLite/content_html пути /upload/news/ → при импорте заменяются на /upload/news_images/.
 *
 * Запуск из корня сайта (DOCUMENT_ROOT):
 *   php local/tools/import-news.php --dry-run
 *   php local/tools/import-news.php --apply
 *   php local/tools/import-news.php --fix-image-paths --apply
 *   php local/tools/import-news.php --export-redirects
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

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/classes/news/NewsSqliteReader.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/classes/news/NewsImporter.php';

$options = getopt('', [
    'dry-run',
    'apply',
    'limit:',
    'sqlite:',
    'images-source:',
    'skip-images',
    'export-redirects',
    'fix-image-paths',
    'help',
]);

if (isset($options['help'])) {
    echo "Usage: php local/tools/import-news.php [--dry-run|--apply] [options]\n";
    echo "  --dry-run            simulate import (default)\n";
    echo "  --apply              create/update elements in IBLOCK 16\n";
    echo "  --fix-image-paths    fix /upload/news/ → /upload/news_images/ in imported DETAIL_TEXT\n";
    echo "  --limit=N            process first N rows\n";
    echo "  --sqlite=path        path to news.sqlite (default: upload/news.sqlite)\n";
    echo "  --images-source=path preview images dir (default: upload/news_images)\n";
    echo "  --skip-images        do not register PREVIEW_PICTURE\n";
    echo "  --export-redirects   write old_news_redirects.php from SQLite urls\n";
    exit(0);
}

$dryRun = !isset($options['apply']);
$skipImages = isset($options['skip-images']);
$exportRedirects = isset($options['export-redirects']);
$fixImagePaths = isset($options['fix-image-paths']);
$limit = isset($options['limit']) ? max(0, (int)$options['limit']) : null;
if ($limit === 0) {
    $limit = null;
}

$documentRoot = $_SERVER['DOCUMENT_ROOT'];
$defaultSqlite = $documentRoot . '/upload/news.sqlite';
$defaultImagesSource = $documentRoot . '/upload/news_images';
$sqlitePath = isset($options['sqlite']) ? (string)$options['sqlite'] : $defaultSqlite;
$imagesSource = isset($options['images-source']) ? (string)$options['images-source'] : $defaultImagesSource;
$redirectsFile = $documentRoot . '/local/php_interface/old_news_redirects.php';

$logger = static function (string $message): void {
    echo $message . PHP_EOL;
};

try {
    $importer = new NewsImporter($documentRoot, $imagesSource, $logger);

    if ($fixImagePaths) {
        echo 'Mode: ' . ($dryRun ? 'dry-run' : 'apply') . ' (fix-image-paths)' . PHP_EOL;
        echo str_repeat('-', 60) . PHP_EOL;

        $stats = $importer->fixImportedContentImagePaths($dryRun);

        echo str_repeat('-', 60) . PHP_EOL;
        echo 'Total elements: ' . $stats['total'] . PHP_EOL;
        echo 'Fixed:          ' . $stats['fixed'] . PHP_EOL;
        echo 'Skipped:        ' . $stats['skipped'] . PHP_EOL;
        echo 'Errors:         ' . $stats['errors'] . PHP_EOL;

        if ($stats['details'] !== []) {
            echo PHP_EOL . 'Details:' . PHP_EOL;
            foreach ($stats['details'] as $detail) {
                echo $detail . PHP_EOL;
            }
        }

        exit($stats['errors'] > 0 ? 2 : 0);
    }

    $backend = NewsSqliteReader::resolveBackend();
    if ($backend === '') {
        throw new \RuntimeException(NewsSqliteReader::driverMissingMessage());
    }

    $reader = new NewsSqliteReader($sqlitePath);
    $totalInDb = $reader->countNewsRows();
    $rows = $reader->fetchNewsRows($limit);

    echo 'CMS: Bitrix, IBLOCK_ID=' . NewsImporter::IBLOCK_ID . ', SEF ' . NewsImporter::SEF_FOLDER . PHP_EOL;
    echo 'SQLite: ' . $sqlitePath . PHP_EOL;
    echo 'Backend: ' . $backend . PHP_EOL;
    echo 'Images dir: ' . $imagesSource . ' → ' . NewsImporter::UPLOAD_IMAGES_DIR . PHP_EOL;
    echo 'Rows in DB: ' . $totalInDb . PHP_EOL;
    echo 'Rows to process: ' . count($rows) . PHP_EOL;

    echo 'Mode: ' . ($dryRun ? 'dry-run' : 'apply') . PHP_EOL;
    echo str_repeat('-', 60) . PHP_EOL;

    $stats = $importer->import($rows, $dryRun, $skipImages);

    echo str_repeat('-', 60) . PHP_EOL;
    echo 'Total:          ' . $stats['total'] . PHP_EOL;
    echo 'Created:        ' . $stats['created'] . PHP_EOL;
    echo 'Updated:        ' . $stats['updated'] . PHP_EOL;
    echo 'Skipped:        ' . $stats['skipped'] . PHP_EOL;
    echo 'Images found:   ' . $stats['images_found'] . PHP_EOL;
    echo 'Redirects map:  ' . count($stats['redirects']) . PHP_EOL;
    echo 'Errors:         ' . $stats['errors'] . PHP_EOL;

    if ($exportRedirects) {
        $written = NewsImporter::writeRedirectsPhpFile($redirectsFile, $stats['redirects']);
        echo PHP_EOL . 'Redirects written: ' . $written . ' → ' . $redirectsFile . PHP_EOL;
    }

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
