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
?>

<div class="marquee">
    <span>
        <?foreach($arResult["ITEMS"] as $key => $arItem):?>
            <?
            $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
            $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));

            $target = $arItem["DISPLAY_PROPERTIES"]['TARGET'];
            $link = $arItem["DISPLAY_PROPERTIES"]['LINK'];
            ?>
            <a href="<?=$link['VALUE'];?>" target="<?=$target['VALUE_XML_ID'];?>"><?echo $arItem["NAME"]?></a>

            <?php
                if( $key + 1 < count($arResult["ITEMS"]) ):
            ?>
            <a href="#">/</a>
            <?php
                endif;
            ?>
        <?endforeach;?>
    </span>
</div>

