<?php

namespace OnlineService\B24\Registration\AjaxRegister;

/**
 * Доступ к глобальному состоянию приложения Bitrix при обработке ошибок CRM.
 */
final class AjaxRegisterBitrixApplication
{
    public const PUBLIC_REGISTRATION_SERVICE_UNAVAILABLE = 'Сервис временно не доступен';

    public static function readExceptionMessage(): string
    {
        global $APPLICATION;
        if (!isset($APPLICATION) || !\is_object($APPLICATION) || !\method_exists($APPLICATION, 'GetException')) {
            return '';
        }
        $ex = $APPLICATION->GetException();
        if (!$ex || !\method_exists($ex, 'GetString')) {
            return '';
        }

        return \trim((string) $ex->GetString());
    }

    /**
     * Текст для JSON регистрации: инфраструктура n8n/CRM — нейтральная фраза;
     * {@see \OnlineService\B24\Registration\CrmRegistrationOrchestrator} с кодом already_registered — как в CRM (со ссылками);
     * прочие проверки — текст исключения; если исключения нет — $fallback.
     */
    public static function publicMessageForRegistrationException(string $fallback): string
    {
        global $APPLICATION;
        if (!isset($APPLICATION) || !\is_object($APPLICATION) || !\method_exists($APPLICATION, 'GetException')) {
            return $fallback;
        }
        $ex = $APPLICATION->GetException();
        if (!$ex || !\method_exists($ex, 'GetString')) {
            return $fallback;
        }

        $msg = \trim((string) $ex->GetString());
        $idRaw = \method_exists($ex, 'GetID') ? $ex->GetID() : false;
        $id = ($idRaw === false || $idRaw === null || $idRaw === '') ? '' : (string) $idRaw;

        if ($id === 'already_registered' && $msg !== '') {
            return $msg;
        }

        if (\in_array($id, ['n8n_registration_webhook', 'crm_precheck_unique', 'crm_precheck_inn'], true)) {
            return self::PUBLIC_REGISTRATION_SERVICE_UNAVAILABLE;
        }

        if ($msg !== '') {
            return $msg;
        }

        return $fallback;
    }
}

