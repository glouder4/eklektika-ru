<?php
 
declare(strict_types=1);

namespace OnlineService\Sync\FromCrm;

/**
 * Коды пользовательских полей CRM (Bitrix24), приходящие во входящем payload на сайт,
 * и привязка к полям сайта. Расширять по мере согласования с порталом.
 *
 * @see bitrix-docker/www/local/sync/docs/functional-contract.md
 */
final class CrmInboundUfMap
{
    // --- Контакт (crm.contact), ключи как в REST/исходящем Updater на сайт ---

    /** «Рекламный агент» на контакте → на сайте хранится в UF пользователя (опечатка в коде сайта сохранена). */
    public const CONTACT_ADVERTISING_AGENT_UF = 'UF_CRM_1775034008956';

    /** «Руководитель компании» (контакт CRM) → на сайте `b_user.UF_IS_DIRECTOR`. */
    public const CONTACT_IS_DIRECTOR_UF = 'UF_CRM_1777068292434';

    // --- Компания (crm.company), для обработчиков UPDATE_COMPANY / реквизитов (пока только константы) ---

    /** Это рекламный агент */
    public const COMPANY_IS_ADVERTISING_AGENT_UF = 'UF_CRM_1774915252680';

    /** Является головной компанией холдинга */
    public const COMPANY_IS_HEAD_OF_HOLDING_UF = 'UF_CRM_1775030726726';

    /**
     * Холдинг (связь с головной компанией).
     * Внимание: при несовпадении с порталом проверьте полный код поля в CRM и поправьте константу.
     */
    public const COMPANY_HOLDING_UF = 'UF_CRM_1775032393';

    /** Скидка компании (источник для OS_COMPANY_DISCOUNT_VALUE). */
    public const COMPANY_DISCOUNT_UF = 'UF_CRM_1777030197';

    /** Фирмы холдинга (множественное поле CRM компании). */
    public const COMPANY_HOLDING_COMPANIES_UF = 'UF_CRM_1777030108';

    /** ID элемента инфоблока на сайте (связь компании CRM ↔ карточка на сайте) */
    public const COMPANY_SITE_IBLOCK_ELEMENT_ID_UF = 'UF_CRM_1774915439581';

    /**
     * ID пользователей сайта (b_user.ID) в payload UPDATE_COMPANY с CRM.
     * Код поля совпадает с {@see \OnlineService\B24\UserSync\Config\RegisterUserCompanyConfig::CRM_CONTACT_SITE_USER_ID_FIELD} на контакте.
     */
    public const COMPANY_SITE_USER_IDS_UF = 'UF_CRM_1776075126830';

    /** crm.company: основной телефон (UF) → свойство элемента {@see \OnlineService\Site\Company} `LEGAN_MAIN_PHONE` при UPDATE_COMPANY. */
    public const COMPANY_CRM_MAIN_PHONE_UF = 'UF_CRM_1777069666894';

    /** crm.company: мобильный телефон (UF) → `LEGAN_MOBILE_PHONE` при UPDATE_COMPANY. */
    public const COMPANY_CRM_MOBILE_PHONE_UF = 'UF_CRM_1777069676348';

    /** crm.company: город → `OS_COMPANY_CITY` (+ зеркало `LEGAN_ENTITY_CITY`) при UPDATE_COMPANY. */
    public const COMPANY_CRM_CITY_UF = 'UF_CRM_1775034571084';

    /** crm.company: веб-сайт → `OS_COMPANY_WEB_SITE` (+ зеркало `LEGAN_ENTITY_WWW`) при UPDATE_COMPANY. */
    public const COMPANY_CRM_WEB_SITE_UF = 'UF_CRM_1777119084064';

    /** crm.company: сфера деятельности → `OS_COMPANY_ACTIVITY` (+ зеркало `LEGAN_ENTITY_ACTIVITY`) при UPDATE_COMPANY. */
    public const COMPANY_CRM_ACTIVITY_UF = 'UF_CRM_1777119807943';

    /** crm.company: юридический адрес → `OS_COMPANY_JUR_ADDRESS` (+ зеркало `LEGAN_ENTITY_ADRESS`) при UPDATE_COMPANY. */
    public const COMPANY_CRM_JUR_ADDRESS_UF = 'UF_CRM_1777120939583';

