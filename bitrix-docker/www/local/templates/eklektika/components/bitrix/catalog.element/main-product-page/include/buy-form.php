<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
?>
<form action="" id="buy">
    <div class="product-data_price" itemprop="offers" itemscope itemtype="http://schema.org/Offer">
        <meta itemprop="priceCurrency" content="RUB">
        <div class="row">
            <div class="col-6">
                <?php
                $publicArtikul = (string)($currentOffer['DISPLAY_PROPERTIES']['ARTIKUL'] ?? ($currentOffer['PROPERTIES']['ARTIKUL'] ?? ''));
                if ($publicArtikul !== '') { ?>
                    <div class="small-title">Артикул</div>
                    <div class="article"><?= htmlspecialcharsbx($publicArtikul); ?></div>
                <?php }
                ?>
            </div>
            <div class="col-6">
                <link itemprop="url"
                      href="https://eklektika.ru//katalog/shtopor-noj_somele_v_podarochnoi_ypakovke_cvet_serebryanii_5400113.php">
                <link itemprop="availability" href="http://schema.org/InStock">
                <link itemprop="availability" href="http://schema.org/OutOfStock">
                <meta itemprop="availability" content="https://schema.org/PreOrder">

                <?php
                if (isset($currentOffer['PRODUCT_PRICE']) && !empty($currentOffer['PRODUCT_PRICE'])) {
                    $pp = $currentOffer['PRODUCT_PRICE'];
                    $basePrice = (float)$pp['MAIN'];
                    [$baseIntegerPart, $baseFractionPart] = explode('.', number_format($basePrice, 2, '.', ''));
                    $discountPct = isset($pp['DISCOUNT']) ? (float)$pp['DISCOUNT'] : 0.0;
                    $showWholesaleStrike = $discountPct > 0.0001;
                    if ($showWholesaleStrike) {
                        $oldPriceVal = (float)$pp['OLD'];
                        [$oldIntegerPart, $oldFractionPart] = explode('.', number_format($oldPriceVal, 2, '.', ''));
                        ?>
                        <div class="small-title">Цена оптовая:</div>
                        <div class="price-big price-throug"><?= $oldIntegerPart; ?>,<sub><?= $oldFractionPart; ?></sub><span
                                style="font-size:19px">₽</span></div>
                        <br>
                        <br>
                        <div class="small-title red">Скидка -<?= htmlspecialchars((string)$pp['DISCOUNT']); ?>%:</div>
                        <div class="price-sale" itemprop="price"><?= $baseIntegerPart; ?>,<sub><?= $baseFractionPart; ?> ₽</sub></div>
                        <br>
                    <?php } else { ?>
                        <div class="small-title">Цена:</div>
                        <div class="price-big" itemprop="price"><?= $baseIntegerPart; ?>,<sub><?= $baseFractionPart; ?></sub><span
                                style="font-size:19px">₽</span></div>
                        <br>
                    <?php }
                }
                ?>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/color-menu.php'; ?>
    <div class="product-data_info count-block">
        <div class="quantity-outer evoShop_shelfItem">
            <div style="display:none">
                <span class="item_url"><?= $currentOffer['PARENT_PRODUCT']['URL']; ?></span>
                <span class="item_image"><?= $currentOffer['DETAIL_PICTURE']; ?></span>
                <span class="item_name"><?= $currentOffer['NAME']; ?></span>
                <span class="item_price"><?= $currentOffer['PRODUCT_PRICE']['MAIN']; ?></span>
                <span class="item_artikul"><?= $currentOffer['PROPERTIES']['ARTIKUL']; ?></span>
                <span class="item_inventory"><?= $currentOffer['AVAILABLE_QUANTITY']; ?></span>
                <span class="item_pricedefault"><?= $currentOffer['PRODUCT_PRICE']['MAIN']; ?></span>
                <span class="item_priceconst"><?= $currentOffer['PRODUCT_PRICE']['MAIN']; ?></span>
            </div>
            <div class="row justify-content-end">
                <p style="color:red;font-size:12px;padding:0 10px 10px;margin:0">Внимание! Стоимость нанесения рассчитывается менеджером
                    после оформления заказа.</p>
                <?php
                $nanesenieOptions = $currentOffer['NANESENIE_OPTIONS'] ?? \OnlineService\Catalog\NanesenieOptionsResolver::getAllOptions();
                $selectedNanesenieValues = [\OnlineService\Catalog\NanesenieOptionsResolver::DEFAULT_OPTION];
                $nanesenieContainerId = 'exampleFormControlSelect1_' . (int)$currentOffer['ID'];
                $nanesenieOfferId = (int)$currentOffer['ID'];
                $nanesenieContainerClass = 'item_nanesenie';
                ?>
                <div class="form-group col-6" style="margin:-5px 59px 0 0">
                    <label style="font-size:12px;font-weight:300;color:#adb4ba">Метод нанесения</label>
                    <?php include __DIR__ . '/nanesenie-select-options.php'; ?>
                </div>
                <div class="col-4">
                    <div class="small-title">На складе</div>
                    <div class="price-info sklad-count"><?= $currentOffer['AVAILABLE_QUANTITY']; ?></div>
                </div>
            </div>
            <div class="quantity-block d-flex justify-content-between">
                <div class="quantity-title 3">Укажите необходимый тираж</div>
                <input name="count" class="item_quantity input-count input-number" placeholder="">
                <input style="display:none" type="button" class="item_add item-add-btn" value="Положить в корзину">
            </div>
        </div>
        <div class="product-button-cart">
            <div class="product-price-total">
                <div class="d-flex">
                    <span>Итого:</span>
                    <strong id="total-sum-formatted">00 000 000<sub>,00</sub></strong>
                    <strong style="display:none" id="total-sum"></strong>
                </div>
            </div>
            <div class="flex-wrapper">
                <button
                        data-product-id="<?= $currentOffer['PARENT_PRODUCT']['ID']; ?>"
                        data-offer-id="<?= $currentOffer['ID']; ?>"
                        data-url="/local/ajax/add2basket.php"
                        data-product-image="<?= $currentOffer['PREVIEW_PICTURE']; ?>"
                        data-product-name="<?= $currentOffer['NAME']; ?>"
                        class="product__element_template-add-to-basket-btn ubtn btn-cart blue-ubtn"
                        itemtype="http://schema.org/BuyAction"
                        disabled=""
                >Заказать
                </button>
                <button type="submit" class="ubtn blue-border-ubtn fancybox" data-src="#remindtovar">Быстрый заказ</button>
            </div>
        </div>
    </div>
