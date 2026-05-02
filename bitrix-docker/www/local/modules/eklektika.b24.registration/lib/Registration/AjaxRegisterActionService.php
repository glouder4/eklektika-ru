<?php

namespace OnlineService\B24\Registration;

use Bitrix\Main\Request;
use CUser;
use OnlineService\B24\Registration\AjaxRegister\AjaxRegisterCrmContactPrecheck;
use OnlineService\B24\Registration\AjaxRegister\AjaxRegisterBitrixApplication;
use OnlineService\B24\Registration\AjaxRegister\AjaxRegisterDuplicateGuard;
use OnlineService\B24\Registration\AjaxRegister\AjaxRegisterExecutionContext;
use OnlineService\B24\Registration\AjaxRegister\AjaxRegisterPostParser;
use OnlineService\B24\Registration\AjaxRegister\AjaxRegisterResponse;
use OnlineService\B24\Registration\AjaxRegister\AjaxRegisterSiteCompanyResolver;
use OnlineService\B24\Registration\AjaxRegister\AjaxRegisterUserPayloadBuilder;

/**
 * Точка входа ajax-register-action: конвейер шагов регистрации юр. лица.
 * Доменная логика: {@see \OnlineService\B24\Registration\AjaxRegister\AjaxRegisterPostParser},
 * {@see \OnlineService\B24\Registration\AjaxRegister\AjaxRegisterSiteCompanyResolver},
 * {@see \OnlineService\B24\Registration\AjaxRegister\AjaxRegisterDuplicateGuard},
 * {@see \OnlineService\B24\Registration\AjaxRegister\AjaxRegisterUserPayloadBuilder},
 * {@see \OnlineService\B24\Registration\AjaxRegister\AjaxRegisterBitrixApplication}.
 *
 * Скрипт {@see /personal/ajax/ajax-register-action.php} только подключает пролог и делегирует сюда.
 */
final class AjaxRegisterActionService
{
    /**
     * @return array<string, mixed> Payload для json_encode (всегда есть ключ success)
     */
    public static function run(Request $request): array
    {
        $ctx = new AjaxRegisterExecutionContext(AjaxRegisterPostParser::parse($request));

        $halt = self::registerSegmentCompanyPrechecks($ctx) // компания: ИНН/n8n/ИБ (и отказ при ambiguous)
            ?? self::registerSegmentUserPrechecksAndCreate($ctx); // пользователь: проверки + CUser::Add

        if ($halt !== null) {
            return $halt->toArray();
        }

        return self::registerStepSyncCrmAndFinalize($ctx)->toArray();
    }

    /**
     * Сегмент 1. Сначала определяемся с компанией (и проверяем её вдоль и поперёк):
     * - обязательные поля (в т.ч. ИНН/название компании)
     * - локальная длина ИНН
     * - пречек ИНН в CRM (n8n) и фиксация payload (для COMPANY_MODE)
     * - поиск компании в ИБ сайта по ИНН (включая отказ при неоднозначности)
     */
    private static function registerSegmentCompanyPrechecks(AjaxRegisterExecutionContext $ctx): ?AjaxRegisterResponse
    {
        return self::registerStepValidateRequiredFields($ctx) // обязательные поля формы
            ?? self::registerStepValidateInnLength($ctx) // ИНН: длина 10 или 12 цифр (локально)
            ?? self::registerStepCrmPrecheckCompanyInnViaN8n($ctx) // CRM: ИНН организации (crm-check-inn-v1), пишет crmInnPrecheckPayload
            ?? self::registerStepResolveSiteCompany($ctx); // сайт: карточка компании в ИБ по ИНН
    }

    /**
     * Сегмент 2. После того как решение по компании принято, проверяем пользователя и создаём его:
     * - уникальность контакта в CRM (n8n)
     * - пароль/e-mail и локальные дубликаты
     * - сбор полей пользователя и CRM-precheck (OnBeforeUserRegisterHandler)
     * - CUser::Add
     */
    private static function registerSegmentUserPrechecksAndCreate(AjaxRegisterExecutionContext $ctx): ?AjaxRegisterResponse
    {
        return self::registerStepCrmPrecheckContactUniqueViaN8n($ctx) // CRM: уникальность e-mail/телефона (crm-check-unique-contact-v1)
            ?? self::registerStepValidatePasswordAndEmail($ctx) // пароль и e-mail
            ?? self::registerStepRejectLocalDuplicates($ctx) // сайт: дубликаты e-mail/телефона в b_user
            ?? self::registerStepBuildUserFields($ctx) // поля для CUser::Add
            ?? self::registerStepMarkCrmPrecheckDone($ctx) // не вызывать повторный legacy-precheck (unique/inn уже сделаны)
            ?? self::registerStepCreateBitrixUser($ctx); // CUser::Add
    }

