<?php

namespace OnlineService\Site\Config;

final class CompanyModuleConfig
{
    public const COMPANY_IBLOCK_ID = 23;

    /**
     * Значение списка CRM (`UF_CRM_1777030197`) → ID группы пользователя на сайте (`b_group`).
     *
     * @var array<int, int>
     */
    public const COMPANY_STATUS_GROUP_ID_MAP = [
        26 => 9, // 20% (UF_CRM_1777030197)
        27 => 10, // 25%
        28 => 11, // 30%
        29 => 12, // 32%
        30 => 13, // 35%
        31 => 14, // 37%
        32 => 15, // 38%
        33 => 16, // 40%
    ];

    /**
     * ID группы пользователя на сайте → процент скидки от оптовой базы на витрине.
     *
     * @var array<int, float>
     */
    public const COMPANY_DISCOUNT_PERCENT_BY_ASSIGNED_GROUP_ID = [
        9 => 20.0,
        10 => 25.0,
        11 => 30.0,
        12 => 32.0,
        13 => 35.0,
        14 => 37.0,
        15 => 38.0,
        16 => 40.0,
    ];

    /**
     * Соответствие ID пользовательского профиля заказа -> поля компании/пользователя.
     *
     * @var array<int, string>
     */
    public const ORDER_CUSTOM_FIELD_IDS = [
        8 => 'OS_COMPANY_NAME',
        10 => 'OS_COMPANY_INN',
        12 => 'USER_NAME__USER_LASTNAME',
        13 => 'OS_COMPANY_EMAIL',
        14 => 'OS_COMPANY_PHONE',
    ];

    /**
     * @return array<int, int>
     */
    public static function getCompanyStatusGroupIdMap(): array
    {
        return self::COMPANY_STATUS_GROUP_ID_MAP;
    }

    /**
     * @return array<int, float>
     */
    public static function getCompanyDiscountPercentByAssignedGroupId(): array
    {
        return self::COMPANY_DISCOUNT_PERCENT_BY_ASSIGNED_GROUP_ID;
    }

    /**
     * Значения списка (CRM/ИБ) для «головной компании холдинга».
     * Не использовать (bool)(int) — любой положительный ID был бы true.
     *
     * 31520 — id enum в фильтре {@see \OnlineService\B24\User::getHeadCompany}; 2074 — приход из вебхука UPDATE_COMPANY.
     *
     * @return list<int>
     */
    public static function getHeadOfHoldingCrmListYesValueIds(): array
    {
        return [31520, 2074];
    }

    /** ID варианта списка ИБ 23: «Рекламный агент» = да (входящий JSON `true` / CRM). */
    public const COMPANY_IBLOCK_LIST_MARKETING_AGENT_YES_ENUM_ID = 2076;

    /** ID варианта списка ИБ 23: головная компания = да (в т.ч. {@see self::getHeadOfHoldingCrmListYesValueIds}). */
    public const COMPANY_IBLOCK_LIST_HEAD_COMPANY_YES_ENUM_ID = 2074;
}
