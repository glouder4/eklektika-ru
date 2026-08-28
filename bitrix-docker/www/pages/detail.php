<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/section_detail_redirect.php';
eklektikaRedirectSectionDetailWithoutElement('/pages/', ['detail.php', 'index.php', 'list.php']);

$elementCode = trim((string)($_REQUEST['ELEMENT_CODE'] ?? ''));
if ($elementCode !== '') {
    $GLOBALS['CANONICAL_URL'] = '/pages/' . rawurlencode($elementCode) . '/';
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$iblockId = 25;
$iblockType = 'content';
if (\Bitrix\Main\Loader::includeModule('iblock')) {
    $iblock = \CIBlock::GetArrayByID($iblockId);
    if (is_array($iblock) && trim((string)($iblock['IBLOCK_TYPE_ID'] ?? '')) !== '') {
        $iblockType = (string)$iblock['IBLOCK_TYPE_ID'];
    }
}

$APPLICATION->IncludeComponent(
    'bitrix:news.detail',
    'custom-page',
    [
        'ACTIVE_DATE_FORMAT' => 'd.m.Y',
        'ADD_ELEMENT_CHAIN' => 'Y',
        'ADD_SECTIONS_CHAIN' => 'N',
        'AJAX_MODE' => 'N',
        'AJAX_OPTION_ADDITIONAL' => '',
        'AJAX_OPTION_HISTORY' => 'N',
        'AJAX_OPTION_JUMP' => 'N',
        'AJAX_OPTION_STYLE' => 'Y',
        'BROWSER_TITLE' => '-',
        'CACHE_GROUPS' => 'Y',
        'CACHE_TIME' => '36000000',
        'CACHE_TYPE' => 'A',
        'CHECK_DATES' => 'N',
        'DETAIL_URL' => '/pages/#ELEMENT_CODE#/',
        'DISPLAY_BOTTOM_PAGER' => 'N',
        'DISPLAY_DATE' => 'N',
        'DISPLAY_NAME' => 'N',
        'DISPLAY_PICTURE' => 'N',
        'DISPLAY_PREVIEW_TEXT' => 'N',
        'DISPLAY_TOP_PAGER' => 'N',
        'ELEMENT_CODE' => $elementCode,
        'ELEMENT_ID' => '',
        'FIELD_CODE' => ['', ''],
        'IBLOCK_ID' => (string)$iblockId,
        'IBLOCK_TYPE' => $iblockType,
        'IBLOCK_URL' => '/pages/',
        'INCLUDE_IBLOCK_INTO_CHAIN' => 'N',
        'MESSAGE_404' => '',
        'META_DESCRIPTION' => '-',
        'META_KEYWORDS' => '-',
        'PAGER_BASE_LINK_ENABLE' => 'N',
        'PAGER_SHOW_ALL' => 'N',
        'PAGER_TEMPLATE' => '.default',
        'PAGER_TITLE' => 'Страница',
        'PROPERTY_CODE' => ['', ''],
        'SET_BROWSER_TITLE' => 'Y',
        'SET_CANONICAL_URL' => 'N',
        'SET_LAST_MODIFIED' => 'N',
        'SET_META_DESCRIPTION' => 'Y',
        'SET_META_KEYWORDS' => 'Y',
        'SET_STATUS_404' => 'Y',
        'SET_TITLE' => 'Y',
        'SHOW_404' => 'N',
        'STRICT_SECTION_CHECK' => 'N',
        'USE_PERMISSIONS' => 'N',
        'USE_SHARE' => 'N',
        'COMPONENT_TEMPLATE' => 'custom-page',
    ],
    false
);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
