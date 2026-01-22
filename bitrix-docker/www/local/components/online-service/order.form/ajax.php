<?php
// /local/components/online-service/order.form/ajax.php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    // Разрешаем вызов только через Bitrix
    define('NO_KEEP_STATISTIC', true);
    define('NOT_CHECK_PERMISSIONS', true);
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
}

use Bitrix\Main\Loader;
use Bitrix\Main\Application;

// Подключаем модули
if (!Loader::includeModule('sale')) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Модуль sale не подключен']);
    exit;
}

// Проверяем, что запрос POST и AJAX
$request = Application::getInstance()->getContext()->getRequest();
if (!$request->isPost() || !$request->isAjaxRequest()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Только AJAX POST']);
    exit;
}

// Проверка сессии
if (!check_bitrix_sessid()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Неверная сессия']);
    exit;
}

// Подключаем основной класс компонента
require_once __DIR__ . '/class.php';

try {
    // Создаём "виртуальный" компонент (без $this)
    $dummyComponent = new \CBitrixComponent();
    $dummyComponent->InitComponentTemplate(); // чтобы работал IncludeComponentTemplate (если нужно)

    $handler = new \OnlineService\OrderForm\OrderFormComponent($dummyComponent);

    // Выполняем только обработку формы
    $result = $handler->handleAjaxRequest($request);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка сервера: ' . $e->getMessage()
    ]);
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';