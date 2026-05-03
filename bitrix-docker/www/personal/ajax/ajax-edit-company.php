<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Application;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

header('Content-Type: application/json; charset=utf-8');

$request = Application::getInstance()->getContext()->getRequest();

if (!$request->isPost()) {
    echo json_encode(['success' => false, 'error' => 'Метод не поддерживается'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!check_bitrix_sessid()) {
    echo json_encode(['success' => false, 'error' => 'Неверная сессия'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Не принимаем подмену пользователя из тела запроса — обновляется только текущая сессия.
foreach (['USER_ID', 'user_id', 'ID'] as $spoofKey) {
    if ($request->getPost($spoofKey) !== null && $request->getPost($spoofKey) !== '') {
        echo json_encode(['success' => false, 'error' => 'Некорректный запрос'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

global $USER;
$userId = (int)$USER->GetID();
if ($userId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Необходима авторизация'], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Перед Update выставляется `$GLOBALS['OS_DEFER_B24_CRM_PROFILE_PUSH_TO_CALLER']`: OnAfter не дублирует CRM-пуш,
 * после успешного Update вызывается {@see \OnlineService\B24\User::pushLinkedSiteUserProfileToB24AfterUserUpdate} (см. finally).
 * {@see \OnlineService\B24\User::pushLocalUserProfileToB24Crm} — crm.contact.update в B24
 * через {@see \OnlineService\B24\RestClient::callRestMethod} (при настроенном n8n — общий CRM webhook
 * `n8n_crm_rest_proxy_webhook_url` / EKLEKTIKA_N8N_CRM_WEBHOOK_URL с телом METHOD+PARAMS, не именованный
 * path `registration/crm-contact-update-v1` из EKLEKTIKA_SYNC_CONFIG регистрации).
 */
$post = [
    'name' => trim((string)$request->getPost('name')),
    'lastname' => trim((string)$request->getPost('lastname')),
    'email' => trim((string)$request->getPost('email')),
    'phone' => trim((string)$request->getPost('phone')),
    'mobilephone' => trim((string)$request->getPost('mobilephone')),
];

$post['name'] = mb_substr($post['name'], 0, 100);
$post['lastname'] = mb_substr($post['lastname'], 0, 100);
$post['email'] = mb_substr($post['email'], 0, 100);
$post['phone'] = mb_substr($post['phone'], 0, 50);
$post['mobilephone'] = mb_substr($post['mobilephone'], 0, 20);

$required = [
    'name' => 'Имя',
    'lastname' => 'Фамилия',
    'phone' => 'Телефон',
];

$missing = [];
foreach ($required as $key => $label) {
    if ($post[$key] === '') {
        $missing[] = $label;
    }
}
if (!empty($missing)) {
    echo json_encode(['success' => false, 'error' => 'Заполните обязательные поля: ' . implode(', ', $missing)], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($post['email'] !== '' && !filter_var($post['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Введите корректный e-mail'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userFields = [
    'NAME' => $post['name'],
    'LAST_NAME' => $post['lastname'],
    'EMAIL' => $post['email'] ?: null,
    'PERSONAL_PHONE' => $post['mobilephone'],
    'WORK_PHONE' => $post['phone'],
];

$cUser = new CUser();
$GLOBALS['OS_DEFER_B24_CRM_PROFILE_PUSH_TO_CALLER'] = true;
try {
    if (!$cUser->Update($userId, $userFields)) {
        echo json_encode(['success' => false, 'error' => $cUser->LAST_ERROR ?: 'Ошибка обновления профиля'], JSON_UNESCAPED_UNICODE);
        return;
    }
    if (\class_exists(\OnlineService\B24\User::class)) {
        \OnlineService\B24\User::pushLinkedSiteUserProfileToB24AfterUserUpdate($userId);
    }
    echo json_encode(['success' => true, 'message' => 'Профиль успешно обновлён'], JSON_UNESCAPED_UNICODE);
} finally {
    unset($GLOBALS['OS_DEFER_B24_CRM_PROFILE_PUSH_TO_CALLER']);
}
