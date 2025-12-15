<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/**
 * @global CMain $APPLICATION
 */

global $APPLICATION;

//delayed function must return a string
if(empty($arResult))
	return "";

$strReturn = '';

$strReturn .= '<ul class="breadcrumb" itemscope="" itemtype="http://schema.org/BreadcrumbList">';

$itemSize = count($arResult);
for($index = 0; $index < $itemSize; $index++)
{
	$title = htmlspecialcharsex($arResult[$index]["TITLE"]);
	$position = $index + 1;

	if($arResult[$index]["LINK"] <> "" && $index != $itemSize-1)
	{
		// Элемент со ссылкой (не последний)
		$strReturn .= '
			<li itemprop="itemListElement" itemscope="" itemtype="http://schema.org/ListItem">
				<a itemprop="item" href="'.$arResult[$index]["LINK"].'">
					<span itemprop="name">'.$title.'</span>
				</a>
				<meta itemprop="position" content="'.$position.'" />
			</li>';
	}
	else
	{
		// Последний элемент без ссылки
		$strReturn .= '
			<li itemprop="itemListElement" itemscope="" itemtype="http://schema.org/ListItem">
				<span itemprop="name">'.$title.'</span>
				<meta itemprop="position" content="'.$position.'" />
			</li>';
	}
}

$strReturn .= '</ul>';

return $strReturn;