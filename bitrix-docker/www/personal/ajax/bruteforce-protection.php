<?php
/**
 * Защита от брутфорса при входе: блокировка IP после N неудачных попыток.
 * Данные хранятся в JSON-файле.
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    return;
}

define('BRUTEFORCE_MAX_ATTEMPTS', 5);
define('BRUTEFORCE_BLOCK_MINUTES', 30);

/**
 * @return string IP клиента
 */
function bruteforce_get_client_ip() {
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

/**
 * @return string Путь к файлу с данными
 */
function bruteforce_get_storage_path() {
    $dir = $_SERVER["DOCUMENT_ROOT"] . '/upload/bruteforce_login';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir . '/attempts.json';
}

/**
 * @return array
 */
function bruteforce_load_data() {
    $path = bruteforce_get_storage_path();
    if (!file_exists($path)) {
        return [];
    }
    $raw = @file_get_contents($path);
    if ($raw === false) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * @param array $data
 */
function bruteforce_save_data($data) {
    $path = bruteforce_get_storage_path();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $fp = @fopen($path, 'c+');
    if (!$fp) return;
    if (flock($fp, LOCK_EX)) {
        ftruncate($fp, 0);
        fwrite($fp, json_encode($data, JSON_UNESCAPED_UNICODE));
        flock($fp, LOCK_UN);
    }
    fclose($fp);
}

/**
 * Удаляет устаревшие записи (старше 24 часов)
 * @param array $data
 * @return array
 */
function bruteforce_cleanup($data) {
    $now = time();
    $maxAge = 86400; // 24 часа
    foreach ($data as $ip => $entry) {
        $last = (int)($entry['last_attempt'] ?? $entry['first_attempt'] ?? 0);
        if ($now - $last > $maxAge && ($entry['blocked_until'] ?? 0) < $now) {
            unset($data[$ip]);
        }
    }
    return $data;
}

/**
 * Проверяет, заблокирован ли IP.
 * @param string $ip
 * @return array ['blocked' => bool, 'blocked_until' => int|null, 'attempts_left' => int|null]
 */
function bruteforce_get_status($ip) {
    $data = bruteforce_load_data();
    $entry = $data[$ip] ?? null;
    if (!$entry) {
        return ['blocked' => false, 'blocked_until' => null, 'attempts_left' => BRUTEFORCE_MAX_ATTEMPTS];
    }

    $blockedUntil = (int)($entry['blocked_until'] ?? 0);
    $now = time();

    if ($blockedUntil > $now) {
        return [
            'blocked' => true,
            'blocked_until' => $blockedUntil,
            'attempts_left' => null,
        ];
    }

    $attempts = (int)($entry['attempts'] ?? 0);
    return [
        'blocked' => false,
        'blocked_until' => null,
        'attempts_left' => max(0, BRUTEFORCE_MAX_ATTEMPTS - $attempts),
    ];
}

/**
 * Регистрирует неудачную попытку входа.
 * @param string $ip
 * @return array ['blocked' => bool, 'blocked_until' => int|null]
 */
function bruteforce_record_failure($ip) {
    $data = bruteforce_load_data();
    $data = bruteforce_cleanup($data);

    $entry = $data[$ip] ?? ['attempts' => 0, 'first_attempt' => time()];
    $entry['attempts'] = (int)($entry['attempts'] ?? 0) + 1;
    $entry['last_attempt'] = time();
    if (!isset($entry['first_attempt'])) {
        $entry['first_attempt'] = $entry['last_attempt'];
    }

    $blockedUntil = 0;
    if ($entry['attempts'] >= BRUTEFORCE_MAX_ATTEMPTS) {
        $blockedUntil = time() + BRUTEFORCE_BLOCK_MINUTES * 60;
        $entry['blocked_until'] = $blockedUntil;
    }

    $data[$ip] = $entry;
    bruteforce_save_data($data);

    return [
        'blocked' => $blockedUntil > 0,
        'blocked_until' => $blockedUntil > 0 ? $blockedUntil : null,
    ];
}

/**
 * Сбрасывает счётчик попыток для IP (успешный вход).
 * @param string $ip
 */
function bruteforce_clear_attempts($ip) {
    $data = bruteforce_load_data();
    $data = bruteforce_cleanup($data);
    unset($data[$ip]);
    bruteforce_save_data($data);
}
