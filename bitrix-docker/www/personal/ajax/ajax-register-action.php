<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use OnlineService\B24\Registration\AjaxRegisterActionService;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

// В этом endpoint цепочка bootstrap модулей может не быть выполнена автоматически.
// Подключаем единый bootstrap eklektika.* чтобы был зарегистрирован autoload registration/usersync.
$eklektikaRequiresPath = $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/eklektika_requires.php';
if (is_file($eklektikaRequiresPath)) {
    require_once $eklektikaRequiresPath;
}

// Fallback: если bootstrap не подключил autoload модуля registration (нестандартные окружения),
// подключаем include.php модуля напрямую.
if (!class_exists(AjaxRegisterActionService::class, false)) {
    $registrationInclude = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/eklektika.b24.registration/include.php';
    if (is_file($registrationInclude)) {
        require_once $registrationInclude;
    }
}

header('Content-Type: application/json; charset=utf-8');

if (!check_bitrix_sessid()) {
    echo json_encode(['success' => false, 'error' => 'Неверная сессия'], JSON_UNESCAPED_UNICODE);
    exit;
}

global $USER;
if ((int) $USER->GetID() > 0) {
    echo json_encode(['success' => false, 'error' => 'Вы уже авторизованы'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!Loader::includeModule('iblock')) {
    echo json_encode(['success' => false, 'error' => 'Модуль инфоблоков не загружен'], JSON_UNESCAPED_UNICODE);
    exit;
}

$request = Application::getInstance()->getContext()->getRequest();
$data= json_encode(AjaxRegisterActionService::run($request), JSON_UNESCAPED_UNICODE);

echo $data;
