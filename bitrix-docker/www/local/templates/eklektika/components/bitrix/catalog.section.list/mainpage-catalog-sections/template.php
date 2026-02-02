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

<!-- BEGIN catalog cats -->

<div class="catalog-cats">
    <div class="flex-wrap d-flex">
        <?foreach($arResult["SECTIONS"] as $arSection):?>
            <div class="catalog-cat-item-wrap">
                <div class="catalog-cat">
                    <a href="<?=$arSection["SECTION_PAGE_URL"]?>/" class="catalog-cat-icon">
                        <!-- <i class="ic-svg ic-svg-<?=$arSection["CODE"]?>   img-static"></i>
                        <i class="ic-svg ic-svg-<?=$arSection["CODE"]?>-hover img-hover"></i>-->
                    </a>
                    <a href="<?=$arSection["SECTION_PAGE_URL"]?>/" class="catalog-cat-title"><?=$arSection["NAME"]?></a>
                    <?if(!empty($arSection["SECTIONS"])):?>
                        <ul>
                            <?foreach($arSection["SECTIONS"] as $arSubSection):?>
                                <li><a href="<?=$arSubSection["SECTION_PAGE_URL"]?>/"><?=$arSubSection["NAME"]?></a></li>
                            <?endforeach;?>
                        </ul>
                    <?endif;?>
                </div>
            </div>
        <?endforeach;?>
    </div>
</div>
<!-- END catalog cats -->

