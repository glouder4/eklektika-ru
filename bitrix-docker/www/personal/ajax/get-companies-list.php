<?php
/**
 * Возвращает список компаний из инфоблока 23 для формы регистрации.
 * Используется в registraciya.php (предзагрузка) и ajax-get-companies.php (API).
 */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    return [];
}

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    return [];
}

$iblockId = 23;
$companies = [];

$rs = CIBlockElement::GetList(
    ['NAME' => 'ASC'],
    ['IBLOCK_ID' => $iblockId],
    false,
    false,
    ['ID', 'NAME', 'ACTIVE']
);

while ($ar = $rs->Fetch()) {
    $elId = (int)$ar['ID'];
    $inn = '';
    $name = trim($ar['NAME'] ?? '');
    $phone = '';
    $address = '';
    $activity = '';
    $sait = '';

    $dbProps = CIBlockElement::GetProperty($iblockId, $elId, ['sort' => 'asc']);
    while ($prop = $dbProps->Fetch()) {
        $val = $prop['VALUE'] ?? '';
        $val = is_array($val) ? trim($val[0] ?? '') : trim((string)$val);
        $code = $prop['CODE'] ?? '';
        if ($code === 'LEGAN_ENTITY_INN' || $code === 'LEGAL_ENTITY_INN') $inn = $val;
        elseif ($code === 'LEGAN_ENTITY_NAME' || $code === 'LEGAL_ENTITY_NAME') { if ($val) $name = $val; }
        elseif ($code === 'LEGAN_ENTITY_PHONE' || $code === 'LEGAL_ENTITY_PHONE') $phone = $val;
        elseif ($code === 'LEGAN_ENTITY_ADRESS' || $code === 'LEGAL_ENTITY_ADRESS') $address = $val;
        elseif ($code === 'LEGAN_ENTITY_ACTIVITY' || $code === 'LEGAL_ENTITY_ACTIVITY') $activity = $val;
        elseif ($code === 'LEGAN_ENTITY_WWW' || $code === 'LEGAL_ENTITY_WWW') $sait = $val;
    }

    if (!$inn) continue;

    $companies[] = [
        'id' => $elId,
        'inn' => $inn,
        'name' => $name,
        'phone' => $phone,
        'address' => $address,
        'activity' => $activity,
        'sait' => $sait,
    ];
}

return $companies;
