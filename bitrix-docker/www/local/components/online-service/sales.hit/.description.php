<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
    "NAME" => "Хиты продаж (по свойству предложения)",
    "DESCRIPTION" => "Выводит элементы инфоблока, у которых есть хотя бы одно торговое предложение со свойством HIT_PRODAZH = 'YES'",
    "ICON" => "/images/icon.gif", // можно опустить или добавить позже
    "SORT" => 10,
    "CACHE_PATH" => "Y",
    "PATH" => [
        "ID" => "custom", // или "content", "e-store" — как вам удобно
        "NAME" => "Пользовательские компоненты",
        "CHILD" => [
            "ID" => "catalog",
            "NAME" => "Каталог"
        ]
    ]
];