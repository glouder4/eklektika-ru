<?
$GLOBALS['ADDITIONAL_WRAPPER_CLASSES'] = 'content';
$GLOBALS['SHOW_SYSTEM_TITLE'] = "Y";

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Личный кабинет");
$APPLICATION->AddChainItem("Личный кабинет", "/personal/lichnyj-kabinet.php");
$APPLICATION->SetPageProperty("title", "Личный кабинет купить оптом в Москве | Эклектика – нанесение логотипов на заказ");
$APPLICATION->SetPageProperty("description", "Компания Эклектика предлагает Регистрация оптом под нанесение логотипа. ✓ Низкие цены. ✓ Доставка по России. ☎ 8(800) 777-4723");


if (!$USER->IsAuthorized()) {
    header("Location: /");
    exit();
}
?>

<div class="personal-profile">
    <?php
    require_once $_SERVER["DOCUMENT_ROOT"] . "/personal/include/personal-menu.php";
    ?>

    <div id="personal-info--wrapper">
        <div id="holding-structure">
            <?php
                require_once $_SERVER["DOCUMENT_ROOT"] . "/personal/include/parts/companies.php";
            ?>
        </div>

        <div id="personal-manager--wrapper">
            <div class="manager-card-fields">
                <div class="manager-personal-info">
                    <div class="manager--avatar_field">
                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" width="60" height="60" viewBox="0 0 256 256" xml:space="preserve">
                            <g style="stroke: none; stroke-width: 0; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: none; fill-rule: nonzero; opacity: 1;" transform="translate(1.4065934065934016 1.4065934065934016) scale(2.81 2.81)">
                                <path d="M 45 88 c -11.049 0 -21.18 -2.003 -29.021 -8.634 C 6.212 71.105 0 58.764 0 45 C 0 20.187 20.187 0 45 0 c 24.813 0 45 20.187 45 45 c 0 13.765 -6.212 26.105 -15.979 34.366 C 66.181 85.998 56.049 88 45 88 z" style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(214,214,214); fill-rule: nonzero; opacity: 1;" transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round"/>
                                <path d="M 45 60.71 c -11.479 0 -20.818 -9.339 -20.818 -20.817 c 0 -11.479 9.339 -20.818 20.818 -20.818 c 11.479 0 20.817 9.339 20.817 20.818 C 65.817 51.371 56.479 60.71 45 60.71 z" style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(165,164,164); fill-rule: nonzero; opacity: 1;" transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round"/>
                                <path d="M 45 90 c -10.613 0 -20.922 -3.773 -29.028 -10.625 c -0.648 -0.548 -0.88 -1.444 -0.579 -2.237 C 20.034 64.919 31.933 56.71 45 56.71 s 24.966 8.209 29.607 20.428 c 0.301 0.793 0.069 1.689 -0.579 2.237 C 65.922 86.227 55.613 90 45 90 z" style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(165,164,164); fill-rule: nonzero; opacity: 1;" transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round"/>
                            </g>
                        </svg>
                    </div>
                    <div class="manager--info">
                        <div class="field post">
                            <span>Руководитель отдела клиентского сервиса</span>
                        </div>
                        <div class="field name">
                            <span>Персональный менеджер</span>
                        </div>
                    </div>
                </div>

                <div class="manager-action-links--wrapper">
                    <div class="phone-link link">
                        <a href="tel:+74951295372">
                            <div class="icon"><svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M16.5643 12.7424L14.3315 10.5095C13.534 9.71209 12.1784 10.0311 11.8594 11.0678C11.6202 11.7855 10.8227 12.1842 10.105 12.0247C8.51012 11.626 6.35702 9.5526 5.9583 7.87797C5.71906 7.16024 6.19753 6.36279 6.91523 6.12359C7.95191 5.80461 8.27089 4.44895 7.47344 3.65151L5.2406 1.41866C4.60264 0.860447 3.64571 0.860447 3.08749 1.41866L1.57235 2.93381C0.0572004 4.5287 1.73184 8.75516 5.47983 12.5032C9.22782 16.2511 13.4543 18.0056 15.0492 16.4106L16.5643 14.8955C17.1226 14.2575 17.1226 13.3006 16.5643 12.7424Z" fill="#0065FF"></path>
                                </svg>
                            </div>
                            <div class="data">+7 (495) 129-53-72</div>
                        </a>
                    </div>
                    <div class="email-link link">
                        <a href="mailto:i.bobarikina@yomerch.ru">
                            <div class="icon"><svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <g clip-path="url(#clip0)">
                                        <path d="M11.9316 9.09224L17.9999 12.9285V5.09399L11.9316 9.09224Z" fill="#0065FF"></path>
                                        <path d="M0 5.09399V12.9285L6.06825 9.09224L0 5.09399Z" fill="#0065FF"></path>
                                        <path d="M16.8754 2.8125H1.12543C0.564055 2.8125 0.118555 3.231 0.0341797 3.76988L9.00043 9.67725L17.9667 3.76988C17.8823 3.231 17.4368 2.8125 16.8754 2.8125Z" fill="#0065FF"></path>
                                        <path d="M10.9014 9.77188L9.30951 10.8204C9.21501 10.8823 9.10813 10.9126 9.00013 10.9126C8.89213 10.9126 8.78526 10.8823 8.69076 10.8204L7.09888 9.77075L0.0361328 14.2381C0.122758 14.7725 0.566008 15.1876 1.12513 15.1876H16.8751C17.4343 15.1876 17.8775 14.7725 17.9641 14.2381L10.9014 9.77188Z" fill="#0065FF"></path>
                                    </g>
                                    <defs>
                                        <clipPath id="clip0">
                                            <rect width="18" height="18" fill="white"></rect>
                                        </clipPath>
                                    </defs>
                                </svg>
                            </div>
                            <div class="data">team@eklektika.ru</div>
                        </a>
                    </div>
                </div>

            </div>
            <div class="manager-card-fields">
                <div class="our-social_links">
                    <div class="title">
                        <span>Мы в сети</span>
                    </div>
                    <div class="links">
                        <a href="#" class="link">
                            <svg xmlns="http://www.w3.org/2000/svg" width="23" height="20" viewBox="0 0 23 20" fill="none">
                                <path d="M22.3989 2.47742L19.0863 18.7283C18.8392 19.8728 18.2052 20.1306 17.289 19.6139L12.3201 15.7737L9.88764 18.2105C9.64166 18.4694 9.39458 18.7283 8.83046 18.7283L9.21857 13.3725L18.4872 4.54645C18.8742 4.13975 18.3812 3.99196 17.8881 4.32534L6.36407 11.9324L1.39411 10.3445C0.301948 9.97563 0.301948 9.19889 1.64119 8.68335L20.9536 0.816248C21.9047 0.520673 22.7159 1.0385 22.3989 2.47742Z" fill="#222222"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div><br><br><a href="nanesen-eklektika.xls">Специальное предложение на услуги нанесения для дилеров </a><br><br></div>
    <div><li><span style="font-weiht:bold;">Чтобы получить дополнительную скидку 20% от цен, заданных в <a href="nanesen-eklektika.xls">прайс листе</a> </span>,  необходимо сообщить об этом Вашему менеджеру при оформлении заказа. <br><br></li><li> Дополнительная скидка 20% действует только при условии нанесения на продукцию, заказанную на нашем сайте!<br><br></li></div>
    <li>Данное ценовое предложение действительно  с 03.02.2021 по 31.09.2021 для всех компаний, зарегестрированных на нашем сайте и имеющих статус дилера компании "Эклектика".</li>

    <br/><br/>
    <a href="/personal/logout.php" style="color: red; text-decoration: dotted;">Выйти из аккаунта</a>
</div>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>