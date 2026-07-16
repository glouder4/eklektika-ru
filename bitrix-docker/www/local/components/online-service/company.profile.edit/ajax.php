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
    $permissionCheck = $company->checkCompanyProfileEditPageAccess($companyId, (int) $USER->GetID());
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
        if (\array_key_exists($field, $_POST)) {
            $updateData[$field] = \trim((string) $_POST[$field]);
        }
    }

    // id поля в форме — company_inn, name — LEGAN_ENTITY_INN; дублируем на случай кастомной отправки.
    if (!\array_key_exists('LEGAN_ENTITY_INN', $updateData) && \array_key_exists('company_inn', $_POST)) {
        $updateData['LEGAN_ENTITY_INN'] = \trim((string) $_POST['company_inn']);
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

    // Валидация, файлы, подготовка $updateData (без записи в ИБ).
    $result = $company->updateCompanyProfile($companyId, $updateData, $uploadedFile, $deleteRequisites);

    // CRM → затем локальный ИБ 23 (те же данные, что ушли в n8n).
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

        $localPersistError = null;
        if ($b24Company['success']) {
            $deleteRequisitesFiles = $result['data']['requisites_files_to_delete_after_persist'] ?? [];
            if (!\is_array($deleteRequisitesFiles)) {
                $deleteRequisitesFiles = [];
            }
            $localPersistError = $company->persistCompanyProfileFormDataToIblock(
                $companyId,
                $updateData,
                $deleteRequisitesFiles
            );
            if ($localPersistError !== null) {
                $result['success'] = false;
                $result['message'] = 'Данные отправлены в CRM, но не сохранены на сайте: ' . $localPersistError;
            }
        } else {
            $result['success'] = false;
            $crmErr = \trim((string) ($b24Company['error'] ?? ''));
            $result['message'] = 'Обновление в CRM не выполнено'
                . ($crmErr !== '' ? ': ' . $crmErr : '')
                . '. Данные на сайте не изменены.';
        }

        $b24Ok = $b24Company['success'] && $b24Requisite['success'];
        $b24ErrParts = array_filter([$b24Company['error'] ?? '', $b24Requisite['error'] ?? '']);
        $result['data']['b24_synced'] = $b24Ok;
        $result['data']['b24_error'] = $b24Ok ? '' : implode(' | ', $b24ErrParts);
        $result['data']['b24_result'] = [
            'company_card' => $b24Company['raw'] ?? null,
            'requisite' => $b24Requisite['raw'] ?? null,
        ];
        if ($localPersistError !== null) {
            $result['data']['local_persist_error'] = $localPersistError;
        }
    }

    // Формируем ответ
    if ($result['success']) {
        $b24Synced = (bool)($result['data']['b24_synced'] ?? false);
        $b24Error = (string)($result['data']['b24_error'] ?? '');
        echo json_encode([
            'success' => true,
            'message' => $b24Synced || $b24Error === ''
                ? $result['message']
                : ('Данные на сайте сохранены, но синхронизация с CRM выполнена не полностью: ' . $b24Error),
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

