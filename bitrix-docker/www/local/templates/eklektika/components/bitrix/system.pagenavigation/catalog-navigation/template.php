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

// Сохраняем начальные значения для пагинации (до использования в циклах)
$paginationStartPage = $arResult["nStartPage"];
$paginationEndPage = $arResult["nEndPage"];

// Используем сохраненные начальные значения для пагинации
$nStartPage = $paginationStartPage;
$nEndPage = $paginationEndPage;
?>
<ul class="pagination" id="pagination-block">
    <?php
    // Стрелка "Назад"
    if ($arResult["NavPageNomer"] > 1):?>
        <li class="">
            <?php if($arResult["bSavePage"]):?>
                <a href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=($arResult["NavPageNomer"]-1)?>" class="arrow-prev"></a>
            <?php else:?>
                <?php if ($arResult["NavPageNomer"] > 2):?>
                    <a href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=($arResult["NavPageNomer"]-1)?>" class="arrow-prev"></a>
                <?php else:?>
                    <a href="<?=$arResult["sUrlPath"]?><?=$strNavQueryStringFull?>" class="arrow-prev"></a>
                <?php endif?>
            <?php endif?>
        </li>
    <?php else:?>
        <li class=""><span class="arrow-prev"></span></li>
    <?php endif?>

    <?php
    // Номера страниц
    while($nStartPage <= $nEndPage):?>
        <li class="<?= ($nStartPage == $arResult["NavPageNomer"]) ? 'active' : '' ?>">
            <?php if ($nStartPage == $arResult["NavPageNomer"]):?>
                <a href="javascript:void(0);"><?=$nStartPage?></a>
            <?php elseif($nStartPage == 1 && $arResult["bSavePage"] == false):?>
                <a href="<?=$arResult["sUrlPath"]?><?=$strNavQueryStringFull?>"><?=$nStartPage?></a>
            <?php else:?>
                <a href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=$nStartPage?>"><?=$nStartPage?></a>
            <?php endif?>
        </li>
        <?php $nStartPage++?>
    <?php endwhile?>

    <?php
    // Стрелка "Вперед"
    if($arResult["NavPageNomer"] < $arResult["NavPageCount"]):?>
        <li class="">
            <a href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=($arResult["NavPageNomer"]+1)?>" class="arrow-next"></a>
        </li>
    <?php else:?>
        <li class=""><span class="arrow-next"></span></li>
    <?php endif?>
</ul>
<span class="pagination-text">Страницa <?=$arResult["NavPageNomer"];?> из <?=$arResult["NavPageCount"]?></span>
<hr>