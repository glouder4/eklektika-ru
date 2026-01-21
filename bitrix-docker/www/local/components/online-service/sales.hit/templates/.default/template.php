<div class="related-products">
    <div class="middle-content">
        <h3>Хит продаж</h3>
    </div>
    <div class="related-list">
        <div class="swiper-container related-slider">
            <div class="swiper-wrapper">
                <?php
                foreach ($arResult['ITEMS'] as $key => $arItem){
                    $firstOfferDiscount = (float)$arItem['OFFERS'][0]['FINAL_PRICE'][0]['DISCOUNT'];

                    $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
                    $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
                    ?>
                    <div class="swiper-slide">
                        <div class="product-item">
                            <div class="label label-sale" style="display: <?=($firstOfferDiscount > 0) ? 'block' : 'none';?>;">Скидка</div>
                            <div class="sale-size" style="display: <?=($firstOfferDiscount > 0) ? 'block' : 'none';?>;">-<?=$firstOfferDiscount;?><sub>%</sub></div>
                            <div class="product-item_images">
                                <div class="product-item_img">
                                    <a class="changed-url" href="<?=$arItem['DETAIL_PAGE_URL'].$arItem['OFFERS'][0]['ID'].'/';?>">
                                        <img class="swiper-lazy" data-src="<?=$arItem['OFFERS'][0]['PREVIEW_PICTURE_URL'];?>"
                                             alt="<?=$arItem['OFFERS'][0]['NAME']?>"
                                             title="<?=$arItem['OFFERS'][0]['NAME'];?>">
                                    </a>
                                </div>
                                <ul class="product-item_gallery">
                                    <?php
                                        foreach ($arItem['OFFERS'] as $key => $arOffer){?>
                                            <li>
                                                <a class="change-image-url" data-id="<?=$key;?>" data-link="<?=$arItem['DETAIL_PAGE_URL'].$arOffer['ID'].'/';?>" href="<?=$arOffer['PREVIEW_PICTURE_URL'];?>">
                                                    <img data-src="<?=$arOffer['PREVIEW_PICTURE_URL'];?>">
                                                </a>
                                            </li>
                                    <?php }
                                    ?>
                                </ul>
                            </div>
                            <div class="infos" data-cacheid="">
                                <?php
                                foreach ($arItem['OFFERS'] as $key => $arOffer){
                                    $basePrice = (float)$arOffer['BASE_PRICE'];
                                    [$baseIntegerPart, $baseFractionPart] = explode('.', number_format($basePrice, 2, '.', ''));

                                    $price = (float)$arOffer['FINAL_PRICE']['DISCOUNT_PRICE'];
                                    [$integerPart, $fractionPart] = explode('.', number_format($price, 2, '.', ''));

                                    $discount = (float)$arOffer['FINAL_PRICE']['DISCOUNT'];
                                    $discountPercent = (float)$arOffer['FINAL_PRICE']['PERCENT'];

                                    $quantity = (int)$arOffer['REAL_QUANTITY'];

                                    ?>
                                    <div class="info-in-card" data-id="<?=$key;?>" data-discount-percent="<?=$discountPercent;?>" style="display:<?=($key == 0) ? 'block' : 'none';?>">
                                        <a href="<?=$arItem['DETAIL_PAGE_URL'].'/'.$arOffer['ID'].'/';?>" class="product-item_title">
                                            <?=$arOffer['NAME'];?>
                                        </a>

                                        <div itemprop="description" class="product-item_fields">
                                            <table>
                                                <tr class="tr-price">
                                                    <td>Цена</td>
                                                    <td>
                                                        <div class="price-big ">
                                                                <span itemprop="offers" itemscope itemtype="http://schema.org/Offer">
                                                                    <span itemprop="price"> <?=$integerPart;?>.<sub><?=$fractionPart;?></sub></span>
                                                                    <span itemprop="priceCurrency" style="font-size: 14px;" content="RUB">р.</span>
                                                                </span>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php
                                                    foreach ($arOffer['PROPERTIES'] as $key => $arProperty){ ?>
                                                        <tr>
                                                            <td><?=$arProperty['NAME'];?>:</td>
                                                            <td><?=$arProperty['VALUE'];?></td>
                                                        </tr>
                                                    <?php }
                                                ?>
                                            </table>
                                        </div>
                                        <div class="product-item_buttons">
                                            <div class="button-cart">
                                                <button class="ubtn blue-border-ubtn btn-to-cart-small" type="submit">
                                                    Заказать
                                                </button>
                                                <form method="post" class="count-block product-item_tooltip">
                                                    <div class="quantity-title">Укажите необходимый тираж <span>(cвободно на складе)</span></div>
                                                    <div class="pit-fields quantity-block evoShop_shelfItem">
                                                        <div style="display:none;">
                                                            <span class="item_url">/katalog/lineika_s_igroi_pyatnashki_belaya_2390027.php</span>
                                                            <span class="item_image">foto-tovara2/2/3/9/2390027_1.jpg</span>
                                                            <span class="item_name">Линейка с игрой &quot;Пятнашки&quot;, белая</span>
                                                            <span class="item_price">171.25</span>
                                                            <span class="item_pricedefault">171.25</span>
                                                            <span class="item_pricera">159.26</span>
                                                            <span class="item_artikul">2390027</span>
                                                            <span class="item_inventory">6191</span>
                                                            <span class="item_diffprices"></span>
                                                            <span class="item_priceconst">171.25</span>
                                                            <span class="inventory-kolvo">6191</span>
                                                            <span class="item_ves"> 38</span>
                                                            <span class="item_obem"> </span>
                                                            <input style="" type="button"
                                                                   class="item_add item-add-btn"
                                                                   value="Положить в корзину">
                                                        </div>
                                                        <input type="text" name="count" placeholder="6191"
                                                               class="item_quantity input-number input-count"
                                                               required="">
                                                    </div>
                                                    <hr>
                                                    <div class="pit-btn ">
                                                        <button type="submit"
                                                                class="global-add btn btn-cart btn-gray btn-round" itemtype="http://schema.org/BuyAction"
                                                                disabled>
                                                            Отложить
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php }
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
        <?php
            if( count($arResult['ITEMS']) > 3 ):
        ?>
        <div class="swiper-nav cp-nav">
            <div class="cp-button-prev">
                <i class="icon-arrow"></i>
            </div>
            <div class="cp-button-next">
                <i class="icon-arrow"></i>
            </div>
        </div>
        <?php
            endif;
        ?>
    </div>
</div>
