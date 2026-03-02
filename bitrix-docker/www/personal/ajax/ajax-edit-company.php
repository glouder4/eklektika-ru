<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Application;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

if (!check_bitrix_sessid()) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Неверная сессия'], JSON_UNESCAPED_UNICODE);
    exit;
}

global $USER;
$userId = (int)$USER->GetID();
if ($userId <= 0) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Необходима авторизация'], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
$request = Application::getInstance()->getContext()->getRequest();

$post = [
    'name'        => trim((string)$request->getPost('name')),
    'lastname'    => trim((string)$request->getPost('lastname')),
    'email'       => trim((string)$request->getPost('email')),
    'phone'       => trim((string)$request->getPost('phone')),
    'mobilephone' => trim((string)$request->getPost('mobilephone')),
];

$required = [
    'name'     => 'Имя',
    'lastname' => 'Фамилия',
    'phone'    => 'Телефон',
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
    'NAME'           => $post['name'],
    'LAST_NAME'      => $post['lastname'],
    'EMAIL'          => $post['email'] ?: null,
    'PERSONAL_PHONE' => $post['mobilephone'],
    'WORK_PHONE'     => $post['phone'],
];

$cUser = new CUser();
if (!$cUser->Update($userId, $userFields)) {
    echo json_encode(['success' => false, 'error' => $cUser->LAST_ERROR ?: 'Ошибка обновления профиля'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Профиль успешно обновлён'], JSON_UNESCAPED_UNICODE);
