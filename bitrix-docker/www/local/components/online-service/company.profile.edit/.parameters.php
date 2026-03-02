<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentParameters = array(
    "PARAMETERS" => array(
        "COMPANY_ID" => array(
            "PARENT" => "BASE",
            "NAME" => "ID компании",
            "TYPE" => "STRING",
            "DEFAULT" => "={$_REQUEST['id']}"
        ),
        "CACHE_TIME" => array(
            "DEFAULT" => 0
        )
    )
);

