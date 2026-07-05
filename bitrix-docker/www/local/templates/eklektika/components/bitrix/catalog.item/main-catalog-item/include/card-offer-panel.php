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
?>
<div class="info-in-card" data-id="<?= (int)$key; ?>"
     style="display:<?= ($key === 0) ? 'block' : 'none'; ?>"
     data-discount-percent="<?= htmlspecialchars((string)$dp); ?>">
    <a href="<?= htmlspecialchars($buildOfferUrl($item['DETAIL_PAGE_URL'], $offer['ID'] ?? 0)); ?>" class="product-item_title" style="height: 17px;">
        <span itemprop="name"><?= $offer['NAME']; ?></span>
    </a>

    <div itemprop="description" class="product-item_fields" style="height: 150px;">
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
            <tr>
                <td>В наличии:</td>
                <td><?= (int)$quantity; ?> шт.</td>
            </tr>
            <?php
            require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/catalog_list_item_properties.php';
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
    </div>

    <div class="product-item_buttons">
        <div class="button-cart">
            <button class="ubtn blue-border-ubtn btn-to-cart-small" type="submit">
                Заказать
            </button>

            <form method="post" class="count-block product-item_tooltip">
                <div class="quantity-title">
                    Укажите необходимый тираж
                    <span>(cвободно на складе)</span>
                </div>

                <div class="pit-fields quantity-block evoShop_shelfItem">
                    <input type="text" name="count" placeholder="<?= (int)$quantity; ?>"
                           class="item_quantity input-number input-count" required>
                </div>
                <hr>
                <div class="pit-btn ">
                    <button type="submit"
                            data-product-id="<?= (int)$item['ID']; ?>"
                            data-offer-id="<?= (int)$offer['ID']; ?>"
                            data-url="/local/ajax/add2basket.php"
                            data-product-image="<?= htmlspecialchars($previewFile['src'] ?? ''); ?>"
                            data-product-name="<?= htmlspecialchars($offer['NAME'] ?? ''); ?>"
                            class="global-add btn btn-cart btn-gray btn-round"
                            itemtype="http://schema.org/BuyAction"
                            disabled
                    >
                        Отложить
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
