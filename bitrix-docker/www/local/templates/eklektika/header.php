<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

global $USER;
?>
<!DOCTYPE html>
<html lang="ru">
	<head>
		<title><?$APPLICATION->ShowTitle();?></title>
		<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" /> 	
		<link rel="canonical" href="<?=SITE_URL;?>/<?/*<?=SITE_URL?>/*/?>">
		<meta charset="utf-8">
        <meta name="robots" content="noindex, nofollow" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">	

		<!-- CSS -->
		
		<link href="<?=SITE_TEMPLATE_PATH?>/assets/css/style.css" rel="stylesheet" media="all"/>
		<link href="<?=SITE_TEMPLATE_PATH?>/assets/css/custom.css" rel="stylesheet" media="all"/>
		<link href="<?=SITE_TEMPLATE_PATH?>/assets/css/custom_styles.css" rel="stylesheet" media="all" />
		<base href="<?=SITE_URL;?>/<?/*<?=SITE_URL?>/*/?>">

		<!-- END CSS -->

        <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>

        <?$APPLICATION->ShowHead();?>

		<!-- JavaScript -->

		<!-- <script src="<?=SITE_TEMPLATE_PATH?>/assets/js/jquery.2.2.4.min.js"></script> -->
		<!--[if lt IE 9]>
			<script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.2/html5shiv.min.js"></script>
			<script src="https://cdnjs.cloudflare.com/ajax/libs/respond.js/1.4.2/respond.min.js"></script>
		<![endif]-->
		<link rel="shortcut icon" href="/favicon.ico">
		<script async src="https://www.googletagmanager.com/gtag/js?id=G-RNB6WPXENS"></script>
		<script>
			window.dataLayer = window.dataLayer || [];
			function gtag(){dataLayer.push(arguments);}
			gtag('js', new Date());

			gtag('config', 'G-RNB6WPXENS');
		</script>

		<!-- Facebook Pixel Code -->

		<!--<script>
		!function(f,b,e,v,n,t,s)
		{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
		n.callMethod.apply(n,arguments):n.queue.push(arguments)};
		if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
		n.queue=[];t=b.createElement(e);t.async=!0;
		t.src=v;s=b.getElementsByTagName(e)[0];
		s.parentNode.insertBefore(t,s)}(window,document,'script',
		'https://connect.facebook.net/en_US/fbevents.js');
		fbq('init', '538650544158741'); 
		fbq('track', 'PageView');
		</script>
		<noscript>
		<img height="1" width="1" 
		src="https://www.facebook.com/tr?id=538650544158741&ev=PageView
		&noscript=1"/>
		</noscript>
		End Facebook Pixel Code 
		<noscript><img height="1" width="1" style="display:none"
		src="https://www.facebook.com/tr?id=538650544158741&ev=PageView&noscript=1"
		/></noscript>-->

		<!-- End Facebook Pixel Code -->

		<!-- Rating Mail.ru counter -->

		<script type="text/javascript">
		var _tmr = window._tmr || (window._tmr = []);
		_tmr.push({id: "3243174", type: "pageView", start: (new Date()).getTime(), pid: "USER_ID"});
		(function (d, w, id) {
		if (d.getElementById(id)) return;
		var ts = d.createElement("script"); ts.type = "text/javascript"; ts.async = true; ts.id = id;
		ts.src = "https://top-fwz1.mail.ru/js/code.js";
		var f = function () {var s = d.getElementsByTagName("script")[0]; s.parentNode.insertBefore(ts, s);};
		if (w.opera == "[object Opera]") { d.addEventListener("DOMContentLoaded", f, false); } else { f(); }
		})(document, window, "topmailru-code");
		</script><noscript><div>
		<img src="https://top-fwz1.mail.ru/counter?id=3243174;js=na" style="border:0;position:absolute;left:-9999px;" alt="Top.Mail.Ru" />
		</div></noscript>

		<!-- //Rating Mail.ru counter -->

		<meta name="yandex-verification" content="3e5439c03c7e1187" />
		<meta name="yandex-verification" content="53817f5a04569ceb" />

		<!-- Yandex.Metrika counter -->
		<script type="text/javascript" >
		(function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
		m[i].l=1*new Date();k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
		(window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");

		ym(1087753, "init", {
				clickmap:true,
				trackLinks:true,
				accurateTrackBounce:true,
				webvisor:true,
				ecommerce:"dataLayer" 
		});
		</script>
		<noscript><div><img src="https://mc.yandex.ru/watch/1087753" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
		<!-- /Yandex.Metrika counter -->

		<meta name="google-site-verification" content="-suLxGYYxCzqkxnXx7YYCTXxPnjGwntixgtY8GPlUj4" />
		<meta name="yandex-verification" content="3e5439c03c7e1187" />
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<link rel="stylesheet" href="<?=SITE_TEMPLATE_PATH?>/assets/css/pop-up.css">
		<script type="text/javascript" src="<?=SITE_TEMPLATE_PATH?>/assets/js/ds-comf/ds-form/js/dsforms.js"></script>
	</head>
	<body>
		<div id="panel">
			<?$APPLICATION->ShowPanel();?>
		</div>
		<!-- Google Tag Manager (noscript) -->
		<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-WWCBBKG";
		height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
		<!-- End Google Tag Manager (noscript) -->


        <div class="side-panel">
            <ul class="side-menu">
                <!--  -->
                <li class="no-active" id="no-active-cart" >
                    <a href="" class="show-tooltip">
                        <span class="icon-show-orders"></span>
                    </a>

                    <div class="info-tooltip dark">
                        <div class="title">Вы пока не отложили товар.</div>
                        <hr>
                        <p>Минимальная сумма заказа товара со склада в Москве на сумму 5000 руб. </p>
                        <p>Подробнее о доставке заказа вы можете в разделе  <a href="/dostavka/">“Доставка”</a>.</p>
                    </div>
                </li>
                <!--  -->

                <li id="active-cart" style="display:none">
                    <a href="" class="show-side">
                        <span class="icon-show-orders"></span>
                    </a>
                </li>
                <!--  -->
                <li >
                    <a href="#sendmessage" class="fancybox">
                        <span class="icon-letter-scribe "></span>
                    </a>
                </li>
                <!--  -->

                <li >
                    <a href="#callback" class="fancybox">
                        <span class="icon-callback "></span>
                    </a>
                </li>

                <!--  -->

                <!--
                <li class="active red">
                    <a href="">
                        <span class="icon-payments"></span>
                    </a>
                </li>
                -->

            </ul>

            <button class="btn-to-top" type="button" aria-label="наверх"><span class="icon-up"></span></button>
        </div><!-- END side panel -->
        <!-- ////////////////////////////////// -->
        <!-- BEGIN cart-side  -->
        <div class="cart-side scrollbar-inner" id="scrollbar-cart">
        </div>
        <!-- END cart-side -->

        <!-- BEGIN wrapper  -->
        <div class="wrapper">
            <header class="header custom-wrap-head">
                <section itemscope itemtype="http://schema.org/WPHeader" style="display:none;">
                    <p itemprop="name">Эклектика</p>
                    <p itemprop="description">нанесение логотипов на сувенирную продукцию</p>
                </section>
                <style>
                    .top-head-line1{ background: #04a7e2; padding: 20px 0; z-index: 1000; position: relative;}

                    .header.custom-wrap-head .navigation .menu a
                    { padding-left: 7px;
                        padding-right: 7px;
                        color: #000000;
                        font-weight: 400;
                        font-size: 15px;
                        line-height: 18px;}

                    .phone1, .phone1 a { text-decoration: none;font-size: 14px;}
                    .text-time-work1,.text-time-work1 a{color: #ffffff;text-decoration: none; font-size: 14px; }
                    .mail-h1 a {color: #ffffff; text-decoration: none;font-size: 14px; }
                    .blok img{margin-right: 8px;margin-left: 2px;}
                    .blok {display:block; width:220px;position: relative; }
                    .blok1 {display: flex;  }.blok2 { display: flex; flex-wrap: wrap;  }


                    .search-head-wrap .search input {
                        background: #Ffffff;
                        border: 2px solid #00A0E3 ;
                        border-radius: 40px;
                        font-weight: 400;
                        font-size: 16px;
                        line-height: 18px;
                        color: #828486;
                        height: 50px;
                    }

                    .header .fb-btn .fb-title {
                        border: 2px solid #828486;
                        color: #828486;
                        background: transparent;
                        display: flex
                    ;
                        padding: 10px;
                        height: 50px;
                        justify-content: center;
                        align-items: center;
                        font-weight: 700;
                        font-size: 16px;
                        line-height: 18px;
                        border-radius: 30px;
                        max-width: 100%;
                        min-width: 166px;
                        box-sizing: border-box;
                        cursor: pointer;
                        transition: all 0.5s;
                        text-decoration: none;
                        text-align: center;
                    }

                    .item-title {
                        color: #000000;
                        text-transform: uppercase;
                        font-size: 26px;
                        font-weight: bold;
                        line-height: 1.1;
                    }
                    @media (max-width: 802px) and (min-width: 769px) {
                        .header-soc__cont {margin-top: 10px;}
                    }
                    @media (max-width: 768px)
                    {

                        .top-head-line1{ background: #04a7e2; padding: 5 px 0; z-index: 1000; position: relative;}
                        .blok {display:block; width:150px; position: relative;  }
                        .blok1 { display: flex; flex-direction: column}
                        .blok2 { display: flex; width:450px;  justify-content: flex-end;}
                        .blok img{  display: none;}
                        .phone1, .phone1 a {text-decoration: none;font-size: 13px;}
                        .text-time-work1,.text-time-work1 a{color: #ffffff;text-decoration: none; font-size: 13px; }
                        .mail-h1 a {color: #ffffff; text-decoration: none;font-size: 13px; }


                        @media (max-width: 478px) {
                            .blok2 {
                                width: 100%;
                            }
                            .blok {
                                width: 135px;
                            }
                            .header-soc__cont {
                                margin-top: 10px;
                            }
                            .header-soc__cont {margin-top: 10px;}
                        }


                    }

                </style>
                <div class="top-head-line1">
                    <div class="container-wrap flex-wrapper align-items-center">
                        <div class="contact-head-block"><div class=blok1>
                                <a href="/" class="logo-mob">
                                    <img data-src="..\logoe.jpg" alt="Эклектика - нанесение логотипов на сувенирную продукцию">
                                </a><div class=blok2>
                                    <div class=blok> <img src="<?=SITE_TEMPLATE_PATH?>/assets/img/tel1.png" align=left alt="phone"><div class="text-time-work1"><a class="phone1" href="tel:+74951295372">+7 (495) 129-53-72</a><br><a class="phone1" href="tel:+78007075211">+7(800)707-52-11</a></div> </div>
                                    <a href="#callback1" class="fancybox link-callback"></a>
                                    <div class=blok><img src="<?=SITE_TEMPLATE_PATH?>/assets/img/mail1.png" align=left alt="mail"><div class="text-time-work1">пн-пт 9:30 - 18:00 МСК<br><a href="mailto:team@eklektika.ru" class="mail-h1">team@eklektika.ru</a></div>
                                    </div>

                                    <div class="header-soc__cont">
                                        <a rel="nofollow noopener" class="telegram-header" href="https://t.me/eklektikaru">
                                            <img src="<?=SITE_TEMPLATE_PATH?>/assets/img/tg1.png" alt="telegram">
                                        </a>
                                        <a rel="nofollow noopener" class="telegram-header1" href="https://vk.com/eklektikaru">
                                            <img src="<?=SITE_TEMPLATE_PATH?>/assets/img/vk1.png" alt="VK">
                                        </a>
                                    </div>  </div> </div></div>





                        <nav class="navigation">
                            <?$APPLICATION->IncludeComponent(
                                "bitrix:menu",
                                "header-main-menu",
                                Array(
                                    "ALLOW_MULTI_SELECT" => "N",
                                    "CHILD_MENU_TYPE" => "left",
                                    "DELAY" => "N",
                                    "MAX_LEVEL" => "2",
                                    "MENU_CACHE_GET_VARS" => array(""),
                                    "MENU_CACHE_TIME" => "3600",
                                    "MENU_CACHE_TYPE" => "N",
                                    "MENU_CACHE_USE_GROUPS" => "Y",
                                    "ROOT_MENU_TYPE" => "top",
                                    "USE_EXT" => "Y"
                                )
                            );?>
                        </nav>

                    </div>
                </div>
                <div class="middle-head-line">
                    <div class="container-wrap flex-wrapper pos-relative align-items-center">
                        <a href="/" class="logo">
                            <img class="lazy-loaded" data-src="<?=SITE_TEMPLATE_PATH?>/assets/img/logo-eklektika.png" alt="Эклектика - нанесение логотипов на сувенирную продукцию">
                        </a>
                        <div class="menu-catalog-container">
                            <a href="" class="btn-menu-catalog">
                                <span class="icon-catalogs"></span> <span class="desk-btn-text">Каталог</span><span class="mob-btn-text">Меню</span>
                            </a>
                            <div class="desktop-catalog">
                                <div class="mob-search-block flex-wrapper">
                                    <div class="search-head-wrap">
                                        <form action="/catalog/" class="search" id="main-search-form2">
                                            <fieldset>
                                                <input type="text" name="q" autocomplete="off" class=simple-poisk placeholder="Поиск"
                                                       required value="">
                                                <button type="submit" aria-label="искать" class="search-btn">

                                                </button>
                                            </fieldset>
                                            <div class="search-sub-results">
                                                <div class="row" id=kategort2 >
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    <a href="<?=($USER->IsAuthorized()) ? '/personal/lichnyj-kabinet.php' : '/personal/vhod.php'?>" id="" class="profile-login"><span class="profile-login-icon"></span><span class="profile-login-title"><?=($USER->IsAuthorized()) ? 'Кабинет' : 'Войти'?></span></a>
                                </div>
                                <div class="to-top-btn js-btn-up">Вверх</div>
                                <div class="show-mob-catalog js-show-mob-catalog">Каталог</div>
                                <ul class="catalog">
                                    <li class="back-link-catalog">&#8592; Назад</li>
                                    <li><a href="/novii_god_i_rojdestvo/"> Новый год и рождество </a>


                                        <ul class="subcatalog">
                                            <li class="back-link">&#8592; Назад</li>
                                            <li  ><a href="/novogodnie-novinki.php">Новогодние новинки 2025-2026</a></li>

                                            <li  ><a href="/yolochnie_shari/">Ёлочные шары</a></li>

                                            <li  ><a href="/novogodnie-girlyandy.php">Новогодние ели и гирлянды</a></li>

                                            <li  ><a href="/novogodnie-eko-igrushki/">Новогодние ЭКО сувенниры</a></li>

                                            <li  ><a href="/novogodnie_igryshki/">Новогодние игрушки</a></li>

                                            <li  ><a href="/novogodnie_svechi/">Новогодние свечи</a></li>

                                            <li  ><a href="/novogodnie_syveniri/">Новогодние подарки и сувениры</a></li>

                                            <li  ><a href="/novogodnie_podarochnie_nabori/">Новогодние подарочные наборы</a></li>

                                            <li  ><a href="/simvol_goda/">Символ года</a></li>

                                            <li  ><a href="/novogodnyaya_ypakovka/">Новогодняя упаковка</a></li>


                                        </ul>
                                    </li>

                                    <li><a href="/eklektika_primo/"> Быстрая поставка </a>


                                        <ul class="subcatalog">
                                            <li class="back-link">&#8592; Назад</li>
                                            <li  ><a href="/ezhednevniki-i-ruchki-za-1-den/">Ежедневники за 1 день!</a></li>

                                            <li  ><a href="/zonty-po-specpredlozheniyu/">Зонты за один день </a></li>

                                            <li  ><a href="/novogodnie-podarki-i-suveniry/">Новогодние подарки и сувениры в день заказа</a></li>

                                            <li  ><a href="/termostakany-i-termokruzhki-s-vashim-logotipom/">Посуда </a></li>

                                            <li  ><a href="/rasprodazha-s-maksimalnoj-skidkoj/">Распродажа: скидки до 80% !</a></li>


                                        </ul>
                                    </li>

                                    <li><a href="/zonti/"> Зонты </a>


                                        <ul class="subcatalog">
                                            <li class="back-link">&#8592; Назад</li>
                                            <li  ><a href="/zonti-trost/">Зонты-трость</a></li>

                                            <li  ><a href="/zonti_originalnie/">Зонты оригинальные</a></li>

                                            <li  ><a href="/zonti_jenskie/">Зонты женские</a></li>

                                            <li  ><a href="/zonti_myjskie/">Зонты мужские</a></li>

                                            <li  ><a href="/zonti_skladnie/">Зонты складные</a></li>

                                            <li  ><a href="/dorogie_zonti/">Дорогие зонты</a></li>


                                        </ul>
                                    </li>

                                    <li><a href="/ejednevniki/"> Ежедневники и блокноты </a>


                                        <ul class="subcatalog">
                                            <li class="back-link">&#8592; Назад</li>
                                            <li  ><a href="/ezhednevniki-po-individualnomu-dizajnu1/">Ежедневники по индивидуальному дизайну</a></li>

                                            <li  ><a href="/brunovisconti/">BrunoVisconti</a></li>

                                            <li  ><a href="/infolio/">Infolio</a></li>

                                            <li  ><a href="/portobello_trend/">Portobello Trend</a></li>

                                            <li  ><a href="/bloknoti/">Блокноты</a></li>

                                            <li  ><a href="/ejednevniki_happy_book/">Ежедневники Happy Book</a></li>

                                            <li  ><a href="/ejednevniki_datirovannie/">Ежедневники датированные</a></li>

                                            <li  ><a href="/ejednevniki_nedatirovannie/">Ежедневники недатированные</a></li>

                                            <li  ><a href="/ejednevniki_polydatirovannie/">Ежедневники полудатированные</a></li>

                                            <li  ><a href="/ejednevniki_tm_adutant/">Ежедневники ТМ &quot;Адъютант&quot;</a></li>

                                            <li  ><a href="/ejenedelniki/">Еженедельники</a></li>

                                            <li  ><a href="/zapisnie_knigi/">Записные книги</a></li>

                                            <li  ><a href="/kojanie_ejednevniki/">Кожаные ежедневники</a></li>

                                            <li  ><a href="/organaizeri/">Органайзеры</a></li>

                                            <li  ><a href="/planingi/">Планинги</a></li>

                                            <li  ><a href="/ypakovka_dlya_ejednevnikov/">Упаковка для ежедневников</a></li>


                                        </ul>
                                    </li>

                                    <li><a href="/termosy-termokruzhki-lanchboksy/"> Термосы и бутылки </a>


                                        <ul class="subcatalog">
                                            <li class="back-link">&#8592; Назад</li>
                                            <li  ><a href="/sportivnie_bytilki/">Спортивные бутылки</a></li>

                                            <li  ><a href="/termokryjki/">Термокружки</a></li>

                                            <li  ><a href="/termosi/">Термосы</a></li>

                                            <li  ><a href="/lanchboksy/">Ланчбоксы</a></li>

                                            <li  ><a href="/cofery/">Коферы</a></li>


                                        </ul>
                                    </li>

                                    <li><a href="/kryjki/"> Кружки </a>


                                        <ul class="subcatalog">
                                            <li class="back-link">&#8592; Назад</li>
                                            <li  ><a href="/kruzhki-s-vashim-logotipom/">Кружки с логотипом </a></li>

                                            <li  ><a href="/gjel_rezerv/">Эко кружки</a></li>

                                            <li  ><a href="/keramicheskie_kryjki/">Керамические кружки</a></li>

                                            <li  ><a href="/kryjki_dlya_syblimacii/">Кружки для сублимации</a></li>

                                            <li  ><a href="/kryjki_hameleon/">Кружки Хамелеон</a></li>

                                            <li  ><a href="/metallicheskie_kryjki/">Металлические кружки</a></li>

                                            <li  ><a href="/plastikovie_kryjki/">Пластиковые кружки</a></li>

                                            <li  ><a href="/kofery/">Кружки для кофе</a></li>

                                            <li  ><a href="/steklyannie_kryjki/">Стеклянные кружки</a></li>


                                        </ul>
                                    </li>

                                    <li><a href="/elektronika/"> Электроника </a>


                                        <ul class="subcatalog">
                                            <li class="back-link">&#8592; Назад</li>
                                            <li  ><a href="/zaryadnie_ystroistva_power_bank/">Зарядные устройства &quot;Power Bank&quot;</a></li>

                                            <li  ><a href="/besprovodnie_zaryadnie_ystroistva/">Беспроводные зарядные устройства</a></li>

                                            <li  ><a href="/nayshniki/">Наушники</a></li>

                                            <li  ><a href="/besprovodnie_portativnie_kolonki/">Беспроводные портативные колонки</a></li>

                                            <li  ><a href="/aksessyari_dlya_telefona/">Аксессуары для телефона</a></li>

                                            <li  ><a href="/aksessuary-dlya-kompyuterov-noutbukov-i-planshetov/">Аксессуары для компьютеров, ноутбуков и планшетов</a></li>

                                            <li  ><a href="/kalkylyatori/">Калькуляторы</a></li>

                                            <li  ><a href="/monopodi/">Моноподы</a></li>

                                            <li  ><a href="/ymnii_dom/">Умный дом</a></li>


                                        </ul>
                                    </li>

                                    <li><a href="/fleshki/"> Флешки </a>


                                        <ul class="subcatalog">
                                            <li class="back-link">&#8592; Назад</li>
                                            <li  ><a href="/usb_fleshki__4_gb/">USB флешки  4 GB</a></li>

                                            <li  ><a href="/usb_fleshki__8_gb/">USB флешки  8 GB</a></li>

                                            <li  ><a href="/usb_fleshki_128_gb/">USB флешки 128 GB</a></li>

                                            <li  ><a href="/usb_fleshki_16_gb/">USB флешки 16 GB</a></li>

                                            <li  ><a href="/usb_fleshki_32_gb/">USB флешки 32 GB</a></li>

                                            <li  ><a href="/usb_fleshki_64_gb/">USB флешки 64 GB</a></li>

                                            <li  ><a href="/jyostkii_disk/">Жёсткий диск</a></li>

                                            <li  ><a href="/fleshki_pylya/">Флешки Пуля</a></li>

                                            <li  ><a href="/derevyannie_fleshki/">Деревянные флешки</a></li>

                                            <li  ><a href="/originalnie_usb_fleshki/">Оригинальные USB флешки</a></li>

                                            <li  ><a href="/fleshki_soft_touch/">Флешки SOFT TOUCH</a></li>

                                            <li  ><a href="/fleshki_v_vide_karti/">Флешки в виде карты</a></li>

                                            <li  ><a href="/fleshki_kluch/">Флешки Ключ</a></li>

                                            <li  ><a href="/fleshki_kojanie/">Флешки кожаные</a></li>

                                            <li  ><a href="/fleshki_metallicheskie/">Флешки металлические</a></li>

                                            <li  ><a href="/fleshki_s_podsvetkoi/">Флешки с подсветкой</a></li>


                                        </ul>
                                    </li>

                                    <li><a href="/rychki/"> Ручки с логотипом </a>


                                        <ul class="subcatalog">
                                            <li class="back-link">&#8592; Назад</li>
                                            <li  ><a href="/antibakterialnye-ruchki/">Антибактериальные ручки</a></li>

                                            <li  ><a href="/ruchki-s-vashim-logotipom/">Ручки с Вашим логотипом </a></li>

                                            <li  ><a href="/ruchki-po-specpredlozheniyu/">Ручки по СПЕЦПРЕДЛОЖЕНИЮ</a></li>

                                            <li  ><a href="/rychki_metallicheskie/">Ручки металлические</a></li>

                                            <li  ><a href="/rychki_plastikovie/">Ручки пластиковые</a></li>

                                            <li  ><a href="/rychki_predstavitelskie/">Ручки представительские</a></li>

                                            <li  ><a href="/rychki_klio-eterna/">Ручки Klio-Eterna</a></li>

                                            <li  ><a href="/rychki-maxema/">ручки-Maxema</a></li>

                                            <li  ><a href="/rychki_prodir/">Ручки Prodir</a></li>

                                            <li  ><a href="/rychki_senator/">Ручки Senator</a></li>

                                            <li  ><a href="/rychki_soft_touch/">Ручки SOFT TOUCH</a></li>

                                            <li  ><a href="/eko-rychki/">ЭКО-ручки</a></li>

                                            <li  ><a href="/rychki_i_markeri_uma/">Ручки и маркеры UMA</a></li>

                                            <li  ><a href="/nabori_rychek/">Наборы ручек</a></li>

                                            <li  ><a href="/karandashi/">Карандаши</a></li>

                                            <li  ><a href="/markeri/">Маркеры</a></li>


                                        </ul>
                                    </li>

                                    <li><a href="/promo_symki/"> Сумки с логотипом </a>


                                        <ul class="subcatalog">
                                            <li class="back-link">&#8592; Назад</li>
                                            <li  ><a href="/symki_dlya_pokypok_i_plyaja/">Шопперы и сумки для пляжа</a></li>

                                            <li  ><a href="/aksessuary-dlya-brendirovaniya-ryukzakov-i-sumok/">Аксессуары для брендирования рюкзаков  и сумок</a></li>

                                            <li  ><a href="/holshovie_symki/">Холщовые сумки</a></li>

                                            <li  ><a href="/sumki-poyasnye/">Сумки поясные</a></li>

                                            <li  ><a href="/rukzaki/">Рюкзаки</a></li>

                                            <li  ><a href="/kosmetichki/">Косметички</a></li>

                                            <li  ><a href="/symki_dlya_konferencii/">Сумки для конференций</a></li>

                                            <li  ><a href="/symki_i_chehli_dlya_noytbyka/">Сумки и чехлы для ноутбука</a></li>

                                            <li  ><a href="/dorojnie_symki/">Дорожные сумки</a></li>

                                            <li  ><a href="/symki-holodilniki/">Сумки-холодильники</a></li>

                                            <li  ><a href="/portfeli/">Портфели</a></li>


                                        </ul>
                                    </li>

                                    <li><a href="/promo_odejda/"> Promo одежда </a>


                                        <ul class="subcatalog">
                                            <li class="back-link">&#8592; Назад</li>
                                            <li  ><a href="/fytbolki/">Футболки</a></li>

                                            <li  ><a href="/dojdeviki/">Дождевики</a></li>

                                            <li  ><a href="/panamy/">Панамы</a></li>

                                            <li  ><a href="/svitshoty-i-hudi/">Свитшоты и Худи</a></li>

                                            <li  ><a href="/polo/">Поло</a></li>

                                            <li  ><a href="/beisbolki/">Бейсболки</a></li>

                                            <li  ><a href="/vetrovki/">Ветровки</a></li>

                                            <li  ><a href="/detskii_tekstil/">Детский текстиль</a></li>

                                            <li  ><a href="/zhilety-s-logotipom/">Жилеты</a></li>

                                            <li  ><a href="/tolstovki/">Толстовки</a></li>

                                            <li  ><a href="/kyrtki/">Куртки</a></li>

                                            <li  ><a href="/rybashki_ofisnie/">Рубашки офисные</a></li>

                                            <li  ><a href="/krasota_i_stil_jizni/">Брюки</a></li>

                                            <li  ><a href="/platki/">Платки</a></li>

                                            <li  ><a href="/fartyki/">Фартуки</a></li>

                                            <li  ><a href="/aksessyari/">Аксессуары</a></li>

                                            <li  ><a href="/shapki/">Шапки</a></li>

                                            <li  ><a href="/sharfi/">Шарфы</a></li>

                                            <li  ><a href="/galstyki/">Галстуки</a></li>


                                        </ul>
                                    </li>

                                    <li><a href="/promosyveniri/"> Промосувениры </a>


                                        <ul class="subcatalog">
                                            <li class="back-link">&#8592; Назад</li>
                                            <li  ><a href="/antistressi/">Антистрессы</a></li>

                                            <li  ><a href="/breloki/">Брелоки</a></li>

                                            <li  ><a href="/promo-suveniry-do-50-rub/">Промо сувениры до 50 руб</a></li>

                                            <li  ><a href="/zajigalki/">Зажигалки</a></li>

                                            <li  ><a href="/zerkala/">Зеркала и косметика</a></li>

                                            <li  ><a href="/neobychnye-podarki-i-kreativnye-suveniry/">Оригинальные сувениры и необычные подарки с логотипом</a></li>

                                            <li  ><a href="/lenti_dlya_beidjei/">Ленты для бейджей</a></li>

                                            <li  ><a href="/ochki_i_binokli/">Очки и бинокли</a></li>

                                            <li  ><a href="/peshehodnye-svetootrazhateli/">Пешеходные светоотражатели</a></li>

                                            <li  ><a href="/ryletki/">Рулетки</a></li>

                                            <li  ><a href="/sredstva-individualnoj-zashhity/">Защита здоровья</a></li>

                                            <li  ><a href="/syveniri_do_500_ryblei/">Сувениры до 500 рублей</a></li>

                                            <li  ><a href="/tovari_dlya_syblimacii/">Товары для сублимации</a></li>

                                            <li  ><a href="/fonari/">Фонари</a></li>


                                        </ul>
                                    </li>

                                    <li><a href="/firma/"> Офис и бизнес </a>


                                        <ul class="subcatalog">
                                            <li class="back-link">&#8592; Назад</li>
                                            <li  ><a href="/zonti2/">Календари, стикеры, наборы бумаг</a></li>

                                            <li  ><a href="/symki_i_otdih/">Канцелярские товары</a></li>

                                            <li  ><a href="/ofisnie_prinadlejnosti_i_biznes-aksessyari/">Офисные принадлежности и бизнес-аксессуары</a></li>

                                            <li  ><a href="/pishyshie_prinadlejnosti/">Папки, обложки, тетради</a></li>

                                            <li  ><a href="/podstavki_dlya_vizitok/">Подставки для визиток</a></li>


                                            <li  ><a href="/vizitnici/">Визитницы</a></li>

                                            <li  ><a href="/kanctovari/">Настольные органайзеры и подставки для ручек</a></li>

                                            <li  ><a href="/chasi_i_elektronika/">Часы для офиса</a></li>


                                            <li><a href="<?=SITE_URL?>/podarochnaya_ypakovka/">Упаковка(резерв)</a></li>

                                        </ul>
                                    </li>

                                    <li><a href="/pyteshestvie/"> Путешествие и отдых </a>


                                        <ul class="subcatalog">
                                            <li class="back-link">&#8592; Назад</li>
                                            <li  ><a href="/naboridlya_avtomobilya/">Аксессуары для автомобиля</a></li>

                                            <li  ><a href="/aksessyari_dlya_piknika_i_barbeku/">Аксессуары для пикника и барбекю</a></li>

                                            <li  ><a href="/golovolomki/">Головоломки</a></li>

                                            <li  ><a href="/chehli_dlya_chemodanov/">Чехлы для чемоданов</a></li>

                                            <li  ><a href="/plyajnie_prinadlejnosti/">Пляжные принадлежности</a></li>

                                            <li  ><a href="/flyajki/">Фляжки</a></li>

                                            <li  ><a href="/letnie-suveniry-i-podarki/">Летние сувениры и подарки</a></li>


                                        </ul>
                                    </li>

                                    <li><a href="/dom_i_obraz_jizni/"> Дом и образ жизни </a>


                                        <ul class="subcatalog">
                                            <li class="back-link">&#8592; Назад</li>
                                            <li  ><a href="/instrymenti_rychnie/">Инструменты ручные</a></li>

                                            <li  ><a href="/vazi/">Вазы</a></li>

                                            <li  ><a href="/derevyannye-svetilniki-i-levitiruyushhie-lampy.php">Деревянные светильники и  левитирующие лампы</a></li>

                                            <li  ><a href="/vinnie_i_kyritelnie_aksessyari/">Винные и курительные аксессуары</a></li>

                                            <li  ><a href="/interer/">Интерьер</a></li>

                                            <li  ><a href="/nabori_dlya_chistki_obyvi/">Наборы для чистки обуви</a></li>

                                            <li  ><a href="/pledi/">Пледы</a></li>

                                            <li  ><a href="/polotenca/">Полотенца</a></li>

                                            <li  ><a href="/chasi/">Часы и погодные станции</a></li>

                                            <li  ><a href="/svechi/">Свечи</a></li>

                                            <li  ><a href="/uvlazhniteli-vozduha/">Забота о безопасности здоровья</a></li>

                                            <li  ><a href="/fotoramki_i_fotoalbomi/">Фоторамки и фотоальбомы</a></li>

                                            <li  ><a href="/sad/">Сад</a></li>

                                            <li  ><a href="/posyda/">Посуда и товары для кухни</a></li>


                                        </ul>
                                    </li>

                                    <li><a href="/biznes-podarki/"> Бизнес-подарки </a>


                                        <ul class="subcatalog">
                                            <li class="back-link">&#8592; Назад</li>
                                            <li  ><a href="/nagradnaya_prodykciya/">Наградная продукция</a></li>

                                            <li  ><a href="/bymajniki/">Бумажники</a></li>

                                            <li  ><a href="/podarki_dlya_myjchin/">Подарки для мужчин</a></li>

                                            <li  ><a href="/dlya_milih_dam/">Для милых дам</a></li>

                                            <li  ><a href="/kalendari/">Календари</a></li>

                                            <li  ><a href="/papki/">Папки</a></li>

                                            <li  ><a href="/podarki_kollegam/">Подарки коллегам</a></li>

                                            <li  ><a href="/rossiiskii_podarok/">Российский подарок</a></li>

                                            <li  ><a href="/syveniri_nastolnie/">Сувениры настольные</a></li>

                                            <li  ><a href="/chasy-v-podarok/">Часы в подарок</a></li>

                                            <li  ><a href="/vip_podarki/">VIP подарки</a></li>


                                        </ul>
                                    </li>

                                    <li><a href="/podarochnie_nabori/"> Подарочные наборы </a>


                                        <ul class="subcatalog">
                                            <li class="back-link">&#8592; Назад</li>
                                            <li  ><a href="/xindao_rezerv/">Вкусные подарочные наборы</a></li>

                                            <li  ><a href="/nabori_s_ejednevnikami/">Наборы с ежедневниками</a></li>

                                            <li  ><a href="/podarki-dlya-sotrudnikov-na-karantine/">Подарки для сотрудников на карантине</a></li>

                                            <li  ><a href="/suveniry-rabotayu-iz-doma/">Сувениры на удаленке</a></li>

                                            <li  ><a href="/podarochnie_nabori_dlya_ofisa/">Подарочные наборы для офиса</a></li>

                                            <li  ><a href="/podarochnie_nabori_dlya_myjchin/">Подарочные наборы для мужчин</a></li>

                                            <li  ><a href="/podarochnie_nabori_dlya_dam/">Подарочные наборы для дам</a></li>

                                            <li  ><a href="/nabory-s-zaryadnym-ustrojstvom/">Наборы с зарядным устройством</a></li>

                                            <li  ><a href="/nabory-s-kolonkami/">Наборы с колонками</a></li>

                                            <li  ><a href="/nabory-s-pledami/">Наборы с пледами</a></li>

                                            <li  ><a href="/nabory-s-termokruzhkami/">Наборы с термокружками</a></li>

                                            <li  ><a href="/nabory-s-fleshkami/">Наборы с флешками</a></li>


                                        </ul>
                                    </li>

                                    <li><a href="/podarki_i_syveniri_k_prazdnikam/"> Подарки и сувениры к праздникам </a>


                                        <ul class="subcatalog">
                                            <li class="back-link">&#8592; Назад</li>
                                            <li  ><a href="/podarki-i-suveniry-na-den-stroitelya/">День Строителя</a></li>

                                            <li  ><a href="/podarki_i_syveniri_k_9_maya/">Подарки и сувениры к 9 мая</a></li>

                                            <li  ><a href="/podarki-na-den-medika/">День Медицинского работника</a></li>

                                            <li  ><a href="/podarki_i_syveniri_k_23_fevralya/">Подарки и сувениры к 23 февраля</a></li>

                                            <li  ><a href="/den-znanij/">День Знаний</a></li>

                                            <li  ><a href="/podarki-i-suveniry-k-8-marta/">Подарки и сувениры к 8 марта</a></li>

                                            <li  ><a href="/podarki_i_syveniri_k_12_iunya/">Подарки и сувениры к 12 июня</a></li>

                                            <li  ><a href="/podarki_vipysknikam/">Подарки выпускникам</a></li>

                                            <li  ><a href="/den-aviacii/">День Авиации</a></li>

                                            <li  ><a href="/podarki-i-suveniry-na-den-buhgaltera/">День Бухгалтера</a></li>

                                            <li  ><a href="/podarki-i-suveniry-na-den-vmf/">День ВМФ</a></li>

                                            <li  ><a href="/podarki-i-suveniry-na-den-geologa/">День Геолога</a></li>

                                            <li  ><a href="/den-zheleznodorozhnika/">День железнодорожника</a></li>

                                            <li  ><a href="/den-molodezhi/">День Молодежи</a></li>

                                            <li  ><a href="/den-metallurga/">День Металлурга</a></li>

                                            <li  ><a href="/den-neftyanika/">День Нефтяника</a></li>

                                            <li  ><a href="/podarki-i-suveniry-na-den-policii/">День Полиции</a></li>

                                            <li  ><a href="/den-programmista/"> День Программиста</a></li>

                                            <li  ><a href="/suveniry-na-den-radio-i-svyazi/">День Радио и связи</a></li>

                                            <li  ><a href="/den-uchitelya/">День Учителя</a></li>

                                            <li  ><a href="/podarki-i-suveniry-na-den-himika/">День Химика</a></li>

                                            <li  ><a href="/podarki-i-suveniry-na-14-fevralya/">14 февраля</a></li>

                                            <li  ><a href="/den-shahtera/">День Шахтера</a></li>

                                            <li  ><a href="/den-sisadmina/">День Сисадмина</a></li>


                                        </ul>
                                    </li>

                                    <li><a href="/otdih/"> Сувениры и подарки для спортсменов </a>


                                        <ul class="subcatalog">
                                            <li class="back-link">&#8592; Назад</li>
                                            <li  ><a href="/odejda_dlya_sporta/">Одежда для спорта</a></li>

                                            <li  ><a href="/sportivnie_igri/">Спортивные игры</a></li>

                                            <li  ><a href="/sportivnie_symki/">Спортивные сумки</a></li>

                                            <li  ><a href="/tovari_dlya_fitnesa/">Спортивные товары</a></li>

                                            <li  ><a href="/fitnes_nabori/">Фитнес наборы</a></li>

                                            <li  ><a href="/fytbolnie_syveniri/">Футбольные сувениры</a></li>

                                            <li  ><a href="/shahmaty/">Шахматы</a></li>


                                        </ul>
                                    </li>

                                    <li><a href="/vkysniee_podarochnie_nabori/"> Вкусные подарки </a>


                                        <ul class="subcatalog">
                                            <li class="back-link">&#8592; Назад</li>
                                            <li  ><a href="/sladkie_podarki/">Сладкие подарки</a></li>

                                            <li  ><a href="/varene-i-myod/">Варенье, мед и мармелад</a></li>

                                            <li  ><a href="/dlya-glintvejna/">Для глинтвейна</a></li>

                                            <li  ><a href="/kofe-i-chaj/">Кофе и чай</a></li>

                                            <li  ><a href="/nabory-s-chaem-kofe-i-sladostyami/">Наборы с чаем, кофе и сладостями</a></li>

                                            <li  ><a href="/shokolad-i-konfety/">Шоколад и конфеты</a></li>


                                        </ul>
                                    </li>

                                    <li><a href="/detskie_tovari/"> Детские подарки </a>


                                        <ul class="subcatalog">
                                            <li class="back-link">&#8592; Назад</li>
                                            <li  ><a href="/detskie-nabory-v-period-karantina/">Детские подарки для on-line мероприятий</a></li>

                                            <li  ><a href="/igri_nastolnie/">Игры настольные</a></li>

                                            <li  ><a href="/igri_ylichnie/">Игры уличные</a></li>

                                            <li  ><a href="/myagkaya_igryshka/">Мягкая игрушка</a></li>

                                            <li  ><a href="/podarki_ychenikam/">Подарки ученикам</a></li>


                                        </ul>
                                    </li>

                                    <li><a href="/eko-syveniri/"> ЭКО-сувениры </a>


                                        <ul class="subcatalog">
                                            <li class="back-link">&#8592; Назад</li>
                                            <li  ><a href="/prodykti_pererabotki/">Продукты переработки</a></li>

                                            <li  ><a href="/eko-bloknoti/">ЭКО-блокноты</a></li>

                                            <li  ><a href="/eko-karandashi/">ЭКО-карандаши</a></li>

                                            <li  ><a href="/eko-kybi/">ЭКО-кубы</a></li>

                                            <li  ><a href="/eko-podarki/">ЭКО-подарки</a></li>

                                            <li  ><a href="/eko-symki/">ЭКО-сумки</a></li>

                                            <li  ><a href="/suveniry-iz-pererabotki/">Сувениры из переработанных материалов</a></li>


                                        </ul>
                                    </li>

                                    <li><a href="/podarochnaya_ypakovka/"> Подарочная упаковка </a>


                                        <ul class="subcatalog">
                                            <li class="back-link">&#8592; Назад</li>
                                            <li  ><a href="/ypakovka_dlya_usb_fleshek/">Упаковка для USB флешек</a></li>

                                            <li  ><a href="/pakety/">Пакеты </a></li>


                                            <li><a href="<?=SITE_URL?>/pakety-s-vashim-logotipom/">Пакеты с логотипом</a></li>

                                        </ul>
                                    </li>

                                    <li><a href="/torsheri_rezerv/"> Сувенирные бренды </a>


                                        <ul class="subcatalog">
                                            <li class="back-link">&#8592; Назад</li>
                                            <li  ><a href="/yoliba/">Yoliba</a></li>

                                            <li  ><a href="/brendy/brunovisconti/">BrunoVisconti</a></li>

                                            <li  ><a href="/lettertone/">Lettertone</a></li>

                                            <li  ><a href="/portobello-trend/">Portobello Trend</a></li>

                                            <li  ><a href="/prodir/">Prodir</a></li>

                                            <li  ><a href="/senator/">Senator</a></li>


                                        </ul>
                                    </li>

                                    <li><a href="">  </a>

                                    </li>


                                    <!-- BEGIN catalog cats -->

                                </ul>
                                <ul itemscope itemtype="http://www.schema.org/SiteNavigationElement" class="mob-menu-pages">
                                    <li itemprop="name"><a itemprop="url" style="color: red;" href="<?=SITE_URL?>/yoliba/">Yoliba </a></li>
                                    <li itemprop="name"><a itemprop="url" href="/kak-zakazat/">Как заказать</a>

                                    </li>
                                    <li itemprop="name"><a itemprop="url" href="/nanesenie/">Нанесение</a>
                                        <span class="open icon_arrow"></span>
                                        <div class="sub-menu justify-content-between">
                                            <ul>
                                                <li><a href="/lazernaya-gravirovka/">Лазерная гравировка</a></li>
                                                <li><a href="/dtf-pechat-na-tkani/">DTF печать на ткани</a></li>
                                                <li><a href="/tampopechat/">Услуги тампопечати</a></li>
                                                <li><a href="/tisnenie/">Тиснение</a></li>
                                                <li><a href="/polnocvetnaya-uf-pechat/">Полноцветная УФ-печать</a></li>
                                                <li><a href="/shelkografiya/">Шелкография на ткани</a></li>
                                                <li><a href="/izgotovlenie-shildikov/">Изготовление шильдиков</a></li>
                                                <li><a href="/markirovka-texnicheskoj-produkczii/">Маркировка технической продукции</a></li>
                                                <li><a href="/nanesenie-logotipov-na-ezhednevniki/">Нанесение логотипов на ежедневники</a></li>
                                                <li><a href="/pechat-na-futbolkax-optom/">Печать на футболках оптом</a></li>
                                            </ul>
                                        </div>



                                    </li>
                                    <li itemprop="name"><a itemprop="url" href="/otzyvy/">Отзывы</a>

                                    </li>
                                    <li itemprop="name"><a itemprop="url" href="/brendy/">Бренды</a>

                                    </li>
                                    <li itemprop="name"><a itemprop="url" href="/o-kompanii/">О компании</a>
                                        <span class="open icon_arrow"></span>
                                        <div class="sub-menu justify-content-between">
                                            <ul>
                                                <li><a href="/novosti/">Новости и статьи</a></li>
                                                <li><a href="/sotrudniki/">Сотрудники</a></li>
                                                <li><a href="/vakansii.php">Вакансии</a></li>
                                                <li><a href="/feedback/">Обратная связь</a></li>
                                                <li><a href="/clients/">Портфолио</a></li>
                                            </ul>
                                        </div>



                                    </li>
                                    <li itemprop="name"><a itemprop="url" href="/oplata/">Оплата</a>

                                    </li>
                                    <li itemprop="name"><a itemprop="url" href="/dostavka/">Доставка</a>

                                    </li>
                                    <li itemprop="name"><a itemprop="url" href="/informacziya-dlya-dilerov/">Для дилеров</a>
                                        <span class="open icon_arrow"></span>
                                        <div class="sub-menu justify-content-between">
                                            <ul>
                                                <li><a href="/razrabotka-suvenirnoj-produkcii.php">Заявка на разработку сувенирной продукции</a></li>
                                            </ul>
                                        </div>



                                    </li>
                                    <li itemprop="name"><a itemprop="url" href="/kontaktyi/">Контакты</a>

                                    </li>
                                    <li itemprop="name"><a itemprop="url" href="/ispolzovanie-vstroennyix-texnologij-sajta/">FAQ</a>




                                    </li>
                                    <li itemprop="name"><a itemprop="url" href="/dejstvuyushhie-akcii/">Акции и скидки</a> </li>

                                    <li itemprop="name"><a itemprop="url" href="/novinki/">Новинки</a></li>
                                    <li itemprop="name"><a itemprop="url" href="/rasprodaja/">Распродажа</a></li>
                                </ul>
                                <a class="telegram-header" href="https://t.me/eklektikaru">
                                    <img src="<?=SITE_TEMPLATE_PATH?>/assets/img/basil_telegram-outline.svg" alt="telegram">
                                </a>
                                <div class="to-down-btn js-btn-down">Вниз</div>
                                <div class="mob-contact-block contact-head-block">
                                    <div class="phone-wrap-h"><a class="phone" href="tel:+78007774723">+7 (800) 777-47-23</a><a class="phone" href="tel:+74951197598">+7 (495) 119-75-98</a> </div>
                                    <span class="text-time-work">пн-пт 9:30 - 18:00 МСК<br><a href="mailto:team@eklektika.ru" class="mail-h">team@eklektika.ru</a></span>
                                    <a href="#callback" class="fancybox link-callback">Заказать звонок</a>
                                    <a class="telegram-icon" href="#"></a>
                                </div>
                            </div>

                            <style>
                                .mob-menu-pages .sub-menu ul {
                                    height: unset;
                                }
                                .mob-menu-pages .sub-menu{
                                    display:none;
                                }
                                .icon_arrow::before{
                                    display: block;
                                    position: relative;
                                    height: 23px;
                                    width: 23px;
                                    content:"↓";
                                    left: 37%;
                                    bottom: 19px;
                                }
                                .icon_arrow_on::before{
                                    display: block;
                                    position: relative;
                                    height: 23px;
                                    width: 23px;
                                    content:"↑";
                                    left: 37%;
                                    bottom: 19px;
                                }
                            </style>

                            <script>
                                $(document).ready(function(){
                                    $(".open").click(function(){

                                        if($(this).next().css("display") == 'none'){
                                            $(this).next().show();
                                            $(this).removeClass('icon_arrow');
                                            $(this).addClass('icon_arrow_on');
                                        }else{
                                            $(this).next().hide();
                                            $(this).removeClass('icon_arrow_on');
                                            $(this).addClass('icon_arrow');
                                        }

                                    })
                                });
                            </script>
                            <!-- END catalog cats -->
                        </div>
                        <?$APPLICATION->IncludeComponent(
                            "bitrix:search.form",
                            "header-search-form",
                            Array(
                                "USE_SUGGEST" => "N"
                            ),
                            false
                        );?>
                        <a href="#sendmessage" class="d-inline-block fb-btn fancybox">

                            <span class="fb-title">Запросить расчет</span>
                        </a>
                        <span class="border-line"></span>
                        <a href="/cart.php" class="top-cart full" id="cart-menu-btn">
                            <span class="cart-icon"></span>
                            <span class="cart-title">Корзина</span>
                        </a>
                        <span class="border-line"></span>
                        <a href="<?=($USER->IsAuthorized()) ? '/personal/lichnyj-kabinet.php' : '/personal/vhod.php'?>" id="" class="profile-login"><span class="profile-login-icon"></span><span class="profile-login-title"><?=($USER->IsAuthorized()) ? 'Кабинет' : 'Войти'?></span></a>
                    </div>
                    <div class="cant-order_header container-wrap">
                        <span style="color: red;">Мин. заказ 50 000 р.</span>
                    </div>
                </div>
                <? if ($APPLICATION->GetCurPage() != '/') { ?>
                    <div class="bottom-head-line">
                        <div class="container-wrap">
                            <nav class="navigation ">
                                <ul class="menu" itemscope="" itemtype="http://www.schema.org/SiteNavigationElement">
                                        <li itemprop="name"><a itemprop="url" style="color: red;" href="<?=SITE_URL?>/yoliba/">Yoliba </a></li>
                                        <li itemprop="name"><a itemprop="url" href="<?=SITE_URL?>/eklektika_primo/">Отгрузка в день заказа</a></li>
                                        <li itemprop="name"><a itemprop="url" href="/dejstvuyushhie-akcii/">Акции и скидки</a>
                                            <div class="sub-menu justify-content-between">
                                                <ul>
                                                    <li><a href="/dejstvuyushhie-akcii/darim-podarki-za-zakaz/">Дарим подарки за заказ  ! </a></li>
                                                    <li><a href="/dejstvuyushhie-akcii/superskidka-40-na-podborku-podarkov/">Суперскидка 40% на подборку подарков</a></li>
                                                </ul>
                                            </div>
                                        </li>
                                        
                                </ul>  
                            </nav>
                        </div>
                    </div>
                <? } ?>
            </header>
            <div style="display: none">
                <header class="header custom-wrap-head">
                    <section itemscope itemtype="http://schema.org/WPHeader" style="display:none;">
                        <p itemprop="name">Эклектика</p>
                        <p itemprop="description">нанесение логотипов на сувенирную продукцию</p>
                    </section>
                    <style>
                        .top-head-line1{ background: #04a7e2; padding: 20px 0; z-index: 1000; position: relative;}

                        .header.custom-wrap-head .navigation .menu a
                        { padding-left: 7px;
                            padding-right: 7px;
                            color: #000000;
                            font-weight: 400;
                            font-size: 15px;
                            line-height: 18px;}

                        .phone1, .phone1 a { text-decoration: none;font-size: 14px;}
                        .text-time-work1,.text-time-work1 a{color: #ffffff;text-decoration: none; font-size: 14px; }
                        .mail-h1 a {color: #ffffff; text-decoration: none;font-size: 14px; }
                        .blok img{margin-right: 8px;margin-left: 2px;}
                        .blok {display:block; width:220px;position: relative; }
                        .blok1 {display: flex;  }.blok2 { display: flex; flex-wrap: wrap;  }


                        .search-head-wrap .search input {
                            background: #Ffffff;
                            border: 2px solid #00A0E3 ;
                            border-radius: 40px;
                            font-weight: 400;
                            font-size: 16px;
                            line-height: 18px;
                            color: #828486;
                            height: 50px;
                        }

                        .header .fb-btn .fb-title {
                            border: 2px solid #828486;
                            color: #828486;
                            background: transparent;
                            display: flex
                        ;
                            padding: 10px;
                            height: 50px;
                            justify-content: center;
                            align-items: center;
                            font-weight: 700;
                            font-size: 16px;
                            line-height: 18px;
                            border-radius: 30px;
                            max-width: 100%;
                            min-width: 166px;
                            box-sizing: border-box;
                            cursor: pointer;
                            transition: all 0.5s;
                            text-decoration: none;
                            text-align: center;
                        }

                        .item-title {
                            color: #000000;
                            text-transform: uppercase;
                            font-size: 26px;
                            font-weight: bold;
                            line-height: 1.1;
                        }
                        @media (max-width: 802px) and (min-width: 769px) {
                            .header-soc__cont {margin-top: 10px;}
                        }
                        @media (max-width: 768px)
                        {

                            .top-head-line1{ background: #04a7e2; padding: 5 px 0; z-index: 1000; position: relative;}
                            .blok {display:block; width:150px; position: relative;  }
                            .blok1 { display: flex; flex-direction: column}
                            .blok2 { display: flex; width:450px;  justify-content: flex-end;}
                            .blok img{  display: none;}
                            .phone1, .phone1 a {text-decoration: none;font-size: 13px;}
                            .text-time-work1,.text-time-work1 a{color: #ffffff;text-decoration: none; font-size: 13px; }
                            .mail-h1 a {color: #ffffff; text-decoration: none;font-size: 13px; }


                            @media (max-width: 478px) {
                                .blok2 {
                                    width: 100%;
                                }
                                .blok {
                                    width: 135px;
                                }
                                .header-soc__cont {
                                    margin-top: 10px;
                                }
                                .header-soc__cont {margin-top: 10px;}
                            }


                        }

                    </style>
                    <div class="top-head-line1">
                        <div class="container-wrap flex-wrapper align-items-center">
                            <div class="contact-head-block"><div class=blok1>
                                    <a href="/" class="logo-mob">
                                        <img data-src="..\logoe.jpg" alt="Эклектика - нанесение логотипов на сувенирную продукцию">
                                    </a><div class=blok2>
                                        <div class=blok> <img src="<?=SITE_TEMPLATE_PATH?>/assets/img/tel1.png" align=left alt="phone"><div class="text-time-work1"><a class="phone1" href="tel:+74951295372">+7(495)129-53-72</a><br><a class="phone1" href="tel:+78007075211">+7(800)707-52-11</a></div> </div>
                                        <a href="#callback1" class="fancybox link-callback"></a>
                                        <div class=blok><img src="<?=SITE_TEMPLATE_PATH?>/assets/img/mail1.png" align=left alt="mail"><div class="text-time-work1">пн-пт 9:30 - 18:00 МСК<br><a href="mailto:team@eklektika.ru" class="mail-h1">team@eklektika.ru</a></div>
                                        </div>

                                        <div class="header-soc__cont">
                                            <a rel="nofollow noopener" class="telegram-header" href="https://t.me/eklektikaru">
                                                <img src="<?=SITE_TEMPLATE_PATH?>/assets/img/tg1.png" alt="telegram">
                                            </a>
                                            <a rel="nofollow noopener" class="telegram-header1" href="https://vk.com/eklektikaru">
                                                <img src="<?=SITE_TEMPLATE_PATH?>/assets/img/vk1.png" alt="VK">
                                            </a>
                                        </div>  </div> </div></div>





                            <nav class="navigation">
                                <ul class=" menu " itemscope itemtype="http://www.schema.org/SiteNavigationElement">
                                    <li itemprop="name"><a itemprop="url" href="/kak-zakazat/">Как заказать</a>
                                    </li>
                                    <li itemprop="name"><a itemprop="url" href="/nanesenie/">Нанесение</a>

                                        <div class="sub-menu justify-content-between">
                                            <ul>
                                                <li><a href="/lazernaya-gravirovka/">Лазерная гравировка</a></li>
                                                <li><a href="/dtf-pechat-na-tkani/">DTF печать на ткани</a></li>
                                                <li><a href="/tampopechat/">Услуги тампопечати</a></li>
                                                <li><a href="/tisnenie/">Тиснение</a></li>
                                                <li><a href="/polnocvetnaya-uf-pechat/">Полноцветная УФ-печать</a></li>
                                                <li><a href="/shelkografiya/">Шелкография на ткани</a></li>
                                                <li><a href="/izgotovlenie-shildikov/">Изготовление шильдиков</a></li>
                                                <li><a href="/markirovka-texnicheskoj-produkczii/">Маркировка технической продукции</a></li>
                                                <li><a href="/nanesenie-logotipov-na-ezhednevniki/">Нанесение логотипов на ежедневники</a></li>
                                                <li><a href="/pechat-na-futbolkax-optom/">Печать на футболках оптом</a></li>
                                            </ul>
                                        </div>


                                    </li>
                                    <li itemprop="name"><a itemprop="url" href="/otzyvy/">Отзывы</a>
                                    </li>
                                    <li itemprop="name"><a itemprop="url" href="/brendy/">Бренды</a>
                                    </li>
                                    <li itemprop="name"><a itemprop="url" href="/o-kompanii/">О компании</a>

                                        <div class="sub-menu justify-content-between">
                                            <ul>
                                                <li><a href="/novosti/">Новости и статьи</a></li>
                                                <li><a href="/sotrudniki/">Сотрудники</a></li>
                                                <li><a href="/vakansii.php">Вакансии</a></li>
                                                <li><a href="/feedback/">Обратная связь</a></li>
                                                <li><a href="/clients/">Портфолио</a></li>
                                            </ul>
                                        </div>


                                    </li>
                                    <li itemprop="name"><a itemprop="url" href="/oplata/">Оплата</a>
                                    </li>
                                    <li itemprop="name"><a itemprop="url" href="/dostavka/">Доставка</a>
                                    </li>
                                    <li itemprop="name"><a itemprop="url" href="/informacziya-dlya-dilerov/">Для дилеров</a>

                                        <div class="sub-menu justify-content-between">
                                            <ul>
                                                <li><a href="/razrabotka-suvenirnoj-produkcii.php">Заявка на разработку сувенирной продукции</a></li>
                                            </ul>
                                        </div>


                                    </li>
                                    <li itemprop="name"><a itemprop="url" href="/kontaktyi/">Контакты</a>
                                    </li>
                                    <li itemprop="name"><a itemprop="url" href="/ispolzovanie-vstroennyix-texnologij-sajta/">FAQ</a>



                                    </li>
                                </ul>
                            </nav>

                        </div>
                    </div>
                    <div class="middle-head-line">
                        <div class="container-wrap flex-wrapper pos-relative align-items-center">
                            <a href="/" class="logo">
                                <img class="lazy-loaded" data-src="<?=SITE_TEMPLATE_PATH?>/assets/img/logo-eklektika.png" alt="Эклектика - нанесение логотипов на сувенирную продукцию">
                            </a>
                            <div class="menu-catalog-container">
                                <a href="" class="btn-menu-catalog">
                                    <span class="icon-catalogs"></span> <span class="desk-btn-text">Каталог</span><span class="mob-btn-text">Меню</span>
                                </a>
                                <div class="desktop-catalog">
                                    <div class="mob-search-block flex-wrapper">
                                        <div class="search-head-wrap">
                                            <form action="/catalog/" class="search" id="main-search-form2">
                                                <fieldset>
                                                    <input type="text" name="q" autocomplete="off" class=simple-poisk placeholder="Поиск"
                                                           required value="">
                                                    <button type="submit" aria-label="искать" class="search-btn">

                                                    </button>
                                                </fieldset>
                                                <div class="search-sub-results">
                                                    <div class="row" id=kategort2">
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                        <a href="<?=($USER->IsAuthorized()) ? '/personal/lichnyj-kabinet.php' : '/personal/vhod.php'?>" id="" class="profile-login"><span class="profile-login-icon"></span><span class="profile-login-title"><?=($USER->IsAuthorized()) ? 'Кабинет' : 'Войти'?></span></a>
                                    </div>
                                    <div class="to-top-btn js-btn-up">Вверх</div>
                                    <div class="show-mob-catalog js-show-mob-catalog">Каталог</div>
                                    <ul class="catalog">
                                        <li class="back-link-catalog">&#8592; Назад</li>
                                        <li><a href="/novii_god_i_rojdestvo/"> Новый год и рождество </a>


                                            <ul class="subcatalog">
                                                <li class="back-link">&#8592; Назад</li>
                                                <li  ><a href="/novogodnie-novinki.php">Новогодние новинки 2025-2026</a></li>

                                                <li  ><a href="/yolochnie_shari/">Ёлочные шары</a></li>

                                                <li  ><a href="/novogodnie-girlyandy.php">Новогодние ели и гирлянды</a></li>

                                                <li  ><a href="/novogodnie-eko-igrushki/">Новогодние ЭКО сувенниры</a></li>

                                                <li  ><a href="/novogodnie_igryshki/">Новогодние игрушки</a></li>

                                                <li  ><a href="/novogodnie_svechi/">Новогодние свечи</a></li>

                                                <li  ><a href="/novogodnie_syveniri/">Новогодние подарки и сувениры</a></li>

                                                <li  ><a href="/novogodnie_podarochnie_nabori/">Новогодние подарочные наборы</a></li>

                                                <li  ><a href="/simvol_goda/">Символ года</a></li>

                                                <li  ><a href="/novogodnyaya_ypakovka/">Новогодняя упаковка</a></li>


                                            </ul>
                                        </li>

                                        <li><a href="/eklektika_primo/"> Быстрая поставка </a>


                                            <ul class="subcatalog">
                                                <li class="back-link">&#8592; Назад</li>
                                                <li  ><a href="/ezhednevniki-i-ruchki-za-1-den/">Ежедневники за 1 день!</a></li>

                                                <li  ><a href="/zonty-po-specpredlozheniyu/">Зонты за один день </a></li>

                                                <li  ><a href="/novogodnie-podarki-i-suveniry/">Новогодние подарки и сувениры в день заказа</a></li>

                                                <li  ><a href="/termostakany-i-termokruzhki-s-vashim-logotipom/">Посуда </a></li>

                                                <li  ><a href="/rasprodazha-s-maksimalnoj-skidkoj/">Распродажа: скидки до 80% !</a></li>


                                            </ul>
                                        </li>

                                        <li><a href="/zonti/"> Зонты </a>


                                            <ul class="subcatalog">
                                                <li class="back-link">&#8592; Назад</li>
                                                <li  ><a href="/zonti-trost/">Зонты-трость</a></li>

                                                <li  ><a href="/zonti_originalnie/">Зонты оригинальные</a></li>

                                                <li  ><a href="/zonti_jenskie/">Зонты женские</a></li>

                                                <li  ><a href="/zonti_myjskie/">Зонты мужские</a></li>

                                                <li  ><a href="/zonti_skladnie/">Зонты складные</a></li>

                                                <li  ><a href="/dorogie_zonti/">Дорогие зонты</a></li>


                                            </ul>
                                        </li>

                                        <li><a href="/ejednevniki/"> Ежедневники и блокноты </a>


                                            <ul class="subcatalog">
                                                <li class="back-link">&#8592; Назад</li>
                                                <li  ><a href="/ezhednevniki-po-individualnomu-dizajnu1/">Ежедневники по индивидуальному дизайну</a></li>

                                                <li  ><a href="/brunovisconti/">BrunoVisconti</a></li>

                                                <li  ><a href="/infolio/">Infolio</a></li>

                                                <li  ><a href="/portobello_trend/">Portobello Trend</a></li>

                                                <li  ><a href="/bloknoti/">Блокноты</a></li>

                                                <li  ><a href="/ejednevniki_happy_book/">Ежедневники Happy Book</a></li>

                                                <li  ><a href="/ejednevniki_datirovannie/">Ежедневники датированные</a></li>

                                                <li  ><a href="/ejednevniki_nedatirovannie/">Ежедневники недатированные</a></li>

                                                <li  ><a href="/ejednevniki_polydatirovannie/">Ежедневники полудатированные</a></li>

                                                <li  ><a href="/ejednevniki_tm_adutant/">Ежедневники ТМ &quot;Адъютант&quot;</a></li>

                                                <li  ><a href="/ejenedelniki/">Еженедельники</a></li>

                                                <li  ><a href="/zapisnie_knigi/">Записные книги</a></li>

                                                <li  ><a href="/kojanie_ejednevniki/">Кожаные ежедневники</a></li>

                                                <li  ><a href="/organaizeri/">Органайзеры</a></li>

                                                <li  ><a href="/planingi/">Планинги</a></li>

                                                <li  ><a href="/ypakovka_dlya_ejednevnikov/">Упаковка для ежедневников</a></li>


                                            </ul>
                                        </li>

                                        <li><a href="/termosy-termokruzhki-lanchboksy/"> Термосы и бутылки </a>


                                            <ul class="subcatalog">
                                                <li class="back-link">&#8592; Назад</li>
                                                <li  ><a href="/sportivnie_bytilki/">Спортивные бутылки</a></li>

                                                <li  ><a href="/termokryjki/">Термокружки</a></li>

                                                <li  ><a href="/termosi/">Термосы</a></li>

                                                <li  ><a href="/lanchboksy/">Ланчбоксы</a></li>

                                                <li  ><a href="/cofery/">Коферы</a></li>


                                            </ul>
                                        </li>

                                        <li><a href="/kryjki/"> Кружки </a>


                                            <ul class="subcatalog">
                                                <li class="back-link">&#8592; Назад</li>
                                                <li  ><a href="/kruzhki-s-vashim-logotipom/">Кружки с логотипом </a></li>

                                                <li  ><a href="/gjel_rezerv/">Эко кружки</a></li>

                                                <li  ><a href="/keramicheskie_kryjki/">Керамические кружки</a></li>

                                                <li  ><a href="/kryjki_dlya_syblimacii/">Кружки для сублимации</a></li>

                                                <li  ><a href="/kryjki_hameleon/">Кружки Хамелеон</a></li>

                                                <li  ><a href="/metallicheskie_kryjki/">Металлические кружки</a></li>

                                                <li  ><a href="/plastikovie_kryjki/">Пластиковые кружки</a></li>

                                                <li  ><a href="/kofery/">Кружки для кофе</a></li>

                                                <li  ><a href="/steklyannie_kryjki/">Стеклянные кружки</a></li>


                                            </ul>
                                        </li>

                                        <li><a href="/elektronika/"> Электроника </a>


                                            <ul class="subcatalog">
                                                <li class="back-link">&#8592; Назад</li>
                                                <li  ><a href="/zaryadnie_ystroistva_power_bank/">Зарядные устройства &quot;Power Bank&quot;</a></li>

                                                <li  ><a href="/besprovodnie_zaryadnie_ystroistva/">Беспроводные зарядные устройства</a></li>

                                                <li  ><a href="/nayshniki/">Наушники</a></li>

                                                <li  ><a href="/besprovodnie_portativnie_kolonki/">Беспроводные портативные колонки</a></li>

                                                <li  ><a href="/aksessyari_dlya_telefona/">Аксессуары для телефона</a></li>

                                                <li  ><a href="/aksessuary-dlya-kompyuterov-noutbukov-i-planshetov/">Аксессуары для компьютеров, ноутбуков и планшетов</a></li>

                                                <li  ><a href="/kalkylyatori/">Калькуляторы</a></li>

                                                <li  ><a href="/monopodi/">Моноподы</a></li>

                                                <li  ><a href="/ymnii_dom/">Умный дом</a></li>


                                            </ul>
                                        </li>

                                        <li><a href="/fleshki/"> Флешки </a>


                                            <ul class="subcatalog">
                                                <li class="back-link">&#8592; Назад</li>
                                                <li  ><a href="/usb_fleshki__4_gb/">USB флешки  4 GB</a></li>

                                                <li  ><a href="/usb_fleshki__8_gb/">USB флешки  8 GB</a></li>

                                                <li  ><a href="/usb_fleshki_128_gb/">USB флешки 128 GB</a></li>

                                                <li  ><a href="/usb_fleshki_16_gb/">USB флешки 16 GB</a></li>

                                                <li  ><a href="/usb_fleshki_32_gb/">USB флешки 32 GB</a></li>

                                                <li  ><a href="/usb_fleshki_64_gb/">USB флешки 64 GB</a></li>

                                                <li  ><a href="/jyostkii_disk/">Жёсткий диск</a></li>

                                                <li  ><a href="/fleshki_pylya/">Флешки Пуля</a></li>

                                                <li  ><a href="/derevyannie_fleshki/">Деревянные флешки</a></li>

                                                <li  ><a href="/originalnie_usb_fleshki/">Оригинальные USB флешки</a></li>

                                                <li  ><a href="/fleshki_soft_touch/">Флешки SOFT TOUCH</a></li>

                                                <li  ><a href="/fleshki_v_vide_karti/">Флешки в виде карты</a></li>

                                                <li  ><a href="/fleshki_kluch/">Флешки Ключ</a></li>

                                                <li  ><a href="/fleshki_kojanie/">Флешки кожаные</a></li>

                                                <li  ><a href="/fleshki_metallicheskie/">Флешки металлические</a></li>

                                                <li  ><a href="/fleshki_s_podsvetkoi/">Флешки с подсветкой</a></li>


                                            </ul>
                                        </li>

                                        <li><a href="/rychki/"> Ручки с логотипом </a>


                                            <ul class="subcatalog">
                                                <li class="back-link">&#8592; Назад</li>
                                                <li  ><a href="/antibakterialnye-ruchki/">Антибактериальные ручки</a></li>

                                                <li  ><a href="/ruchki-s-vashim-logotipom/">Ручки с Вашим логотипом </a></li>

                                                <li  ><a href="/ruchki-po-specpredlozheniyu/">Ручки по СПЕЦПРЕДЛОЖЕНИЮ</a></li>

                                                <li  ><a href="/rychki_metallicheskie/">Ручки металлические</a></li>

                                                <li  ><a href="/rychki_plastikovie/">Ручки пластиковые</a></li>

                                                <li  ><a href="/rychki_predstavitelskie/">Ручки представительские</a></li>

                                                <li  ><a href="/rychki_klio-eterna/">Ручки Klio-Eterna</a></li>

                                                <li  ><a href="/rychki-maxema/">ручки-Maxema</a></li>

                                                <li  ><a href="/rychki_prodir/">Ручки Prodir</a></li>

                                                <li  ><a href="/rychki_senator/">Ручки Senator</a></li>

                                                <li  ><a href="/rychki_soft_touch/">Ручки SOFT TOUCH</a></li>

                                                <li  ><a href="/eko-rychki/">ЭКО-ручки</a></li>

                                                <li  ><a href="/rychki_i_markeri_uma/">Ручки и маркеры UMA</a></li>

                                                <li  ><a href="/nabori_rychek/">Наборы ручек</a></li>

                                                <li  ><a href="/karandashi/">Карандаши</a></li>

                                                <li  ><a href="/markeri/">Маркеры</a></li>


                                            </ul>
                                        </li>

                                        <li><a href="/promo_symki/"> Сумки с логотипом </a>


                                            <ul class="subcatalog">
                                                <li class="back-link">&#8592; Назад</li>
                                                <li  ><a href="/symki_dlya_pokypok_i_plyaja/">Шопперы и сумки для пляжа</a></li>

                                                <li  ><a href="/aksessuary-dlya-brendirovaniya-ryukzakov-i-sumok/">Аксессуары для брендирования рюкзаков  и сумок</a></li>

                                                <li  ><a href="/holshovie_symki/">Холщовые сумки</a></li>

                                                <li  ><a href="/sumki-poyasnye/">Сумки поясные</a></li>

                                                <li  ><a href="/rukzaki/">Рюкзаки</a></li>

                                                <li  ><a href="/kosmetichki/">Косметички</a></li>

                                                <li  ><a href="/symki_dlya_konferencii/">Сумки для конференций</a></li>

                                                <li  ><a href="/symki_i_chehli_dlya_noytbyka/">Сумки и чехлы для ноутбука</a></li>

                                                <li  ><a href="/dorojnie_symki/">Дорожные сумки</a></li>

                                                <li  ><a href="/symki-holodilniki/">Сумки-холодильники</a></li>

                                                <li  ><a href="/portfeli/">Портфели</a></li>


                                            </ul>
                                        </li>

                                        <li><a href="/promo_odejda/"> Promo одежда </a>


                                            <ul class="subcatalog">
                                                <li class="back-link">&#8592; Назад</li>
                                                <li  ><a href="/fytbolki/">Футболки</a></li>

                                                <li  ><a href="/dojdeviki/">Дождевики</a></li>

                                                <li  ><a href="/panamy/">Панамы</a></li>

                                                <li  ><a href="/svitshoty-i-hudi/">Свитшоты и Худи</a></li>

                                                <li  ><a href="/polo/">Поло</a></li>

                                                <li  ><a href="/beisbolki/">Бейсболки</a></li>

                                                <li  ><a href="/vetrovki/">Ветровки</a></li>

                                                <li  ><a href="/detskii_tekstil/">Детский текстиль</a></li>

                                                <li  ><a href="/zhilety-s-logotipom/">Жилеты</a></li>

                                                <li  ><a href="/tolstovki/">Толстовки</a></li>

                                                <li  ><a href="/kyrtki/">Куртки</a></li>

                                                <li  ><a href="/rybashki_ofisnie/">Рубашки офисные</a></li>

                                                <li  ><a href="/krasota_i_stil_jizni/">Брюки</a></li>

                                                <li  ><a href="/platki/">Платки</a></li>

                                                <li  ><a href="/fartyki/">Фартуки</a></li>

                                                <li  ><a href="/aksessyari/">Аксессуары</a></li>

                                                <li  ><a href="/shapki/">Шапки</a></li>

                                                <li  ><a href="/sharfi/">Шарфы</a></li>

                                                <li  ><a href="/galstyki/">Галстуки</a></li>


                                            </ul>
                                        </li>

                                        <li><a href="/promosyveniri/"> Промосувениры </a>


                                            <ul class="subcatalog">
                                                <li class="back-link">&#8592; Назад</li>
                                                <li  ><a href="/antistressi/">Антистрессы</a></li>

                                                <li  ><a href="/breloki/">Брелоки</a></li>

                                                <li  ><a href="/promo-suveniry-do-50-rub/">Промо сувениры до 50 руб</a></li>

                                                <li  ><a href="/zajigalki/">Зажигалки</a></li>

                                                <li  ><a href="/zerkala/">Зеркала и косметика</a></li>

                                                <li  ><a href="/neobychnye-podarki-i-kreativnye-suveniry/">Оригинальные сувениры и необычные подарки с логотипом</a></li>

                                                <li  ><a href="/lenti_dlya_beidjei/">Ленты для бейджей</a></li>

                                                <li  ><a href="/ochki_i_binokli/">Очки и бинокли</a></li>

                                                <li  ><a href="/peshehodnye-svetootrazhateli/">Пешеходные светоотражатели</a></li>

                                                <li  ><a href="/ryletki/">Рулетки</a></li>

                                                <li  ><a href="/sredstva-individualnoj-zashhity/">Защита здоровья</a></li>

                                                <li  ><a href="/syveniri_do_500_ryblei/">Сувениры до 500 рублей</a></li>

                                                <li  ><a href="/tovari_dlya_syblimacii/">Товары для сублимации</a></li>

                                                <li  ><a href="/fonari/">Фонари</a></li>


                                            </ul>
                                        </li>

                                        <li><a href="/firma/"> Офис и бизнес </a>


                                            <ul class="subcatalog">
                                                <li class="back-link">&#8592; Назад</li>
                                                <li  ><a href="/zonti2/">Календари, стикеры, наборы бумаг</a></li>

                                                <li  ><a href="/symki_i_otdih/">Канцелярские товары</a></li>

                                                <li  ><a href="/ofisnie_prinadlejnosti_i_biznes-aksessyari/">Офисные принадлежности и бизнес-аксессуары</a></li>

                                                <li  ><a href="/pishyshie_prinadlejnosti/">Папки, обложки, тетради</a></li>

                                                <li  ><a href="/podstavki_dlya_vizitok/">Подставки для визиток</a></li>


                                                <li  ><a href="/vizitnici/">Визитницы</a></li>

                                                <li  ><a href="/kanctovari/">Настольные органайзеры и подставки для ручек</a></li>

                                                <li  ><a href="/chasi_i_elektronika/">Часы для офиса</a></li>


                                                <li><a href="<?=SITE_URL?>/podarochnaya_ypakovka/">Упаковка(резерв)</a></li>

                                            </ul>
                                        </li>

                                        <li><a href="/pyteshestvie/"> Путешествие и отдых </a>


                                            <ul class="subcatalog">
                                                <li class="back-link">&#8592; Назад</li>
                                                <li  ><a href="/naboridlya_avtomobilya/">Аксессуары для автомобиля</a></li>

                                                <li  ><a href="/aksessyari_dlya_piknika_i_barbeku/">Аксессуары для пикника и барбекю</a></li>

                                                <li  ><a href="/golovolomki/">Головоломки</a></li>

                                                <li  ><a href="/chehli_dlya_chemodanov/">Чехлы для чемоданов</a></li>

                                                <li  ><a href="/plyajnie_prinadlejnosti/">Пляжные принадлежности</a></li>

                                                <li  ><a href="/flyajki/">Фляжки</a></li>

                                                <li  ><a href="/letnie-suveniry-i-podarki/">Летние сувениры и подарки</a></li>


                                            </ul>
                                        </li>

                                        <li><a href="/dom_i_obraz_jizni/"> Дом и образ жизни </a>


                                            <ul class="subcatalog">
                                                <li class="back-link">&#8592; Назад</li>
                                                <li  ><a href="/instrymenti_rychnie/">Инструменты ручные</a></li>

                                                <li  ><a href="/vazi/">Вазы</a></li>

                                                <li  ><a href="/derevyannye-svetilniki-i-levitiruyushhie-lampy.php">Деревянные светильники и  левитирующие лампы</a></li>

                                                <li  ><a href="/vinnie_i_kyritelnie_aksessyari/">Винные и курительные аксессуары</a></li>

                                                <li  ><a href="/interer/">Интерьер</a></li>

                                                <li  ><a href="/nabori_dlya_chistki_obyvi/">Наборы для чистки обуви</a></li>

                                                <li  ><a href="/pledi/">Пледы</a></li>

                                                <li  ><a href="/polotenca/">Полотенца</a></li>

                                                <li  ><a href="/chasi/">Часы и погодные станции</a></li>

                                                <li  ><a href="/svechi/">Свечи</a></li>

                                                <li  ><a href="/uvlazhniteli-vozduha/">Забота о безопасности здоровья</a></li>

                                                <li  ><a href="/fotoramki_i_fotoalbomi/">Фоторамки и фотоальбомы</a></li>

                                                <li  ><a href="/sad/">Сад</a></li>

                                                <li  ><a href="/posyda/">Посуда и товары для кухни</a></li>


                                            </ul>
                                        </li>

                                        <li><a href="/biznes-podarki/"> Бизнес-подарки </a>


                                            <ul class="subcatalog">
                                                <li class="back-link">&#8592; Назад</li>
                                                <li  ><a href="/nagradnaya_prodykciya/">Наградная продукция</a></li>

                                                <li  ><a href="/bymajniki/">Бумажники</a></li>

                                                <li  ><a href="/podarki_dlya_myjchin/">Подарки для мужчин</a></li>

                                                <li  ><a href="/dlya_milih_dam/">Для милых дам</a></li>

                                                <li  ><a href="/kalendari/">Календари</a></li>

                                                <li  ><a href="/papki/">Папки</a></li>

                                                <li  ><a href="/podarki_kollegam/">Подарки коллегам</a></li>

                                                <li  ><a href="/rossiiskii_podarok/">Российский подарок</a></li>

                                                <li  ><a href="/syveniri_nastolnie/">Сувениры настольные</a></li>

                                                <li  ><a href="/chasy-v-podarok/">Часы в подарок</a></li>

                                                <li  ><a href="/vip_podarki/">VIP подарки</a></li>


                                            </ul>
                                        </li>

                                        <li><a href="/podarochnie_nabori/"> Подарочные наборы </a>


                                            <ul class="subcatalog">
                                                <li class="back-link">&#8592; Назад</li>
                                                <li  ><a href="/xindao_rezerv/">Вкусные подарочные наборы</a></li>

                                                <li  ><a href="/nabori_s_ejednevnikami/">Наборы с ежедневниками</a></li>

                                                <li  ><a href="/podarki-dlya-sotrudnikov-na-karantine/">Подарки для сотрудников на карантине</a></li>

                                                <li  ><a href="/suveniry-rabotayu-iz-doma/">Сувениры на удаленке</a></li>

                                                <li  ><a href="/podarochnie_nabori_dlya_ofisa/">Подарочные наборы для офиса</a></li>

                                                <li  ><a href="/podarochnie_nabori_dlya_myjchin/">Подарочные наборы для мужчин</a></li>

                                                <li  ><a href="/podarochnie_nabori_dlya_dam/">Подарочные наборы для дам</a></li>

                                                <li  ><a href="/nabory-s-zaryadnym-ustrojstvom/">Наборы с зарядным устройством</a></li>

                                                <li  ><a href="/nabory-s-kolonkami/">Наборы с колонками</a></li>

                                                <li  ><a href="/nabory-s-pledami/">Наборы с пледами</a></li>

                                                <li  ><a href="/nabory-s-termokruzhkami/">Наборы с термокружками</a></li>

                                                <li  ><a href="/nabory-s-fleshkami/">Наборы с флешками</a></li>


                                            </ul>
                                        </li>

                                        <li><a href="/podarki_i_syveniri_k_prazdnikam/"> Подарки и сувениры к праздникам </a>


                                            <ul class="subcatalog">
                                                <li class="back-link">&#8592; Назад</li>
                                                <li  ><a href="/podarki-i-suveniry-na-den-stroitelya/">День Строителя</a></li>

                                                <li  ><a href="/podarki_i_syveniri_k_9_maya/">Подарки и сувениры к 9 мая</a></li>

                                                <li  ><a href="/podarki-na-den-medika/">День Медицинского работника</a></li>

                                                <li  ><a href="/podarki_i_syveniri_k_23_fevralya/">Подарки и сувениры к 23 февраля</a></li>

                                                <li  ><a href="/den-znanij/">День Знаний</a></li>

                                                <li  ><a href="/podarki-i-suveniry-k-8-marta/">Подарки и сувениры к 8 марта</a></li>

                                                <li  ><a href="/podarki_i_syveniri_k_12_iunya/">Подарки и сувениры к 12 июня</a></li>

                                                <li  ><a href="/podarki_vipysknikam/">Подарки выпускникам</a></li>

                                                <li  ><a href="/den-aviacii/">День Авиации</a></li>

                                                <li  ><a href="/podarki-i-suveniry-na-den-buhgaltera/">День Бухгалтера</a></li>

                                                <li  ><a href="/podarki-i-suveniry-na-den-vmf/">День ВМФ</a></li>

                                                <li  ><a href="/podarki-i-suveniry-na-den-geologa/">День Геолога</a></li>

                                                <li  ><a href="/den-zheleznodorozhnika/">День железнодорожника</a></li>

                                                <li  ><a href="/den-molodezhi/">День Молодежи</a></li>

                                                <li  ><a href="/den-metallurga/">День Металлурга</a></li>

                                                <li  ><a href="/den-neftyanika/">День Нефтяника</a></li>

                                                <li  ><a href="/podarki-i-suveniry-na-den-policii/">День Полиции</a></li>

                                                <li  ><a href="/den-programmista/"> День Программиста</a></li>

                                                <li  ><a href="/suveniry-na-den-radio-i-svyazi/">День Радио и связи</a></li>

                                                <li  ><a href="/den-uchitelya/">День Учителя</a></li>

                                                <li  ><a href="/podarki-i-suveniry-na-den-himika/">День Химика</a></li>

                                                <li  ><a href="/podarki-i-suveniry-na-14-fevralya/">14 февраля</a></li>

                                                <li  ><a href="/den-shahtera/">День Шахтера</a></li>

                                                <li  ><a href="/den-sisadmina/">День Сисадмина</a></li>


                                            </ul>
                                        </li>

                                        <li><a href="/otdih/"> Сувениры и подарки для спортсменов </a>


                                            <ul class="subcatalog">
                                                <li class="back-link">&#8592; Назад</li>
                                                <li  ><a href="/odejda_dlya_sporta/">Одежда для спорта</a></li>

                                                <li  ><a href="/sportivnie_igri/">Спортивные игры</a></li>

                                                <li  ><a href="/sportivnie_symki/">Спортивные сумки</a></li>

                                                <li  ><a href="/tovari_dlya_fitnesa/">Спортивные товары</a></li>

                                                <li  ><a href="/fitnes_nabori/">Фитнес наборы</a></li>

                                                <li  ><a href="/fytbolnie_syveniri/">Футбольные сувениры</a></li>

                                                <li  ><a href="/shahmaty/">Шахматы</a></li>


                                            </ul>
                                        </li>

                                        <li><a href="/vkysniee_podarochnie_nabori/"> Вкусные подарки </a>


                                            <ul class="subcatalog">
                                                <li class="back-link">&#8592; Назад</li>
                                                <li  ><a href="/sladkie_podarki/">Сладкие подарки</a></li>

                                                <li  ><a href="/varene-i-myod/">Варенье, мед и мармелад</a></li>

                                                <li  ><a href="/dlya-glintvejna/">Для глинтвейна</a></li>

                                                <li  ><a href="/kofe-i-chaj/">Кофе и чай</a></li>

                                                <li  ><a href="/nabory-s-chaem-kofe-i-sladostyami/">Наборы с чаем, кофе и сладостями</a></li>

                                                <li  ><a href="/shokolad-i-konfety/">Шоколад и конфеты</a></li>


                                            </ul>
                                        </li>

                                        <li><a href="/detskie_tovari/"> Детские подарки </a>


                                            <ul class="subcatalog">
                                                <li class="back-link">&#8592; Назад</li>
                                                <li  ><a href="/detskie-nabory-v-period-karantina/">Детские подарки для on-line мероприятий</a></li>

                                                <li  ><a href="/igri_nastolnie/">Игры настольные</a></li>

                                                <li  ><a href="/igri_ylichnie/">Игры уличные</a></li>

                                                <li  ><a href="/myagkaya_igryshka/">Мягкая игрушка</a></li>

                                                <li  ><a href="/podarki_ychenikam/">Подарки ученикам</a></li>


                                            </ul>
                                        </li>

                                        <li><a href="/eko-syveniri/"> ЭКО-сувениры </a>


                                            <ul class="subcatalog">
                                                <li class="back-link">&#8592; Назад</li>
                                                <li  ><a href="/prodykti_pererabotki/">Продукты переработки</a></li>

                                                <li  ><a href="/eko-bloknoti/">ЭКО-блокноты</a></li>

                                                <li  ><a href="/eko-karandashi/">ЭКО-карандаши</a></li>

                                                <li  ><a href="/eko-kybi/">ЭКО-кубы</a></li>

                                                <li  ><a href="/eko-podarki/">ЭКО-подарки</a></li>

                                                <li  ><a href="/eko-symki/">ЭКО-сумки</a></li>

                                                <li  ><a href="/suveniry-iz-pererabotki/">Сувениры из переработанных материалов</a></li>


                                            </ul>
                                        </li>

                                        <li><a href="/podarochnaya_ypakovka/"> Подарочная упаковка </a>


                                            <ul class="subcatalog">
                                                <li class="back-link">&#8592; Назад</li>
                                                <li  ><a href="/ypakovka_dlya_usb_fleshek/">Упаковка для USB флешек</a></li>

                                                <li  ><a href="/pakety/">Пакеты </a></li>


                                                <li><a href="<?=SITE_URL?>/pakety-s-vashim-logotipom/">Пакеты с логотипом</a></li>

                                            </ul>
                                        </li>

                                        <li><a href="/torsheri_rezerv/"> Сувенирные бренды </a>


                                            <ul class="subcatalog">
                                                <li class="back-link">&#8592; Назад</li>
                                                <li  ><a href="/yoliba/">Yoliba</a></li>

                                                <li  ><a href="/brendy/brunovisconti/">BrunoVisconti</a></li>

                                                <li  ><a href="/lettertone/">Lettertone</a></li>

                                                <li  ><a href="/portobello-trend/">Portobello Trend</a></li>

                                                <li  ><a href="/prodir/">Prodir</a></li>

                                                <li  ><a href="/senator/">Senator</a></li>


                                            </ul>
                                        </li>

                                        <li><a href="">  </a>

                                        </li>


                                        <!-- BEGIN catalog cats -->

                                    </ul>
                                    <ul itemscope itemtype="http://www.schema.org/SiteNavigationElement" class="mob-menu-pages">
                                        <li itemprop="name"><a itemprop="url" style="color: red;" href="<?=SITE_URL?>/yoliba/">Yoliba </a></li>
                                        <li itemprop="name"><a itemprop="url" href="/kak-zakazat/">Как заказать</a>

                                        </li>
                                        <li itemprop="name"><a itemprop="url" href="/nanesenie/">Нанесение</a>
                                            <span class="open icon_arrow"></span>
                                            <div class="sub-menu justify-content-between">
                                                <ul>
                                                    <li><a href="/lazernaya-gravirovka/">Лазерная гравировка</a></li>
                                                    <li><a href="/dtf-pechat-na-tkani/">DTF печать на ткани</a></li>
                                                    <li><a href="/tampopechat/">Услуги тампопечати</a></li>
                                                    <li><a href="/tisnenie/">Тиснение</a></li>
                                                    <li><a href="/polnocvetnaya-uf-pechat/">Полноцветная УФ-печать</a></li>
                                                    <li><a href="/shelkografiya/">Шелкография на ткани</a></li>
                                                    <li><a href="/izgotovlenie-shildikov/">Изготовление шильдиков</a></li>
                                                    <li><a href="/markirovka-texnicheskoj-produkczii/">Маркировка технической продукции</a></li>
                                                    <li><a href="/nanesenie-logotipov-na-ezhednevniki/">Нанесение логотипов на ежедневники</a></li>
                                                    <li><a href="/pechat-na-futbolkax-optom/">Печать на футболках оптом</a></li>
                                                </ul>
                                            </div>



                                        </li>
                                        <li itemprop="name"><a itemprop="url" href="/otzyvy/">Отзывы</a>

                                        </li>
                                        <li itemprop="name"><a itemprop="url" href="/brendy/">Бренды</a>

                                        </li>
                                        <li itemprop="name"><a itemprop="url" href="/o-kompanii/">О компании</a>
                                            <span class="open icon_arrow"></span>
                                            <div class="sub-menu justify-content-between">
                                                <ul>
                                                    <li><a href="/novosti/">Новости и статьи</a></li>
                                                    <li><a href="/sotrudniki/">Сотрудники</a></li>
                                                    <li><a href="/vakansii.php">Вакансии</a></li>
                                                    <li><a href="/feedback/">Обратная связь</a></li>
                                                    <li><a href="/clients/">Портфолио</a></li>
                                                </ul>
                                            </div>



                                        </li>
                                        <li itemprop="name"><a itemprop="url" href="/oplata/">Оплата</a>

                                        </li>
                                        <li itemprop="name"><a itemprop="url" href="/dostavka/">Доставка</a>

                                        </li>
                                        <li itemprop="name"><a itemprop="url" href="/informacziya-dlya-dilerov/">Для дилеров</a>
                                            <span class="open icon_arrow"></span>
                                            <div class="sub-menu justify-content-between">
                                                <ul>
                                                    <li><a href="/razrabotka-suvenirnoj-produkcii.php">Заявка на разработку сувенирной продукции</a></li>
                                                </ul>
                                            </div>



                                        </li>
                                        <li itemprop="name"><a itemprop="url" href="/kontaktyi/">Контакты</a>

                                        </li>
                                        <li itemprop="name"><a itemprop="url" href="/ispolzovanie-vstroennyix-texnologij-sajta/">FAQ</a>




                                        </li>
                                        <li itemprop="name"><a itemprop="url" href="/dejstvuyushhie-akcii/">Акции и скидки</a> </li>

                                        <li itemprop="name"><a itemprop="url" href="/novinki/">Новинки</a></li>
                                        <li itemprop="name"><a itemprop="url" href="/rasprodaja/">Распродажа</a></li>
                                    </ul>
                                    <a class="telegram-header" href="https://t.me/eklektikaru">
                                        <img src="<?=SITE_TEMPLATE_PATH?>/assets/img/basil_telegram-outline.svg" alt="telegram">
                                    </a>
                                    <div class="to-down-btn js-btn-down">Вниз</div>
                                    <div class="mob-contact-block contact-head-block">
                                        <div class="phone-wrap-h"><a class="phone" href="tel:+78007774723">+7 (800) 777-47-23</a><a class="phone" href="tel:+74951197598">+7 (495) 119-75-98</a> </div>
                                        <span class="text-time-work">пн-пт 9:30 - 18:00 МСК<br><a href="mailto:team@eklektika.ru" class="mail-h">team@eklektika.ru</a></span>
                                        <a href="#callback" class="fancybox link-callback">Заказать звонок</a>
                                        <a class="telegram-icon" href="#"></a>
                                    </div>
                                </div>

                                <style>
                                    .mob-menu-pages .sub-menu ul {
                                        height: unset;
                                    }
                                    .mob-menu-pages .sub-menu{
                                        display:none;
                                    }
                                    .icon_arrow::before{
                                        display: block;
                                        position: relative;
                                        height: 23px;
                                        width: 23px;
                                        content:"↓";
                                        left: 37%;
                                        bottom: 19px;
                                    }
                                    .icon_arrow_on::before{
                                        display: block;
                                        position: relative;
                                        height: 23px;
                                        width: 23px;
                                        content:"↑";
                                        left: 37%;
                                        bottom: 19px;
                                    }
                                </style>

                                <script>
                                    $(document).ready(function(){
                                        $(".open").click(function(){

                                            if($(this).next().css("display") == 'none'){
                                                $(this).next().show();
                                                $(this).removeClass('icon_arrow');
                                                $(this).addClass('icon_arrow_on');
                                            }else{
                                                $(this).next().hide();
                                                $(this).removeClass('icon_arrow_on');
                                                $(this).addClass('icon_arrow');
                                            }

                                        })
                                    });
                                </script>
                                <!-- END catalog cats -->
                            </div>
                            <?$APPLICATION->IncludeComponent(
                                "bitrix:search.form",
                                "header-search-form",
                                Array(),
                                false
                            );?>
                            <a href="#sendmessage" class="d-inline-block fb-btn fancybox">

                                <span class="fb-title">Запросить расчет</span>
                            </a>
                            <span class="border-line"></span>
                            <a href="/cart.php" class="top-cart full" id="cart-menu-btn">
                                <span class="cart-icon"></span>
                                <span class="cart-title">Корзина</span>
                            </a>
                            <span class="border-line"></span>
                            <a href="<?=($USER->IsAuthorized()) ? '/personal/lichnyj-kabinet.php' : '/personal/vhod.php'?>" id="" class="profile-login"><span class="profile-login-icon"></span><span class="profile-login-title"><?=($USER->IsAuthorized()) ? 'Кабинет' : 'Войти'?></span></a>
                        </div>
                        <div class="cant-order_header container-wrap">
                            <span style="color: red;">Мин. заказ 50 000 р.</span>
                        </div>
                    </div>
                    <? if ($APPLICATION->GetCurPage() != '/') { ?>
                        <div class="bottom-head-line">
                            <div class="container-wrap">
                                <nav class="navigation ">
                                    <ul class="menu" itemscope="" itemtype="http://www.schema.org/SiteNavigationElement">
                                            <li itemprop="name"><a itemprop="url" style="color: red;" href="<?=SITE_URL?>/yoliba/">Yoliba </a></li>
                                            <li itemprop="name"><a itemprop="url" href="<?=SITE_URL?>/eklektika_primo/">Отгрузка в день заказа</a></li>
                                            <li itemprop="name"><a itemprop="url" href="/dejstvuyushhie-akcii/">Акции и скидки</a>
                                                <div class="sub-menu justify-content-between">
                                                    <ul>
                                                        <li><a href="/vsem-podarki-za-zakazy!/">Дарим подарки за заказ  ! </a></li>
                                                        <li><a href="/akciya-!-besplatnoe-nanesenie-logotipa-pri-zakaze-ot-80-000-rub/">АКЦИЯ ! Бесплатное нанесение логотипа при заказе от 80 000 руб  (завершена)</a></li>
                                                    </ul>
                                                </div>
                                            </li>
                                            
                                    </ul>  
                                </nav>
                            </div>
                        </div>
                    <? } ?>
                </header>
            </div>

            <!-- ####################################################### -->

            <!-- BEGIN middle -->

            <div class="middle <?=($APPLICATION->GetCurPage() == '/') ? "main container-wrap" : null;?>">
                <?php
                    $page = $APPLICATION->GetCurPage();

                    $fullExclude = ['/'];
                    $innerExact = [''];
                    $innerPartial = ['/search/'];

                    $skipOuter = in_array($page, $fullExclude);
                    $skipInner = in_array($page, $innerExact) || array_filter($innerPartial, fn($p) => strpos($page, $p) !== false);

                    if (!$skipOuter) {
                ?>
                        <?php
                            if(!$skipInner) :
                        ?>
                            <?$APPLICATION->IncludeComponent(
                                "bitrix:breadcrumb",
                                "main",
                                Array(
                                    "PATH" => "",
                                    "SITE_ID" => "s1",
                                    "START_FROM" => "0"
                                )
                            );?>
                        <?php
                            endif;
                        ?>

                        <?php
                            $additionalClass = '';
                            if (isset($GLOBALS['ADDITIONAL_WRAPPER_CLASSES'])) {
                                $additionalClass = trim($GLOBALS['ADDITIONAL_WRAPPER_CLASSES']);
                            }
                        ?>
                    <div class="middle-content <?= $additionalClass ? ' ' . htmlspecialchars($additionalClass) : '' ?>">
                        <?php
                            $showSystemTitle = $APPLICATION->GetDirProperty("SHOW_SYSTEM_TITLE");
                            if( isset($GLOBALS['SHOW_SYSTEM_TITLE']) )
                                $showSystemTitle = $GLOBALS['SHOW_SYSTEM_TITLE'];

                        ?>

                        <?php
                            if( $showSystemTitle !== 'N' ):
                        ?>
                            <h1><?$APPLICATION->ShowTitle(false);?></h1>

                            <?php $APPLICATION->ShowViewContent('after-title-description'); ?>
                        <?php
                            endif;
                        ?>
                <?php } ?>