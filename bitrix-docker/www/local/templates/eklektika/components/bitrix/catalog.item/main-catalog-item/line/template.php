<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

?>

<div class="col-12 product-item-wrapper line" style="min-height: 852px;" data-entity='items-row'>
    <?php
    $firstOfferDiscount = (float)$item['OFFERS'][0]['ITEM_PRICES'][0]['DISCOUNT'];
    ?>
    <div class="product-item full" style="min-height: 852px;">
        <ul class="color-menu">
            <div class="swiper-container gallery-thumbs">
                <div class="swiper-wrapper">
                    <?php
                    foreach ($item['OFFERS'] as $key => $offer):
                        if ($offer['PREVIEW_PICTURE']['ID'] > 0)
                            $file = CFile::ResizeImageGet( $offer['PREVIEW_PICTURE']['ID'], array('width' => 50, 'height' => 50), BX_RESIZE_IMAGE_PROPORTIONAL, true);
                        else{
                            $file['src'] = $offer['PREVIEW_PICTURE']['SRC'];
                        }
                        ?>

                        <div class="swiper-slide" id="big-card-switcher">
                            <a data-id="<?=$key;?>" data-tovar="<?=$offer['ID'];?>" data-tid="<?=$offer['ID'];?>" href="<?=$offer['PREVIEW_PICTURE']['SRC'];?>"> <img src="<?=$offer['PREVIEW_PICTURE']['SRC'];?>"> </a>

                        </div>
                        <?php
                        unset($file);
                    endforeach;
                    ?>

                </div>
            </div>
        </ul>
        <div class="infos">

            <?php
            foreach ($item['OFFERS'] as $key => $offer):
                if ($offer['PREVIEW_PICTURE']['ID'] > 0)
                    $file = CFile::ResizeImageGet( $offer['PREVIEW_PICTURE']['ID'], array('width' => 270, 'height' => 270), BX_RESIZE_IMAGE_PROPORTIONAL, true);
                else
                    $file['src'] = $offer['PREVIEW_PICTURE']['SRC'];

                $basePrice = (float)$offer['ITEM_PRICES'][0]['BASE_PRICE'];
                [$baseIntegerPart, $baseFractionPart] = explode('.', number_format($basePrice, 2, '.', ''));
                $price = (float)$offer['ITEM_PRICES'][0]['PRICE'];
                [$integerPart, $fractionPart] = explode('.', number_format($price, 2, '.', ''));

                $discount = (float)$offer['ITEM_PRICES'][0]['DISCOUNT'];
                $discountPercent = (float)$offer['ITEM_PRICES'][0]['PERCENT'];

                $quantity = (int)$offer['CATALOG_QUANTITY'];
            ?>
                <div class="info-in-card" data-id="<?=$key;?>" style="display:<?=($key == 0) ? "block" : "none"; ?>">
                    <div class="row align-items-center">
                        <!--  -->
                        <div class="col-lg-4">
                            <div class="product-item_images">
                                <div class="product-item_img cvetov1 ">
                                    <a class="cat-tovar-foto" href="<?=$item['DETAIL_PAGE_URL'].$offer['ID'].'/';?>" onclick="#">
                                        <div class="label label-sale" style="display: <?=($firstOfferDiscount > 0) ? 'block' : 'none';?>;">Скидка</div>
                                        <div class="sale-size" style="display: <?=($firstOfferDiscount > 0) ? 'block' : 'none';?>;">-<?=$firstOfferDiscount;?><sub>%</sub></div>
                                            <img class="shk-image  photo_tovar lazy-loaded" data-src="<?=$file['src'];?>" style="margin-left:5px" width="<?=$file['width'];?>" height="<?=$file['height'];?>" data-src="<?=$file['src'];?>" src="<?=$file['src'];?>">
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-4">
                            <a href="<?=$item['DETAIL_PAGE_URL'].$offer['ID'].'/';?>" onclick="#" class="product-item_title" style="height: 86px;"><?=$offer['NAME'];?></a>
                            <div class="product-item_fields" style="">
                                <table>
                                    <tbody>
                                        <tr>
                                            <td>В наличии:</td>
                                            <td><?=$quantity;?> шт.</td>
                                        </tr>
                                        <?php
                                        foreach ($offer['DISPLAY_PROPERTIES'] as $property):
                                            ?>
                                            <tr>
                                                <td><?=$property['NAME'];?>:</td>
                                                <td><?=$property['VALUE'];?></td>
                                            </tr>
                                        <?php
                                        endforeach;
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!--  -->
                        <div class="col-sm-6 col-lg-4">
                            <div class="product-item_action">
                                <div class="product-item_buttons">
                                    <div class="button-cart no-wide">
                                        <div class="price-outer">
                                            <div class="price-block"> <span>Цена</span>
                                                <div class="price-big <?=( $discountPercent > 0 ) ? 'price-throug' : null;?>"> <?=$baseIntegerPart;?><sub>,<?=$baseFractionPart;?> ₽ </sub> </div>
                                            </div>
                                            <?php
                                                if( $discountPercent > 0 ):
                                            ?>
                                            <div class="row">
                                                <div class="col">
                                                    <div class="sale-block"> <span class="red">Скидка - <?=$discountPercent;?>%</span>
                                                        <div class="price-sale"> <?=$integerPart;?><sub>,<?=$fractionPart;?> ₽</sub> </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php
                                                endif;
                                            ?>
                                        </div>
                                        <div class="gray-block count-block  evoShop_shelfItem">
                                            <!--<div style="display:none;">
                                                <span class="item_url"><?php /*=$item['DETAIL_PAGE_URL'].$offer['ID'].'/';*/?></span>
                                                <span class="item_image"><?php /*=$file['src'];*/?></span>
                                                <span class="item_name">Елочный шарик "Лосенок", 80 мм, оранжевый с синим бантом</span>
                                                <span class="item_price">99</span>
                                                <span class="item_pricedefault">99</span>
                                                <span class="item_pricera">80</span>
                                                <span class="item_artikul">2810023</span>
                                                <span class="item_inventory">1865</span>
                                                <span class="item_diffprices"></span>
                                                <span class="item_priceconst">99</span>
                                                <span class="item_ves"> 22</span>
                                                <span class="item_obem"> </span>
                                                <input style="" type="button" class="item_add item-add-btn" value="Положить в корзину">
                                            </div>-->
                                            <div class="quantity-title"> Укажите тираж </div>
                                            <div class="quantity-block">
                                                <input type="text" name="count" placeholder="000000" class="item_quantity input-number input-count" required="">
                                            </div>
                                            <button type="submit" class="global-add btn btn-cart btn-gray btn-round" disabled=""> Отложить </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--  -->

                    </div>
                </div>
            <?php

                unset($file);
            endforeach;
            ?>
        </div>
    </div>
</div>