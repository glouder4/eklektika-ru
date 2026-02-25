<?php
/**
 * Возвращает данные компании из инфоблока 23 по ИНН и проверяет, является ли userId руководителем.
 * @param string $inn ИНН организации
 * @param int $userId ID пользователя для проверки LEGAN_ENTITY_BOSS
 * @return array|null ['id'=>int, 'name'=>'', 'phone'=>'', 'address'=>'', 'activity'=>'', 'sait'=>'', 'isBoss'=>bool] или null
 */
function getCompanyByInn($inn, $userId) {
    if (!\Bitrix\Main\Loader::includeModule('iblock')) {
        return null;
    }

    $inn = preg_replace('/\D/', '', (string)$inn);
    $userId = (int)$userId;
    if (!$inn || !$userId) return null;

    $iblockId = 23;
    $rs = CIBlockElement::GetList(
    ['ID' => 'ASC'],
    ['IBLOCK_ID' => $iblockId, 'PROPERTY_LEGAN_ENTITY_INN' => $inn],
    false,
    ['nTopCount' => 1],
    ['ID', 'NAME']
    );

    if (!$ar = $rs->Fetch()) return null;

    $elId = (int)$ar['ID'];
    $name = trim($ar['NAME'] ?? '');
    $phone = '';
    $address = '';
    $activity = '';
    $sait = '';
    $bossId = null;

    $dbProps = CIBlockElement::GetProperty($iblockId, $elId, ['sort' => 'asc']);
    while ($prop = $dbProps->Fetch()) {
        $val = $prop['VALUE'] ?? '';
        $val = is_array($val) ? trim($val[0] ?? '') : trim((string)$val);
        $code = $prop['CODE'] ?? '';
        if ($code === 'LEGAN_ENTITY_NAME' || $code === 'LEGAL_ENTITY_NAME') { if ($val) $name = $val; }
        elseif ($code === 'LEGAN_ENTITY_PHONE' || $code === 'LEGAL_ENTITY_PHONE') $phone = $val;
        elseif ($code === 'LEGAN_ENTITY_ADRESS' || $code === 'LEGAL_ENTITY_ADRESS') $address = $val;
        elseif ($code === 'LEGAN_ENTITY_ACTIVITY' || $code === 'LEGAL_ENTITY_ACTIVITY') $activity = $val;
        elseif ($code === 'LEGAN_ENTITY_WWW' || $code === 'LEGAL_ENTITY_WWW') $sait = $val;
        elseif ($code === 'LEGAN_ENTITY_BOSS' || $code === 'LEGAL_ENTITY_BOSS') $bossId = (int)$val;
    }

    return [
        'id' => $elId,
        'inn' => $inn,
        'name' => $name,
        'phone' => $phone,
        'address' => $address,
        'activity' => $activity,
        'sait' => $sait,
        'isBoss' => ($bossId > 0 && $bossId === $userId),
    ];
}
