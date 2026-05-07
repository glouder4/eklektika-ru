<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->SetTitle("Оформление заказа");
$APPLICATION->AddChainItem("Оформление заказа", "/personal/lichnyj-kabinet.php");

$APPLICATION->SetPageProperty("title", "Оформление заказа купить оптом в Москве | Эклектика – нанесение логотипов на заказ");
$APPLICATION->SetPageProperty("description", "Компания Эклектика предлагает Оформление заказа заказов оптом под нанесение логотипа. ✓ Низкие цены. ✓ Доставка по России. ☎ 8(800) 777-4723");

global $USER;
if (!$USER || !$USER->IsAuthorized()) {
    $backUrl = $APPLICATION->GetCurPageParam('', ['login', 'logout', 'register', 'forgot_password', 'change_password']);
    LocalRedirect('/personal/vhod.php?backurl=' . urlencode($backUrl));
}

?>

<?php
    $APPLICATION->IncludeComponent(
        "online-service:order.form",
        ".default",
        [
            "SUCCESS_PAGE" => "/order/success/",
            "PERSON_TYPE_ID" => 1,
            "USE_AJAX" => "Y"
        ]
    );
?>


<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
