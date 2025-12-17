<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);

if(!$arResult["NavShowAlways"])
{
	if ($arResult["NavRecordCount"] == 0 || ($arResult["NavPageCount"] == 1 && $arResult["NavShowAll"] == false))
		return;
}

$strNavQueryString = ($arResult["NavQueryString"] != "" ? $arResult["NavQueryString"]."&amp;" : "");
$strNavQueryStringFull = ($arResult["NavQueryString"] != "" ? "?".$arResult["NavQueryString"] : "");
?>

<div class="pagination-links">
    <?if($arResult["bDescPageNumbering"] === true):?>
        <?// Обратная нумерация страниц?>
        <?if ($arResult["NavPageNomer"] < $arResult["NavPageCount"]):?>
            <?if($arResult["bSavePage"]):?>
                <a href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=($arResult["NavPageNomer"]+1)?>">Предыдущая</a>
            <?else:?>
                <?if ($arResult["NavPageCount"] == ($arResult["NavPageNomer"]+1) ):?>
                    <a href="<?=$arResult["sUrlPath"]?><?=$strNavQueryStringFull?>">Предыдущая</a>
                <?else:?>
                    <a href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=($arResult["NavPageNomer"]+1)?>">Предыдущая</a>
                <?endif?>
            <?endif?>
        <?else:?>
        <?endif?>
        
        <?if ($arResult["NavPageNomer"] > 1):?>
            <a href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=($arResult["NavPageNomer"]-1)?>">Следующая</a>
        <?else:?>
        <?endif?>
    <?else:?>
        <?// Обычная нумерация страниц?>
        <?if ($arResult["NavPageNomer"] > 1):?>
            <?if($arResult["bSavePage"]):?>
                <a href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=($arResult["NavPageNomer"]-1)?>">Предыдущая</a>
            <?else:?>
                <?if ($arResult["NavPageNomer"] > 2):?>
                    <a href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=($arResult["NavPageNomer"]-1)?>">Предыдущая</a>
                <?else:?>
                    <a href="<?=$arResult["sUrlPath"]?><?=$strNavQueryStringFull?>">Предыдущая</a>
                <?endif?>
            <?endif?>
        <?else:?>
        <?endif?>
        
        <?if($arResult["NavPageNomer"] < $arResult["NavPageCount"]):?>
            <a href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=($arResult["NavPageNomer"]+1)?>">Следующая</a>
        <?else:?>
        <?endif?>
    <?endif?>
</div>