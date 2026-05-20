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
 * ЛК «Редактирование данных»: сначала crm.contact.update (данные формы), затем {@see CUser::Update} на сайте.
 * Inbound UPDATE_CONTACT не должен перетирать мобильный — см. {@see \OnlineService\B24\User::mapInboundCrmPhoneMultifieldToUserFields}.
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
    'UF_MOBILE_PHONE' => $post['mobilephone'],
];

$cUser = new CUser();

if (\class_exists(\OnlineService\B24\User::class)) {
    $b24Push = \OnlineService\B24\User::pushSiteUserProfileFieldsToB24($userId, $userFields);
    if (!$b24Push['success']) {
        $crmErr = \trim((string) ($b24Push['error'] ?? ''));
        echo json_encode([
            'success' => false,
            'error' => 'Обновление в CRM не выполнено'
                . ($crmErr !== '' ? ': ' . $crmErr : '')
                . '. Данные на сайте не изменены.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$skipSyncWasSet = \array_key_exists('OS_SKIP_USERSYNC_EVENTS', $GLOBALS);
$skipSyncPrev = $skipSyncWasSet ? $GLOBALS['OS_SKIP_USERSYNC_EVENTS'] : null;
$GLOBALS['OS_SKIP_USERSYNC_EVENTS'] = true;
try {
    if (!$cUser->Update($userId, $userFields)) {
        echo json_encode(['success' => false, 'error' => $cUser->LAST_ERROR ?: 'Ошибка обновления профиля'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo json_encode(['success' => true, 'message' => 'Профиль успешно обновлён'], JSON_UNESCAPED_UNICODE);
} finally {
    if ($skipSyncWasSet) {
        $GLOBALS['OS_SKIP_USERSYNC_EVENTS'] = $skipSyncPrev;
    } else {
        unset($GLOBALS['OS_SKIP_USERSYNC_EVENTS']);
    }
}
