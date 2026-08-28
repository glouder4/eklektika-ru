<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Application;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Метод не поддерживается'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!check_bitrix_sessid()) {
    echo json_encode(['success' => false, 'error' => 'Неверная сессия'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once $_SERVER["DOCUMENT_ROOT"] . "/personal/ajax/bruteforce-password-reset.php";

$request = Application::getInstance()->getContext()->getRequest();
$action = trim((string)$request->getPost('action'));

$ip = pwd_reset_bruteforce_get_client_ip();
$status = pwd_reset_bruteforce_get_status($ip);

if ($status['blocked']) {
    $minutes = (int)ceil(($status['blocked_until'] - time()) / 60);
    echo json_encode([
        'success' => false,
        'error'   => 'Превышено количество попыток. Попробуйте снова через ' . $minutes . ' мин.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$neutralRequestMessage = 'Если аккаунт с таким e-mail существует, мы отправили инструкции';

if ($action === 'request') {
    $email = trim((string)$request->getPost('email'));

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        pwd_reset_bruteforce_record_failure($ip);
        // Анти-enumeration: тот же нейтральный ответ
        echo json_encode(['success' => true, 'message' => $neutralRequestMessage], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Важно: CUser::SendPassword → SendUserInfo(..., immediate=true) → CEvent::SendImmediate
    // не пишет в b_event, поэтому «события нет» в админке — норма для штатного SendPassword.
    // Здесь ставим USER_PASS_REQUEST в очередь (Send), чтобы:
    // 1) событие было видно в Настройки → Почтовые события / b_event;
    // 2) письмо шло тем же пайплайном, что остальная рабочая почта сайта.
    $siteId = (defined('SITE_ID') && SITE_ID) ? SITE_ID : 's1';
    $by = 'ID';
    $order = 'ASC';
    $rs = CUser::GetList(
        $by,
        $order,
        ['=EMAIL' => $email, 'ACTIVE' => 'Y'],
        ['FIELDS' => ['ID', 'LOGIN', 'EMAIL', 'ACTIVE']]
    );

    if ($ar = $rs->Fetch()) {
        $userId = (int)$ar['ID'];
        try {
            // bImmediate=false → CEvent::Send (очередь), не SendImmediate
            // Instance call — на части сборок SendUserInfo тоже не static
            (new CUser())->SendUserInfo(
                $userId,
                $siteId,
                'Запрос на восстановление пароля',
                false,
                'USER_PASS_REQUEST'
            );
            AddMessage2Log(
                'password-reset queued USER_PASS_REQUEST userId=' . $userId
                . ' email=' . $email
                . ' site=' . $siteId
                . ' captcha_opt=' . \COption::GetOptionString('main', 'captcha_restoring_password', 'N'),
                'password_reset'
            );
        } catch (\Throwable $e) {
            AddMessage2Log(
                'password-reset SendUserInfo exception for ' . $email . ': ' . $e->getMessage(),
                'password_reset'
            );
        }
    } else {
        AddMessage2Log(
            'password-reset user not found or inactive for email=' . $email,
            'password_reset'
        );
    }

    pwd_reset_bruteforce_record_failure($ip);

    echo json_encode(['success' => true, 'message' => $neutralRequestMessage], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'change') {
    $login = trim((string)$request->getPost('USER_LOGIN'));
    $checkword = trim((string)$request->getPost('USER_CHECKWORD'));
    $password = (string)$request->getPost('password');
    $confirm = (string)$request->getPost('password_confirm');

    if ($login === '' || $checkword === '' || $password === '' || $confirm === '') {
        pwd_reset_bruteforce_record_failure($ip);
        echo json_encode(['success' => false, 'error' => 'Заполните все поля'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    global $USER;
    if (!is_object($USER)) {
        $USER = new CUser();
    }
    $wasAuthorized = $USER->IsAuthorized();

    $siteId = (defined('SITE_ID') && SITE_ID) ? SITE_ID : 's1';
    // Instance call: на части сборок ChangePassword не static.
    // Без 8-го authActions (старое API) — автологин снимаем Logout ниже.
    $changeResult = $USER->ChangePassword(
        $login,
        $checkword,
        $password,
        $confirm,
        $siteId
    );

    if (isset($changeResult['TYPE']) && $changeResult['TYPE'] === 'ERROR') {
        pwd_reset_bruteforce_record_failure($ip);
        $msg = !empty($changeResult['MESSAGE'])
            ? strip_tags($changeResult['MESSAGE'])
            : 'Не удалось сменить пароль';
        echo json_encode(['success' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Контракт: без автологина после смены пароля
    if (!$wasAuthorized && $USER->IsAuthorized()) {
        $USER->Logout();
    }

    pwd_reset_bruteforce_clear_attempts($ip);

    echo json_encode([
        'success'  => true,
        'redirect' => '/personal/vhod.php',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

pwd_reset_bruteforce_record_failure($ip);
echo json_encode(['success' => false, 'error' => 'Неизвестное действие'], JSON_UNESCAPED_UNICODE);
