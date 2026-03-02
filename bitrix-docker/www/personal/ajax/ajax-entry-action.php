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

require_once $_SERVER["DOCUMENT_ROOT"] . "/personal/ajax/bruteforce-protection.php";

header('Content-Type: application/json; charset=utf-8');
$request = Application::getInstance()->getContext()->getRequest();

$username = trim((string)$request->getPost('username'));
$password = (string)$request->getPost('password');
$remember = $request->getPost('rememberme') === '1' || $request->getPost('rememberme') === 'Y';

$ip = bruteforce_get_client_ip();
$status = bruteforce_get_status($ip);

if ($status['blocked']) {
    $minutes = (int)ceil(($status['blocked_until'] - time()) / 60);
    echo json_encode([
        'success' => false,
        'error'   => 'Превышено количество попыток входа. Попробуйте снова через ' . $minutes . ' мин.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($username === '' || $password === '') {
    echo json_encode(['success' => false, 'error' => 'Введите e-mail и пароль'], JSON_UNESCAPED_UNICODE);
    exit;
}

global $USER;

// Определяем логин: если похоже на email — ищем пользователя по email
$login = $username;
if (strpos($username, '@') !== false && filter_var($username, FILTER_VALIDATE_EMAIL)) {
    $rs = CUser::GetList('ID', 'ASC', ['EMAIL' => $username], ['FIELDS' => ['LOGIN']]);
    if ($ar = $rs->Fetch()) {
        $login = $ar['LOGIN'];
    }
}

$arAuthResult = $USER->Login($login, $password, $remember ? 'Y' : 'N');

if (isset($arAuthResult['TYPE']) && $arAuthResult['TYPE'] === 'ERROR') {
    bruteforce_record_failure($ip);
    $msg = !empty($arAuthResult['MESSAGE']) ? $arAuthResult['MESSAGE'] : 'Неверный e-mail или пароль';
    echo json_encode(['success' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

bruteforce_clear_attempts($ip);

echo json_encode([
    'success'  => true,
    'redirect' => '/personal/lichnyj-kabinet.php',
], JSON_UNESCAPED_UNICODE);