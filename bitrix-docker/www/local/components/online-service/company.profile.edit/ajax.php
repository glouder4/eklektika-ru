<?php
/**
 * AJAX обработчик для обновления профиля компании
 * 
 * @package OnlineService\CompanyProfileEdit
 */

define("NO_KEEP_STATISTIC", true);
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use OnlineService\Site\Company;

header('Content-Type: application/json; charset=utf-8');

global $USER;

// Проверяем авторизацию
if (!$USER->IsAuthorized()) {
    echo json_encode([
        'success' => false,
        'message' => 'Необходимо авторизоваться'
    ]);
    die();
}

// Проверяем метод запроса
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        'success' => false,
        'message' => 'Неверный метод запроса'
    ]);
    die();
}

try {
    // Проверяем действие
    $action = $_POST['action'] ?? '';
    if ($action !== 'update_company') {
        echo json_encode([
            'success' => false,
            'message' => 'Неверное действие'
        ]);
        die();
    }

    // Получаем ID компании
    $companyId = intval($_POST['company_id'] ?? 0);
    if (empty($companyId)) {
        echo json_encode([
            'success' => false,
            'message' => 'Не указан ID компании'
        ]);
        die();
    }

    // Создаем экземпляр класса Company
    $company = new Company();

    // Проверяем права доступа
    $permissionCheck = $company->checkEditPermission($companyId, $USER->GetID());
    if (!$permissionCheck['has_access']) {
        echo json_encode([
            'success' => false,
            'message' => $permissionCheck['message'] ?? 'У вас нет прав для редактирования этой компании'
        ]);
        die();
    }

    // Собираем данные для обновления
    $updateData = [];
    $fieldsToUpdate = [
        'LEGAN_ENTITY_NAME',
        'LEGAN_ENTITY_INN',
        'LEGAN_ENTITY_CITY',
        'LEGAN_MAIN_PHONE',
        'LEGAN_MOBILE_PHONE',
        'LEGAN_ENTITY_EMAIL',
        'LEGAN_ENTITY_WWW',
    ];

    foreach ($fieldsToUpdate as $field) {
        if (isset($_POST[$field])) {
            $updateData[$field] = trim($_POST[$field]);
        }
    }

    // Получаем данные о файле и флаг удаления (только успешная загрузка — иначе updateCompanyProfile игнорирует файл)
    $uploadedFile = null;
    if (isset($_FILES['LEGAN_ENTITY_FILE']) && is_array($_FILES['LEGAN_ENTITY_FILE'])) {
        $fe = (int) ($_FILES['LEGAN_ENTITY_FILE']['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($fe === UPLOAD_ERR_OK) {
            $uploadedFile = $_FILES['LEGAN_ENTITY_FILE'];
        }
    }
    
    $deleteRequisites = (isset($_POST['delete_requisites']) && $_POST['delete_requisites'] === 'Y');

    // Выполняем обновление через метод класса Company
    $result = $company->updateCompanyProfile($companyId, $updateData, $uploadedFile, $deleteRequisites);

    // CRM: отдельно карточка компании, затем реквизит (после успешной записи в ИБ).
    if (!empty($result['success'])) {
        $b24Company = $company->syncCompanyProfileCompanyCardToBitrix24($companyId, $updateData, false);
        $b24Requisite = ['success' => true, 'error' => '', 'raw' => null];
        if ($b24Company['success']) {
            $b24Requisite = $company->syncCompanyProfileRequisiteToBitrix24($companyId, $updateData, false);
         } else {
            $b24Requisite = [
                'success' => false,
                'error' => 'Реквизиты не отправлялись: не выполнен crm.company.update',
                'raw' => null,
            ];
        }
        $b24Ok = $b24Company['success'] && $b24Requisite['success'];
        $b24ErrParts = array_filter([$b24Company['error'] ?? '', $b24Requisite['error'] ?? '']);
        $result['data']['b24_synced'] = $b24Ok;
        $result['data']['b24_error'] = $b24Ok ? '' : implode(' | ', $b24ErrParts);
        $result['data']['b24_result'] = [
            'company_card' => $b24Company['raw'] ?? null,
            'requisite' => $b24Requisite['raw'] ?? null,
        ];
    }

    // Формируем ответ
    if ($result['success']) {
        $b24Synced = (bool)($result['data']['b24_synced'] ?? false);
        $b24Error = (string)($result['data']['b24_error'] ?? '');
        echo json_encode([
            'success' => $b24Synced || $b24Error === '',
            'message' => $b24Synced || $b24Error === '' ? $result['message'] : ('Данные на сайте обновлены, но синхронизация с CRM не выполнена: ' . $b24Error),
            'company_id' => $result['data']['company_id'],
            'company_code' => $result['data']['company_code'],
            'b24_synced' => $b24Synced,
            'b24_company_id' => (int)($result['data']['b24_company_id'] ?? 0),
            'b24_error' => $b24Error,
            'b24_result' => $result['data']['b24_result'] ?? null
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }

} catch (Exception $e) {
    if (defined('EKLEKTIKA_COMPANY_PROFILE_DEBUG_LOG') && EKLEKTIKA_COMPANY_PROFILE_DEBUG_LOG) {
        error_log('Company profile update error: ' . $e->getMessage());
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Произошла ошибка при обновлении данных компании'
    ]);
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");

