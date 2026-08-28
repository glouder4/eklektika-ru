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
    $previewSrc = (string)($arItem['PREVIEW_PICTURE']['SRC'] ?? '');
    if ($previewSrc === '') {
        continue;
    }
    $items[] = $arItem;
}

if ($items === []) {
    return;
}
?>
<div class="clients">
    <div class="row">
        <?foreach ($items as $arItem):?>
            <?
            $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
            $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));

            $code = trim((string)($arItem['CODE'] ?? ''));
            $href = '/' . $code . '/';
            $previewSrc = (string)($arItem['PREVIEW_PICTURE']['SRC'] ?? '');
            $name = (string)($arItem['NAME'] ?? '');
            ?>
            <div class="col-6 col-md-4 col-xl1-3" id="<?=$this->GetEditAreaId($arItem['ID']);?>">
                <a href="<?=htmlspecialcharsbx($href)?>" class="client" target="_blank"><img src="<?=htmlspecialcharsbx($previewSrc)?>" alt="<?=htmlspecialcharsbx($name)?>"></a>
            </div>
        <?endforeach;?>
    </div>
</div>