</form>
<script>
    (function () {
        var offerId = "<?= (int)$currentOffer['ID']; ?>";
        var storageKey = "selectedNanesenie_" + offerId;

        function readStoredValues() {
            var raw = localStorage.getItem(storageKey);
            if (!raw) {
                return null;
            }
            try {
                return window.EklektikaNanesenie.normalizeValues(JSON.parse(raw));
            } catch (e) {
                return window.EklektikaNanesenie.normalizeValues([raw]);
            }
        }

        function persistValues(values) {
            localStorage.setItem(storageKey, JSON.stringify(window.EklektikaNanesenie.normalizeValues(values)));
        }

        function onValuesChange(values) {
            persistValues(values);
            window.EklektikaNanesenie.syncContainersByOfferId(offerId, values);
        }

        document.addEventListener("DOMContentLoaded", function () {
            var container = document.getElementById("exampleFormControlSelect1_" + offerId);
            if (container) {
                window.EklektikaNanesenie.bindContainer(container);
            }

            var saved = readStoredValues();
            if (saved) {
                window.EklektikaNanesenie.syncContainersByOfferId(offerId, saved);
            } else if (container) {
                onValuesChange(window.EklektikaNanesenie.getContainerValues(container));
            }

            document.addEventListener("nanesenie:change", function (event) {
                var container = event.target;
                if (!container || container.id !== "exampleFormControlSelect1_" + offerId) {
                    return;
                }
                onValuesChange(event.detail.values || window.EklektikaNanesenie.getContainerValues(container));
            });
        });
    })();
</script>
<a href="#calculate-application" class="ubtn blue-border-ubtn fancybox card-btn">Рассчитать стоимость нанесения</a>
