<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $templateData
 * @var array $arParams
 * @var string $templateFolder
 * @global CMain $APPLICATION
 */

global $APPLICATION;

if (!empty($templateData['TEMPLATE_LIBRARY']))
{
	CJSCore::Init($templateData['TEMPLATE_LIBRARY']);
}
else
{
	// Фоллбек: автоподключаемый script.js зависит от BX.PopupWindowButton.
	CJSCore::Init(['popup', 'fx']);
}

$offerId = (int)($GLOBALS['CATALOG_CURRENT_OFFER_ID'] ?? ($arParams['SELECTED_OFFER_ID'] ?? 0));
if ($offerId <= 0) {
	return;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/catalog_list_item_properties.php';

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
	return;
}

$offer = \CIBlockElement::GetList(
	[],
	['ID' => $offerId],
	false,
	['nTopCount' => 1],
	['ID', 'NAME', 'IBLOCK_ID']
)->Fetch();

if (!$offer) {
	return;
}

$offersIblockId = (int)($offer['IBLOCK_ID'] ?? 14);
$pageTitleSource = catalogBuildOfferPageTitleSource($offer, $offerId, $offersIblockId);
$pageTitle = catalogApplyPublicArtikulToTitle($pageTitleSource, $offerId, $offersIblockId);

if ($pageTitle !== '') {
	$GLOBALS['CATALOG_PUBLIC_PAGE_TITLE'] = $pageTitle;
	$APPLICATION->SetTitle($pageTitle);
}
