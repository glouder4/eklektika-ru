<?php
// /local/create-admin.php
// Запускать ОДИН РАЗ, потом удалить файл.

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

if (!\Bitrix\Main\Loader::includeModule('main')) {
    die('main module not loaded');
}

$login = 'admin';
$password = 'Wethab345';
$email = 'admin@' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

$user = new CUser();

// Если admin уже есть — обновим пароль и группы
$rs = CUser::GetByLogin($login);
if ($existing = $rs->Fetch()) {
    $userId = (int)$existing['ID'];
    $ok = $user->Update($userId, [
        'PASSWORD' => $password,
        'CONFIRM_PASSWORD' => $password,
        'ACTIVE' => 'Y',
        'GROUP_ID' => [1], // 1 = администраторы
    ]);

    if ($ok) {
        echo "Admin updated. ID={$userId}";
    } else {
        echo "Update error: " . $user->LAST_ERROR;
    }
    die();
}

// Создаём нового admin
$userId = $user->Add([
    'LOGIN' => $login,
    'NAME' => 'Admin',
    'LAST_NAME' => 'User',
    'EMAIL' => $email,
    'ACTIVE' => 'Y',
    'PASSWORD' => $password,
    'CONFIRM_PASSWORD' => $password,
    'GROUP_ID' => [1], // 1 = администраторы
    'LID' => SITE_ID,
]);

if ($userId > 0) {
    echo "Admin created. ID={$userId}";
} else {
    echo "Create error: " . $user->LAST_ERROR;
}