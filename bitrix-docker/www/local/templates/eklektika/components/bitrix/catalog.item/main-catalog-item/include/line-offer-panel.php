<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * @var array $item
 * @var array $offer
 * @var int $key
 * @var float $firstOfferDiscount
 * @var array $file
 * @var array $offerPriceUi
 * @var int $quantity
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/catalog_offer_url.php';

$offerDetailUrl = (string)($offer['DETAIL_PAGE_URL'] ?? '');
if ($offerDetailUrl === '') {
    $offerDetailUrl = catalogBuildOfferDetailUrl((string)($item['DETAIL_PAGE_URL'] ?? ''), (int)($offer['ID'] ?? 0));
}

$showDiscount = !empty($offerPriceUi['valid']) && !empty($offerPriceUi['showDiscount']);
$dp = (float)($offerPriceUi['discountPercent'] ?? 0);
?>
<div class="info-in-card" data-id="<?= (int)$key; ?>" style="display:<?= ($key === 0) ? 'block' : 'none'; ?>">
    <div class="row align-items-center">
        <div class="col-lg-4">
            <div class="product-item_images">
                <div class="product-item_img cvetov1 ">
                    <a class="cat-tovar-foto" href="<?= htmlspecialchars($offerDetailUrl); ?>"
                       onclick="#">
                        <div class="label label-sale" style="display: <?= ($firstOfferDiscount > 0) ? 'block' : 'none'; ?>;">Скидка</div>
                        <div class="sale-size" style="display: <?= ($firstOfferDiscount > 0) ? 'block' : 'none'; ?>;">-<?= htmlspecialchars((string)$firstOfferDiscount); ?><sub>%</sub></div>
                        <img class="shk-image photo_tovar lazy-loaded" data-src="<?= htmlspecialchars($file['src'] ?? ''); ?>"
                             style="margin-left:5px" width="<?= (int)($file['width'] ?? 0); ?>" height="<?= (int)($file['height'] ?? 0); ?>"
                             src="<?= htmlspecialchars($file['src'] ?? ''); ?>" alt="<?= htmlspecialchars(html_entity_decode((string)($item['NAME'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')); ?>">
                    </a>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <a href="<?= htmlspecialchars($offerDetailUrl); ?>"
               onclick="#" class="product-item_title" style="height: 86px;"><?= htmlspecialchars(html_entity_decode((string)($item['NAME'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')); ?></a>
            <?php
            require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/catalog_list_item_properties.php';
            $sizeArticleRows = catalogListBuildColorSizeArticleRows($item, $offer);
            $isSizedOfferCard = $sizeArticleRows !== [];
            ?>
            <div class="product-item_fields<?= $isSizedOfferCard ? ' has-size-articles' : ''; ?>">
                <table>
                    <tbody>
                    <tr>
                        <td>В наличии:</td>
                        <td><?= (int)$quantity; ?> шт.</td>
                    </tr>
                    <?php
                    $offerDisplayProperties = catalogItemBuildOfferDisplayProperties($item, $offer);

                    foreach ($offerDisplayProperties as $property) {
                        if (!is_array($property)) {
                            continue;
                        }
                        $propertyCode = (string)($property['CODE'] ?? '');
                        if ($propertyCode !== '' && catalogListIsHiddenDisplayPropertyCode($propertyCode)) {
                            continue;
                        }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($property['NAME'] ?? ''); ?>:</td>
                            <td><?php include __DIR__ . '/display-property-value.php'; ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                </table>
                <?php if ($isSizedOfferCard) { ?>
                <div class="product-item_articles-scroll">
                    <table class="product-item_articles">
                        <thead>
                        <tr>
                            <th>Арт.</th>
                            <th>Раз.</th>
                            <th>Остаток</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($sizeArticleRows as $sizeRow) { ?>
                            <tr>
                                <td class="cat-artikle"><?= htmlspecialcharsbx((string)$sizeRow['ARTIKUL']); ?></td>
                                <td><?= htmlspecialcharsbx((string)$sizeRow['SIZE']); ?></td>
                                <td><?= (int)$sizeRow['QUANTITY']; ?> шт.</td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
                <?php } ?>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="product-item_action">
                <div class="product-item_buttons">
                    <div class="button-cart no-wide">
                        <div class="price-outer">
                            <div class="price-block">
                                <span>Цена</span>
                                <?php if ($showDiscount) { ?>
                                    <div class="price-big price-throug"><?= htmlspecialchars($offerPriceUi['oldInt']); ?><sub>,<?= htmlspecialchars($offerPriceUi['oldFrac']); ?> ₽ </sub></div>
                                <?php } else { ?>
                                    <div class="price-big"><?= htmlspecialchars($offerPriceUi['mainInt']); ?><sub>,<?= htmlspecialchars($offerPriceUi['mainFrac']); ?> ₽</sub></div>
                                <?php } ?>
                            </div>
                            <?php if ($showDiscount) { ?>
                                <div class="row">
                                    <div class="col">
                                        <div class="sale-block">
                                            <span class="red">Скидка - <?= htmlspecialchars((string)$dp); ?>%</span>
                                            <div class="price-sale"><?= htmlspecialchars($offerPriceUi['mainInt']); ?><sub>,<?= htmlspecialchars($offerPriceUi['mainFrac']); ?> ₽</sub></div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="gray-block count-block evoShop_shelfItem">
                            <div class="quantity-title"> Укажите тираж </div>
                            <div class="quantity-block">
                                <input type="text" name="count" placeholder="000000" class="item_quantity input-number input-count" required>
                            </div>
                            <button type="submit" class="global-add btn btn-cart btn-gray btn-round" disabled> Отложить </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
