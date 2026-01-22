<?php
    // Подготовка данных
    $price = (float)$arItem['PRICE'];
    [$integerPart, $fractionPart] = explode('.', number_format($price, 2, '.', ''));

    $sumPrice = (float)$arItem['SUM_VALUE'];
    [$sumIntegerPart, $sumFractionPart] = explode('.', number_format($sumPrice, 2, '.', ''));

    $previewPicture = $arItem['PREVIEW_PICTURE_SRC'] ?: '/local/templates/eklektika/components/bitrix/catalog.section/main-catalog-section/images/no_photo.png';

?>
    <div class="cart-product-row">
            <div class="row">
                <div class="cart-col cart-col1">
                    <a href="<?= htmlspecialchars($arItem['DETAIL_PAGE_URL']) ?>">
                        <img src="<?= $previewPicture ?>" alt="<?= htmlspecialchars_decode($arItem['NAME'], ENT_QUOTES) ?>">
                    </a>
                </div>
                <div class="cart-col cart-col2">
                    <div class="cart-product-article"><?= htmlspecialchars($arItem['PROPERTY_ARTICLE_VALUE']) ?></div>
                    <a href="<?= htmlspecialchars($arItem['DETAIL_PAGE_URL']) ?>" class="cart-product-title"><?= htmlspecialchars_decode($arItem['NAME'], ENT_QUOTES) ?></a>
                </div>
                <div class="cart-col cart-col3">
                    <div class="row-label">Цена за шт.</div>
                    <div class="cart-product-price">
                        <?= $integerPart ?><sub>,<?= $fractionPart ?></sub>
                        <span style="font-size: 17px;">₽</span>
                    </div>
                </div>
                <div class="cart-col cart-col4">
                    <div class="row-label">Тираж</div>
                    <div class="cart-product-quantity">
                        <input
                            type="text"
                            class="input-number item_quantity1 item-quantity"
                            data-value="<?= (int)$arItem['QUANTITY'] ?>"
                            value="<?= (int)$arItem['QUANTITY'] ?>"
                            data-offer-id="<?= (int)$arItem['PRODUCT_ID'] ?>"
                        >
                    </div>
                </div>
                <div class="cart-col cart-col5" style="margin: -7px 35px 0 -35px;">
                    <select name="spaceSelect" class="form-control item_nanesenie_chek">
                        <option value="Без нанесения">Без нанесения</option>
                        <option value="Тампопечать">Тампопечать</option>
                        <option value="Лазерная гравировка">Лазерная гравировка</option>
                    </select>
                </div>
                <div class="cart-col cart-col5">
                    <div class="row-label">Сумма</div>
                    <div class="cart-product-summ">
                        <?= $sumIntegerPart ?><sub>,<?= $sumFractionPart ?></sub>
                        <span style="font-size: 17px;">₽</span>
                    </div>
                </div>
                <div class="cart-col cart-col7">
                    <div class="cart-product-actions">
                        <button type="button" class="cart-product-remove red" data-product-id="<?=$arItem['PRODUCT_ID'];?>">
                            <i class="icon-close"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>