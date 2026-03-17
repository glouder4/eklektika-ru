<?php
/**
 * Редирект со старых URL вида /katalog/название_товара_ARTIKUL.php
 * на корректный DETAIL_URL предложения (301)
 *
 * Маска: /katalog/..._ARTIKUL.php, где ARTIKUL — свойство предложения (ID: 117, CODE: ARTIKUL)
 */
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

$artikul = trim($_GET['ARTIKUL'] ?? '');
if ($artikul === '') {
    \CHTTP::SetStatus('404 Not Found');
    require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
    $APPLICATION->IncludeFile('/404.php');
    require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
    die;
}

if (!\Bitrix\Main\Loader::includeModule('iblock') || !\Bitrix\Main\Loader::includeModule('catalog')) {
    \CHTTP::SetStatus('404 Not Found');
    require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
    $APPLICATION->IncludeFile('/404.php');
    require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
    die;
}

$offersIblockId = 14;
$linkPropertyCode = 'CML2_LINK';

// Ищем предложение по свойству ARTIKUL
$offer = \CIBlockElement::GetList(
    [],
    [
        'IBLOCK_ID' => $offersIblockId,
        'PROPERTY_ARTIKUL' => $artikul,
        'ACTIVE' => 'Y',
    ],
    false,
    ['nTopCount' => 1],
    ['ID', 'CODE']
)->GetNext();

if (!$offer) {
    \CHTTP::SetStatus('404 Not Found');
    require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
    $APPLICATION->IncludeFile('/404.php');
    require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
    die;
}

$offerId = (int)$offer['ID'];

// Получаем родительский товар
$rsLink = \CIBlockElement::GetProperty(
    $offersIblockId,
    $offerId,
    ['sort' => 'asc'],
    ['CODE' => $linkPropertyCode]
);
$parentProductId = null;
if ($linkProp = $rsLink->Fetch()) {
    $parentProductId = (int)$linkProp['VALUE'];
}

if (!$parentProductId) {
    \CHTTP::SetStatus('404 Not Found');
    require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
    $APPLICATION->IncludeFile('/404.php');
    require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
    die;
}

// Получаем родительский товар с путём к разделу
$parentEl = \CIBlockElement::GetList(
    [],
    ['ID' => $parentProductId, 'ACTIVE' => 'Y'],
    false,
    false,
    ['ID', 'CODE', 'IBLOCK_ID', 'IBLOCK_SECTION_ID']
)->GetNext();

if (!$parentEl) {
    \CHTTP::SetStatus('404 Not Found');
    require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
    $APPLICATION->IncludeFile('/404.php');
    require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
    die;
}

// Строим путь раздела (SECTION_CODE_PATH)
$sectionPath = '';
$sectionId = (int)($parentEl['IBLOCK_SECTION_ID'] ?? 0);
if ($sectionId > 0) {
    $navChain = \CIBlockSection::GetNavChain($parentEl['IBLOCK_ID'], $sectionId);
    $codes = [];
    while ($chain = $navChain->GetNext()) {
        if (!empty($chain['CODE'])) {
            $codes[] = $chain['CODE'];
        }
    }
    $sectionPath = implode('/', $codes);
}

// Формируем целевой URL: /catalog/#SECTION_CODE_PATH#/#ELEMENT_CODE#/offer/#OFFER_ID#/
$pathParts = array_filter(['/catalog', $sectionPath, $parentEl['CODE'], 'offer', (string)$offerId]);
$targetPath = implode('/', $pathParts) . '/';

\LocalRedirect($targetPath, true);
