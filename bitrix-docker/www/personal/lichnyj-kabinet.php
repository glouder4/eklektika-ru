<?
$GLOBALS['ADDITIONAL_WRAPPER_CLASSES'] = 'content';
$GLOBALS['SHOW_SYSTEM_TITLE'] = "N";

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Личный кабинет");
$APPLICATION->AddChainItem("Личный кабинет", "/personal/lichnyj-kabinet.php");
$APPLICATION->SetPageProperty("title", "Авторизация купить оптом в Москве | Эклектика – нанесение логотипов на заказ");
$APPLICATION->SetPageProperty("description", "Компания Эклектика предлагает Регистрация оптом под нанесение логотипа. ✓ Низкие цены. ✓ Доставка по России. ☎ 8(800) 777-4723");


if (!$USER->IsAuthorized()) {
    header("Location: /");
    exit();
}
?>

<div class="personal-profile">
    <br>
    <a href="/personal/lichnyj-kabinet.php">Главная страница личного кабинета</a>

    <a href="/personal/redaktirovanie-dannyh.php">Редактировать данные</a>

    <a href="/personal/prosmotr-zakazov.php">Просмотр заказов</a>

    <h1>Личный кабинет</h1>


    Ваш менеджер -
    <a href="mailto:team@eklektika.ru"><a href="mailto:team@eklektika.ru">team@eklektika.ru</a></a><br>

    <div><br><br><a href="nanesen-eklektika.xls">Специальное предложение на услуги нанесения для дилеров </a><br><br></div>
    <div><li><span style="font-weiht:bold;">Чтобы получить дополнительную скидку 20% от цен, заданных в <a href="nanesen-eklektika.xls">прайс листе</a> </span>,  необходимо сообщить об этом Вашему менеджеру при оформлении заказа. <br><br></li><li> Дополнительная скидка 20% действует только при условии нанесения на продукцию, заказанную на нашем сайте!<br><br></li></div>
    <li>Данное ценовое предложение действительно  с 03.02.2021 по 31.09.2021 для всех компаний, зарегестрированных на нашем сайте и имеющих статус дилера компании "Эклектика".</li>

    <br/><br/>
    <a href="/personal/logout.php" style="color: red; text-decoration: dotted;">Выйти из аккаунта</a>
</div>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>