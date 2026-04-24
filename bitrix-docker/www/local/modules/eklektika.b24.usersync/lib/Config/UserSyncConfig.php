<?php

namespace OnlineService\B24\UserSync\Config;

final class UserSyncConfig
{
    public const ADMINISTRATORS_GROUP_ID = 1;
    public const MARKETING_AGENT_GROUP_ID = 7;
    public const DIRECTOR_GROUP_ID = 8;

    /**
     * Поле пользователя сайта: ID контакта в Bitrix24 (входящий `B24_ID` в `UPDATE_CONTACT` и т.п.).
     */
    public const USER_UF_CONTACT_B24_ID = 'UF_BITRIX24_ID';

    /** Легаси; резерв при поиске и дублируется при привязке контакта после регистрации. */
    public const USER_UF_CONTACT_B24_ID_LEGACY = 'UF_B24_USER_ID';
} 
