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
// Отключаем кеш результата, чтобы не показывать "рекламную" пару неавторизованным.
if (isset($this) && is_object($this) && method_exists($this, 'AbortResultCache')) {
    $this->AbortResultCache();
}

$mainPriceTypeId = $canShowAdvertisingPrice ? 3 : 2; // 2) оптовая
$oldPriceTypeId = $canShowAdvertisingPrice ? 2 : 2;

$firstPriceRow = $firstOfferId > 0 ? getCatalogPriceDiscount($firstOfferId, $mainPriceTypeId, $oldPriceTypeId) : null;
$firstOfferDiscount = is_array($firstPriceRow) ? (float)($firstPriceRow['DISCOUNT'] ?? 0) : 0.0;

$includeBase = __DIR__ . '/../include';

?>

<div class="col-12 product-item-wrapper line" style="min-height: 852px;" data-entity='items-row'>
    <div class="product-item full" style="min-height: 852px;">
        <?php include $includeBase . '/line-color-swatches.php'; ?>
        <div class="infos">
            <?php
            foreach ($item['OFFERS'] as $key => $offer) {
                if (!empty($offer['PREVIEW_PICTURE']['ID']) && (int)$offer['PREVIEW_PICTURE']['ID'] > 0) {
                    $file = CFile::ResizeImageGet(
                        (int)$offer['PREVIEW_PICTURE']['ID'],
                        ['width' => 270, 'height' => 270],
                        BX_RESIZE_IMAGE_PROPORTIONAL,
                        true
                    );
                } else {
                    $file['src'] = $offer['PREVIEW_PICTURE']['SRC'] ?? '';
                    $file['width'] = 270;
                    $file['height'] = 270;
                }

                include $includeBase . '/offer-price-compute.php';

                $quantity = (int)($offer['CATALOG_QUANTITY'] ?? 0);

                include $includeBase . '/line-offer-panel.php';
                unset($file);
            }
            ?>
        </div>
    </div>
</div>
