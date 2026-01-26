<!-- BEGIN quantity-outer -->
<div class="quantity-outer evoShop_shelfItem">
    <div style="display:none;">
        <span class="item_idivid"><?=$ID?></span>
        <span class="item_url"><?=$page?></span>
        <span class="item_image"><?=$photo['src']?></span>
        <span class="item_name"><?=$NAME?></span>
        <span class="item_price">209</span>
        <span class="item_artikul"><?=$ARTICLE?></span>
        <span class="item_inventory"><?$QUANTITY?></span>
        <span class="item_pricedefault">209</span>
        <span class="item_pricera">188.1</span>
        <span class="item_priceconst">209</span>
        <span class="item_diffprices"> []</span>
        <span class="item_ves"><?=$WEIGHT?></span>
        <span class="item_obem"> </span>
        <!-- <span class="item_nanesenie"> 3223 </span> -->
    </div>
    <div class="row justify-content-end">
        <p style="color: red; font-size: 12px; padding: 0 10px 10px; margin: 0;">Внимание! Стоимость нанесения рассчитывается менеджером после оформления заказа.</p>
        <div class="form-group col-6" style="margin: -5px 59px 0 0;">
            <label style="font-size: 12px;font-weight: 300;color: #adb4ba;">Метод нанесения</label>
            <select name="spaceSelect" class="form-control item_nanesenie" style="margin-top: 4px; padding: 0; height: 30px;" id="exampleFormControlSelect1_1327959">
                <option class="item_nanesenie2" value="Лазерная гравировка">Лазерная гравировка</option>
                <option class="item_nanesenie2" value="УФ-печать">УФ-печать</option>
                <option class="item_nanesenie2" value="Без нанесения">Без нанесения</option>                                
            </select>
        </div>
        <div class="col-4">
            <div class="small-title">На складе</div>
            <div class="price-info sklad-count"><?=$QUANTITY?></div>
        </div>
    </div>
    <!--  -->
    <div class="quantity-block d-flex justify-content-between">
        <div class="quantity-title 3">
            Укажите необходимый тираж
        </div>
        <input type="text" name="count" class="item_quantity input-count input-number" value="" placeholder="">
        <input style="display: none" type="button" class="item_add item-add-btn" value="Положить в корзину">
    </div>
</div>
<!-- END quantity-outer -->
<div class="product-button-cart ">
    <div class="product-price-total">
        <div class="d-flex">
            <span>Итого:</span>
            <strong id="total-sum-formatted">00 000 000<sub>,00</sub></strong>
            <strong style="display:none" id="total-sum"></strong>
        </div>
    </div> 
    <div class="flex-wrapper">
        <button itemscope="" itemtype="http://schema.org/BuyAction" type="" class="global-add ubtn btn-cart blue-ubtn" data-tooltip="Товар добавлен в корзину" disabled="">Заказать</button>  
        <button type="submit" class="ubtn blue-border-ubtn fancybox" data-src="#remindtovar">Быстрый заказ </button>
    </div>   
</div>
<!--  -->