<?php

namespace OnlineService\Events;

/**
 * Статические обёртки для регистрации в EventManager без глобальных функций.
 */
final class SyncEventHandlers
{
    public static function onBeforeUserDelete($userId): void
    {
        (new \OnlineService\B24\User())->OnBeforeUserDeleteHandler($userId);
    }

    /**
     * @return mixed
     */
    public static function onBeforeUserRegister(&$arFields)
    {
        $registerUserCompany = new \OnlineService\B24\RegisterUserCompany();

        return $registerUserCompany->OnBeforeUserRegisterHandler($arFields);
    }

    public static function onAfterUserRegister(&$arFields): void
    {
        $registerUserCompany = new \OnlineService\B24\RegisterUserCompany();
        $registerUserCompany->OnAfterUserRegisterHandler($arFields);
    }

    public static function onAfterUserUpdate(&$arFields): void
    {
        if (isset($arFields['RESULT']) && $arFields['RESULT']) {
            $userObj = new \OnlineService\B24\User();
            $userObj->OnAfterUserUpdateHandler($arFields);
        }
    }
}
