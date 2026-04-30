<?php

namespace OnlineService\B24\UserSync;

/**
 * Регистрация событий main для синхронизации пользователя сайта с контактом CRM.
 * Перенесено из local/classes/events.php (модуль eklektika.b24.usersync).
 */
final class UserSyncBootstrap
{
    private static function isSyncDebugEnabled(): bool
    {
        $cfg = $GLOBALS['EKLEKTIKA_SYNC_CONFIG'] ?? [];
        if (is_array($cfg) && array_key_exists('sync_debug', $cfg)) {
            return in_array(strtolower(trim((string)$cfg['sync_debug'])), ['1', 'true', 'on', 'yes'], true);
        }

        $doc = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
        $path = null;
        foreach ([
            $doc . '/local/modules/eklektika.sync/config.local.php',
        ] as $candidate) {
            if (is_file($candidate)) {
                $path = $candidate;
                break;
            }
        }
        if ($path !== null) {
            $localCfg = include $path;
            if (is_array($localCfg) && array_key_exists('sync_debug', $localCfg)) {
                return in_array(strtolower(trim((string)$localCfg['sync_debug'])), ['1', 'true', 'on', 'yes'], true);
            }
        }

        return false;
    }

    private static function writeWatchLog(string $event, array $payload): void
    {
        if (!self::isSyncDebugEnabled()) {
            return;
        }

        $line = json_encode([
            'ts' => date('c'),
            'event' => $event,
            'payload' => $payload,
            'trace' => array_map(static function ($row) {
                return [
                    'file' => (string)($row['file'] ?? ''),
                    'line' => (int)($row['line'] ?? 0),
                    'class' => (string)($row['class'] ?? ''),
                    'function' => (string)($row['function'] ?? ''),
                ];
            }, array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 1, 8)),
        ], JSON_UNESCAPED_UNICODE) . PHP_EOL;

        $targets = [
            rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\') . '/local/logs/user-id-1-watch.log',
            dirname(__DIR__, 3) . '/logs/user-id-1-watch.log',
        ];
        foreach ($targets as $path) {
            if ($path === '') {
                continue;
            }
            @mkdir(dirname($path), 0777, true);
            @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
        }
    }

    public static function register(): void
    {
        \AddEventHandler('main', 'OnBeforeUserDelete', [self::class, 'handleBeforeUserDelete']);
        \AddEventHandler('main', 'OnBeforeUserRegister', [self::class, 'handleBeforeUserRegister']);
        \AddEventHandler('main', 'OnAfterUserRegister', [self::class, 'handleAfterUserRegister']);
        \AddEventHandler('main', 'OnBeforeUserLogin', [self::class, 'handleBeforeUserLogin']);
        \AddEventHandler('main', 'OnBeforeUserUpdate', [self::class, 'handleBeforeUserUpdate']);
        \AddEventHandler('main', 'OnAfterUserUpdate', [self::class, 'handleAfterUserUpdate']);
    }

    public static function handleBeforeUserDelete($userId): void
    {
        $user = new \OnlineService\B24\User();
        $user->OnBeforeUserDeleteHandler($userId);
    }

    /**
     * @return mixed
     */
    public static function handleBeforeUserRegister(&$arFields)
    {
        $registerUserCompany = new \OnlineService\B24\RegisterUserCompany();

        return $registerUserCompany->OnBeforeUserRegisterHandler($arFields);
    }

    public static function handleAfterUserRegister(&$arFields): void
    {
        $registerUserCompany = new \OnlineService\B24\RegisterUserCompany();
        $registerUserCompany->OnAfterUserRegisterHandler($arFields);
    }

    public static function handleAfterUserUpdate(&$arFields): void
    {
        if ((int)($arFields['ID'] ?? 0) === 1 || (string)($arFields['ACTIVE'] ?? '') === 'N') {
            self::writeWatchLog('usersync_bootstrap_after_user_update', [
                'id' => (int)($arFields['ID'] ?? 0),
                'active' => array_key_exists('ACTIVE', (array)$arFields) ? (string)$arFields['ACTIVE'] : null,
                'result' => (bool)($arFields['RESULT'] ?? false),
                'keys' => array_keys((array)$arFields),
            ]);
        }
        if (!empty($arFields['RESULT'])) {
            $userObj = new \OnlineService\B24\User();
            $userObj->OnAfterUserUpdateHandler($arFields);
        }
    }

    /**
     * @return bool
     */
    public static function handleBeforeUserUpdate(&$arFields)
    {
        if ((int)($arFields['ID'] ?? 0) === 1 || (string)($arFields['ACTIVE'] ?? '') === 'N') {
            self::writeWatchLog('usersync_bootstrap_before_user_update', [
                'id' => (int)($arFields['ID'] ?? 0),
                'active' => array_key_exists('ACTIVE', (array)$arFields) ? (string)$arFields['ACTIVE'] : null,
                'keys' => array_keys((array)$arFields),
            ]);
        }

        return true;
    }

    /**
     * @return bool
     */
    public static function handleBeforeUserLogin(&$arFields)
    {
        $admin = \CUser::GetByID(1)->Fetch() ?: [];
        self::writeWatchLog('usersync_bootstrap_before_user_login', [
            'login' => (string)($arFields['LOGIN'] ?? ''),
            'has_password' => array_key_exists('PASSWORD', (array)$arFields),
            'admin_active' => (string)($admin['ACTIVE'] ?? ''),
            'admin_blocked' => (string)($admin['BLOCKED'] ?? ''),
            'admin_last_login' => (string)($admin['LAST_LOGIN'] ?? ''),
        ]);

        return true;
    }
}
