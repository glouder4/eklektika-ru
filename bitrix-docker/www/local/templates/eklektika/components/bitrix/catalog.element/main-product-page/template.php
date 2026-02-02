<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Catalog\ProductTable;

/**
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 * @var CatalogSectionComponent $component
 * @var CBitrixComponentTemplate $this
 * @var string $templateName
 * @var string $componentPath
 * @var string $templateFolder
 */

$this->setFrameMode(true);

$currentOffer = $arResult['OFFER_DATA'];
pre($currentOffer);
?>
    <div class="main-product-page">
        <div class="product-block">
                <div class="row">
                    <div class="col-md-5 col-xl-6">
                        <div class="product-gallery">
                            <div class="swiper-container gallery-top swiper-container-horizontal">
                                <div class="swiper-wrapper" style="transform:translate3d(0,0,0)">
                                    <?foreach ($currentOffer['PROPERTIES']['PHOTOS'] as $key => $galleryItem){ ?>
                                        <a href="<?=\CFile::GetPath($galleryItem['VALUE']);?>" class="swiper-slide fancybox-gallery <?=($key == 0) ? 'swiper-slide-active' : null;?>" data-fancybox="gallery" title="<?=$currentOffer['NAME'];?> фото" style="width:428px;margin-right:10px">
                                            <img src="<?=\CFile::GetPath($galleryItem['VALUE']);?>" alt="фото <?=$currentOffer['NAME'];?>">
                                        </a>
                                    <?php }?>
                                </div>
                                <div class="swiper-button-next swiper-button-disabled" tabindex="0" role="button" aria-label="Next slide" aria-disabled="true"></div>
                                <div class="swiper-button-prev swiper-button-disabled" tabindex="0" role="button" aria-label="Previous slide" aria-disabled="true"></div><span class="swiper-notification" aria-live="assertive" aria-atomic="true"></span>
                            </div>
                            <div class="swiper-container gallery-thumbs swiper-container-horizontal">
                                <div class="swiper-wrapper"></div><span class="swiper-notification" aria-live="assertive" aria-atomic="true"></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-0 col-lg-1"></div>
                    <div class="col-md-7 col-lg-6 col-xl-5 d-lg-flex justify-content-lg-end">
                        <div class="product-data">
                            <div class="b-popup" id="popup1">
                                <div class="b-popup-content">
                                    <h3>Товар успешно добавлен в корзину</h3>
                                    <div class="popup-info"><img src="foto-tovara2/5/4/0/5400113_1.jpg" alt="">
                                        <div>
                                            <p>Штопор-нож "Сомелье" в подарочной упаковке, цвет серебряный</p>
                                            <p>Цена за штуку: 328<span style="font-size:18px">₽</span></p>
                                            <p>Тираж: <span class="count-prod2"></span></p>
                                            <p style="color:red">Внимание! Стоимость нанесения рассчитывается менеджером после оформления заказа.</p>
                                            <div class="form-group col-6" style="padding:0">
                                                <label style="font:16px Ubuntu;color:unset">Метод нанесения:</label>
                                                <select name="spaceSelect" class="form-control" style="margin-top:4px;padding:0;height:30px" id="exampleFormControlSelect2_1269005">
                                                    <option class="item_nanesenie2 cart-product-nanesenie" value="Тампопечать">Тампопечать</option>
                                                    <option class="item_nanesenie2 cart-product-nanesenie" value="Лазерная гравировка">Лазерная гравировка</option>
                                                    <option class="item_nanesenie2" value="Без нанесения">Без нанесения</option>
                                                </select>
                                            </div>
                                            <p>Итого: <span class="count-price2"></span><span style="font-size:18px">₽</span></p>
                                        </div>
                                    </div>
                                    <div class="popup-buttons">
                                        <button class="hide-pop-up btn btn-gray btn-round">Вернуться к покупкам</button>
                                        <button class="go-to-card btn btn-gray btn-round">Продолжить оформление</button>
                                    </div>
                                </div>
                            </div>
                            <script src="/pop-up.js"></script>
                            <form action="" id="buy">
                                <div class="product-data_price cartItem_SCI-5400113" itemprop="offers" itemscope itemtype="http://schema.org/Offer">
                                    <meta itemprop="priceCurrency" content="RUB">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="small-title">Артикул</div>
                                            <div class="article">5400113</div>
                                        </div>
                                        <div class="col-6">
                                            <link itemprop="url" href="https://eklektika.ru//katalog/shtopor-noj_somele_v_podarochnoi_ypakovke_cvet_serebryanii_5400113.php">
                                            <link itemprop="availability" href="http://schema.org/InStock">
                                            <link itemprop="availability" href="http://schema.org/OutOfStock">
                                            <meta itemprop="availability" content="https://schema.org/PreOrder">
                                            <div class="small-title">Цена оптовая:</div>
                                            <div class="price-big price-throug" itemprop="price">820,<sub>00</sub><span style="font-size:19px">₽</span></div>
                                            <br>
                                            <br>
                                            <div class="small-title red">Скидка -60%:</div>
                                            <div class="price-sale">328,<sub>00 ₽</sub></div>
                                            <br>
                                        </div>
                                    </div>
                                </div>
                                <ul class="color-menu">
                                    <li class="active">
                                        <a href="/katalog/shtopor-noj_somele_v_podarochnoi_ypakovke_cvet_serebryanii_5400113.php"><img src="foto-tovara2/5/4/0/5400113_1.jpg" title="Серебряный" alt="color-serebryanii-f"></a>
                                    </li>
                                    <li>
                                        <a href="/katalog/shtopor-noj_somele_v_podarochnoi_ypakovke_cvet_chyornii_5400114.php"><img src="foto-tovara2/5/4/0/5400114_1.jpg" title="Черный" alt="color-chernii-f"></a>
                                    </li>
                                </ul>
                                <div class="product-data_info count-block">
                                    <div class="quantity-outer evoShop_shelfItem">
                                        <div style="display:none"><span class="item_idivid">1269005</span> <span class="item_url">/katalog/shtopor-noj_somele_v_podarochnoi_ypakovke_cvet_serebryanii_5400113.php</span> <span class="item_image">foto-tovara2/5/4/0/5400113_1.jpg</span> <span class="item_name">Штопор-нож "Сомелье" в подарочной упаковке, цвет серебряный</span> <span class="item_price">328</span> <span class="item_artikul">5400113</span> <span class="item_inventory">663</span> <span class="item_pricedefault">328</span> <span class="item_pricera">290</span> <span class="item_priceconst">328</span> <span class="item_diffprices">[]</span> <span class="item_ves">164</span> <span class="item_obem">1.89</span></div>
                                        <div class="row justify-content-end">
                                            <p style="color:red;font-size:12px;padding:0 10px 10px;margin:0">Внимание! Стоимость нанесения рассчитывается менеджером после оформления заказа.</p>
                                            <div class="form-group col-6" style="margin:-5px 59px 0 0">
                                                <label style="font-size:12px;font-weight:300;color:#adb4ba">Метод нанесения</label>
                                                <select name="spaceSelect" class="form-control item_nanesenie" style="margin-top:4px;padding:0;height:30px" id="exampleFormControlSelect1_1269005">
                                                    <option class="item_nanesenie2" value="Тампопечать">Тампопечать</option>
                                                    <option class="item_nanesenie2" value="Лазерная гравировка">Лазерная гравировка</option>
                                                    <option class="item_nanesenie2" value="Без нанесения">Без нанесения</option>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <div class="small-title">На складе</div>
                                                <div class="price-info sklad-count">663</div>
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
                                            <div class="d-flex"><span>Итого:</span> <strong id="total-sum-formatted">00 000 000<sub>,00</sub></strong> <strong style="display:none" id="total-sum"></strong></div>
                                        </div>
                                        <div class="flex-wrapper">
                                            <button itemscope itemtype="http://schema.org/BuyAction" type="" class="global-add ubtn btn-cart blue-ubtn" data-tooltip="Товар добавлен в корзину" disabled>Заказать</button>
                                            <button type="submit" class="ubtn blue-border-ubtn fancybox" data-src="#remindtovar">Быстрый заказ</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            <script>
                                function setSelectValues(e, t) {
                                    var l = document.getElementById("exampleFormControlSelect1_" + e),
                                        o = document.getElementById("exampleFormControlSelect2_" + e);
                                    l.value = t, o.value = t
                                }
                                document.getElementById("exampleFormControlSelect1_1269005").addEventListener("change", function(e) {
                                    var t = e.target.value;
                                    localStorage.setItem("selectedOption_1269005", t), setSelectValues("1269005", t), console.log("Новый выбор для товара 1269005 (селект 1):", t), console.log("Новый выбор для товара 1269005 (селект 2):", t)
                                }), document.getElementById("exampleFormControlSelect2_1269005").addEventListener("change", function(e) {
                                    var t = e.target.value;
                                    localStorage.setItem("selectedOption_1269005", t), setSelectValues("1269005", t), console.log("Новый выбор для товара 1269005 (селект 2):", t), console.log("Новый выбор для товара 1269005 (селект 1):", t)
                                }), document.addEventListener("DOMContentLoaded", function() {
                                    var e = "1269005",
                                        t = document.getElementById("exampleFormControlSelect1_" + e),
                                        l = (document.getElementById("exampleFormControlSelect2_" + e), localStorage.getItem("selectedOption_1269005"));
                                    if(l) setSelectValues(e, l), console.log("Сохраненный вариант для товара " + e + " (селект 1 и 2):", l);
                                    else {
                                        var o = t.options[0].value;
                                        setSelectValues(e, o), l = o, localStorage.setItem("selectedOption_1269005", o), console.log("Первый выбранный вариант для товара " + e + " (селект 1 и 2):", o)
                                    }
                                });
                            </script><a href="#calculate-application" class="ubtn blue-border-ubtn fancybox card-btn">Рассчитать стоимость нанесения</a>
                            <div class="tabs product-tabs">
                                <ul class="tabs__caption">
                                    <li class="active">Описание</li>
                                    <li>Файлы</li>
                                    <li>Транспорт</li>
                                    <li>Условия</li>
                                </ul>
                                <div class="tabs__content active">
                                    <table class="product-table">
                                        <tbody>
                                        <tr>
                                            <td>Вес</td>
                                            <td>164 гр.</td>
                                        </tr>
                                        <tr>
                                            <td>Объем:</td>
                                            <td>2 см<sup>3</sup></td>
                                        </tr>
                                        <tr>
                                            <td>Размер</td>
                                            <td>14,5х6,3х2 см</td>
                                        </tr>
                                        <tr>
                                            <td>Цвет</td>
                                            <td>Серебряный</td>
                                        </tr>
                                        <tr>
                                            <td>Материал</td>
                                            <td>Металл</td>
                                        </tr>
                                        <tr>
                                            <td>Упаковка:</td>
                                            <td>транспортная</td>
                                        </tr>
                                        <tr>
                                            <td>Метод нанесения</td>
                                            <td>Тампопечать; Лазерная гравировка</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                    <hr>
                                    <p>Элегантный и практичный нож "Сомелье" в жестяной подарочной упаковке, идеально подходящей под нанесение логотипа методами лазерной гравировки. <b>У небольшой части продукции возможны незначительные потертости на корпусе ножа или у паковки , в связи с этим цена на товар снижена. К возврату по данному виду дефекта не принимаем.</b>Уценка из-за вывода из ассортимента.</p>Компания «Эклектика» осуществляет нанесение логотипа на : Штопор-нож "Сомелье" в подарочной упаковке, цвет серебряный, арт. 5400113.
                                    <br>
                                    <br>Быстрая поставка - отличный вариант корпоративной сувенирной продукции и подарков.
                                    <br>
                                    <br>Стоимость заказа определяется количеством. Специальные цены и предложения для оптовых заказчиков. Мы создаем не просто сувенирную продукцию с Вашим логотипом, а продумываем идею и оформление будущего корпоративного подарка или мерча до мельчайших деталей, с учетом фирменного стиля компании и заданного бюджета.
                                    <br>
                                    <br>
                                </div>
                                <div class="tabs__content">
                                    <p>Макет cdr</p>
                                    <div class="lin"><a id="cdr" href="cdr/5400113-s.cdr">&nbsp;Скачать макет в cdr &gt;&gt;&gt;&nbsp;</a></div>
                                </div>
                                <div class="tabs__content 5">
                                    <p class="strong">Способы доставки товаров.</p>
                                    <p class="strong">1. Самовывоз (доставка транспортом покупателя).
                                        <br>
                                        <br>2. Доставка транспортом поставщика (Компания Эклектика) - для клиентов, расположенных в городе Москве и Московской области.
                                        <br>
                                        <br>3. Транспортными компаниями-партнерами Компании Эклектика:
                                        <br>- «<a href="http://nordw.ru/tarify/kalkulyator/" target="_blank" rel="nofollow noopener">Норд-Вил</a>» , для клиентов, расположенных в городах Санкт-Петербург и Казань;
                                        <br>- "<a href="http://tk-tline.ru/" rel="nofollow">Т-Лайн</a>", для клиентов расположенных в г. Екатеринбурге;
                                        <br>- "Байкал - Сервис" ;
                                        <br>- «<a href="http://www.pecom.ru/ru/service/store/msk/" target="_blank" rel="nofollow noopener">Первая Экспедиционная Компания</a>» - терминал "Москва Восток".
                                        <br>
                                        <br class="strong">4. Транспортными компаниями, выбранными клиентом.</p>
                                    <p class="strong">Способы оплаты</p>
                                    <p><span class="strong">&nbsp; 1. Наличными<br></span><span style="position:relative;left:25px">Оплата производится при получении товара в пункте самовывоза .</span></p>
                                    <p>&nbsp; <span class="strong">2.&nbsp;</span><span class="strong">Безналичными<br></span><span style="position:relative;left:25px">Оплата производится на расчетный счет, согласно выставленному счету.</span></p>
                                    <p>&nbsp; <span class="strong">3</span>.&nbsp;<span class="strong">Банковской картой<br></span><span style="position:relative;left:25px">Оплата производится в пункте самовывоза или через безопасный сервер Яндекс.Касса.</span></p>
                                </div>
                                <div class="tabs__content">
                                    <h3 class="red">Минимальная сумма заказа товара со склада в Москве - 5000 руб.</h3>
                                    <hr>
                                    <h3>Варианты доставки корпоративных подарков и сувениров, например таких как Штопор-нож "Сомелье" в подарочной упаковке, цвет серебряный, мы можем предложить:</h3>
                                    <ol>
                                        <li>
                                            <p><span style="font-weiht:bold">Самовывоз</span></p>
                                            <p>Мы все знаем про товар и ответим на вопросы.</p>
                                        </li>
                                        <li>
                                            <p><span style="font-weiht:bold">Доставка транспортом Эклектика</span></p>
                                            <p>Для клиентов, расположенных в городе Москве и Московской области.</p>
                                        </li>
                                        <li>
                                            <p><span style="font-weiht:bold">Транспортными компаниями-партнерами</span></p>
                                            <ul>
                                                <li>«Норд-Вил» , для клиентов, расположенных в городах Санкт-Петербург и Казань;</li>
                                                <li>"Т-Лайн", для клиентов расположенных в г. Екатеринбурге;</li>
                                                <li>"Байкал - Сервис";</li>
                                                <li>«Первая Экспедиционная Компания» - терминал "Москва Восток"</li>
                                            </ul>
                                        </li>
                                        <li><span style="font-weiht:bold">Транспортными компаниями, выбранными клиентом.</span></li>
                                    </ol>
                                    <hr>
                                    <h3>Какие Способы оплаты при покупке корпоративных подарков и сувениров, таких как Штопор-нож "Сомелье" в подарочной упаковке, цвет серебряный. Мы готовы предложить оптимальные решения:</h3>
                                    <ol>
                                        <li>
                                            <p><span style="font-weiht:bold">Наличными</span></p>
                                            <p>Оплата производится при получении товара в пункте самовывоза или в процессе доставки.</p>
                                        </li>
                                        <li>
                                            <p><span style="font-weiht:bold">Безналичными</span></p>
                                            <p>Оплата производится на расчетный счет, согласно выставленному счету.</p>
                                        </li>
                                        <li>
                                            <p><span style="font-weiht:bold">Банковской картой</span></p>
                                            <p>Оплата производится в пункте самовывоза или через безопасный сервер Яндекс.Касса.</p>
                                        </li>
                                    </ol>
                                    <hr>
                                    <h3>Знаете, где купить корпоративные подарки и сувениры, такие как Штопор-нож "Сомелье" в подарочной упаковке, цвет серебряный в интернет-магазине компании Эклектика, так как:</h3>
                                    <ol class="no-counter">
                                        <li>
                                            <p><span style="font-weiht:bold">Все по-честному</span></p>
                                            <p>Мы все знаем про товар в нашем интернет-магазине и ответим на вопросы.</p>
                                        </li>
                                        <li>
                                            <p><span style="font-weiht:bold">Цены и акции</span></p>
                                            <p>Кроме оптимальных цен вас ждут постоянные акции, бонусы и ошеломительные скидки.</p>
                                        </li>
                                        <li>
                                            <p><span style="font-weiht:bold">Приятные сюрпризы</span></p>
                                            <p>В каждом заказе тебя ждет журнал с полезными статьями, подарок-сюрприз или подарок на выбор.</p>
                                        </li>
                                    </ol>
                                </div>
                            </div>
                            <div class="product-info-message">
                                <script type="application/ld+json">{ "@context": "https://schema.org/", "@type": "Product", "name": "Штопор-нож &quot;Сомелье&quot; в подарочной упаковке, цвет серебряный", "image": "foto-tovara2/5/4/0/5400114_1.jpg", "description": "Элегантный и практичный нож Сомелье в жестяной подарочной упаковке, идеально подходящей под нанесение логотипа методами лазерной гравировки. <b>У небольшой части продукции возможны незначительные потертости на корпусе ножа или у паковки , в связи с этим цена на товар снижена. К возврату по данному виду дефекта не принимаем.</b>Уценка из-за вывода из ассортимента.", "brand": "", "offers": { "@type": "Offer", "url": "https://eklektika.ru/katalog/shtopor-noj_somele_v_podarochnoi_ypakovke_cvet_serebryanii_5400113.php", "priceCurrency": "RUB", "price": "290" } }</script>Эклектика оставляет за собой право без предварительных уведомлений менять технические параметры и потребительские характеристики представленных товаров.</div>
                        </div>
                    </div>
                </div>
            </div>
    </div>
<?php

?>

