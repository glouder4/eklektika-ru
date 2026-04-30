<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
 

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

// Проверка sessid (обязательно!)
if (!check_bitrix_sessid()) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Неверная сессия'], JSON_UNESCAPED_UNICODE);
    exit;
}
  
global $USER;
$userId = $USER->GetID();

if ($userId > 0) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Вы уже авторизованы'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!Loader::includeModule('iblock')) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Модуль инфоблоков не загружен'], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
$request = Application::getInstance()->getContext()->getRequest();

$normalizeInnValue = static function (string $inn): string {
    return (string)preg_replace('/\D+/', '', $inn);
};

$logRegisterResolution = static function (string $event, array $context = []): void {
    $logPath = (string)($_SERVER['DOCUMENT_ROOT'] ?? '') . '/local/logs/ajax-register-action.log';
    if ($logPath === '/local/logs/ajax-register-action.log') {
        return;
    }

    $payload = [
        'ts' => date('c'),
        'event' => $event,
        'context' => $context,
    ];
    @file_put_contents($logPath, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
};

/**
 * Server-side resolve компании по ИНН: dropdown не источник истины.
 *
 * @return array{status: 'none'|'exact'|'ambiguous', company: array<string, mixed>|null}
 */
$resolveSiteCompanyByInn = static function (string $inn) use ($normalizeInnValue, $logRegisterResolution): array {
    $safeInnHash = static function (string $value): string {
        return substr(sha1($value), 0, 8);
    };
    $buildSafeCrmErrorContext = static function (\Throwable $e): array {
        return [
            'error_type' => get_class($e),
            'error_code' => is_scalar($e->getCode()) ? (string)$e->getCode() : '0',
            'error_fingerprint' => substr(sha1(get_class($e) . '|' . (string)$e->getCode()), 0, 12),
        ];
    };
    $inn = $normalizeInnValue($inn);
    if ($inn === '' || (strlen($inn) !== 10 && strlen($inn) !== 12)) {
        return ['status' => 'none', 'company' => null];
    }

    $requestInnHash = $safeInnHash((string)$inn);
    $iblockId = 23;
    $filters = [
        ['IBLOCK_ID' => $iblockId, '=PROPERTY_LEGAN_ENTITY_INN' => $inn],
        ['IBLOCK_ID' => $iblockId, '=PROPERTY_LEGAL_ENTITY_INN' => $inn],
        ['IBLOCK_ID' => $iblockId, '=PROPERTY_OS_COMPANY_INN' => $inn],
    ];
    $maxCandidateIdsForExact = 200;
    $candidateOverflow = false;
    $candidateIds = [];
    foreach ($filters as $filter) {
        $rs = CIBlockElement::GetList(
            ['ID' => 'ASC'],
            $filter,
            false,
            false,
            ['ID']
        );
        while ($row = $rs->Fetch()) {
            $id = (int)($row['ID'] ?? 0);
            if ($id > 0) {
                $candidateIds[$id] = true;
                if (count($candidateIds) > $maxCandidateIdsForExact) {
                    $candidateOverflow = true;
                    break;
                }
            }
        }
        if ($candidateOverflow) {
            break;
        }
    }
    if ($candidateOverflow) {
        $logRegisterResolution('resolve_site_company_by_inn_ambiguous_overflow', [
            'inn_hash' => $requestInnHash,
            'prefilter_candidate_count' => count($candidateIds),
            'exact_candidate_count' => 0,
            'candidate_limit' => $maxCandidateIdsForExact,
            'decision' => 'ambiguous',
            'reason_code' => 'prefilter_overflow_limit',
        ]);
        return ['status' => 'ambiguous', 'company' => null];
    }
    if ($candidateIds === []) {
        $logRegisterResolution('resolve_site_company_by_inn_none', [
            'inn_hash' => $requestInnHash,
        ]);
        return ['status' => 'none', 'company' => null];
    }

    $loadCandidate = static function (int $companyId) use ($iblockId, $inn, $normalizeInnValue, $safeInnHash): ?array {
        $rsEl = CIBlockElement::GetList(['ID' => 'ASC'], ['IBLOCK_ID' => $iblockId, 'ID' => $companyId], false, ['nTopCount' => 1], ['ID', 'NAME']);
        $el = $rsEl->Fetch();
        if (!$el) {
            return null;
        }

        $company = [
            'id' => $companyId,
            'inn' => $inn,
            'name' => trim((string)($el['NAME'] ?? '')),
            'address' => '',
            'activity' => '',
            'site' => '',
        ];
        $resolvedInnHashes = [];
        $b24CompanyId = 0;
        $dbProps = CIBlockElement::GetProperty($iblockId, $companyId, ['sort' => 'asc']);
        while ($prop = $dbProps->Fetch()) {
            $code = (string)($prop['CODE'] ?? '');
            $val = $prop['VALUE'] ?? '';
            $val = is_array($val) ? trim((string)($val[0] ?? '')) : trim((string)$val);
            if ($code === 'LEGAN_ENTITY_NAME' || $code === 'LEGAL_ENTITY_NAME' || $code === 'OS_COMPANY_NAME') {
                if ($val !== '') {
                    $company['name'] = $val;
                }
            } elseif ($code === 'LEGAN_ENTITY_ADRESS' || $code === 'LEGAL_ENTITY_ADRESS' || $code === 'OS_COMPANY_JUR_ADDRESS') {
                if ($val !== '') {
                    $company['address'] = $val;
                }
            } elseif ($code === 'LEGAN_ENTITY_ACTIVITY' || $code === 'LEGAL_ENTITY_ACTIVITY' || $code === 'OS_COMPANY_ACTIVITY') {
                if ($val !== '') {
                    $company['activity'] = $val;
                }
            } elseif ($code === 'LEGAN_ENTITY_WWW' || $code === 'LEGAL_ENTITY_WWW' || $code === 'OS_COMPANY_WEB_SITE') {
                if ($val !== '') {
                    $company['site'] = $val;
                }
            } elseif ($code === 'LEGAN_ENTITY_INN' || $code === 'LEGAL_ENTITY_INN' || $code === 'OS_COMPANY_INN') {
                $normalizedPropInn = $normalizeInnValue($val);
                if ($normalizedPropInn !== '') {
                    $resolvedInnHashes[] = $safeInnHash((string)$normalizedPropInn);
                }
            } elseif ($code === 'OS_COMPANY_B24_ID') {
                $b24CompanyId = (int)$val;
            }
        }

        $resolvedInnHashes = array_values(array_unique($resolvedInnHashes));
        $requestInnHash = $safeInnHash((string)$inn);
        $isExactByProps = in_array($requestInnHash, $resolvedInnHashes, true);

        return [
            'company' => $company,
            'is_exact' => $isExactByProps,
            'b24_company_id' => $b24CompanyId,
            'company_id' => $companyId,
            'resolved_inn_hashes' => $resolvedInnHashes,
            'request_inn_hash' => $requestInnHash,
        ];
    };

    $candidates = [];
    foreach (array_keys($candidateIds) as $candidateId) {
        $candidate = $loadCandidate((int)$candidateId);
        if ($candidate !== null) {
            if ((bool)$candidate['is_exact']) {
                $candidates[] = $candidate;
            }
        }
    }
    if ($candidates === []) {
        $logRegisterResolution('resolve_site_company_by_inn_none_after_props', [
            'inn_hash' => $requestInnHash,
            'prefilter_candidate_count' => count($candidateIds),
            'exact_candidate_count' => 0,
            'decision' => 'none',
            'reason_code' => 'none_after_props_no_exact',
            'company_ids' => array_values(array_map('intval', array_keys($candidateIds))),
        ]);
        return ['status' => 'none', 'company' => null];
    }
    if (count($candidates) === 1) {
        $resolved = $candidates[0];
        $logRegisterResolution('resolve_site_company_by_inn_exact_single', [
            'inn_hash' => $requestInnHash,
            'company_id' => (int)$resolved['company_id'],
            'has_b24_id' => (int)($resolved['b24_company_id'] ?? 0) > 0,
        ]);
        return ['status' => 'exact', 'company' => (array)$resolved['company']];
    }

    $withB24Id = array_values(array_filter($candidates, static function (array $candidate): bool {
        return (int)($candidate['b24_company_id'] ?? 0) > 0;
    }));

    $verifiedInCrm = [];
    $crmValidationAttempted = false;
    if (function_exists('sendRequestB24')) {
        foreach ($withB24Id as $candidate) {
            $b24Id = (int)($candidate['b24_company_id'] ?? 0);
            if ($b24Id <= 0) {
                continue;
            }
            $crmValidationAttempted = true;
            try {
                $crmCompany = sendRequestB24('crm.company.get', ['id' => $b24Id], false);
                if (is_array($crmCompany) && (int)($crmCompany['ID'] ?? 0) === $b24Id) {
                    $verifiedInCrm[] = $candidate;
                }
            } catch (\Throwable $e) {
                $logRegisterResolution('resolve_site_company_by_inn_crm_get_error', [
                    'inn_hash' => $requestInnHash,
                    'b24_id' => $b24Id,
                    'error' => $buildSafeCrmErrorContext($e),
                ]);
            } 
        }
    }

    $priorityPool = $verifiedInCrm;
    if (!$crmValidationAttempted && count($withB24Id) === 1) {
        $priorityPool = $withB24Id;
    }
    if (count($priorityPool) === 1) {
        $resolved = $priorityPool[0];
        $logRegisterResolution('resolve_site_company_by_inn_exact_b24_priority', [
            'inn_hash' => $requestInnHash,
            'company_id' => (int)$resolved['company_id'],
            'b24_id' => (int)$resolved['b24_company_id'],
            'crm_validation_attempted' => $crmValidationAttempted,
        ]);
        return ['status' => 'exact', 'company' => (array)$resolved['company']];
    }

    if (count($priorityPool) > 1 && function_exists('sendRequestB24')) {
        $narrowedByRequisite = [];
        foreach ($priorityPool as $candidate) {
            $b24Id = (int)($candidate['b24_company_id'] ?? 0);
            if ($b24Id <= 0) {
                continue;
            }
            try {
                $requisites = sendRequestB24('crm.requisite.list', [
                    'fields' => [],
                    'params' => [],
                    'select' => ['ID', 'ENTITY_ID', 'RQ_INN'],
                    'filter' => [
                        'ENTITY_TYPE_ID' => 4,
                        'RQ_INN' => $inn,
                        'ENTITY_ID' => $b24Id,
                    ],
                ], false);
                if (is_array($requisites) && !empty($requisites)) {
                    $narrowedByRequisite[] = $candidate;
                }
            } catch (\Throwable $e) {
                $logRegisterResolution('resolve_site_company_by_inn_requisite_error', [
                    'inn_hash' => $requestInnHash,
                    'b24_id' => $b24Id,
                    'error' => $buildSafeCrmErrorContext($e),
                ]);
            }
        }
        if (count($narrowedByRequisite) === 1) {
            $resolved = $narrowedByRequisite[0];
            $logRegisterResolution('resolve_site_company_by_inn_exact_requisite', [
                'inn_hash' => $requestInnHash,
                'company_id' => (int)$resolved['company_id'],
                'b24_id' => (int)$resolved['b24_company_id'],
            ]);
            return ['status' => 'exact', 'company' => (array)$resolved['company']];
        }
        if (count($narrowedByRequisite) > 1) {
            $logRegisterResolution('resolve_site_company_by_inn_ambiguous_requisite', [
                'inn_hash' => $requestInnHash,
                'prefilter_candidate_count' => count($candidateIds),
                'exact_candidate_count' => count($candidates),
                'requisite_candidate_count' => count($narrowedByRequisite),
                'decision' => 'ambiguous',
                'reason_code' => 'ambiguous_after_requisite',
                'company_ids' => array_values(array_map(static function (array $candidate): int {
                    return (int)($candidate['company_id'] ?? 0);
                }, $narrowedByRequisite)),
                'b24_ids' => array_values(array_map(static function (array $candidate): int {
                    return (int)($candidate['b24_company_id'] ?? 0);
                }, $narrowedByRequisite)),
            ]);
            return ['status' => 'ambiguous', 'company' => null];
        }
    }

    $logRegisterResolution('resolve_site_company_by_inn_ambiguous', [
        'inn_hash' => $requestInnHash,
        'prefilter_candidate_count' => count($candidateIds),
        'exact_candidate_count' => count($candidates),
        'decision' => 'ambiguous',
        'reason_code' => 'ambiguous_exact_candidates',
        'candidates_with_b24' => count($withB24Id),
        'candidates_verified_in_crm' => count($verifiedInCrm),
        'company_ids' => array_values(array_map(static function (array $candidate): int {
            return (int)($candidate['company_id'] ?? 0);
        }, $candidates)),
    ]);
    return ['status' => 'ambiguous', 'company' => null];
};

$post = [
    'name'           => trim((string)$request->getPost('name')),
    'lastname'       => trim((string)$request->getPost('lastname')),
    'mobilephone'    => trim((string)$request->getPost('mobilephone')),
    'phone'          => trim((string)$request->getPost('main-phone')),
    'address'        => trim((string)$request->getPost('address')),
    'inn'            => $normalizeInnValue(trim((string)$request->getPost('inn'))),
    'activities'     => trim((string)$request->getPost('activities')),
    'name_company'   => trim((string)$request->getPost('name_company')),
    'sait'           => trim((string)$request->getPost('sait')),
    'email'          => trim((string)$request->getPost('email')),
    'password'       => (string)$request->getPost('password'),
    'password_confirm'=> (string)$request->getPost('password_confirm'),
];

$required = [
    'name'         => 'Имя',
    'lastname'     => 'Фамилия',
    'phone'        => 'Телефон',
    'email'        => 'E-mail',
    'name_company' => 'Название юридического лица',
    'inn'          => 'ИНН организации',
    'password'     => 'Пароль',
];

$missing = [];
foreach ($required as $postKey => $label) {
    if ($post[$postKey] === '') {
        $missing[] = $label;
    }
}

if (!empty($missing)) {
    echo json_encode([
        'success' => false,
        'error'   => 'Заполните обязательные поля: ' . implode(', ', $missing)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (strlen($post['inn']) !== 10 && strlen($post['inn']) !== 12) {
    echo json_encode([
        'success' => false,
        'error'   => 'ИНН организации должен содержать 10 или 12 цифр'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$resolvedCompany = $resolveSiteCompanyByInn((string)$post['inn']);
if ($resolvedCompany['status'] === 'ambiguous') {
    echo json_encode([
        'success' => false,
        'error'   => 'По указанному ИНН найдено несколько компаний. Обратитесь к менеджеру для регистрации.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
$existingCompany = $resolvedCompany['status'] === 'exact' ? (array)($resolvedCompany['company'] ?? []) : [];
$isExistingCompanyByInn = !empty($existingCompany);
if ($isExistingCompanyByInn) {
    // Для существующей компании значения из формы не должны менять карточку компании.
    $post['name_company'] = (string)($existingCompany['name'] ?? $post['name_company']);
    $post['address'] = (string)($existingCompany['address'] ?? $post['address']);
    $post['activities'] = (string)($existingCompany['activity'] ?? $post['activities']);
    $post['sait'] = (string)($existingCompany['site'] ?? $post['sait']);
}

if ($post['password'] !== $post['password_confirm']) {
    echo json_encode([
        'success' => false,
        'error'   => 'Пароли не совпадают'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (strlen($post['password']) < 6) {
    echo json_encode([
        'success' => false,
        'error'   => 'Пароль должен быть не менее 6 символов'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($post['email'] !== '' && !filter_var($post['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'error'   => 'Введите корректный e-mail'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$countUsersByFilter = static function (array $filter): int {
    $rs = CUser::GetList($by = 'id', $order = 'asc', $filter, ['FIELDS' => ['ID']]);
    $count = 0;
    while ($rs->Fetch()) {
        $count++;
    }
    return $count;
};
$normalizePhone = static function (string $phone): string {
    $digits = preg_replace('/\D+/', '', $phone);
    if ($digits === '') {
        return '';
    }
    if (strlen($digits) === 11 && $digits[0] === '8') {
        $digits = '7' . substr($digits, 1);
    }
    if (strlen($digits) === 10) {
        $digits = '7' . $digits;
    }
    return $digits;
};
$emailDupCount = $post['email'] !== '' ? $countUsersByFilter(['=EMAIL' => $post['email']]) : 0;
$phoneDupCount = 0;
$phoneDupIds = [];
if (trim((string)$post['phone']) !== '') {
    $targetPhone = $normalizePhone((string)$post['phone']);
    $rsUsersByPhone = CUser::GetList(
        $by = 'id',
        $order = 'asc',
        ['>ID' => 0],
        ['FIELDS' => ['ID', 'PERSONAL_PHONE', 'WORK_PHONE']]
    );
    while ($u = $rsUsersByPhone->Fetch()) {
        $personalPhone = $normalizePhone((string)($u['PERSONAL_PHONE'] ?? ''));
        $workPhone = $normalizePhone((string)($u['WORK_PHONE'] ?? ''));
        if ($targetPhone !== '' && ($personalPhone === $targetPhone || $workPhone === $targetPhone)) {
            $phoneDupCount++;
            $phoneDupIds[] = (int)($u['ID'] ?? 0);
        }
    }
}

if ($emailDupCount > 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Пользователь с таким e-mail уже существует',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($phoneDupCount > 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Пользователь с таким телефоном уже существует',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Проверка уникальности логина
$phoneClean = preg_replace('/\D/', '', $post['phone']);
$emailPart = $post['email'] ? substr(md5($post['email']), 0, 6) : substr(uniqid(), -6);
$loginBase = 'u' . $phoneClean . '_' . $emailPart;
$login = $loginBase;
$loginSuffix = 0;
while (CUser::GetByLogin($login)->Fetch()) {
    $login = $loginBase . '_' . (++$loginSuffix);
}

$userFields = [
    'LOGIN'             => $login,
    'EMAIL'             => $post['email'] ?: ('reg' . $phoneClean . '.' . time() . '@temp.eklektika.local'),
    'PASSWORD'          => $post['password'],
    'CONFIRM_PASSWORD'  => $post['password'],
    'NAME'              => $post['name'],
    'LAST_NAME'         => $post['lastname'],
    'PERSONAL_PHONE'    => $post['mobilephone'] ?: $post['phone'],
    'WORK_PHONE'        => $post['phone'],
    'PERSONAL_STREET'   => $post['address'],
    'UF_INN'            => $post['inn'],
    'UF_WORK_PROFILE'   => $post['activities'],
    'WORK_COMPANY'      => $post['name_company'],
    'WORK_WWW'          => $post['sait'],
    // CRM createB24Company: юр.лицо (5), дублируем поля компании в UF_* для Bitrix24
    'UF_TYPE'           => '5',
    'UF_NAME_COMPANY'     => $post['name_company'],
    'UF_SITE'             => $post['sait'],
    'UF_SPERE'            => $post['activities'],
    'UF_JUR_ADDRESS'      => $post['address'],
    'ACTIVE'            => 'N',
    'LID'               => SITE_ID,
];

if (!defined('OS_SKIP_USERSYNC_EVENTS')) {
    define('OS_SKIP_USERSYNC_EVENTS', true);
}
$GLOBALS['OS_SKIP_USERSYNC_EVENTS'] = true;

$cUser = new CUser();
$newUserId = $cUser->Add($userFields);

if (!$newUserId) {
    $errMsg = $cUser->LAST_ERROR ?: 'Не удалось создать пользователя';
    echo json_encode([
        'success' => false,
        'error'   => $errMsg
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$safeSyncCompleted = false;
$safeSyncError = '';

if (class_exists('\OnlineService\B24\RegisterUserCompany')) {
    $crmWorkPhone = trim($post['mobilephone']) !== '' ? trim($post['mobilephone']) : trim($post['phone']);
    $emailForCrm = trim($post['email']) !== '' ? trim($post['email']) : (string)$userFields['EMAIL'];
    $syncFields = [
        'USER_ID' => (int)$newUserId,
        'EMAIL' => (string)$emailForCrm,
        'NAME' => (string)$post['name'],
        'SECOND_NAME' => '',
        'LAST_NAME' => (string)$post['lastname'],
        'PERSONAL_PHONE' => (string)$crmWorkPhone,
        'WORK_PHONE' => (string)$post['phone'],
        'WORK_POSITION' => '',
        'PERSONAL_BIRTHDAY' => '',
        'UF_CITY' => '',
        'UF_TYPE' => '5',
        'UF_INN' => (string)$post['inn'],
        'UF_NAME_COMPANY' => (string)$post['name_company'],
        'UF_ADVERSTERING_AGENT' => '',
        'UF_SITE' => (string)$post['sait'],
        'UF_SPERE' => (string)$post['activities'],
        'UF_JUR_ADDRESS' => (string)$post['address'],
        'UF_MAIN_PHONE' => (string)$post['phone'],
        'UF_MOBILE_PHONE' => (string)$post['mobilephone'],
        'UF_KPP' => '',
        'COMPANY_MODE' => $isExistingCompanyByInn ? 'existing' : 'new',
    ];
    try {
        $sync = new \OnlineService\B24\RegisterUserCompany();
        $syncOk = $sync->syncFromSiteRegistration($syncFields);
        $safeSyncCompleted = (bool)$syncOk;
        if (!$safeSyncCompleted) {
            $syncFailureMessage = '';
            if (isset($APPLICATION) && is_object($APPLICATION) && method_exists($APPLICATION, 'GetException')) {
                $syncException = $APPLICATION->GetException();
                if ($syncException && method_exists($syncException, 'GetString')) {
                    $syncFailureMessage = trim((string)$syncException->GetString());
                }
            }
            if ($syncFailureMessage !== '') {
                $safeSyncError = $syncFailureMessage;
            } else {
                $safeSyncError = 'Синхронизация с CRM завершилась с ошибкой.';
            }
        }
    } catch (\Throwable $e) {
        $safeSyncError = 'Не удалось синхронизировать регистрацию с CRM.';
    }
} else {
    $safeSyncError = 'Модуль синхронизации CRM недоступен.';
}

// legacy локальная синхронизация компании отключена:
// компания/контакт и запись B24 ID выполняются единым safeSync потоком.
if (!$safeSyncCompleted) {
    (new \CUser())->Delete((int)$newUserId);
    echo json_encode([
        'success' => false,
        'error' => $safeSyncError !== '' ? $safeSyncError : 'Регистрация не завершена: не удалось синхронизировать данные с CRM.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$userAfterSync = \CUser::GetByID((int)$newUserId)->Fetch();

if (($userAfterSync['ACTIVE'] ?? '') !== 'N') {
    $forceInactive = new CUser();
    $forceInactive->Update((int)$newUserId, ['ACTIVE' => 'N']);
}

echo json_encode([
    'success' => true,
    'message' => 'Регистрация успешно завершена',
    /** Главная: показ баннера «ожидается модерация» по GET reg_pending=1 */
    'redirect' => '/?reg_pending=1',
], JSON_UNESCAPED_UNICODE);
