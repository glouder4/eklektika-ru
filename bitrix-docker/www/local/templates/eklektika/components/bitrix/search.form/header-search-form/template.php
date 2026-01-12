<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
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
$searchValue = isset($arResult['REQUEST']['q']) ? htmlspecialcharsbx($arResult['REQUEST']['q']) : '';
?>
<div class="search-head-wrap" itemscope itemtype="https://schema.org/WebSite">
    <meta itemprop="url" content="<?=SITE_URL?>/" />
    <form action="<?=$arResult['FORM_ACTION']?>" class="search search" id="main-search-form" itemprop="potentialAction" itemscope itemtype="https://schema.org/SearchAction">
        <meta itemprop="target" content="<?=SITE_URL?><?=$arResult['FORM_ACTION']?>?q={search}" />
        <fieldset>
            <input itemprop="query-input" type="text" name="q" autocomplete="off" class="simple-poisk" placeholder="Поиск" required value="<?=$searchValue?>">
            <button type="submit" aria-label="искать" class="search-btn">

            </button>
        </fieldset>


        <div class="search-sub">
            <div class="search-sub-inner">
                <input type="text" name="s_price_from" id="cenaot" placeholder="цена от">
                <input type="text" name="s_price_to" id="cenado" placeholder="цена до">
                <input type="text" name="kolvo" id="tiraj" placeholder="остаток">
            </div>
        </div>
        <div class="search-sub-results">
            <div class="row" id="kategort">
            </div>
            <div class="row" id="tovart">
            </div>
        </div>
    </form>
</div>