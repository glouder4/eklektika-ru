<?php
/**
 * Возвращает данные компании по ID для автозаполнения формы заказа.
 * Использует класс Company (как в company.profile.edit).
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    define('NO_KEEP_STATISTIC', true);
    define('NOT_CHECK_PERMISSIONS', true);
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/order_form_phone_normalize.php';

$companyId = (int)($_REQUEST['company_id'] ?? 0);
if (!$companyId) {
    echo json_encode(['success' => false, 'message' => 'Не указан ID компании']);
    exit;
}

global $USER;
if (!$USER->IsAuthorized()) {
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit;
}

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    echo json_encode(['success' => false, 'message' => 'Модуль iblock не подключен']);
    exit;
}

$company = new \OnlineService\Site\Company();
$companyData = $company->getCompany($companyId);

if (empty($companyData)) {
    echo json_encode(['success' => false, 'message' => 'Компания не найдена']);
    exit;
}

$phoneRaw = order_form_company_phone_raw_from_ib($companyData);
$phone = order_form_normalize_ru_company_phone($phoneRaw);

// Как в templates/.../personal-info.php: при пустом телефоне компании — рабочий телефон пользователя
if ($phone === '') {
    $uf = CUser::GetByID($USER->GetID())->Fetch();
    if (!empty($uf)) {
        $phone = order_form_normalize_ru_company_phone((string)($uf['WORK_PHONE'] ?? ''));
    }
}

$requisites = [];
$requisitesFileId = null;
$requisitesFileName = '';
if (!empty($companyData['LEGAN_ENTITY_INN'])) $requisites[] = 'ИНН: ' . $companyData['LEGAN_ENTITY_INN'];
if (!empty($companyData['LEGAN_ENTITY_NAME'])) $requisites[] = $companyData['LEGAN_ENTITY_NAME'];
if (!empty($companyData['LEGAN_ENTITY_ADRESS'])) $requisites[] = $companyData['LEGAN_ENTITY_ADRESS'];
if (!empty($companyData['LEGAN_ENTITY_CITY'])) $requisites[] = 'г. ' . $companyData['LEGAN_ENTITY_CITY'];
if (!empty($companyData['LEGAN_ENTITY_FILE'])) {
    $file = CFile::GetFileArray($companyData['LEGAN_ENTITY_FILE']);
    if ($file) {
        $requisitesFileId = (int)$companyData['LEGAN_ENTITY_FILE'];
        $requisitesFileName = $file['ORIGINAL_NAME'] ?? '';
        $requisites[] = 'Файл реквизитов: ' . $requisitesFileName . ' (' . $file['SRC'] . ')';
    }
}

echo json_encode([
    'success' => true,
    'data' => [
        'off_company' => trim((string)($companyData['LEGAN_ENTITY_NAME'] ?? '')),
        'off_phone' => $phone,
        'off_inn' => trim((string)($companyData['LEGAN_ENTITY_INN'] ?? '')),
        'off_email' => trim((string)($companyData['LEGAN_ENTITY_EMAIL'] ?? '')),
        'off_requisites' => implode("\n", $requisites),
        'requisites_file_id' => $requisitesFileId,
        'requisites_file_name' => $requisitesFileName,
    ]
]);
