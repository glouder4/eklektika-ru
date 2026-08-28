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

<div class="main-popular-block flex-wrapper">
    <?foreach($arResult["ITEMS"] as $arItem):?>
        <?
        $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
        $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));

        $code = trim((string)($arItem['CODE'] ?? ''));
        if ($code === '') {
            continue;
        }

        $href = '/' . $code . '/';
        $previewSrc = (string)($arItem['PREVIEW_PICTURE']['SRC'] ?? '');
        $styleAttr = $previewSrc !== ''
            ? ' style="background-image: url(' . htmlspecialcharsbx($previewSrc) . ');"'
            : '';
        ?>
        <a href="<?=htmlspecialcharsbx($href)?>"
           class="popular-cat-item p-cat-item-4"<?=$styleAttr?>
           id="<?=$this->GetEditAreaId($arItem['ID']);?>"></a>
    <?endforeach;?>
</div>
