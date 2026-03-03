<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

global $APPLICATION;

if(empty($arResult))
	return "";

// Переопределение ссылки из arDirProperties["CHAIN_LINK"] в .section.php
$chainLink = trim($APPLICATION->GetDirProperty("CHAIN_LINK"));
$chainTitle = trim($APPLICATION->GetDirProperty("CHAIN_TITLE"));

if ($chainLink !== "" && $chainTitle !== "") {
	foreach ($arResult as $i => $item) {
		if (trim($item["TITLE"]) === $chainTitle) {
			$arResult[$i]["LINK"] = $chainLink;
			break;
		}
	}
}

$strReturn = '';
$strReturn .= '<ul class="breadcrumb" itemscope="" itemtype="http://schema.org/BreadcrumbList">';

$itemSize = count($arResult);
for($index = 0; $index < $itemSize; $index++)
{
	$title = htmlspecialcharsex($arResult[$index]["TITLE"]);
	$position = $index + 1;
	$link = $arResult[$index]["LINK"];

	if($link <> "" && $index != $itemSize-1)
	{
		$strReturn .= '
			<li itemprop="itemListElement" itemscope="" itemtype="http://schema.org/ListItem">
				<a itemprop="item" href="'.$link.'">
					<span itemprop="name">'.$title.'</span>
				</a>
				<meta itemprop="position" content="'.$position.'" />
			</li>';
	}
	else
	{
		$strReturn .= '
			<li itemprop="itemListElement" itemscope="" itemtype="http://schema.org/ListItem">
				<span itemprop="name">'.$title.'</span>
				<meta itemprop="position" content="'.$position.'" />
			</li>';
	}
}

$strReturn .= '</ul>';
return $strReturn;