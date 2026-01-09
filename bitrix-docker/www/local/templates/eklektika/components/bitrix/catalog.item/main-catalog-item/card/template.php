<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
?>

<div class="col-sm-6 col-lg-4 col-xl1-3 product-item-wrapper card" style="min-height: 554px;" data-entity='items-row'>
    <div itemscope="" itemtype="http://schema.org/Product" class="product-item is-sale" style="min-height: 554px;">
        <div class="label label-sale">Скидка</div>
        <div class="sale-size">-3<sub>%</sub></div>
        <div class="product-item_images">
            <div class="product-item_img">
                <a class="changed-url" href="<?=$item['DETAIL_PAGE_URL'];?>">
                    <?php
                    $file = CFile::ResizeImageGet(($item['PREVIEW_PICTURE']['ID'] > 0) ? $item['PREVIEW_PICTURE']['ID'] : $item['PREVIEW_PICTURE'], array('width'=>160, 'height'=>160), BX_RESIZE_IMAGE_PROPORTIONAL, true);
                    ?>
                    <img itemprop="image" width="<?=$file['width'];?>" height="<?=$file['height'];?>" data-src="<?=$file['src'];?>" src="<?=$file['src'];?>" class="lazy-loaded">
                    <?php
                    unset($file);
                    ?>
                </a>
            </div>
            <ul class="product-item_gallery">
                <?php
                foreach ($item['OFFERS'] as $key => $offer):
                    if ($offer['PREVIEW_PICTURE']['ID'] > 0)
                        $file = CFile::ResizeImageGet( $offer['PREVIEW_PICTURE']['ID'], array('width' => 50, 'height' => 50), BX_RESIZE_IMAGE_PROPORTIONAL, true);
                    else
                        $file['src'] = $offer['PREVIEW_PICTURE']['SRC'];
                    ?>
                    <li>
                        <a class="change-image-url" data-id="<?=$key;?>" data-tid="<?=$offer['ID'];?>" data-tovar="<?=$offer['ID'];?>" data-link="<?=$item['DETAIL_PAGE_URL'].$offer['ID'].'/';?>" href="<?=$offer['PREVIEW_PICTURE']['SRC'];?>">
                            <img data-src="<?=$file['src'];?>" itemprop="image" src="<?=$file['src'];?>" class="lazy-loaded">
                        </a>
                    </li>
                    <?php
                    unset($file);
                endforeach;
                ?>
            </ul>
        </div>
        <div class="infos" data-cacheid="analogsf5737c72-ff18-4b08-9ea7-37217b8fd015">
            <?php
            foreach ($item['OFFERS'] as $key => $offer):
                ?>
                <div class="info-in-card" data-id="<?=$key;?>" style="display:<?=($key == 0) ? "block" : "none"; ?>">
                    <a href="<?=$item['DETAIL_PAGE_URL'].$offer['ID'].'/';?>" class="product-item_title" style="height: 17px;"><span itemprop="name"><?=$offer['NAME'];?></span></a>

                    <div itemprop="description" class="product-item_fields" style="height: 150px;">
                        <table>
                            <tbody><tr class="tr-price">
                                <td>Цена</td>
                                <td>
                                    <div class="price-big price-throug"><span itemprop="offers" itemscope="" itemtype="http://schema.org/Offer"><span itemprop="price"> 313.<sub>50</sub><span itemprop="priceCurrency" style="font-size: 14px;" content="RUB">р.</span></span></span></div>
                                </td>
                            </tr>
                            <tr class="tr-price-sale">
                                <td>
                                    <div class="red">- 3%</div>
                                </td>
                                <td>
                                    <div class="price-sale"><span itemprop="offers" itemscope="" itemtype="http://schema.org/Offer"><span itemprop="price"> 302.<sub>50</sub> </span><span itemprop="priceCurrency" style="font-size: 12px;" content="RUB">р.</span></span>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Артикул:</td>
                                <td>2810127</td>
                            </tr>
                            <tr>
                                <td>В наличии:</td>
                                <td>938 шт.</td>
                            </tr>
                            <tr>
                                <td>Материал:</td>
                                <td>Дерево; Акрил; Смола</td>
                            </tr>
                            <tr>
                                <td>Цвет:</td>
                                <td>Белый матовый; Золотой</td>
                            </tr>
                            <tr>
                                <td>Метод нанесения</td>
                                <td>Лазерная гравировка; УФ-печать</td>
                            </tr>
                            </tbody></table>
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
                                    <div style="display:none;">
                                        <select name="spaceSelect" class="form-control" id="exampleFormControlSelect5_">
                                            <option class="item_nanesenie2" value="Без нанесения">Без нанесения</option><option class="item_nanesenie2" value="Лазерная гравировка">Лазерная гравировка</option><option class="item_nanesenie2" value=" УФ-печать"> УФ-печать</option>                                </select>
                                        <span class="item_url">/katalog/yolochnaya_igryshka_snejinka_2810127.php</span>

                                        <span class="item_image">foto-tovara2/2/8/1/2810127_1.jpg</span>
                                        <span class="item_name">Ёлочная игрушка Снежинка</span>
                                        <span class="item_price">302.5</span>
                                        <span class="item_pricedefault">302.5</span>
                                        <span class="item_pricera">272.25</span>
                                        <span class="item_artikul">2810127</span>
                                        <span class="item_inventory">938</span>
                                        <span class="item_diffprices"></span>
                                        <span class="item_priceconst">302.5</span>


                                        <span class="inventory-kolvo">938</span>
                                        <span class="item_ves"> 7</span>
                                        <span class="item_obem"> </span>
                                        <input style="" type="button" class="item_add item-add-btn" value="Положить в корзину">
                                    </div>
                                    <input type="text" name="count" placeholder="938" class="item_quantity input-number input-count" required="">
                                </div>
                                <hr>
                                <div class="pit-btn ">
                                    <button type="submit" class="global-add btn btn-cart btn-gray btn-round" itemtype="http://schema.org/BuyAction" disabled="">
                                        Отложить
                                    </button>

                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php
            endforeach;
            ?>
        </div>
    </div>
</div>