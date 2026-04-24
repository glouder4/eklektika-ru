<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
 

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

// Проверка sessid (обязательно!)
if (!check_bitrix_sessid()) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Неверная сессия'], JSON_UNESCAPED_UNICODE);
    exit;
}
  
global $USER;
$userId = $USER->GetID();

if ($userId > 0) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Вы уже авторизованы'], JSON_UNESCAPED_UNICODE);
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
    'name'           => trim((string)$request->getPost('name')),
    'lastname'       => trim((string)$request->getPost('lastname')),
    'mobilephone'    => trim((string)$request->getPost('mobilephone')),
    'phone'          => trim((string)$request->getPost('main-phone')),
    'address'        => trim((string)$request->getPost('address')),
    'inn'            => preg_replace('/\D/', '', trim((string)$request->getPost('inn'))),
    'activities'     => trim((string)$request->getPost('activities')),
    'name_company'   => trim((string)$request->getPost('name_company')),
    'sait'           => trim((string)$request->getPost('sait')),
    'email'          => trim((string)$request->getPost('email')),
    'password'       => (string)$request->getPost('password'),
    'password_confirm'=> (string)$request->getPost('password_confirm'),
];

$required = [
    'name'         => 'Имя',
    'lastname'     => 'Фамилия',
    'phone'        => 'Телефон',
    'name_company' => 'Название юридического лица',
    'inn'          => 'ИНН организации',
    'password'     => 'Пароль',
];

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

