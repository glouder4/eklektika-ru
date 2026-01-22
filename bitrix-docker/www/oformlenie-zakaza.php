<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php"); ?>

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
