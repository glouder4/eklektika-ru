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
    $code = trim((string)($arItem['CODE'] ?? ''));
    if ($code === '') {
        continue;
    }
    $items[] = $arItem;
}

foreach (array_chunk($items, 2) as $rowItems):
?>
<div class="row no-gutters flex-wrapper main-items-anons">
    <?foreach ($rowItems as $arItem):?>
        <?
        $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
        $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));

        $code = trim((string)($arItem['CODE'] ?? ''));
        $href = '/' . $code . '/';
        $previewSrc = (string)($arItem['PREVIEW_PICTURE']['SRC'] ?? '');
        $name = (string)($arItem['NAME'] ?? '');
        $previewText = trim((string)($arItem['PREVIEW_TEXT'] ?? ''));
        $previewTextType = (string)($arItem['PREVIEW_TEXT_TYPE'] ?? 'text');
        ?>
        <div class="item-anons" id="<?=$this->GetEditAreaId($arItem['ID']);?>">
            <a href="<?=htmlspecialcharsbx($href)?>" class="item" style="background: linear-gradient(99.89deg, #F4F4F4 1.16%, #F6FAFF 101.52%)">
                <?if ($previewSrc !== ''):?>
                    <img data-src="<?=htmlspecialcharsbx($previewSrc)?>" alt="<?=htmlspecialcharsbx($name)?>" src="<?=htmlspecialcharsbx($previewSrc)?>" class="lazy-loaded">
                <?endif;?>
                <span class="item-top">
                    <span class="item-title"><p class="h3 strong"><?=htmlspecialcharsbx($name)?></p></span>
                    <?if ($previewText !== ''):?>
                        <span class="item-description"><?
                            if ($previewTextType === 'html') {
                                echo $previewText;
                            } else {
                                echo '<p>' . htmlspecialcharsbx($previewText) . '</p>';
                            }
                        ?></span>
                    <?endif;?>
                </span>
            </a>
        </div>
    <?endforeach;?>
</div>
<?endforeach;?>
