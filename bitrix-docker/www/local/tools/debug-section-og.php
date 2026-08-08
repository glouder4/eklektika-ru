<?php
/**
 * Диагностика OG-шаблонов раздела dwstroy.opengraph.
 *
 *   php local/tools/debug-section-og.php --section=236
 *   php local/tools/debug-section-og.php --section=236 --write-title='Тестовый title'
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
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/classes/catalog/CatalogOgTemplateDefaults.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/classes/catalog/CatalogOgIpropertyWriter.php';

$options = getopt('', ['section:', 'iblock:', 'write-title:', 'help']);
if (isset($options['help']) || !isset($options['section'])) {
    echo "Usage: php local/tools/debug-section-og.php --section=236 [--write-title='...']\n";
    exit(isset($options['help']) ? 0 : 1);
}

$sectionId = (int)$options['section'];
$iblockId = isset($options['iblock'])
    ? (int)$options['iblock']
    : CatalogOgTemplateDefaults::CATALOG_IBLOCK_ID;
$writeTitle = isset($options['write-title']) ? trim((string)$options['write-title']) : '';

try {
    if (!\Bitrix\Main\Loader::includeModule('dwstroy.opengraph')) {
        throw new RuntimeException('dwstroy.opengraph not loaded');
    }
    if (!\Bitrix\Main\Loader::includeModule('iblock')) {
        throw new RuntimeException('iblock not loaded');
    }

    $section = CIBlockSection::GetByID($sectionId)->Fetch();
    if (!$section) {
        throw new RuntimeException('Section not found: ' . $sectionId);
    }

    echo 'Section: ' . $sectionId . ' / ' . $section['NAME'] . PHP_EOL;
    echo 'Iblock: ' . $iblockId . PHP_EOL;

    $langs = \Dwstroy\OpenGraph\COpenGraph::getLang($iblockId, 'IBLOCK');
    echo 'getLang tabs:' . PHP_EOL;
    if ($langs === []) {
        echo '  (empty!)' . PHP_EOL;
    }
    foreach ($langs as $lang) {
        echo '  LANG_ID=' . $lang['LANG_ID'] . ' NAME=' . $lang['NAME'] . PHP_EOL;
    }

    $tabs = CatalogOgIpropertyWriter::resolveOgTabCodes($iblockId);
    echo 'resolveOgTabCodes: ' . implode(', ', $tabs) . PHP_EOL;
    echo str_repeat('-', 60) . PHP_EOL;

    foreach ($tabs as $tab) {
        $found = CatalogOgIpropertyWriter::readSectionOgTemplates($iblockId, $sectionId, $tab);
        echo 'TAB=' . $tab . ' templates=' . count($found) . PHP_EOL;
        foreach (['SECTION_OG_TITLE', 'SECTION_OG_DESCRIPTION', 'SECTION_OG_TYPE', 'SECTION_OG_SITE_NAME'] as $code) {
            if (!isset($found[$code])) {
                echo '  ' . $code . ': <missing>' . PHP_EOL;
                continue;
            }
            $row = $found[$code];
            echo sprintf(
                '  %s: inherited=%s entity=%s:%s template=%s' . PHP_EOL,
                $code,
                $row['INHERITED'] ?? '?',
                $row['ENTITY_TYPE'] ?? '?',
                $row['ENTITY_ID'] ?? '?',
                mb_substr((string)($row['TEMPLATE'] ?? ''), 0, 120)
            );
        }
        echo PHP_EOL;
    }

    // Raw rows for this section from dws table
    echo str_repeat('-', 60) . PHP_EOL;
    echo 'Raw dws_og_iblock_iproperty_multilang for ENTITY_TYPE=S ENTITY_ID=' . $sectionId . PHP_EOL;
    $res = \Dwstroy\OpenGraph\InheritedPropertyTable::getList([
        'filter' => [
            '=IBLOCK_ID' => $iblockId,
            '=ENTITY_TYPE' => 'S',
            '=ENTITY_ID' => $sectionId,
        ],
        'select' => ['ID', 'LANG_ID', 'CODE', 'TEMPLATE'],
        'order' => ['LANG_ID' => 'ASC', 'CODE' => 'ASC'],
    ]);
    $rawCount = 0;
    while ($row = $res->fetch()) {
        ++$rawCount;
        if (!in_array($row['CODE'], ['SECTION_OG_TITLE', 'SECTION_OG_DESCRIPTION'], true)) {
            continue;
        }
        echo sprintf(
            '  id=%s lang=%s code=%s template=%s' . PHP_EOL,
            $row['ID'],
            $row['LANG_ID'],
            $row['CODE'],
            mb_substr((string)$row['TEMPLATE'], 0, 100)
        );
    }
    echo 'Raw rows total: ' . $rawCount . PHP_EOL;

    if ($writeTitle !== '') {
        echo str_repeat('-', 60) . PHP_EOL;
        echo 'WRITE SECTION_OG_TITLE => ' . $writeTitle . PHP_EOL;
        $tabsWritten = CatalogOgIpropertyWriter::saveSectionOgTemplates(
            $iblockId,
            $sectionId,
            ['SECTION_OG_TITLE' => $writeTitle]
        );
        echo 'Written tabs: ' . implode(', ', $tabsWritten) . PHP_EOL;

        foreach ($tabsWritten as $tab) {
            $found = CatalogOgIpropertyWriter::readSectionOgTemplates($iblockId, $sectionId, $tab);
            $tpl = (string)($found['SECTION_OG_TITLE']['TEMPLATE'] ?? '');
            echo 'Read-back tab=' . $tab . ' title=' . $tpl . PHP_EOL;
        }
    }

    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Fatal: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
