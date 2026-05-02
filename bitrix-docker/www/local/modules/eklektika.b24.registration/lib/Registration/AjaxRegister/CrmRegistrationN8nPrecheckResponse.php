<?php

namespace OnlineService\B24\Registration\AjaxRegister;

/**
 * Разбор тел ответов n8n для пречеков регистрации (контакт и компания).
 */
final class CrmRegistrationN8nPrecheckResponse
{
    /**
     * @param array<string, mixed> $data декодированное тело ответа webhook
     * @param mixed                $result результат {@see unwrapWebhookResult}
     */
    public static function registrationPrecheckResponseIndicatesSuccess(array $data, $result): bool
    {
        if ((int) ($data['success'] ?? 0) === 1) {
            return true;
        }
        if (\is_array($result) && (int) ($result['success'] ?? 0) === 1) {
            return true;
        }

        return false;
    }

    /**
     * Ответ n8n при сбое (404 webhook, тестовый режим и т.п.), не контракт регистрации {@see unwrapWebhookResult}.
     *
     * @param array<string, mixed> $data
     */
    public static function isProbableN8nErrorResponseBody($data): bool
    {
        if (!\is_array($data) || $data === []) {
            return false;
        }
        if (\array_key_exists('success', $data) || \array_key_exists('result', $data)) {
            return false;
        }
        if (empty($data['message']) || !\is_string($data['message'])) {
            return false;
        }
        if (\array_key_exists('code', $data)) {
            return true;
        }
        if (!empty($data['hint']) || !empty($data['stacktrace'])) {
            return true;
        }

        return false;
    }

    /**
     * @param array<string, mixed>|null $decoded
     *
     * @return mixed
     */
    public static function unwrapWebhookResult($decoded)
    {
        if (!\is_array($decoded)) {
            return null;
        }
        if (isset($decoded['success']) && (int) $decoded['success'] === 0) {
            return $decoded;
        }
        if (\array_key_exists('result', $decoded)) {
            return $decoded['result'];
        }

        return $decoded;
    }

    /**
     * Текст отказа из тела ответа CRM/n8n при success=0.
     *
     * @param array<string, mixed> $result
     */
    public static function formatCrmPrecheckRejectionMessage(array $result): string
    {
        foreach (['error_description', 'error', 'message', 'hint'] as $key) {
            if (!empty($result[$key]) && \is_scalar($result[$key])) {
                return \trim((string) $result[$key]);
            }
        }

        return 'Проверка в CRM завершилась с ошибкой.';
    }
}

