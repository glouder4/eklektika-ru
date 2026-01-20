<?php
[$allSumIntegerPart, $allSumPractionPart] = explode('.', number_format((float)$arResult['TOTAL_RENDER_DATA']['PRICE'], 2, '.', ''));

$totalCount = 0; $totalVolume = 0;
$weightKg = number_format($arResult['allWeight'] / 1000, 2, ',', ' ');

foreach ($arResult['GRID']['ROWS'] as $key => $arItem){
    $totalCount += $arItem['QUANTITY'];
}
?>
<div class="panel-cart">
    <div class="container-wrap">
        <!-- BEGIN cart-side  -->
        <div id="shopCart" class="panel-cart-inner">
            <div class="panel-cart-title">Итого</div>
            <table style="width:100%;">
                <tbody>
                <tr>
                    <td style="min-width:140px;">Общий тираж</td>
                    <td><span id="total-quantity"><?=$totalCount;?></span> шт.</td>
                </tr>
                <tr style="">
                    <td style="min-width:140px;">Вес заказа(кг)</td>
                    <td><span id="total-ves"><?=$weightKg;?></span></td>
                </tr>
                <tr style="display: none">
                    <td style="min-width:140px;">Обьем(м<sup>3</sup>)</td>
                    <td><span id="total-obem"><?=$totalVolume;?></span></td>
                </tr>
                </tbody>
            </table>
            <div class="panel-cart-total"> <span id="grand-total"><?=$allSumIntegerPart;?><sub>,<?=$allSumPractionPart;?></sub></span> руб. </div>
            <div id="order-block-minprice" style="width: 100%;">
                <p>Минимальная сумма запроса ( корзины ) - 5000 р. (обращаем внимание, что сумма минимального заказа больше минимальной корзины !)</p>
                <p>Если Вы хотите сделать запрос на меньшую сумму, пожалуйста, пришлите его на почту <a style="display:inline-block;" href="mailto:team@eklektika.ru"><a href="mailto:team@eklektika.ru">team@eklektika.ru</a></a>
                </p>
            </div>
            <div class="d-flex">
                <div id="order-block" style="display: none;"> <a href="/oformlenie-zakaza.php" class="ubtn blue-ubtn">Оформить
                        заказ</a>
                    <script type="text/javascript">
                        jQuery(document).ready(function() {
                            jQuery("#shopOrderForm").css('display', 'block');
                            jQuery("#zakaz").css('display ','none');
                        });
                    </script>
                </div>
                <form id="komm_form" action="komm_predlozenie.php" method="POST">
                    <input type="hidden" name="export" value="1">
                    <!-- <input   style="cursor:pointer" id="komm_button" class="ubtn blue-border-ubtn"
                                                            value="Скачать коммерческое предложение"> -->
                    <button id="komm_button" class="ubtn blue-border-ubtn">Скачать коммерческое предложение</button>
                </form>
            </div>
            <!--
                                <div class="input-box">

                                    <input type="file" name="presentation" id="presentation" class="inputfile inputfile-link" />

                                    <label for="presentation"><span>Добавить макет</span></label>

                                </div><a href="" class="create-presentation">Создать презентацию</a>

             -->
            <hr>
            <button href="" class="btn btn-red-border btn-round" id="clear_cart">Очистить корзину</button> <a class="cart-continue" href="/">Продолжить покупки</a> </div>
        <!-- END cart-side -->
    </div>
</div>