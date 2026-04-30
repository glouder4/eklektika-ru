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

if (!function_exists('companyProfileEditTrace')) {
    /**
     * Локальный trace отправщика обновления компании.
     *
     * @param array<string, mixed> $context
     */
    function companyProfileEditTrace(string $event, array $context = []): void
    {
        $line = date('Y-m-d H:i:s') . ' [trace] ' . $event;
        if ($context !== []) {
            $json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
            $line .= ' ' . ($json !== false ? $json : '{"encode":"failed"}');
        }
        $line .= PHP_EOL;

        $path = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/') . '/local/logs/inbound-b24.log';
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (@file_put_contents($path, $line, FILE_APPEND | LOCK_EX) !== false) {
            return;
        }
        @file_put_contents('/tmp/inbound-b24.log', $line, FILE_APPEND | LOCK_EX);
    }
}

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

    // Получаем данные о файле и флаг удаления
    $uploadedFile = null;
    if (isset($_FILES['LEGAN_ENTITY_FILE']) && $_FILES['LEGAN_ENTITY_FILE']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadedFile = $_FILES['LEGAN_ENTITY_FILE'];
    }
    
    $deleteRequisites = (isset($_POST['delete_requisites']) && $_POST['delete_requisites'] === 'Y');

    companyProfileEditTrace('company.profile.edit.ajax.input', [
        'action' => $action,
        'company_id' => $companyId,
        'post_keys' => array_keys($_POST),
        'files_keys' => is_array($_FILES) ? array_keys($_FILES) : [],
        'has_uploaded_file' => is_array($uploadedFile),
        'uploaded_file_error' => is_array($uploadedFile) ? (int)($uploadedFile['error'] ?? -1) : null,
        'uploaded_file_size' => is_array($uploadedFile) ? (int)($uploadedFile['size'] ?? 0) : null,
        'uploaded_file_name' => is_array($uploadedFile) ? (string)($uploadedFile['name'] ?? '') : null,
        'delete_requisites' => $deleteRequisites,
        'update_data_keys' => array_keys($updateData),
    ]);

    // Выполняем обновление через метод класса Company
    $result = $company->updateCompanyProfile($companyId, $updateData, $uploadedFile, $deleteRequisites);

    companyProfileEditTrace('company.profile.edit.ajax.result', [
        'company_id' => $companyId,
        'success' => !empty($result['success']),
        'message' => (string)($result['message'] ?? ''),
        'b24_synced' => (bool)($result['data']['b24_synced'] ?? false),
        'b24_error' => (string)($result['data']['b24_error'] ?? ''),
    ]);

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

