<?php

namespace OnlineService\B24\Registration\AjaxRegister;

use CUser;

/**
 * Подготовка полей Bitrix-пользователя и payload синхронизации с CRM.
 */
final class AjaxRegisterUserPayloadBuilder
{
    /**
     * @param array<string, string> $post
     */
    public static function allocateUniqueLogin(array $post): string
    {
        $phoneClean = \preg_replace('/\D/', '', $post['phone']);
        $emailPart = $post['email'] ? \substr(\md5($post['email']), 0, 6) : \substr(\uniqid(), -6);
        $loginBase = 'u' . $phoneClean . '_' . $emailPart;
        $login = $loginBase;
        $loginSuffix = 0;
        while (CUser::GetByLogin($login)->Fetch()) {
            $login = $loginBase . '_' . (++$loginSuffix);
        }

        return $login;
    }

    /**
     * @param array<string, string> $post
     *
     * @return array<string, mixed>
     */
    public static function buildUserFieldsForAdd(array $post, string $login): array
    {
        $phoneClean = \preg_replace('/\D/', '', $post['phone']);

        return [
            'LOGIN' => $login,
            'EMAIL' => $post['email'] ?: ('reg' . $phoneClean . '.' . \time() . '@temp.eklektika.local'),
            'PASSWORD' => $post['password'],
            'CONFIRM_PASSWORD' => $post['password'],
            'NAME' => $post['name'],
            'LAST_NAME' => $post['lastname'],
            'PERSONAL_PHONE' => $post['mobilephone'] ?: $post['phone'],
            'WORK_PHONE' => $post['phone'],
            'PERSONAL_STREET' => $post['address'],
            'UF_INN' => $post['inn'],
            'UF_WORK_PROFILE' => $post['activities'],
            'WORK_COMPANY' => $post['name_company'],
            'WORK_WWW' => $post['sait'],
            'UF_TYPE' => '5',
            'UF_NAME_COMPANY' => $post['name_company'],
            'UF_SITE' => $post['sait'],
            'UF_SPERE' => $post['activities'],
            'UF_JUR_ADDRESS' => $post['address'],
            'UF_COMPANY_INN' => $post['inn'],
            'ACTIVE' => 'N',
            'LID' => SITE_ID,
        ];
    }

    /**
     * @param array<string, string> $post
     * @param array<string, mixed>  $userFields
     *
     * @return array<string, mixed>
     */
    public static function buildSyncFields(
        array $post,
        int $newUserId,
        array $userFields,
        bool $isExistingCompanyByInn,
        $crmInnPrecheckPayload = null
    ): array
    {
        $crmWorkPhone = \trim($post['mobilephone']) !== '' ? \trim($post['mobilephone']) : \trim($post['phone']);
        $emailForCrm = \trim($post['email']) !== '' ? \trim($post['email']) : (string) $userFields['EMAIL'];

        $fields = [
            'USER_ID' => $newUserId,
            'EMAIL' => (string) $emailForCrm,
            'NAME' => (string) $post['name'],
            'SECOND_NAME' => '',
            'LAST_NAME' => (string) $post['lastname'],
            'PERSONAL_PHONE' => (string) $crmWorkPhone,
            'WORK_PHONE' => (string) $post['phone'],
            'WORK_POSITION' => '',
            'PERSONAL_BIRTHDAY' => '',
            'UF_CITY' => '',
            'UF_TYPE' => '5',
            'UF_INN' => (string) $post['inn'],
            'UF_NAME_COMPANY' => (string) $post['name_company'],
            'UF_ADVERSTERING_AGENT' => '',
            'UF_SITE' => (string) $post['sait'],
            'UF_SPERE' => (string) $post['activities'],
            'UF_JUR_ADDRESS' => (string) $post['address'],
            'UF_MAIN_PHONE' => (string) $post['phone'],
            'UF_MOBILE_PHONE' => (string) $post['mobilephone'],
            'UF_KPP' => '',
            'COMPANY_MODE' => $isExistingCompanyByInn ? 'existing' : 'new',
        ];

        if (\is_array($crmInnPrecheckPayload)) {
            $fields['CRM_INN_PRECHECK'] = $crmInnPrecheckPayload;
        }

        return $fields;
    }
}

