<?php

namespace OnlineService\B24\Registration;

/**
 * Фасад домена регистрации юрлица в CRM (n8n): пречеки ИНН, обработчики main, safe-sync после CUser::Add.
 */
final class CompanyRegistrationService
{
    /**
     * @param array<string, string> $post поля формы регистрации
     *
     * @return array{ok: bool, inn_precheck: array|null}
     */
    public static function runInnPrecheck(array $post): array
    {
        return self::orchestrator()->runAjaxCompanyInnPrecheck($post);
    }

    /**
     * @param array<string, mixed> $userFields
     *
     * @return array<string, mixed>|bool
     */
    public static function onBeforeUserRegister(array $userFields)
    {
        return self::orchestrator()->OnBeforeUserRegisterHandler($userFields);
    }

    /**
     * @param array<string, mixed> $syncFields
     */
    public static function syncFromSiteRegistration(array $syncFields): bool
    {
        return (bool) self::orchestrator()->syncFromSiteRegistration($syncFields);
    }

    private static function orchestrator(): CrmRegistrationOrchestrator
    {
        if (!\class_exists(CrmRegistrationOrchestrator::class)) {
            throw new \RuntimeException('Регистрация недоступна: не загружен модуль CRM (CrmRegistrationOrchestrator).');
        }

        return new CrmRegistrationOrchestrator();
    }
}

