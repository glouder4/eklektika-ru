<?php

namespace OnlineService\B24\Registration\AjaxRegister;

use CUser;

/**
 * Локальная проверка уникальности контактов (e-mail / телефон) до создания пользователя.
 * Уникальность ИНН юрлица на стороне компании — не здесь: см. {@see AjaxRegisterSiteCompanyResolver}
 * и ранний пречек CRM/n8n ({@see \OnlineService\B24\Registration\CrmRegistrationOrchestrator::runAjaxFormCrmPrecheck}).
 */
final class AjaxRegisterDuplicateGuard
{
    /**
     * @param array<string, string> $post
     *
     * @return array{success: false, error: string}|null
     */
    public static function checkEmailPhoneDuplicates(array $post): ?array
    {
        $emailDupCount = $post['email'] !== '' ? self::countUsersByFilter(['=EMAIL' => $post['email']]) : 0;
        if ($emailDupCount > 0) {
            return ['success' => false, 'error' => 'Пользователь с таким e-mail уже существует'];
        }

        $phoneDupCount = 0;
        if (\trim((string) $post['phone']) !== '') {
            $targetPhone = self::normalizePhoneDigits((string) $post['phone']);
            $rsUsersByPhone = CUser::GetList(
                $by = 'id',
                $order = 'asc',
                ['>ID' => 0],
                ['FIELDS' => ['ID', 'PERSONAL_PHONE', 'WORK_PHONE']]
            );
            while ($u = $rsUsersByPhone->Fetch()) {
                $personalPhone = self::normalizePhoneDigits((string) ($u['PERSONAL_PHONE'] ?? ''));
                $workPhone = self::normalizePhoneDigits((string) ($u['WORK_PHONE'] ?? ''));
                if ($targetPhone !== '' && ($personalPhone === $targetPhone || $workPhone === $targetPhone)) {
                    $phoneDupCount++;
                }
            }
        }

        if ($phoneDupCount > 0) {
            return ['success' => false, 'error' => 'Пользователь с таким телефоном уже существует'];
        }

        return null;
    }

    /**
     * @param array<string, mixed> $filter
     */
    private static function countUsersByFilter(array $filter): int
    {
        $rs = CUser::GetList($by = 'id', $order = 'asc', $filter, ['FIELDS' => ['ID']]);
        $count = 0;
        while ($rs->Fetch()) {
            $count++;
        }

        return $count;
    }

    private static function normalizePhoneDigits(string $phone): string
    {
        $digits = \preg_replace('/\D+/', '', $phone);
        if ($digits === '') {
            return '';
        }
        if (\strlen($digits) === 11 && $digits[0] === '8') {
            $digits = '7' . \substr($digits, 1);
        }
        if (\strlen($digits) === 10) {
            $digits = '7' . $digits;
        }

        return $digits;
    }
}