if (strlen($post['inn']) !== 10 && strlen($post['inn']) !== 12) {
    echo json_encode([
        'success' => false,
        'error'   => 'ИНН организации должен содержать 10 или 12 цифр'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($post['password'] !== $post['password_confirm']) {
    echo json_encode([
        'success' => false,
        'error'   => 'Пароли не совпадают'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (strlen($post['password']) < 6) {
    echo json_encode([
        'success' => false,
        'error'   => 'Пароль должен быть не менее 6 символов'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($post['email'] !== '' && !filter_var($post['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'error'   => 'Введите корректный e-mail'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$countUsersByFilter = static function (array $filter): int {
    $rs = CUser::GetList($by = 'id', $order = 'asc', $filter, ['FIELDS' => ['ID']]);
    $count = 0;
    while ($rs->Fetch()) {
        $count++;
    }
    return $count;
};
$normalizePhone = static function (string $phone): string {
    $digits = preg_replace('/\D+/', '', $phone);
    if ($digits === '') {
        return '';
    }
    if (strlen($digits) === 11 && $digits[0] === '8') {
        $digits = '7' . substr($digits, 1);
    }
    if (strlen($digits) === 10) {
        $digits = '7' . $digits;
    }
    return $digits;
};
$phoneForUniq = preg_replace('/\D/', '', (string)($post['mobilephone'] ?: $post['phone']));
$emailDupCount = $post['email'] !== '' ? $countUsersByFilter(['=EMAIL' => $post['email']]) : 0;
$phoneDupCount = 0;
$phoneDupIds = [];
if ($phoneForUniq !== '') {
    $targetPhone = $normalizePhone((string)($post['mobilephone'] ?: $post['phone']));
    $rsUsersByPhone = CUser::GetList(
        $by = 'id',
        $order = 'asc',
        ['>ID' => 0],
        ['FIELDS' => ['ID', 'PERSONAL_PHONE', 'WORK_PHONE']]
    );
    while ($u = $rsUsersByPhone->Fetch()) {
        $personalPhone = $normalizePhone((string)($u['PERSONAL_PHONE'] ?? ''));
        $workPhone = $normalizePhone((string)($u['WORK_PHONE'] ?? ''));
        if ($targetPhone !== '' && ($personalPhone === $targetPhone || $workPhone === $targetPhone)) {
            $phoneDupCount++;
            $phoneDupIds[] = (int)($u['ID'] ?? 0);
        }
    }
}

if ($emailDupCount > 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Пользователь с таким e-mail уже существует',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($phoneDupCount > 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Пользователь с таким телефоном уже существует',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Проверка уникальности логина
$phoneClean = preg_replace('/\D/', '', $post['phone']);
$emailPart = $post['email'] ? substr(md5($post['email']), 0, 6) : substr(uniqid(), -6);
$loginBase = 'u' . $phoneClean . '_' . $emailPart;
$login = $loginBase;
$loginSuffix = 0;
while (CUser::GetByLogin($login)->Fetch()) {
    $login = $loginBase . '_' . (++$loginSuffix);
}

$userFields = [
    'LOGIN'             => $login,
    'EMAIL'             => $post['email'] ?: ('reg' . $phoneClean . '.' . time() . '@temp.eklektika.local'),
    'PASSWORD'          => $post['password'],
    'CONFIRM_PASSWORD'  => $post['password'],
    'NAME'              => $post['name'],
    'LAST_NAME'         => $post['lastname'],
    'PERSONAL_PHONE'    => $post['mobilephone'] ?: $post['phone'],
    'WORK_PHONE'        => $post['phone'],
    'PERSONAL_STREET'   => $post['address'],
    'UF_INN'            => $post['inn'],
    'UF_WORK_PROFILE'   => $post['activities'],
    'WORK_COMPANY'      => $post['name_company'],
    'WORK_WWW'          => $post['sait'],
    // CRM createB24Company: юр.лицо (5), дублируем поля компании в UF_* для Bitrix24
    'UF_TYPE'           => '5',
    'UF_NAME_COMPANY'     => $post['name_company'],
    'UF_SITE'             => $post['sait'],
    'UF_SPERE'            => $post['activities'],
    'UF_JUR_ADDRESS'      => $post['address'],
    'ACTIVE'            => 'N',
    'LID'               => SITE_ID,
];

if (!defined('OS_SKIP_USERSYNC_EVENTS')) {
    define('OS_SKIP_USERSYNC_EVENTS', true);
}
$GLOBALS['OS_SKIP_USERSYNC_EVENTS'] = true;

$cUser = new CUser();
$newUserId = $cUser->Add($userFields);

if (!$newUserId) {
    $errMsg = $cUser->LAST_ERROR ?: 'Не удалось создать пользователя';
    echo json_encode([
        'success' => false,
        'error'   => $errMsg
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$safeSyncCompleted = false;
$safeSyncError = '';

if (class_exists('\OnlineService\B24\RegisterUserCompany')) {
    $syncFields = [
        'USER_ID' => (int)$newUserId,
        'EMAIL' => (string)$userFields['EMAIL'],
        'NAME' => (string)$post['name'],
        'SECOND_NAME' => '',
        'LAST_NAME' => (string)$post['lastname'],
        'PERSONAL_PHONE' => (string)($post['mobilephone'] ?: $post['phone']),
        'WORK_POSITION' => '',
        'PERSONAL_BIRTHDAY' => '',
        'UF_CITY' => '',
        'UF_TYPE' => '5',
        'UF_INN' => (string)$post['inn'],
        'UF_NAME_COMPANY' => (string)$post['name_company'],
        'UF_ADVERSTERING_AGENT' => '',
        'UF_SITE' => (string)$post['sait'],
        'UF_SPERE' => (string)$post['activities'],
        'UF_JUR_ADDRESS' => (string)$post['address'],
        'UF_KPP' => '',
    ];
    try {
        $sync = new \OnlineService\B24\RegisterUserCompany();
        $syncOk = $sync->syncFromSiteRegistration($syncFields);
        $safeSyncCompleted = (bool)$syncOk;
        if (!$safeSyncCompleted) {
            $safeSyncError = 'Синхронизация с CRM завершилась с ошибкой.';
        }
    } catch (\Throwable $e) {
        $safeSyncError = 'Не удалось синхронизировать регистрацию с CRM.';
    }
} else {
    $safeSyncError = 'Модуль синхронизации CRM недоступен.';
}

// legacy локальная синхронизация компании отключена:
// компания/контакт и запись B24 ID выполняются единым safeSync потоком.
if (!$safeSyncCompleted) {
    (new \CUser())->Delete((int)$newUserId);
    echo json_encode([
        'success' => false,
        'error' => $safeSyncError !== '' ? $safeSyncError : 'Регистрация не завершена: не удалось синхронизировать данные с CRM.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Авторизуем пользователя
$USER->Authorize($newUserId);

echo json_encode([
    'success' => true,
    'message' => 'Регистрация успешно завершена',
    'redirect' => '/',
], JSON_UNESCAPED_UNICODE);
