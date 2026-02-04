<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("title", "Нанесение логотипов на ежедневники купить оптом в Москве | Эклектика – нанесение логотипов на заказ");
$APPLICATION->SetPageProperty("description", "Компания Эклектика предлагает Нанесение логотипов на ежедневники оптом под нанесение логотипа. ✓ Низкие цены. ✓ Доставка по России. ☎ 8(800) 777-4723");
$APPLICATION->SetTitle("Нанесение логотипов на ежедневники  ");?>

<div itemprop="articleBody">
    <div class="content" style="padding-bottom:0">
        <div class="related-list portfolio related-list-nanesenie">
            <h2>Примеры нанесения</h2>
            <div class="swiper-container related-slider-clients instance-1 swiper-container-horizontal" style="max-height:180px;margin:0;padding-top:15px;padding-bottom:15px;border-bottom:1px dotted #caced3;border-top:1px dotted #caced3">
                <div class="swiper-wrapper">
                    <div class="swiper-slide swiper-slide-active" style="width:404px;margin-right:10px">
                        <div class="product-item_img" style="max-height:150px;margin:0"><img src="obor/s3.jpg"></div>
                    </div>
                    <div class="swiper-slide swiper-slide-next" style="width:404px;margin-right:10px">
                        <div class="product-item_img" style="max-height:150px;margin:0"><img src="obor/s4.jpg"></div>
                    </div>
                    <div class="swiper-slide" style="width:404px;margin-right:10px">
                        <div class="product-item_img" style="max-height:150px;margin:0"><img src="obor/s5.jpg"></div>
                    </div>
                    <div class="swiper-slide" style="width:404px;margin-right:10px">
                        <div class="product-item_img" style="max-height:150px;margin:0"><img src="obor/s6.jpg"></div>
                    </div>
                    <div class="swiper-slide" style="width:404px;margin-right:10px">
                        <div class="product-item_img" style="max-height:150px;margin:0"><img src="obor/s7.jpg"></div>
                    </div>
                    <div class="swiper-slide" style="width:404px;margin-right:10px">
                        <div class="product-item_img" style="max-height:150px;margin:0"><img src="obor/s8.jpg"></div>
                    </div>
                    <div class="swiper-slide" style="width:404px;margin-right:10px">
                        <div class="product-item_img" style="max-height:150px;margin:0"><img src="obor/s9.jpg"></div>
                    </div>
                    <div class="swiper-slide" style="width:404px;margin-right:10px">
                        <div class="product-item_img" style="max-height:150px;margin:0"><img src="obor/s10.jpg"></div>
                    </div>
                </div><span class="swiper-notification" aria-live="assertive" aria-atomic="true"></span></div>
            <div class="swiper-nav cp-nav" style="max-height:150px;padding-top:5px;margin-top:5px">
                <div class="cp-button-prev btn-prev-1 swiper-button-disabled" tabindex="0" role="button" aria-label="Previous slide" aria-disabled="true">&nbsp;</div>
                <div class="cp-button-next btn-next-1" tabindex="0" role="button" aria-label="Next slide" aria-disabled="false">&nbsp;</div>
            </div>
        </div>
    </div>
    <?$APPLICATION->IncludeComponent(
        "online-service:sales.hit",
        "",
        Array(
            "CACHE_TIME" => "3600",
            "CACHE_TYPE" => "A",
            "ELEMENT_COUNT" => "10",
            "FILTER_OFFER_PROPERTY" => "HIT_PRODAZH",
            "FILTER_OFFER_VALUE" => "22",
            "IBLOCK_ID" => "13",
            "OFFER_BASE_FIELDS" => array("ID", "NAME", "PREVIEW_PICTURE", ""),
            "OFFER_GET_PRICES" => "Y",
            "OFFER_PROPERTY_CODE" => array("ARTICLE", "MATERIAL", "METOD_NANESENIYA", "COLOR", ""),
            "PROPERTY_CODE" => array("ARTICLE", "MATERIAL")
        )
    );?>
</div>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
