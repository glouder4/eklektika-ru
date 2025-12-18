<?
include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/urlrewrite.php');

CHTTP::SetStatus("404 Not Found");
@define("ERROR_404","Y");

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->SetTitle("404: Страница не найдена или ее больше нет");
$APPLICATION->AddChainItem("404: Страница не найдена или ее больше нет", "");

?>
    <div class="middle container-wrap error-page-wrap">


        <img src="/assets/404.png" alt="">

        <div class="page-404-title">404</div>
        <p>Такой страницы нет или вы ошиблись адресом. Если искомой страницы нет по нашей вине, нам хотелось бы
            об этом знать, <a href="#sendmessage" class=" fancybox">сообщите нам об этом</a>. В любом случае, ссылки внизу работают</p>
        <!-- <style type="text/css">
.space-between {
    justify-content: space-between;
display: flex;
}
.sween {
    width:31%;

}

.sween1 {
    width:32%;

}


</style>
<div class="space-between ">

<div class="sween"><a href="https://eklektika.ru/yoliba/"><img  src ="/banner/mm.jpg" width=100% alt="yoliba"></a></div>
<div class="sween"><a href="https://eklektika.ru/dejstvuyushhie-akcii/"><img  src ="/banner/55.jpg" width=100% alt="акции и скидки"></a></div>
<div class="sween"><a href="https://eklektika.ru/eklektika-primo-novinki-s-otgruzkoj-v-den-zakaza/"><img  src ="/banner/rr.jpg" width=100% alt="распродажа"></a></div>

</div> <div><br></div> -->

    </div>
<?php

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>