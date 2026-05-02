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

    /**
     * Карточки менеджера на сайте (привязка пользователя к элементу ИБ).
     * Во входящем UPDATE_CONTACT значение UF — это «внешний» идентификатор в CRM;
     * на сайте ищем элемент по свойству {@see self::MANAGER_CARD_BITRIX24_PROPERTY_CODE}.
     */
    public const MANAGER_CARD_IBLOCK_ID = 24;

    public const MANAGER_CARD_BITRIX24_PROPERTY_CODE = 'BITRIX24_ID';

    /** Менеджер 1 / 2 (UF пользователя на сайте), значения конвертируются из CRM в ID элемента ИБ. */
    public const USER_UF_PERSONAL_MANAGER_1 = 'UF_PERSONAL_MANAGER_1';

    public const USER_UF_PERSONAL_MANAGER_2 = 'UF_PERSONAL_MANAGER_2';

    /**
     * Массив ID компании в Bitrix24 (`crm.company`), те же значения что {@see \OnlineService\Site\Company} `OS_COMPANY_B24_ID` на элементе ИБ 23.
     * Во входящем payload — компании, где контакт является **сотрудником**; сайт идемпотентно добавляет пользователя в `LEGAN_ENTITY_USERS`
     * (снятие `UF_IS_DIRECTOR` не удаляет из этого списка — только из BOSS через отдельную синхронизацию).
     */
    public const CONTACT_ASSOCIATED_COMPANY_B24_IDS_FIELD = 'ASSOCIATED_WITH_ENTITY';
}
