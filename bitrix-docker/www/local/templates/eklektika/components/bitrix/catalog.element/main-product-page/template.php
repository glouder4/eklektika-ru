<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Catalog\ProductTable;

/**
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 * @var CatalogSectionComponent $component
 * @var CBitrixComponentTemplate $this
 * @var string $templateName
 * @var string $componentPath
 * @var string $templateFolder
 */

$this->setFrameMode(true);
$this->addExternalJs($templateFolder.'/main-product-page.js');

$currentOffer = $arResult['OFFER_DATA'];

?>
    <div class="main-product-page">
        <?php include __DIR__ . '/include/color-menu.php'; ?>
        <div class="product-block">
                <div class="row">
                    <div class="col-md-5 col-xl-6">
                        <?php include __DIR__ . '/include/gallery.php'; ?>
                    </div>
                    <div class="col-md-0 col-lg-1"></div>
                    <div class="col-md-7 col-lg-6 col-xl-5 d-lg-flex justify-content-lg-end">
                        <div class="product-data">
                            <?php //include __DIR__ . '/include/cart-popup.php'; ?>
                            <?php include __DIR__ . '/include/buy-form.php'; ?>
                            <?php include __DIR__ . '/include/product-tabs.php'; ?>
                            <?php include __DIR__ . '/include/product-schema-footer.php'; ?>
                        </div>
                    </div>
                </div>
            </div>
    </div>

