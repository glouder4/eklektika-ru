<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

header('Content-Type: application/json; charset=utf-8');

$companies = require $_SERVER["DOCUMENT_ROOT"] . "/personal/ajax/get-companies-list.php";

$debug = isset($_GET['debug']) && $_GET['debug'] === '1';
if ($debug) {
    $iblockId = 23;
    $firstElId = null;
    $firstProps = [];
    if (\Bitrix\Main\Loader::includeModule('iblock')) {
        $rs2 = CIBlockElement::GetList([], ['IBLOCK_ID' => $iblockId], false, ['nTopCount' => 1], ['ID']);
        if ($ar2 = $rs2->Fetch()) {
            $firstElId = (int)$ar2['ID'];
            $dbP = CIBlockElement::GetProperty($iblockId, $firstElId);
            while ($p = $dbP->Fetch()) {
                $firstProps[] = ['CODE' => $p['CODE'] ?? '', 'VALUE' => $p['VALUE'] ?? ''];
            }
        }
    }
    echo json_encode(['companies' => $companies, 'debug' => ['total_elements' => count($companies), 'first_element_id' => $firstElId, 'first_element_properties' => $firstProps]], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['companies' => $companies], JSON_UNESCAPED_UNICODE);
}
