<?php 
/**
 * Быстрый CLI-скрипт для автотеста регистрации без ручного ввода формы.
 *
 * Пример:
 *   php local/tools/register_test_user.php
 *   php local/tools/register_test_user.php --base-url=http://new.eklektika.ru --inn=111111111111
 *
 * Скрипт:
 * 1) открывает /personal/registraciya.php и забирает sessid;
 * 2) отправляет POST на /personal/ajax/ajax-register-action.php;
 * 3) печатает HTTP-код и JSON-ответ.
 */
declare(strict_types=1);

$opts = [];
$isCli = PHP_SAPI === 'cli';

if ($isCli) {
    $args = $argv ?? [];
    array_shift($args);
    foreach ($args as $arg) {
        if (strpos($arg, '--') === 0) {
            $eq = strpos($arg, '=');
            if ($eq !== false) {
                $opts[substr($arg, 2, $eq - 2)] = substr($arg, $eq + 1);
            } else {
                $opts[substr($arg, 2)] = '1';
            }
        }
    }
} else {
    $opts = is_array($_GET ?? null) ? $_GET : [];
    header('Content-Type: application/json; charset=utf-8');
}

$out = static function (array $payload, int $exitCode = 0) use ($isCli): void {
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($isCli) {
        echo $json . PHP_EOL;
    } else {
        if (!headers_sent()) {
            http_response_code($exitCode === 0 ? 200 : 500);
        }
        echo $json;
    }
    exit($exitCode);
};

$baseUrl = rtrim((string)($opts['base-url'] ?? 'http://new.eklektika.ru'), '/');
$inn = preg_replace('/\D+/', '', (string)($opts['inn'] ?? '111111111111'));
$name = (string)($opts['name'] ?? 'Андрей');
$lastName = (string)($opts['lastname'] ?? 'Егоров');
$phone = (string)($opts['phone'] ?? '+8 (917) 808-50-03');
$companyName = (string)($opts['company'] ?? 'test Название юридического лица');
$address = (string)($opts['address'] ?? 'Айская 79');
$activities = (string)($opts['activities'] ?? 'Сфера деятельности');
$site = (string)($opts['site'] ?? 'qqqq.ru');
$password = (string)($opts['password'] ?? 'Wethab345');
$mode = (string)($opts['mode'] ?? 'single'); // single | all

$uniq = date('YmdHis') . '_' . substr(bin2hex(random_bytes(4)), 0, 8);
$email = (string)($opts['email'] ?? ('autotest_' . $uniq . '@example.local'));

$cookieFile = sys_get_temp_dir() . '/eklektika_register_' . md5($uniq) . '.cookie';

$fetch = static function (string $url, array $post = null) use ($cookieFile): array {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
    ]);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $body = (string)curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $effectiveUrl = (string)curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $errNo = (int)curl_errno($ch);
    $err = (string)curl_error($ch);
    curl_close($ch);

    return ['code' => $code, 'body' => $body, 'errno' => $errNo, 'error' => $err, 'effective_url' => $effectiveUrl];
};

$pageResp = $fetch($baseUrl . '/personal/registraciya.php');
if ($pageResp['errno'] !== 0) {
    $out(['success' => false, 'stage' => 'get_registration_page', 'error' => $pageResp['error']], 1);
}
if ($pageResp['code'] < 200 || $pageResp['code'] >= 400) {
    $out(['success' => false, 'stage' => 'get_registration_page', 'error' => 'HTTP ' . $pageResp['code']], 1);
}

$extractSessid = static function (string $html): string {
    $patterns = [
        '/<input[^>]*name=["\']sessid["\'][^>]*value=["\']([^"\']+)["\']/i',
        '/<input[^>]*value=["\']([^"\']+)["\'][^>]*name=["\']sessid["\']/i',
        '/["\']bitrix_sessid["\']\s*:\s*["\']([^"\']+)["\']/i',
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $html, $m)) {
            $value = (string)($m[1] ?? '');
            if ($value !== '') {
                return $value;
            }
        }
    }

    return '';
};

$sessid = $extractSessid((string)$pageResp['body']);

