<?php
$GLOBALS['SHOW_SYSTEM_TITLE'] = "N";

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

/**
 * @global CMain $APPLICATION
 * @global CUser $USER
 */

// Получаем ID компании из URL
$companyId = intval($_REQUEST['id'] ?? 0);

// Проверяем авторизацию
if (!$USER->IsAuthorized()) {
    LocalRedirect('/');
}

// Проверяем наличие ID компании
if (empty($companyId)) {
    LocalRedirect('/personal/profile/');
}

?>
<style>
    ul.breadcrumb{
        display: none;
    }
</style>
<div class="container personal-profile-wrapper">
    <?php
    $APPLICATION->IncludeComponent(
        "online-service:company.profile.edit",
        ".default",
        [
            "COMPANY_ID" => $companyId,
            "CACHE_TIME" => 0
        ]
    );
    ?>
</div>

<?php require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php"); ?>
