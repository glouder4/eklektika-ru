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

<?foreach($arResult["ITEMS"] as $arItem):?>
    <?
    $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
    $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
    $NAME         = $arItem['NAME'];
    $PREVIEW_TEXT = $arItem['PREVIEW_TEXT'];
    $DETAIL_PAGE  = $arItem['DETAIL_PAGE_URL'];
    $TEXT_BTN     = $arItem['PROPERTIES']['TEXT_BTN']['VALUE'];
    $PREVIEW_PIC  = $arItem['PREVIEW_PICTURE']
    ?>
    <?/*
    <pre>
        <? print_r($arItem); ?>
    </pre>
    */?>
    <div class="middle-content content" style="padding-bottom: 0px;" id="<?=$this->GetEditAreaId($arItem['ID']);?>">
        <!-- BEGIN block -->
        <div class="block">
            <div class="row">
                <div class="col-md-6 ">
                    <h3><?=$NAME?></h3>
                    <div class="text">
                        <?=$PREVIEW_TEXT?>
                        <p class="akcii-slogan">
                            <a href="<?=$DETAIL_PAGE?>"><?=$TEXT_BTN?></a>
                        </p>
                    </div>
                </div>
                <div class="col-md-6">
                    <img data-src="<?=$PREVIEW_PIC?>" alt="" src="<?=$PREVIEW_PIC?>" class="lazy-loaded">
                </div>
            </div>
        </div>
        <!-- END block -->
        <hr>
    </div>  
<?endforeach;?>

