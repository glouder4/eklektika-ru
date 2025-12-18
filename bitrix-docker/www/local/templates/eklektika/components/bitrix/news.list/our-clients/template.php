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
<div class="swiper-container related-slider-clients"
     style="max-height:180px;margin:0px;padding-top:15px;padding-bottom:15px; border-bottom: 1px dotted #caced3;border-top: 1px dotted #caced3;">
    <div class="swiper-wrapper">
    <?foreach($arResult["ITEMS"] as $arItem):?>
        <?
        $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
        $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
        $NAME         = $arItem['NAME'];
        $PREVIEW_TEXT = $arItem['PREVIEW_TEXT'];
        $DETAIL_PAGE  = $arItem['DETAIL_PAGE_URL'];
        $LINK     = $arItem['PROPERTIES']['LINK']['VALUE'];
        $PREVIEW_PIC  = $arItem['PREVIEW_PICTURE'];
        ?>
        <div class="swiper-slide" id="<?=$this->GetEditAreaId($arItem['ID']);?>">
            <div class="product-item_img" style=" max-height:150px;margin:0px;">
                <a href="<?=$LINK;?>"
                   class="client" target="_blank"><img src="<?=$PREVIEW_PIC['SRC']?>" alt=""></a>
            </div>
        </div>
    <?endforeach;?>
    </div>
</div>

