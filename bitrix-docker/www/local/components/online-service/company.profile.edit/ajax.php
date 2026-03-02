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
        'LEGAN_ENTITY_PHONE',
        'LEGAN_ENTITY_EMAIL',
        'LEGAN_ENTITY_WWW'
    ];

    foreach ($fieldsToUpdate as $field) {
        if (isset($_POST[$field])) {
            $updateData[$field] = trim($_POST[$field]);
        }
    }

    // Получаем данные о файле и флаг удаления
    $uploadedFile = null;
    if (isset($_FILES['LEGAN_ENTITY_FILE']) && $_FILES['LEGAN_ENTITY_FILE']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadedFile = $_FILES['LEGAN_ENTITY_FILE'];
    }
    
    $deleteRequisites = (isset($_POST['delete_requisites']) && $_POST['delete_requisites'] === 'Y');

    // Выполняем обновление через метод класса Company
    $result = $company->updateCompanyProfile($companyId, $updateData, $uploadedFile, $deleteRequisites);

    // Формируем ответ
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'company_id' => $result['data']['company_id'],
            'company_code' => $result['data']['company_code'],
            'b24_synced' => $result['data']['b24_synced'] ?? false
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }

} catch (Exception $e) {
    // Логируем ошибку для отладки (можно добавить запись в лог-файл)
    error_log('Company profile update error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Произошла ошибка при обновлении данных компании'
    ]);
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");