    private static function registerStepValidateRequiredFields(AjaxRegisterExecutionContext $ctx): ?AjaxRegisterResponse
    {
        $missingLabels = AjaxRegisterPostParser::collectMissingRequiredFields($ctx->post);
        if ($missingLabels !== []) {
            return AjaxRegisterResponse::fail('Заполните обязательные поля: ' . \implode(', ', $missingLabels));
        }

        return null;
    }

    private static function registerStepValidateInnLength(AjaxRegisterExecutionContext $ctx): ?AjaxRegisterResponse
    {
        if (\strlen($ctx->post['inn']) !== 10 && \strlen($ctx->post['inn']) !== 12) {
            return AjaxRegisterResponse::fail('ИНН организации должен содержать 10 или 12 цифр');
        }

        return null;
    }

    private static function registerStepResolveSiteCompany(AjaxRegisterExecutionContext $ctx): ?AjaxRegisterResponse
    {
        $resolvedCompany = AjaxRegisterSiteCompanyResolver::resolveByInn((string) $ctx->post['inn']);
        if ($resolvedCompany['status'] === 'ambiguous') {
            return AjaxRegisterResponse::fail('По указанному ИНН найдено несколько компаний. Обратитесь к менеджеру для регистрации.');
        }

        $existingCompany = $resolvedCompany['status'] === 'exact' ? (array) ($resolvedCompany['company'] ?? []) : [];
        $ctx->isExistingCompanyByInn = !empty($existingCompany);
        if ($ctx->isExistingCompanyByInn) {
            $ctx->post['name_company'] = (string) ($existingCompany['name'] ?? $ctx->post['name_company']);
            $ctx->post['address'] = (string) ($existingCompany['address'] ?? $ctx->post['address']);
            $ctx->post['activities'] = (string) ($existingCompany['activity'] ?? $ctx->post['activities']);
            $ctx->post['sait'] = (string) ($existingCompany['site'] ?? $ctx->post['sait']);
        }

        return null;
    }

    private static function registerStepValidatePasswordAndEmail(AjaxRegisterExecutionContext $ctx): ?AjaxRegisterResponse
    {
        if ($ctx->post['password'] !== $ctx->post['password_confirm']) {
            return AjaxRegisterResponse::fail('Пароли не совпадают');
        }

        if (\strlen($ctx->post['password']) < 6) {
            return AjaxRegisterResponse::fail('Пароль должен быть не менее 6 символов');
        }

        if ($ctx->post['email'] !== '' && !filter_var($ctx->post['email'], FILTER_VALIDATE_EMAIL)) {
            return AjaxRegisterResponse::fail('Введите корректный e-mail');
        }

        return null;
    }

    private static function registerStepRejectLocalDuplicates(AjaxRegisterExecutionContext $ctx): ?AjaxRegisterResponse
    {
        $dup = AjaxRegisterDuplicateGuard::checkEmailPhoneDuplicates($ctx->post);
        if ($dup !== null) {
            return AjaxRegisterResponse::fail((string) ($dup['error'] ?? 'Ошибка проверки уникальности'));
        }

        return null;
    }

    private static function registerStepCrmPrecheckContactUniqueViaN8n(AjaxRegisterExecutionContext $ctx): ?AjaxRegisterResponse
    {
        if (!AjaxRegisterCrmContactPrecheck::runFromRegistrationPost($ctx->post)) {
            return AjaxRegisterResponse::fail(
                AjaxRegisterBitrixApplication::publicMessageForRegistrationException(
                    'Регистрация отклонена предварительной проверкой CRM (n8n): контакт.'
                )
            );
        }

        return null;
    }

    private static function registerStepCrmPrecheckCompanyInnViaN8n(AjaxRegisterExecutionContext $ctx): ?AjaxRegisterResponse
    {
        try {
            $innResult = CompanyRegistrationService::runInnPrecheck($ctx->post);
        } catch (\Throwable $e) {
            return AjaxRegisterResponse::fail(AjaxRegisterBitrixApplication::PUBLIC_REGISTRATION_SERVICE_UNAVAILABLE);
        }
        if (!$innResult['ok']) {
            return AjaxRegisterResponse::fail(
                AjaxRegisterBitrixApplication::publicMessageForRegistrationException(
                    'Регистрация отклонена предварительной проверкой CRM (n8n): ИНН.'
                )
            );
        }
        $ctx->crmInnPrecheckPayload = $innResult['inn_precheck'] ?? null;

        return null;
    }

