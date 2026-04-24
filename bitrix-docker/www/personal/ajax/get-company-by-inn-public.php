<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;

header('Content-Type: application/json; charset=utf-8');

if (!Loader::includeModule('iblock')) {
    echo json_encode(['success' => false, 'error' => 'iblock module not loaded'], JSON_UNESCAPED_UNICODE);
    exit;
}

$inn = preg_replace('/\D/', '', (string)($_REQUEST['inn'] ?? ''));
if ($inn === '' || (strlen($inn) !== 10 && strlen($inn) !== 12)) {
    echo json_encode(['success' => true, 'company' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

$iblockId = 23;
// В исторических данных встречаются оба кода свойства ИНН.
// Используем точный фильтр (=PROPERTY_*) и затем подтверждаем значение свойства.
$ar = null;
$matchedInn = '';
$filters = [
    ['IBLOCK_ID' => $iblockId, '=PROPERTY_LEGAN_ENTITY_INN' => $inn],
    ['IBLOCK_ID' => $iblockId, '=PROPERTY_LEGAL_ENTITY_INN' => $inn],
];
foreach ($filters as $filter) {
    $rs = CIBlockElement::GetList(
        ['ID' => 'ASC'],
        $filter,
        false,
        ['nTopCount' => 1],
        ['ID', 'NAME']
    );
    $candidate = $rs->Fetch();
    if (!$candidate) {
        continue;
    }

    $candidateId = (int)($candidate['ID'] ?? 0);
    if ($candidateId <= 0) {
        continue;
    }

    $dbInnProps = CIBlockElement::GetProperty($iblockId, $candidateId, ['sort' => 'asc']);
    while ($innProp = $dbInnProps->Fetch()) {
        $innCode = (string)($innProp['CODE'] ?? '');
        if ($innCode !== 'LEGAN_ENTITY_INN' && $innCode !== 'LEGAL_ENTITY_INN') {
            continue;
        }
        $innVal = preg_replace('/\D/', '', (string)($innProp['VALUE'] ?? ''));
        if ($innVal === $inn) {
            $ar = $candidate;
            $matchedInn = $innVal;
            break 2;
        }
    }
}

if (!$ar) {
    echo json_encode(['success' => true, 'company' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

$elId = (int)$ar['ID'];
$company = [
    'id' => $elId,
    'inn' => $matchedInn !== '' ? $matchedInn : $inn,
    'name' => trim((string)($ar['NAME'] ?? '')),
    'address' => '',
    'activity' => '',
    'sait' => '',
];

$dbProps = CIBlockElement::GetProperty($iblockId, $elId, ['sort' => 'asc']);
$resolvedInnValues = [];
while ($prop = $dbProps->Fetch()) {
    $val = $prop['VALUE'] ?? '';
    $val = is_array($val) ? trim((string)($val[0] ?? '')) : trim((string)$val);
    $code = (string)($prop['CODE'] ?? '');
    if (($code === 'LEGAN_ENTITY_NAME' || $code === 'LEGAL_ENTITY_NAME') && $val !== '') {
        $company['name'] = $val;
    } elseif ($code === 'LEGAN_ENTITY_INN' || $code === 'LEGAL_ENTITY_INN') {
        $resolvedInnValues[] = $val;
    } elseif ($code === 'LEGAN_ENTITY_ADRESS' || $code === 'LEGAL_ENTITY_ADRESS') {
        $company['address'] = $val;
    } elseif ($code === 'LEGAN_ENTITY_ACTIVITY' || $code === 'LEGAL_ENTITY_ACTIVITY') {
        $company['activity'] = $val;
    } elseif ($code === 'LEGAN_ENTITY_WWW' || $code === 'LEGAL_ENTITY_WWW') {
        $company['sait'] = $val;
    }
}

echo json_encode(['success' => true, 'company' => $company], JSON_UNESCAPED_UNICODE);
