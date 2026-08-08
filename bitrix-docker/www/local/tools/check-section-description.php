<?php
/**
 * Проверка DESCRIPTION раздела каталога после импорта.
 *
 *   php local/tools/check-section-description.php 230
 *   php local/tools/check-section-description.php ruchki_s_logotipom
 */
declare(strict_types=1);

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');
if ($_SERVER['DOCUMENT_ROOT'] === false) {
    fwrite(STDERR, "Cannot resolve DOCUMENT_ROOT\n");
    exit(1);
}

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

\Bitrix\Main\Loader::includeModule('iblock');

$arg = trim((string)($argv[1] ?? '230'));
$iblockId = 13;
$filter = ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y'];

if (ctype_digit($arg)) {
    $filter['ID'] = (int)$arg;
} else {
    $filter['=CODE'] = $arg;
}

$row = CIBlockSection::GetList([], $filter, false, [
    'ID',
    'NAME',
    'CODE',
    'DESCRIPTION',
    'DESCRIPTION_TYPE',
])->GetNext();

if (!$row) {
    fwrite(STDERR, "Section not found: {$arg}\n");
    exit(1);
}

$raw = (string)($row['~DESCRIPTION'] ?? $row['DESCRIPTION'] ?? '');

echo 'ID: ' . $row['ID'] . PHP_EOL;
echo 'NAME: ' . $row['NAME'] . PHP_EOL;
echo 'CODE: ' . $row['CODE'] . PHP_EOL;
echo 'DESCRIPTION_TYPE: ' . ($row['DESCRIPTION_TYPE'] ?? '') . PHP_EOL;
echo 'DESCRIPTION bytes: ' . strlen($raw) . PHP_EOL;
echo 'DESCRIPTION preview: ' . substr(trim(strip_tags($raw)), 0, 200) . PHP_EOL;
