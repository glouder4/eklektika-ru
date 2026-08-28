<?php
/**
 * Compat-редирект со стандартных Bitrix auth-URL (из почтовых шаблонов)
 * на кастомные страницы личного кабинета.
 */
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

$changePassword = isset($_REQUEST['change_password']) && (string)$_REQUEST['change_password'] === 'yes';
$forgotPassword = isset($_REQUEST['forgot_password']) && (string)$_REQUEST['forgot_password'] === 'yes';

if ($changePassword) {
    $login = trim((string)($_REQUEST['USER_LOGIN'] ?? ''));
    $checkword = trim((string)($_REQUEST['USER_CHECKWORD'] ?? ''));
    $query = http_build_query([
        'change_password' => 'yes',
        'USER_LOGIN' => $login,
        'USER_CHECKWORD' => $checkword,
    ]);
    LocalRedirect('/personal/vosstanovlenie-parolya.php?' . $query);
}

if ($forgotPassword) {
    LocalRedirect('/personal/vosstanovlenie-parolya.php');
}

if (isset($_REQUEST['register']) && (string)$_REQUEST['register'] === 'yes') {
    LocalRedirect('/personal/registraciya.php');
}

LocalRedirect('/personal/vhod.php');
