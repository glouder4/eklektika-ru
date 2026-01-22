<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

// Подключаем класс
require_once __DIR__ . '/class.php';

// Запускаем
$component = new \OnlineService\OrderForm\OrderFormComponent($this);
$component->execute();