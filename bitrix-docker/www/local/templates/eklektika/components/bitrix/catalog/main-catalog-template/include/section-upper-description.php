<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * @var array $arParams
 * @var array $arResult
 */

$iblockId = (int)($arParams['IBLOCK_ID'] ?? 0);
$sectionId = (int)($arResult['VARIABLES']['SECTION_ID'] ?? 0);
$sectionCode = (string)($arResult['VARIABLES']['SECTION_CODE'] ?? '');

CatalogSectionUpperDescription::render($iblockId, $sectionId, $sectionCode);
