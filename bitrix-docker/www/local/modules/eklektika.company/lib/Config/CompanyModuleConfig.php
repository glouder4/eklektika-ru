<?php

namespace OnlineService\Site\Config;

final class CompanyModuleConfig
{
    public const COMPANY_IBLOCK_ID = 23;

    /**
     * Боевой маппинг: ID группы статуса (после UserGroups::searchGroup) -> ID группы для присвоения пользователю.
     *
     * @var array<int, int>
     */ 
    public const PROD_COMPANY_STATUS_GROUP_ID_MAP = [
        26 => 9, // 20%
        27 => 10, // 25%
        28 => 11, // 30%
        28 => 12, // 32%
        30 => 13, // 35%
        31 => 14, // 37%
        32 => 15, // 38%
        33 => 16, // 40%
    ];

    /**
     * Боевой маппинг: ID группы пользователя -> процент скидки от оптовой базы на витрине.
     *
     * @var array<int, float>
     */
    public const PROD_COMPANY_DISCOUNT_PERCENT_BY_ASSIGNED_GROUP_ID = [
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
     * Тестовый маппинг. Заполнить отдельной матрицей тестового портала при наличии отличий от боевого.
     *
     * @var array<int, int>
     */
    public const TEST_COMPANY_STATUS_GROUP_ID_MAP = [
        26 => 9, // 20%
        27 => 10, // 25%
        28 => 11, // 30%
        28 => 12, // 32%
        30 => 13, // 35%
        31 => 14, // 37%
        32 => 15, // 38%
        33 => 16, // 40%
    ];

    /**
     * Тестовый маппинг. Заполнить отдельной матрицей тестового портала при наличии отличий от боевого.
     *
     * @var array<int, float>
     */
    public const TEST_COMPANY_DISCOUNT_PERCENT_BY_ASSIGNED_GROUP_ID = [
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
        return self::isTestPortal()
            ? self::TEST_COMPANY_STATUS_GROUP_ID_MAP
            : self::PROD_COMPANY_STATUS_GROUP_ID_MAP;
    }

    /**
     * @return array<int, float>
     */
    public static function getCompanyDiscountPercentByAssignedGroupId(): array
    {
        return self::isTestPortal()
            ? self::TEST_COMPANY_DISCOUNT_PERCENT_BY_ASSIGNED_GROUP_ID
            : self::PROD_COMPANY_DISCOUNT_PERCENT_BY_ASSIGNED_GROUP_ID;
    }

    private static function isTestPortal(): bool
    {
        return \defined('B24_USE_TEST_PORTAL') ? (bool)\B24_USE_TEST_PORTAL : false;
    }
}