    /**
     * Режим «уже есть компания» для CRM: карточка в каталоге сайта по ИНН ({@see AjaxRegisterSiteCompanyResolver})
     * или непустой успешный ответ пречека ИНН в CRM (n8n), по смыслу как непустой dataRequisite после проверки ИНН в {@see \OnlineService\B24\Registration\CrmRegistrationOrchestrator::createB24Company}.
     */
    private static function isExistingCompanyRegistrationMode(AjaxRegisterExecutionContext $ctx): bool
    {
        if ($ctx->isExistingCompanyByInn) {
            return true;
        }
        $payload = $ctx->crmInnPrecheckPayload;

        return \is_array($payload) && $payload !== [] && !empty($payload);
    }

    private static function registerStepBuildUserFields(AjaxRegisterExecutionContext $ctx): ?AjaxRegisterResponse
    {
        $login = AjaxRegisterUserPayloadBuilder::allocateUniqueLogin($ctx->post);
        $ctx->userFields = AjaxRegisterUserPayloadBuilder::buildUserFieldsForAdd($ctx->post, $login);

        return null;
    }

    private static function registerStepMarkCrmPrecheckDone(AjaxRegisterExecutionContext $ctx): ?AjaxRegisterResponse
    {
        $GLOBALS['OS_REGISTER_USER_PRECHECK_DONE'] = true;

        if (!\defined('OS_SKIP_USERSYNC_AFTER_USER_ADD')) {
            \define('OS_SKIP_USERSYNC_AFTER_USER_ADD', true);
        }
        $GLOBALS['OS_SKIP_USERSYNC_AFTER_USER_ADD'] = true;

        return null;
    }

    private static function registerStepCreateBitrixUser(AjaxRegisterExecutionContext $ctx): ?AjaxRegisterResponse
    {
        $cUser = new CUser();
        $newUserId = $cUser->Add($ctx->userFields);
        if (!$newUserId) {
            $errMsg = $cUser->LAST_ERROR ?: 'Не удалось создать пользователя';

            return AjaxRegisterResponse::fail($errMsg);
        }
        $ctx->newUserId = (int) $newUserId;

        return null;
    }

    private static function registerStepSyncCrmAndFinalize(AjaxRegisterExecutionContext $ctx): AjaxRegisterResponse
    {
        $syncFields = AjaxRegisterUserPayloadBuilder::buildSyncFields(
            $ctx->post,
            $ctx->newUserId,
            $ctx->userFields,
            self::isExistingCompanyRegistrationMode($ctx),
            $ctx->crmInnPrecheckPayload
        );

        $safeSyncCompleted = false;
        $safeSyncError = '';

        try {
            $syncOk = CompanyRegistrationService::syncFromSiteRegistration($syncFields);
            $safeSyncCompleted = (bool) $syncOk;
            if (!$safeSyncCompleted) {
                $safeSyncError = AjaxRegisterBitrixApplication::publicMessageForRegistrationException(
                    'Синхронизация с CRM завершилась с ошибкой.'
                );
            }
        } catch (\Throwable $e) {
            $safeSyncError = 'Не удалось синхронизировать регистрацию с CRM.';
        }

        if (!$safeSyncCompleted) {
            (new CUser())->Delete($ctx->newUserId);

            return AjaxRegisterResponse::fail(
                $safeSyncError !== ''
                    ? $safeSyncError
                    : 'Регистрация не завершена: не удалось синхронизировать данные с CRM.'
            );
        }

        $userAfterSync = CUser::GetByID($ctx->newUserId)->Fetch();
        if (($userAfterSync['ACTIVE'] ?? '') !== 'N') {
            (new CUser())->Update($ctx->newUserId, ['ACTIVE' => 'N']);
        }

        return AjaxRegisterResponse::ok();
    }

    /**
     * Тело ответа n8n/HTTP при сбое (не массив полей Bitrix-пользователя): success ложен, часто есть error.
     *
     * @param array<string, mixed> $payload
     */
    private static function isCrmPrecheckFailureEnvelope(array $payload): bool
    {
        if (!\array_key_exists('success', $payload)) {
            return false;
        }

        $s = $payload['success'];
        if ($s === true || $s === 1 || $s === '1') {
            return false;
        }

        return $s === false || $s === null || $s === '' || $s === 0 || $s === '0' || $s === 'false';
    }
}

