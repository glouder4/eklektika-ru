<?php
/** @global \CMain $APPLICATION */

use Bitrix\Main\Security\Sign;

const STOP_STATISTICS = true;
const NOT_CHECK_PERMISSIONS = true;

$siteId = isset($_REQUEST['siteId']) && is_string($_REQUEST['siteId']) ? $_REQUEST['siteId'] : '';
$siteId = mb_substr(preg_replace('/[^a-z0-9_]/i', '', $siteId), 0, 2);
if (!empty($siteId) && is_string($siteId))
{
	define('SITE_ID', $siteId);
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

if (!\Bitrix\Main\Loader::includeModule('iblock'))
	return;

$signer = new Sign\Signer;
try
{
	$template = $signer->unsign($request->get('template') ?: '', 'catalog.section') ?: '.default';
	$paramString = $signer->unsign($request->get('parameters') ?: '', 'catalog.section');
}
catch (Sign\BadSignatureException | \Bitrix\Main\ArgumentTypeException)
{
	die();
}

$parameters = unserialize(base64_decode($paramString), ['allowed_classes' => false]);

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/brand_catalog.php';
$filterName = is_array($parameters) ? (string)($parameters['FILTER_NAME'] ?? 'arrFilter') : 'arrFilter';
$iblockId = is_array($parameters) ? (int)($parameters['IBLOCK_ID'] ?? 0) : 0;
if ($iblockId > 0) {
    brandCatalogFinalizeGlobalFilter($filterName);
    if (brandCatalogResolveActiveSectionFilterId($filterName) > 0) {
        brandCatalogApplySectionSubtreeToGlobalFilter($filterName, $iblockId);
        brandCatalogFinalizeGlobalFilter($filterName);
    }
}

if (isset($parameters['PARENT_NAME']))
{
	$parent = new CBitrixComponent();
	$parent->InitComponent($parameters['PARENT_NAME'], $parameters['PARENT_TEMPLATE_NAME']);
	$parent->InitComponentTemplate($parameters['PARENT_TEMPLATE_PAGE']);
}
else
{
	$parent = false;
}

$APPLICATION->IncludeComponent(
	'bitrix:catalog.section',
	$template,
	$parameters,
	$parent
);
