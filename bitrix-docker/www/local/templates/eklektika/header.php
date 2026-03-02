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
                                    <img data-src="<?=SITE_TEMPLATE_PATH?>/assets/img/logoe.jpg" alt="Эклектика - нанесение логотипов на сувенирную продукцию">
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
                                <?php include $_SERVER["DOCUMENT_ROOT"] . "/include/header_catalog_menu.php"; ?>
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
                                        <li itemprop="name"><a itemprop="url" style="color: red;" href="/catalog/ofis_i_biznes/" rel="nofollow">Yoliba </a></li>
                                        <li itemprop="name"><a itemprop="url" href="<?=SITE_URL?>/eklektika_primo/" rel="nofollow">Отгрузка в день заказа</a></li>
                                        <li itemprop="name"><a itemprop="url" href="/dejstvuyushhie-akcii/" rel="nofollow">Акции и скидки</a>
                                            <div class="sub-menu justify-content-between">
                                                <ul>
                                                    <li><a href="/dejstvuyushhie-akcii/darim-podarki-za-zakaz/" rel="nofollow">Дарим подарки за заказ  ! </a></li>
                                                    <li><a href="/dejstvuyushhie-akcii/superskidka-40-na-podborku-podarkov/" rel="nofollow">Суперскидка 40% на подборку подарков</a></li>
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
                                        <img data-src="<?=SITE_TEMPLATE_PATH?>/assets/img/logoe.jpg" alt="Эклектика - нанесение логотипов на сувенирную продукцию">
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


                                    <?php include $_SERVER["DOCUMENT_ROOT"] . "/include/header_catalog_menu.php"; ?>


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