<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Application;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

// Проверка sessid (обязательно!)
if (!check_bitrix_sessid()) {
    die('Invalid session');
}

global $USER;
// Получение ID текущего пользователя
$userId = $USER->GetID();

if ($userId <= 0) {
    // Пользователь не авторизован
    die('User not logged in');
}

header('Content-Type: application/json; charset=utf-8');
$request = Application::getInstance()->getContext()->getRequest();

// Получаем и нормализуем входные данные
$post = [
    'name'         => trim((string)$request->getPost('name')),
    'lastname'     => trim((string)$request->getPost('lastname')),
    'mobilephone'  => trim((string)$request->getPost('mobilephone')),
    'phone'        => trim((string)$request->getPost('phone')),
    'address'      => trim((string)$request->getPost('address')),
    'inn'          => trim((string)$request->getPost('inn')),
    'activities'   => trim((string)$request->getPost('activities')),
    'name_company' => trim((string)$request->getPost('name_company')),
    'sait'         => trim((string)$request->getPost('sait')),
];

// Обязательные поля: [POST-ключ => человекочитаемое название]
$required = [
    'name'         => 'Имя',
    'lastname'     => 'Фамилия',
    'mobilephone'  => 'Телефон',
    'name_company' => 'Название компании',
];

// Проверка обязательных полей
$missing = [];
foreach ($required as $postKey => $label) {
    if ($post[$postKey] === '') {
        $missing[] = $label;
    }
}

if (!empty($missing)) {
    echo json_encode([
        'success' => false,
        'error'   => 'Заполните обязательные поля: ' . implode(', ', $missing)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Формируем данные для Bitrix
$fields = [
    'NAME'            => $post['name'],
    'LAST_NAME'       => $post['lastname'],
    'PERSONAL_PHONE'  => $post['mobilephone'],
    'WORK_PHONE'      => $post['phone'],
    'PERSONAL_STREET' => $post['address'],
    'UF_INN'          => $post['inn'],
    'UF_WORK_PROFILE' => $post['activities'],
    'WORK_COMPANY'    => $post['name_company'],
    'UF_WORK_WWW'     => $post['sait'],
];

// Обновляем профиль пользователя
$user = new \CUser();
$result = $user->Update($userId, $fields);

if ($result) {
    echo json_encode([
        'success' => true,
        'message' => 'Профиль успешно обновлён'
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'success' => false,
        'error'   => $user->LAST_ERROR ?: 'Неизвестная ошибка при обновлении профиля'
    ], JSON_UNESCAPED_UNICODE);
}
