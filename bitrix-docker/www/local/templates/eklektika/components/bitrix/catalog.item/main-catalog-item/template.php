<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;

/**
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 * @var CatalogProductsViewedComponent $component
 * @var CBitrixComponentTemplate $this
 * @var string $templateName
 * @var string $componentPath
 * @var string $templateFolder
 */

$this->setFrameMode(true);

if (isset($arResult['ITEM']))
{
    $item = $arResult['ITEM'];
    if( count($item['OFFERS']) == 0 )
        $item['OFFERS'][0] = $item;

    $haveOffers = !empty($item['OFFERS']);
    if ($haveOffers)
    {
        $actualItem = isset($item['OFFERS'][$item['OFFERS_SELECTED']])
            ? $item['OFFERS'][$item['OFFERS_SELECTED']]
            : reset($item['OFFERS']);
    }
    else
    {
        $actualItem = $item;
    }

    $documentRoot = Main\Application::getDocumentRoot();
    $templatePath = mb_strtolower($arResult['TYPE']).'/template.php';

    $_templatefile = new Main\IO\File($documentRoot.$templateFolder.'/'.$templatePath);
    if ($_templatefile->isExists())
    {
        include($_templatefile->getPath());
    }
	?>

	<?php
}