    /**
     * Сырое значение для синхронизации группы рекламного агента (до нормализации в int для b_user).
     */
    public static function peekMarketingAgentRawValue(array $fields): mixed
    {
        if (\array_key_exists('IS_MARKETING_AGENT', $fields)) {
            $v = $fields['IS_MARKETING_AGENT'];
            if (self::isMarketingPayloadAbsent($v)) {
                return null;
            }

            return $v;
        }
        if (\array_key_exists(self::CONTACT_ADVERTISING_AGENT_UF, $fields)) {
            $v = $fields[self::CONTACT_ADVERTISING_AGENT_UF];
            // Пустая строка с CRM = «не менять маркетинг», а не «выключить агента»
            if (self::isMarketingPayloadAbsent($v)) {
                return null;
            }

            return $v;
        }
        if (\array_key_exists('UF_ADVERSTERING_AGENT', $fields)) {
            return $fields['UF_ADVERSTERING_AGENT'];
        }

        return null;
    }

    /** null / пустая строка / только пробелы — не считаем явным значением для синхронизации агента */
    private static function isMarketingPayloadAbsent(mixed $v): bool
    {
        if ($v === null) {
            return true;
        }
        if (\is_string($v) && \trim($v) === '') {
            return true;
        }

        return false;
    }

    /**
     * Публичная обёртка для канала CRM → сайт и зеркального to-crm (сборка POST на портале).
     */
    public static function marketingInboundSignalAbsent(mixed $v): bool
    {
        return self::isMarketingPayloadAbsent($v);
    }

    /**
     * Явное «да» для маркетингового признака (совпадает с нормализацией во входящем payload).
     */
    public static function marketingInboundSignalTrue(mixed $v): bool
    {
        return self::crmValueIsTruthy($v);
    }

    /**
     * Явное «нет» (отдельно от «неизвестно»: нечисловые id вариантов списка CRM не считаем false).
     */
    public static function marketingInboundSignalFalse(mixed $v): bool
    {
        return $v === false || $v === 0 || $v === '0' || $v === 'N' || $v === 'n'
            || $v === 'Нет' || $v === 'нет' || $v === 'off' || $v === 'OFF';
    }

    /**
     * Подготовка payload пользователя перед CUser::Update:
     * - маппинг известных UF_CRM_* контакта → поля b_user;
     * - удаление всех оставшихся UF_CRM_* (в Б24 нет таких полей у пользователя — не валим Update).
     *
     * @param array<string, mixed> $fields
     */
    public static function prepareUserUpdatePayload(array &$fields): void
    {
        $crmAdv = self::CONTACT_ADVERTISING_AGENT_UF;
        if (\array_key_exists($crmAdv, $fields)) {
            $rawAdv = $fields[$crmAdv];
            if (self::isMarketingPayloadAbsent($rawAdv)) {
                unset($fields[$crmAdv]);
            } else {
                $fields['UF_ADVERSTERING_AGENT'] = self::toUserBoolInt($rawAdv);
                unset($fields[$crmAdv]);
            }
        }

        $crmDir = self::CONTACT_IS_DIRECTOR_UF;
        if (\array_key_exists($crmDir, $fields)) {
            $rawDir = $fields[$crmDir];
            if (self::isMarketingPayloadAbsent($rawDir)) {
                unset($fields[$crmDir]);
            } else {
                $fields['UF_IS_DIRECTOR'] = self::toUserBoolInt($rawDir);
                unset($fields[$crmDir]);
            }
        }

        foreach (\array_keys($fields) as $key) {
            if (\is_string($key) && \str_starts_with($key, 'UF_CRM_')) {
                unset($fields[$key]);
            }
        }
    }

    private static function toUserBoolInt(mixed $v): int
    {
        return self::crmValueIsTruthy($v) ? 1 : 0;
    }

    /**
     * 0/1 для {@see self::CONTACT_IS_DIRECTOR_UF} в `crm.contact.update` по значению `b_user.UF_IS_DIRECTOR`.
     */
    public static function userDirectorUfToCrmInt(mixed $v): int
    {
        return self::toUserBoolInt($v);
    }

    private static function crmValueIsTruthy(mixed $v): bool
    {
        return $v === true || $v === 1 || $v === '1' || $v === 'Y' || $v === 'y'
            || $v === 'Да' || $v === 'да' || $v === 'on' || $v === 'ON';
    }
}
