<?php

namespace OnlineService\Events;

/**
 * Статические обёртки для регистрации в EventManager без глобальных функций.
 */
final class SyncEventHandlers
{
    private static function shouldSkipUserSyncEvents(): bool
    {
        if (!empty($GLOBALS['OS_SKIP_USERSYNC_EVENTS'])) {
            return true;
        }
        return defined('OS_SKIP_USERSYNC_EVENTS') && OS_SKIP_USERSYNC_EVENTS === true;
    }

    public static function onBeforeUserDelete($userId): void
    {
        (new \OnlineService\B24\User())->OnBeforeUserDeleteHandler($userId);
    }

    /**
     * Регистрация через стандартный CUser::Register() не используется в ajax-register-action:
     * там вызывается CUser::Add(), который не шлёт OnBeforeUserRegister / OnAfterUserRegister.
     * Для Add() срабатывают OnBeforeUserAdd / OnAfterUserAdd (см. bitrix/modules/main/classes/general/user.php).
     *
     * @return mixed
     */
    public static function onBeforeUserAdd(&$arFields)
    {
        if (self::shouldSkipUserSyncEvents()) {
            return true;
        }
        if (defined('ADMIN_SECTION') && ADMIN_SECTION === true) {
            return true;
        }
        $registerUserCompany = new \OnlineService\B24\RegisterUserCompany();

        return $registerUserCompany->OnBeforeUserRegisterHandler($arFields);
    }

    public static function onAfterUserAdd(&$arFields): void
    {
        if (self::shouldSkipUserSyncEvents()) {
            return;
        }
        if (defined('ADMIN_SECTION') && ADMIN_SECTION === true) {
            return;
        }
        if (empty($arFields['ID'])) {
            return;
        }
        if (empty($arFields['USER_ID'])) {
            $arFields['USER_ID'] = $arFields['ID'];
        }
        $registerUserCompany = new \OnlineService\B24\RegisterUserCompany();
        $registerUserCompany->OnAfterUserRegisterHandler($arFields);
    }

    public static function onAfterUserUpdate(&$arFields): void
    {
        if ((int)($arFields['ID'] ?? 0) <= 1) {
            return;
        }
        // В части сценариев (в т.ч. CUser::Update из личного кабинета) ключ RESULT не передаётся — явный false = ошибка.
        if (\array_key_exists('RESULT', $arFields) && !$arFields['RESULT']) {
            return;
        }
        $userObj = new \OnlineService\B24\User();
        $userObj->OnAfterUserUpdateHandler($arFields);
    }

}
