<?php

namespace OnlineService\Events;

/**
 * Статические обёртки для регистрации в EventManager без глобальных функций.
 * Размещение файла: модуль eklektika.b24.usersync (ранее local/events/SyncEventHandlers.php).
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

    /**
     * Только OnAfterUserAdd: чтобы ajax-register-action мог выполнить CRM через syncFromSiteRegistration,
     * не дублируя OnAfterUserRegisterHandler, при этом OnBeforeUserAdd всё ещё отрабатывает (n8n pre-check).
     */
    private static function shouldSkipUserSyncAfterAdd(): bool
    {
        if (!empty($GLOBALS['OS_SKIP_USERSYNC_AFTER_USER_ADD'])) {
            return true;
        }

        return defined('OS_SKIP_USERSYNC_AFTER_USER_ADD') && OS_SKIP_USERSYNC_AFTER_USER_ADD === true;
    }

    public static function onBeforeUserDelete($userId): void
    {
        (new \OnlineService\B24\User())->OnBeforeUserDeleteHandler($userId);
    }

    /**
     * Регистрация через стандартный CUser::Register() не используется в ajax-register-action:
     * там вызывается CUser::Add(), который не шлёт OnBeforeUserRegister / OnAfterUserRegister.
     * Для Add() срабатывают OnBeforeUserAdd / OnAfterUserAdd (см. bitrix/modules/main/classes/general/user.php).
     * Ajax задаёт только OS_SKIP_USERSYNC_AFTER_USER_ADD: до Add отрабатывает OnBeforeUserRegisterHandler (n8n pre-check),
     * после Add legacy OnAfter не вызывается — CRM синхронизирует syncFromSiteRegistration.
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
        $orchestrator = new \OnlineService\B24\Registration\CrmRegistrationOrchestrator();

        return $orchestrator->OnBeforeUserRegisterHandler($arFields);
    }

    public static function onAfterUserAdd(&$arFields): void
    {
        if (self::shouldSkipUserSyncEvents() || self::shouldSkipUserSyncAfterAdd()) {
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
        $orchestrator = new \OnlineService\B24\Registration\CrmRegistrationOrchestrator();
        $orchestrator->OnAfterUserRegisterHandler($arFields);
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
