<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * @var array $item
 * @var array $offer
 * @var int $key
 * @var callable $buildOfferUrl
 * @var array $offerPriceUi
 * @var array $previewFile 
 */
$showDiscount = !empty($offerPriceUi['valid']) && !empty($offerPriceUi['showDiscount']);
$dp = (float)($offerPriceUi['discountPercent'] ?? 0);

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/catalog_list_item_properties.php';
$sizeArticleRows = catalogListBuildColorSizeArticleRows($item, $offer);
$isSizedOfferCard = $sizeArticleRows !== [];
$hasAvailableSizedOffer = false;
foreach ($sizeArticleRows as $sizeRow) {
    if ((int)($sizeRow['QUANTITY'] ?? 0) > 0) {
        $hasAvailableSizedOffer = true;
        break;
    }
}
$quantity = (int)($quantity ?? ($offer['CATALOG_QUANTITY'] ?? 0));
$isOutOfStock = $isSizedOfferCard ? !$hasAvailableSizedOffer : $quantity <= 0;
$publicArtikul = catalogResolvePublicArtikulValue(
    (int)($offer['ID'] ?? 0),
    (int)($offer['IBLOCK_ID'] ?? 14)
);
$productName = html_entity_decode((string)($item['NAME'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
$productImage = (string)($previewFile['src'] ?? '');
$detailUrl = $buildOfferUrl($item['DETAIL_PAGE_URL'], $offer['ID'] ?? 0);
?>
<div class="info-in-card" data-id="<?= (int)$key; ?>"
     style="display:<?= ($key === 0) ? 'block' : 'none'; ?>"
     data-discount-percent="<?= htmlspecialchars((string)$dp); ?>">
    <a href="<?= htmlspecialchars($detailUrl); ?>" class="product-item_title" style="height: 17px;">
        <span itemprop="name"><?= htmlspecialcharsbx($productName); ?></span>
    </a>

    <div itemprop="description" class="product-item_fields<?= $isSizedOfferCard ? ' has-size-articles' : ''; ?>">
        <table>
            <tbody>
            <?php if ($showDiscount) { ?>
                <tr class="tr-price">
                    <td>Цена</td>
                    <td>
                        <div class="price-big price-throug">
                            <?= htmlspecialchars($offerPriceUi['oldInt']); ?>.<sub><?= htmlspecialchars($offerPriceUi['oldFrac']); ?></sub>
                            <span style="font-size: 14px;">р.</span>
                        </div>
                    </td>
                </tr>
                <tr class="tr-price-sale">
                    <td>
                        <div class="red">- <?= htmlspecialchars((string)$dp); ?>%</div>
                    </td>
                    <td>
                        <div class="price-sale">
                            <span itemprop="offers" itemscope itemtype="http://schema.org/Offer">
                                <span itemprop="price"><?= htmlspecialchars($offerPriceUi['mainInt']); ?>.<sub><?= htmlspecialchars($offerPriceUi['mainFrac']); ?></sub></span>
                                <span itemprop="priceCurrency" style="font-size: 12px;" content="RUB">р.</span>
                            </span>
                        </div>
                    </td>
                </tr>
            <?php } else { ?>
                <tr class="tr-price">
                    <td>Цена</td>
                    <td>
                        <div class="price-big">
                            <span itemprop="offers" itemscope itemtype="http://schema.org/Offer">
                                <span itemprop="price"><?= htmlspecialchars($offerPriceUi['mainInt']); ?>.<sub><?= htmlspecialchars($offerPriceUi['mainFrac']); ?></sub>
                                    <span itemprop="priceCurrency" style="font-size: 14px;" content="RUB">р.</span>
                                </span>
                            </span>
                        </div>
                    </td>
                </tr>
            <?php } ?>
            <tr<?= $isOutOfStock ? ' class="red"' : ''; ?>>
                <td<?= $isOutOfStock ? ' class="red"' : ''; ?>>В наличии:</td>
                <td<?= $isOutOfStock ? ' class="red"' : ''; ?>><?= $quantity; ?> шт.</td>
            </tr>
            <?php if ($publicArtikul !== '') { ?>
                <tr class="tr-artikul">
                    <td>Артикул:</td>
                    <td><?= htmlspecialcharsbx((string)$publicArtikul); ?></td>
                </tr>
            <?php } ?>
            <?php
            $offerDisplayProperties = catalogItemBuildOfferDisplayProperties($item, $offer, null, $isSizedOfferCard);

            foreach ($offerDisplayProperties as $property) {
                if (!is_array($property)) {
                    continue;
                }
                $propertyCode = (string)($property['CODE'] ?? '');
                $propertyName = (string)($property['NAME'] ?? '');
                if ($propertyCode !== '' && catalogListIsHiddenDisplayPropertyCode($propertyCode)) {
                    continue;
                }
                if ($isSizedOfferCard && catalogListIsSizeLikeDisplayPropertyCode($propertyCode, $propertyName)) {
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

    <div class="product-item_buttons">
        <div class="button-cart">
            <?php if ($isOutOfStock) { ?>
            <button type="button"
                    class="ubtn blue-border-ubtn btn-to-cart-small js-open-preorder-modal"
                    data-src="#preordertovar"
                    data-product-id="<?= (int)$item['ID']; ?>"
                    data-offer-id="<?= (int)$offer['ID']; ?>"
                    data-product-image="<?= htmlspecialchars($productImage); ?>"
                    data-product-name="<?= htmlspecialchars($productName); ?>"
                    data-product-link="<?= htmlspecialchars($detailUrl); ?>"
                    data-product-artikul="<?= htmlspecialchars((string)$publicArtikul); ?>"
            >Предзаказ</button>
            <?php } else { ?>
            <button class="ubtn blue-border-ubtn btn-to-cart-small" type="button">
                Заказать
            </button>

            <form method="post" class="count-block product-item_tooltip<?= $isSizedOfferCard ? ' product-item_tooltip--sizes' : ''; ?>">
                <div class="quantity-title">
                    Укажите необходимый тираж
                    <span>(cвободно на складе)</span>
                </div>

                <?php if ($isSizedOfferCard) { ?>
                    <div class="pit-fields quantity-block">
                        <table>
                            <tbody>
                            <?php foreach ($sizeArticleRows as $sizeRow) {
                                $sizeOfferId = (int)($sizeRow['ID'] ?? 0);
                                $sizeLabel = (string)($sizeRow['SIZE'] ?? '');
                                $sizeQty = (int)($sizeRow['QUANTITY'] ?? 0);
                                if ($sizeOfferId <= 0 || $sizeLabel === '') {
                                    continue;
                                }
                                ?>
                                <tr>
                                    <td><?= htmlspecialcharsbx($sizeLabel); ?></td>
                                    <td class="evoShop_shelfItem">
                                        <input
                                            type="text"
                                            name="count-m"
                                            placeholder="<?= $sizeQty > 0 ? $sizeQty : ''; ?>"
                                            class="item_quantity input-number input-count"
                                            data-offer-id="<?= $sizeOfferId; ?>"
                                            data-size="<?= htmlspecialcharsbx($sizeLabel); ?>"
                                            autocomplete="off"
                                        >
                                    </td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                <?php } else { ?>
                    <div class="pit-fields quantity-block evoShop_shelfItem">
                        <input type="text" name="count" placeholder="<?= $quantity > 0 ? $quantity : ''; ?>"
                               class="item_quantity input-number input-count" required>
                    </div>
                <?php } ?>
                <hr>
                <div class="pit-btn ">
                    <button type="submit"
                            data-product-id="<?= (int)$item['ID']; ?>"
                            data-offer-id="<?= (int)$offer['ID']; ?>"
                            data-url="/local/ajax/add2basket.php"
                            data-product-image="<?= htmlspecialchars($productImage); ?>"
                            data-product-name="<?= htmlspecialchars($productName); ?>"
                            class="<?= $isSizedOfferCard ? 'global-add-multi' : 'global-add'; ?> btn btn-cart btn-gray btn-round"
                            itemtype="http://schema.org/BuyAction"
                            disabled
                    >
                        Отложить
                    </button>
                </div>
            </form>
            <?php } ?>
        </div>
    </div>
</div>
