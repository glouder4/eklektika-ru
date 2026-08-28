<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$buildOfferUrl = static function ($detailPageUrl, $offerId) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/catalog_offer_url.php';
    return catalogBuildOfferDetailUrl((string)$detailPageUrl, (int)$offerId);
};

$firstOfferId = (int)($item['OFFERS'][0]['ID'] ?? 0);

$canShowAdvertisingPrice = catalogCanShowAdvertisingPrice();

// В этой карточке цены/скидки зависят от UF_ADVERSTERING_AGENT.
// Чтобы не отдавать HTML из кеша "для другого пользователя", отключаем кеширование результата для данного шаблона.
if (isset($this) && is_object($this) && method_exists($this, 'AbortResultCache')) {
    $this->AbortResultCache();
}

$mainPriceTypeId = $canShowAdvertisingPrice ? 3 : 2; // 2) оптовая
$oldPriceTypeId = $canShowAdvertisingPrice ? 2 : 2;

$firstPriceRow = $firstOfferId > 0 ? getCatalogPriceDiscount($firstOfferId, $mainPriceTypeId, $oldPriceTypeId) : null;
$firstOfferDiscount = is_array($firstPriceRow) ? (float)($firstPriceRow['DISCOUNT'] ?? 0) : 0.0;

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/catalog_list_item_properties.php';
$hasSizeMatrix = !empty($item['OFFERS'][0])
    && catalogListBuildColorSizeArticleRows($item, $item['OFFERS'][0]) !== [];

$includeBase = __DIR__ . '/../include';

?>

<div class="col-sm-6 col-lg-4 col-xl1-3 product-item-wrapper card<?= $hasSizeMatrix ? ' has-size-matrix' : ''; ?>" data-entity='items-row'>
    <div itemscope itemtype="http://schema.org/Product" class="product-item is-sale<?= $hasSizeMatrix ? ' has-size-matrix' : ''; ?>">
        <?php include $includeBase . '/card-product-media.php'; ?>
        <div class="infos" data-cacheid="analogsf5737c72-ff18-4b08-9ea7-37217b8fd015">
            <?php
            foreach ($item['OFFERS'] as $key => $offer) {
                include $includeBase . '/offer-price-compute.php';

                $quantity = (int)($offer['CATALOG_QUANTITY'] ?? 0);

                if (!empty($offer['PREVIEW_PICTURE']['ID']) && (int)$offer['PREVIEW_PICTURE']['ID'] > 0) {
                    $previewFile = CFile::ResizeImageGet(
                        (int)$offer['PREVIEW_PICTURE']['ID'],
                        ['width' => 50, 'height' => 50],
                        BX_RESIZE_IMAGE_PROPORTIONAL,
                        true
                    );
                } elseif (!empty($offer['PREVIEW_PICTURE']['SRC'])) {
                    $previewFile = ['src' => $offer['PREVIEW_PICTURE']['SRC']];
                } else {
                    $previewFile = [
                        'src' => '/local/templates/eklektika/components/bitrix/catalog.section/main-catalog-section/images/no_photo.png',
                    ];
                }

                include $includeBase . '/card-offer-panel.php';
                unset($previewFile);
            }
            ?>
        </div>
    </div>
</div>
