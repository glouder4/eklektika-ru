<?php

namespace OnlineService\B24\UserSync\Config;

final class RegisterUserCompanyConfig
{
    public const ASSIGNED_BY_ID = 3036;

    public const REGISTRATION_NOTE_DEFAULT = 'Пользователь зарегистрировался через сайт';
    public const REGISTRATION_NOTE_AD_AGENT = 'Пользователь зарегистрировался как рекламный агент';

    public const CRM_CONTACT_CITY_FIELD = 'UF_CRM_3804624445810';
    public const CRM_CONTACT_NOTE_FIELD = 'UF_CRM_1701839165901';
    public const CRM_CONTACT_SITE_USER_ID_FIELD = 'UF_CRM_3804624445748';
    public const CRM_CONTACT_AD_AGENT_FIELD = 'UF_CRM_1698752707853';

    public const CRM_COMPANY_SPHERE_FIELD = 'UF_CRM_1669208000616';
    public const CRM_COMPANY_JUR_ADDRESS_FIELD = 'UF_CRM_1669208295583';
    public const CRM_COMPANY_CITY_FIELD = 'UF_CRM_1618551330657';

    public const CRM_REQUISITES_FILE_FIELD = 'UF_CRM_1755643990423';

    /**
     * Возвращает поле CRM для файла реквизитов.
     */
    public static function getRequisitesFileField(): string
    {
        return self::CRM_REQUISITES_FILE_FIELD;
    }
}
