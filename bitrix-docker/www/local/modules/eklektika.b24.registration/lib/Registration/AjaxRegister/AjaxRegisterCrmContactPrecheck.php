<?php

namespace OnlineService\B24\Registration\AjaxRegister;

/**
 * Пречек контакта CRM по e-mail и телефону (n8n crm-check-unique-contact-v1).
 * {@see \OnlineService\B24\Registration\CrmRegistrationOrchestrator::crmCheckUniqueContact} делегирует сюда — одна реализация разбора ответа.
 * Обязанность «компания / ИНН» остаётся в {@see \OnlineService\B24\Registration\CrmRegistrationOrchestrator}.
 */
final class AjaxRegisterCrmContactPrecheck
{
    /**
     * Поля как у формы после {@see AjaxRegisterPostParser::parse}.
     */
    public static function runFromRegistrationPost(array $post): bool
    {
        $probe = self::probeFieldsFromRegistrationPost($post);

        return self::haltIfDuplicateContactFromCrmCheck(self::checkUniqueContactInCrm($probe));
    }

    /**
     * Поля пользователя Bitrix (EMAIL, PERSONAL_PHONE), как в {@see \OnlineService\B24\Registration\CrmRegistrationOrchestrator::runSyncPreCheck}.
     *
     * @param array<string, mixed> $arFields
     */
    public static function runFromUserFields(array $arFields): bool
    {
        $probe = [
            'EMAIL' => (string) ($arFields['EMAIL'] ?? ''),
            'PERSONAL_PHONE' => (string) ($arFields['PERSONAL_PHONE'] ?? ''),
        ];

        return self::haltIfDuplicateContactFromCrmCheck(self::checkUniqueContactInCrm($probe));
    }

    /**
     * Контракт ответа n8n crm-check-unique-contact-v1:
     * пустой массив — уникально; непустой массив — найден контакт; false — ошибка запроса/конфигурации.
     *
     * @param array{EMAIL?: string, PERSONAL_PHONE?: string} $contactProbeFields
     *
     * @return array<mixed>|false
     */
    public static function checkUniqueContactInCrm(array $contactProbeFields)
    {
        $webhookPayload = [
            'EMAIL' => (string) ($contactProbeFields['EMAIL'] ?? ''),
            'PERSONAL_PHONE' => (string) ($contactProbeFields['PERSONAL_PHONE'] ?? ''),
        ];
        $webhook = CrmRegistrationN8nTransport::post('registration_webhook_unique_url', $webhookPayload);
        if (!empty($webhook['used'])) {
            if (empty($webhook['ok'])) {
                CrmRegistrationN8nTransport::throwWebhookFailure($webhook);

                return false;
            }
            $data = $webhook['data'] ?? null;
            if (\is_array($data) && CrmRegistrationN8nPrecheckResponse::isProbableN8nErrorResponseBody($data)) {
                CrmRegistrationN8nTransport::throwWebhookFailure($webhook);

                return false;
            }
            $result = CrmRegistrationN8nPrecheckResponse::unwrapWebhookResult($data);

            if (\is_array($result) && isset($result['success']) && (int) $result['success'] === 0) {
                global $APPLICATION;
                $APPLICATION->ThrowException(
                    CrmRegistrationN8nPrecheckResponse::formatCrmPrecheckRejectionMessage($result),
                    'crm_precheck_unique'
                );

                return false;
            }
            if (\is_array($result) && isset($result[0]) && \is_array($result[0])) {
                return $result[0];
            }
            if (\is_array($result) && !empty($result['ID'])) {
                return $result;
            }

            if (!\is_array($data) || !CrmRegistrationN8nPrecheckResponse::registrationPrecheckResponseIndicatesSuccess($data, $result)) {
                global $APPLICATION;
                $APPLICATION->ThrowException(
                    'Проверка уникальности в CRM вернула неожиданный ответ. Повторите попытку позже или обратитесь в поддержку.',
                    'crm_precheck_unique_ambiguous'
                );

                return false;
            }

            return [];
        }

        global $APPLICATION;
        $APPLICATION->ThrowException(
            'Регистрация через n8n: задайте registration_webhook_unique_url или n8n_registration_http_base (crm-check-unique-contact-v1).'
        );

        return false;
    }

    /**
     * @param array|false $response результат {@see checkUniqueContactInCrm}
     */
    public static function haltIfDuplicateContactFromCrmCheck($response): bool
    {
        global $APPLICATION;
        if ($response === false) {
            return false;
        }
        if ($response) {
            if ((isset($response['PHONE']) && !empty($response['PHONE'])) || (isset($response['EMAIL']) && !empty($response['EMAIL']))) {
                $APPLICATION->ThrowException('Пользователь с указанными почтой или телефоном уже существует в системе. Вы можете <a href="/personal/profile/">авторизоваться</a> или <a href="/personal/profile/?forgot_password=yes">восстановить пароль</a>', 'already_registered');
            } else {
                $APPLICATION->ThrowException('Что-то пошло не так.', 'already_registered');
            }

            return false;
        }

        return true;
    }

    /**
     * @param array<string, string> $post
     *
     * @return array{EMAIL: string, PERSONAL_PHONE: string}
     */
    private static function probeFieldsFromRegistrationPost(array $post): array
    {
        $personalPhone = \trim((string) ($post['mobilephone'] ?? ''));
        if ($personalPhone === '') {
            $personalPhone = \trim((string) ($post['phone'] ?? ''));
        }

        return [
            'EMAIL' => \trim((string) ($post['email'] ?? '')),
            'PERSONAL_PHONE' => $personalPhone,
        ];
    }
}

