<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("description", "Компания Эклектика предлагает Политика конфиденциальности оптом под нанесение логотипа. ✓ Низкие цены. ✓ Доставка по России. ☎ 8(800) 777-4723");
$APPLICATION->SetPageProperty("title", "Политика конфиденциальности купить оптом в Москве | Эклектика – нанесение логотипов на заказ");
$APPLICATION->SetTitle("Политика конфиденциальности");
?>
<?$APPLICATION->IncludeComponent(
    "bitrix:main.include",
    "",
    Array(
        "AREA_FILE_SHOW" => "file",
        "AREA_FILE_SUFFIX" => "inc",
        "EDIT_TEMPLATE" => "",
        "PATH" => "/include/footer-upper-footer-form-offerta.php"
    )
);?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
