<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = array(
    "NAME" => "Редактирование профиля компании",
    "DESCRIPTION" => "Компонент для редактирования данных компании",
    "ICON" => "/images/icon.gif",
    "SORT" => 10,
    "PATH" => array(
        "ID" => "online-service",
        "NAME" => "Online Service",
        "CHILD" => array(
            "ID" => "company",
            "NAME" => "Компании"
        )
    ),
);

