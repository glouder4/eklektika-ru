<?php
    // Подготовка данных
    $detailPageUrl = (string)($arItem['DETAIL_PAGE_URL'] ?? '');
    $offerId = (int)($arItem['PRODUCT_ID'] ?? 0);

    if ($offerId > 0 && !preg_match('#/offer/\d+/?$#', $detailPageUrl)) {
        if (\Bitrix\Main\Loader::includeModule('catalog') && \Bitrix\Main\Loader::includeModule('iblock')) {
            $skuInfo = \CCatalogSku::GetProductInfo($offerId);
            if (is_array($skuInfo) && !empty($skuInfo['ID'])) {
                $parentRes = \CIBlockElement::GetList(
                    [],
                    ['ID' => (int)$skuInfo['ID'], 'ACTIVE' => 'Y'],
                    false,
                    false,
                    ['DETAIL_PAGE_URL']
                );
                if ($parent = $parentRes->GetNext()) {
                    $parentUrl = (string)($parent['DETAIL_PAGE_URL'] ?? '');
                    if ($parentUrl !== '') {
                        $detailPageUrl = rtrim($parentUrl, '/') . '/offer/' . $offerId . '/';
                    }
                }
            }
        }
    }   

    $price = (float)$arItem['PRICE'];
    [$integerPart, $fractionPart] = explode('.', number_format($price, 2, '.', ''));

    $sumPrice = (float)$arItem['SUM_VALUE'];
    [$sumIntegerPart, $sumFractionPart] = explode('.', number_format($sumPrice, 2, '.', ''));

    $previewPicture = $arItem['PREVIEW_PICTURE_SRC'] ?: '/local/templates/eklektika/components/bitrix/catalog.section/main-catalog-section/images/no_photo.png';

$offerId = (int)($arItem['PRODUCT_ID'] ?? 0);
if (isset($arResult['NANESENIE_BY_OFFER'][$offerId])) {
    $selectedNanesenieValues = $arResult['NANESENIE_BY_OFFER'][$offerId];
} else {
    $propsSource = [];
    if (!empty($arItem['PROPS_ALL']) && is_array($arItem['PROPS_ALL'])) {
        $propsSource = $arItem['PROPS_ALL'];
    } elseif (!empty($arItem['PROPS']) && is_array($arItem['PROPS'])) {
        $propsSource = $arItem['PROPS'];
    }
    $selectedNanesenieValues = $propsSource !== []
        ? \OnlineService\Catalog\NanesenieOptionsResolver::extractSelectedFromItemProps($propsSource)
        : [\OnlineService\Catalog\NanesenieOptionsResolver::DEFAULT_OPTION];
}

$nanesenieOptions = $arResult['NANESENIE_OPTIONS'] ?? \OnlineService\Catalog\NanesenieOptionsResolver::getAllOptions();
foreach ($selectedNanesenieValues as $selectedValue) {
    if ($selectedValue !== '' && !in_array($selectedValue, $nanesenieOptions, true)) {
        $nanesenieOptions[] = $selectedValue;
    }
}
$nanesenieOfferId = (int)($arItem['PRODUCT_ID'] ?? 0);
$nanesenieContainerClass = 'item_nanesenie_chek';

?>
    <div class="cart-product-row">
            <div class="row">
                <div class="cart-col cart-col1">
                    <a href="<?= htmlspecialchars($detailPageUrl) ?>">
                        <img src="<?= $previewPicture ?>" alt="<?= htmlspecialchars_decode($arItem['NAME'], ENT_QUOTES) ?>">
                    </a>
                </div>
                <div class="cart-col cart-col2">
                    <div class="cart-product-article"><?= htmlspecialchars($arItem['PROPERTY_ARTICLE_VALUE']) ?></div>
                    <a href="<?= htmlspecialchars($detailPageUrl) ?>" class="cart-product-title"><?= htmlspecialchars_decode($arItem['NAME'], ENT_QUOTES) ?></a>
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
                    <?php include $_SERVER['DOCUMENT_ROOT'] . '/local/templates/eklektika/components/bitrix/catalog.element/main-product-page/include/nanesenie-select-options.php'; ?>
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