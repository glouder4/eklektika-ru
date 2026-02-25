<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Application;
use Bitrix\Main\Loader;

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

if (!Loader::includeModule('iblock')) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Модуль инфоблоков не загружен'], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
$request = Application::getInstance()->getContext()->getRequest();

$post = [
    'name'         => trim((string)$request->getPost('name')),
    'lastname'     => trim((string)$request->getPost('lastname')),
    'email'        => trim((string)$request->getPost('email')),
    'phone'        => trim((string)$request->getPost('phone')),
    'mobilephone'  => trim((string)$request->getPost('mobilephone')),
    'address'      => trim((string)$request->getPost('address')),
    'inn'          => preg_replace('/\D/', '', trim((string)$request->getPost('inn'))),
    'activities'   => trim((string)$request->getPost('activities')),
    'name_company' => trim((string)$request->getPost('name_company')),
    'sait'         => trim((string)$request->getPost('sait')),
    'company_editable' => $request->getPost('company_editable') === '1',
];

$required = [
    'name'     => 'Имя',
    'lastname' => 'Фамилия',
    'phone'    => 'Телефон',
    'name_company' => 'Название юридического лица',
    'inn'      => 'ИНН организации',
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

if (strlen($post['inn']) < 10 || strlen($post['inn']) > 12) {
    echo json_encode(['success' => false, 'error' => 'ИНН организации должен содержать от 10 до 12 цифр'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($post['email'] !== '' && !filter_var($post['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Введите корректный e-mail'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($post['sait'] !== '') {
    $url = $post['sait'];
    if (!preg_match('#^https?://#i', $url)) $url = 'https://' . $url;
    if (!preg_match('/^(https?:\/\/)?[a-z0-9][a-z0-9.-]*\.[a-z]{2,}(\/.*)?$/i', $url)) {
        echo json_encode(['success' => false, 'error' => 'Введите корректный адрес сайта (например: example.com или qqqqq.ru)'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

require_once $_SERVER["DOCUMENT_ROOT"] . "/personal/ajax/get-company-by-inn.php";

$userData = CUser::GetByID($userId)->Fetch();
$oldInn = preg_replace('/\D/', '', (string)($userData['UF_INN'] ?? ''));
$company = getCompanyByInn($oldInn, $userId);
$canEditCompany = $company && $company['isBoss'];

$userFields = [
    'NAME'            => $post['name'],
    'LAST_NAME'       => $post['lastname'],
    'EMAIL'           => $post['email'] ?: null,
    'PERSONAL_PHONE'  => $post['mobilephone'],
    'WORK_PHONE'      => $post['phone'],
    'PERSONAL_STREET' => $post['address'],
    'UF_UR_ADRES'     => $post['address'],
    'UF_INN'          => $post['inn'],
    'WORK_PROFILE'    => $post['activities'],
    'WORK_COMPANY'    => $post['name_company'],
    'WORK_WWW'        => $post['sait'],
];

$cUser = new CUser();
if (!$cUser->Update($userId, $userFields)) {
    echo json_encode(['success' => false, 'error' => $cUser->LAST_ERROR ?: 'Ошибка обновления профиля'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($canEditCompany && $company) {
    $iblockId = 23;
    CIBlockElement::SetPropertyValuesEx($company['id'], $iblockId, [
        'LEGAN_ENTITY_NAME'    => $post['name_company'],
        'LEGAN_ENTITY_PHONE'   => $post['phone'],
        'LEGAN_ENTITY_ADRESS'  => $post['address'],
        'LEGAN_ENTITY_ACTIVITY'=> $post['activities'],
        'LEGAN_ENTITY_INN'     => $post['inn'],
        'LEGAN_ENTITY_WWW'     => $post['sait'],
    ]);
}

echo json_encode(['success' => true, 'message' => 'Профиль успешно обновлён'], JSON_UNESCAPED_UNICODE);
