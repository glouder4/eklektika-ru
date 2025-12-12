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

<div class="main-slider-wrap">
    <div class="swiper-container main-slider">
        <div class="swiper-wrapper">
            <?foreach($arResult["ITEMS"] as $arItem):?>
                <?
                    $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
                    $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));

                    $target = $arItem["DISPLAY_PROPERTIES"]['TARGET'];
                    $link = $arItem["DISPLAY_PROPERTIES"]['LINK'];
                ?>
                    <div class="swiper-slide">
                        <a href="<?=$link['VALUE'];?>" target="<?=$target['VALUE_XML_ID'];?>" class="main-slider_img" style="background-image:url('<?=$arItem['DETAIL_PICTURE']['SRC'];?>');">
                            <div class="title-slide"></div> <div class="sub-title-slide"></div>
                        </a>
                    </div>
            <?endforeach;?>
        </div>
    </div>
    <div class="swiper-pagination"></div>
</div>

