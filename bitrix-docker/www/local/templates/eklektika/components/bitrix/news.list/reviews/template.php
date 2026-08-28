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

$items = [];
foreach ($arResult["ITEMS"] as $arItem) {
    $previewText = trim((string)($arItem['PREVIEW_TEXT'] ?? ''));
    if ($previewText === '') {
        continue;
    }
    $items[] = $arItem;
}

if ($items === []) {
    return;
}
?>
<div class="testimonials">
    <?foreach ($items as $arItem):?>
        <?
        $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
        $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));

        $name = (string)($arItem['NAME'] ?? '');
        $previewText = (string)($arItem['PREVIEW_TEXT'] ?? '');
        $metaName = htmlspecialcharsbx(htmlspecialcharsback(strip_tags($previewText)));
        ?>
        <div class="testimonial-item" itemprop="review" itemscope="" itemtype="http://schema.org/Review" id="<?=$this->GetEditAreaId($arItem['ID']);?>">
            <div class="row">
                <div class="col-sm-3">
                </div>
                <div class="col-sm-9">
                    <div itemprop="author" class="testimonial-title"><?=htmlspecialcharsbx($name)?></div>
                    <div class="testimonial-company"></div>
                    <div itemprop="reviewBody" class="testimonial-txt"><?=$previewText?></div>
                    <meta itemprop="name" content="<?=$metaName?>">
                </div>
            </div>
        </div>
    <?endforeach;?>
</div>
