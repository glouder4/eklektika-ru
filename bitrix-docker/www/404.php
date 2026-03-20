<?
include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/urlrewrite.php');

CHTTP::SetStatus("404 Not Found");
@define("ERROR_404","Y");

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->SetPageProperty("title", "Страница не найдена или ее больше нет");
$APPLICATION->SetPageProperty("description", "404: Страница не найдена или ее больше нет");


$APPLICATION->SetTitle("404: Страница не найдена или ее больше нет");
$APPLICATION->AddChainItem("404: Страница не найдена или ее больше нет", "");

?>
    <div class="middle container-wrap error-page-wrap">


        <img src="/assets/404.png" alt="">

        <div class="page-404-title">404</div>
        <p>Такой страницы нет или вы ошиблись адресом. Если искомой страницы нет по нашей вине, нам хотелось бы
            об этом знать, <a href="#sendmessage" class=" fancybox">сообщите нам об этом</a>. В любом случае, ссылки внизу работают</p>


    </div>
<?php

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>