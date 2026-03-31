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

if (strlen($post['inn']) < 10 || strlen($post['inn']) > 12) {
    echo json_encode([
        'success' => false,
        'error'   => 'ИНН организации должен содержать от 10 до 12 цифр'
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
    'ACTIVE'            => 'Y',
    'LID'               => SITE_ID,
];

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

$ajaxRegisterLog = function (string $line): void {
    $path = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/ajax-register-action.log';
    @mkdir(dirname($path), 0755, true);
    @file_put_contents($path, date('Y-m-d H:i:s') . ' ' . $line . PHP_EOL, FILE_APPEND | LOCK_EX);
};

// Инфоблок 23 — юридические лица
$iblockId = 23;

// Ищем элемент с таким ИНН (arSelect только ID/NAME — при выборке PROPERTY_* в Fetch() ID иногда не приходит)
$rsElement = CIBlockElement::GetList(
    ['ID' => 'ASC'],
    [
        'IBLOCK_ID' => $iblockId,
        'PROPERTY_LEGAN_ENTITY_INN' => $post['inn'],
    ],
    false,
    ['nTopCount' => 1],
    ['ID', 'NAME']
);

$legalEntityElementId = 0;
if ($obElement = $rsElement->GetNextElement()) {
    $arEl = $obElement->GetFields();
    $legalEntityElementId = (int)($arEl['ID'] ?? 0);
}

if ($legalEntityElementId > 0) {
    // Элемент с таким ИНН уже есть — только допривязываем пользователя
    $existingUsers = [];
    $dbProps = CIBlockElement::GetProperty($iblockId, $legalEntityElementId, [], ['CODE' => 'LEGAN_ENTITY_USERS']);
    while ($ar = $dbProps->Fetch()) {
        if (!empty($ar['VALUE'])) {
            $existingUsers[] = (int)$ar['VALUE'];
        }
    }
    $existingUsers[] = (int)$newUserId;
    $existingUsers = array_unique(array_filter($existingUsers));

    CIBlockElement::SetPropertyValuesEx(
        $legalEntityElementId,
        $iblockId,
        ['LEGAN_ENTITY_USERS' => $existingUsers]
    );
} else {
    // Создаём новый элемент
    $el = new CIBlockElement();
    $arFields = [
        'IBLOCK_ID' => $iblockId,
        'NAME'      => $post['name_company'],
        'ACTIVE'    => 'Y',
        'PROPERTY_VALUES' => [
            'LEGAN_ENTITY_NAME'    => $post['name_company'],
            'LEGAN_ENTITY_PHONE'   => $post['phone'],
            'LEGAN_ENTITY_ADRESS'  => $post['address'],
            'LEGAN_ENTITY_ACTIVITY'=> $post['activities'],
            'LEGAN_ENTITY_INN'     => $post['inn'],
            'LEGAN_ENTITY_WWW'     => $post['sait'],
            'LEGAN_ENTITY_BOSS'    => $newUserId,
            'LEGAN_ENTITY_USERS'   => [$newUserId],
        ],
    ];
    $newElementId = $el->Add($arFields);
    if ($newElementId) {
        $legalEntityElementId = (int) $newElementId;
    }
}

if ($legalEntityElementId > 0) {
    $legalEntityElementIdStr = (string) $legalEntityElementId;
    $ufLegalEntity = [
        'UF_CRM_1774915439581' => $legalEntityElementIdStr,
    ];
    $ajaxRegisterLog(
        'ajax-register-action: UF payload (сайт→пользователь, не CRM REST) user_id=' . $newUserId
        . ' iblock23_element_id=' . $legalEntityElementIdStr
        . ' field=UF_CRM_1774915439581 value=' . $legalEntityElementIdStr
    );
    $userUpdate = new CUser();
    $updated = $userUpdate->Update($newUserId, $ufLegalEntity);
    if ($updated) {
        $ajaxRegisterLog(
            'ajax-register-action: CUser::Update UF OK user_id=' . $newUserId
            . ' iblock23_element_id=' . $legalEntityElementIdStr
        );
    } else {
        $ajaxRegisterLog(
            'ajax-register-action: CUser::Update UF FAILED user_id=' . $newUserId
            . ' iblock23_element_id=' . $legalEntityElementIdStr
            . ' err=' . ($userUpdate->LAST_ERROR ?: '(empty)')
        );
        /** @var \CUserTypeManager $USER_FIELD_MANAGER */
        global $USER_FIELD_MANAGER;
        if (isset($USER_FIELD_MANAGER) && is_object($USER_FIELD_MANAGER)) {
            $USER_FIELD_MANAGER->Update('USER', $newUserId, $ufLegalEntity);
            $ajaxRegisterLog(
                'ajax-register-action: USER_FIELD_MANAGER->Update fallback after FAILED user_id=' . $newUserId
                . ' value=' . $legalEntityElementIdStr
            );
        }
    }
} else {
    $ajaxRegisterLog(
        'ajax-register-action: UF skipped — нет элемента ИБ 23 по ИНН user_id=' . $newUserId
        . ' inn=' . $post['inn']
    );
}

// CRM: UF_CRM_1774915439581 — пользовательское поле КОМПАНИИ. Ищем компанию по ИНН через реквизиты (как RegisterUserCompany::createB24Company).
if ($legalEntityElementId > 0) {
    $crmUfPayload = ['UF_CRM_1774915439581' => $legalEntityElementIdStr];

    $reqList = sendRequestB24('crm.requisite.list', [
        'fields' => [],
        'params' => [],
        'select' => [
            'ID',
            'RQ_INN',
            'ENTITY_ID',
        ],
        'filter' => [
            'RQ_INN' => $post['inn'],
        ],
    ]);

    $companyB24Id = 0;
    if (is_array($reqList)) {
        if (isset($reqList[0]['ENTITY_ID'])) {
            $companyB24Id = (int) $reqList[0]['ENTITY_ID'];
        } elseif (isset($reqList['requisites'][0]['ENTITY_ID'])) {
            $companyB24Id = (int) $reqList['requisites'][0]['ENTITY_ID'];
        }
    }

    if ($companyB24Id > 0) {
        $ajaxRegisterLog(
            'ajax-register-action: CRM company_id по RQ_INN inn=' . $post['inn'] . ' company_id=' . $companyB24Id
        );
        $crmCompanyResult = sendRequestB24('crm.company.update', [
            'id' => $companyB24Id,
            'fields' => $crmUfPayload,
        ]);
        if ($crmCompanyResult === null) {
            $ajaxRegisterLog(
                'ajax-register-action: CRM crm.company.update UF FAILED/null company_id=' . $companyB24Id
                . ' value=' . $legalEntityElementIdStr . ' (см. local/logs/b24-rest.log)'
            );
        } else {
            $ajaxRegisterLog(
                'ajax-register-action: CRM crm.company.update UF OK company_id=' . $companyB24Id
                . ' UF_CRM_1774915439581=' . $legalEntityElementIdStr
            );
        }
    } else {
        $ajaxRegisterLog(
            'ajax-register-action: CRM crm.requisite.list не вернул компанию по RQ_INN inn=' . $post['inn']
            . ' — crm.company.update не вызывался'
        );
    }
}

// Авторизуем пользователя
$USER->Authorize($newUserId);

echo json_encode([
    'success' => true,
    'message' => 'Регистрация успешно завершена',
    'redirect' => '/personal/lichnyj-kabinet.php',
], JSON_UNESCAPED_UNICODE);