if ($sessid === '') {
    // Bitrix fallback: получить sessid для текущей cookie-сессии.
    $sessResp = $fetch($baseUrl . '/bitrix/tools/ajax_sessid.php');
    if (($sessResp['errno'] ?? 0) === 0 && (int)($sessResp['code'] ?? 0) === 200) {
        $sessidCandidate = trim((string)($sessResp['body'] ?? ''));
        if ($sessidCandidate !== '' && preg_match('/^[a-f0-9]{16,}$/i', $sessidCandidate)) {
            $sessid = $sessidCandidate;
        }
    }
}

if ($sessid === '') {
    $out([
        'success' => false,
        'stage' => 'parse_sessid',
        'error' => 'sessid not found on registration page',
        'debug' => [
            'effective_url' => (string)($pageResp['effective_url'] ?? ''),
            'http_code' => (int)($pageResp['code'] ?? 0),
            'body_head' => substr((string)$pageResp['body'], 0, 1200),
        ],
    ], 1);
}

$runRegistration = static function (array $case) use ($fetch, $baseUrl, $sessid, $name, $lastName, $address, $activities, $site, $password): array {
    $payload = [
        'sessid' => $sessid,
        'name' => $name,
        'lastname' => $lastName,
        'main-phone' => (string)$case['phone'],
        'mobilephone' => (string)$case['phone'],
        'email' => (string)$case['email'],
        'password' => $password,
        'password_confirm' => $password,
        'inn' => (string)$case['inn'],
        'name_company' => (string)$case['company'],
        'address' => $address,
        'activities' => $activities,
        'sait' => $site,
    ];
    $postResp = $fetch($baseUrl . '/personal/ajax/ajax-register-action.php', $payload);
    $decoded = json_decode($postResp['body'], true);

    return [
        'case' => $case['id'],
        'http_code' => (int)$postResp['code'],
        'curl_errno' => (int)$postResp['errno'],
        'curl_error' => (string)$postResp['error'],
        'request' => [
            'email' => (string)$case['email'],
            'phone' => (string)$case['phone'],
            'inn' => (string)$case['inn'],
            'company' => (string)$case['company'],
        ],
        'response' => is_array($decoded) ? $decoded : ['raw' => (string)$postResp['body']],
    ];
};

if ($mode === 'all') {
    $basePhone = (string)($opts['phone'] ?? ('+8 (900) 100-' . substr($uniq, -4, 2) . '-' . substr($uniq, -2)));
    $altPhoneA = '+8 (900) 200-' . substr($uniq, -4, 2) . '-' . substr($uniq, -2);
    $altPhoneB = '+8 (900) 300-' . substr($uniq, -4, 2) . '-' . substr($uniq, -2);

    $cases = [
        [
            'id' => 'new-user-new-company',
            'email' => $email,
            'phone' => $basePhone,
            'inn' => $inn,
            'company' => $companyName,
        ],
        [
            'id' => 'duplicate-email',
            'email' => $email,
            'phone' => $altPhoneA,
            'inn' => $inn,
            'company' => $companyName,
        ],
        [
            'id' => 'duplicate-phone',
            'email' => 'autotest_dup_phone_' . $uniq . '@example.local',
            'phone' => $basePhone,
            'inn' => $inn,
            'company' => $companyName,
        ],
        [
            'id' => 'same-company-second-user',
            'email' => 'autotest_same_company_' . $uniq . '@example.local',
            'phone' => $altPhoneB,
            'inn' => $inn,
            'company' => $companyName,
        ],
    ];

    $results = [];
    foreach ($cases as $case) {
        $results[] = $runRegistration($case);
    }
    @unlink($cookieFile);
    $out([
        'success' => true,
        'mode' => 'all',
        'results' => $results,
    ], 0);
}

$single = $runRegistration([
    'id' => 'single',
    'email' => $email,
    'phone' => $phone,
    'inn' => $inn,
    'company' => $companyName,
]);
@unlink($cookieFile);

if (($single['curl_errno'] ?? 0) !== 0) {
    $out(['success' => false, 'stage' => 'post_registration', 'error' => $single['curl_error'] ?? 'curl failed'], 1);
}

$out([
    'success' => true,
    'mode' => 'single',
    'result' => $single,
], 0);

