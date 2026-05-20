<?php
$GLOBALS['ADDITIONAL_WRAPPER_CLASSES'] = 'content';
$GLOBALS['SHOW_SYSTEM_TITLE'] = "Y";

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Редактирование данных");
$APPLICATION->AddChainItem("Редактирование данных", "/personal/lichnyj-kabinet.php");

$APPLICATION->SetPageProperty("title", "Редактирование данных купить оптом в Москве | Эклектика – нанесение логотипов на заказ");
$APPLICATION->SetPageProperty("description", "Компания Эклектика предлагает Редактирование данных оптом под нанесение логотипа. ✓ Низкие цены. ✓ Доставка по России. ☎ 8(800) 777-4723");

global $USER;
if (!$USER->IsAuthorized()) {
    header("Location: /");
    exit();
}

require_once $_SERVER["DOCUMENT_ROOT"] . "/personal/ajax/get-company-by-inn.php";

$userId = (int)$USER->GetID();
$userFields = CUser::GetByID($userId)->Fetch();

$name = $userFields['NAME'] ?? '';
$lastName = $userFields['LAST_NAME'] ?? '';
$email = $userFields['EMAIL'] ?? '';
$phone = \trim((string)($userFields['UF_MOBILE_PHONE'] ?? ''));
if ($phone === '') {
    $phone = \trim((string)($userFields['PERSONAL_PHONE'] ?? ''));
}
$workPhone = \trim((string)($userFields['WORK_PHONE'] ?? ''));
$inn = preg_replace('/\D/', '', (string)($userFields['UF_INN'] ?? ''));
$company = getCompanyByInn($inn, $userId);
$isUserCompanyDirector = false;
if (\class_exists(\OnlineService\Sync\FromCrm\CrmInboundUfMap::class)) {
    $isUserCompanyDirector = \OnlineService\Sync\FromCrm\CrmInboundUfMap::userDirectorUfToCrmInt($userFields['UF_IS_DIRECTOR'] ?? null) === 1;
}

$rdInclude = $_SERVER["DOCUMENT_ROOT"] . "/personal/include/redaktirovanie-dannyh/";
?>
<div class="content cart-order" style="margin:0;">
    <?php
    require_once $_SERVER["DOCUMENT_ROOT"] . "/personal/include/personal-menu.php";
    ?>

    <?php include $rdInclude . 'form.php'; ?>
</div>

<?php include $rdInclude . 'form-assets-style.php'; ?>
<?php include $rdInclude . 'form-assets-script.php'; ?>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
