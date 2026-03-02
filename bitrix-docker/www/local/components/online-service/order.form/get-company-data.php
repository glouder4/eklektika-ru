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

$phone = trim((string)($companyData['LEGAN_ENTITY_PHONE'] ?? ''));
if ($phone && !preg_match('/^(\+7|7|8)/', $phone)) {
    $phone = '+7' . $phone;
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
        'off_email' => trim((string)($companyData['LEGAN_ENTITY_EMAIL'] ?? '')),
        'off_requisites' => implode("\n", $requisites),
        'requisites_file_id' => $requisitesFileId,
        'requisites_file_name' => $requisitesFileName,
    ]
]);
