<?php
/**
 * Нормализация телефона компании для формы заказа: вид +7XXXXXXXXXX (цифры после +7).
 * Учитывает ввод с 8 / +8 и артефакт +7+8… после ошибочного префикса.
 */
if (!function_exists('order_form_normalize_ru_company_phone')) {
    function order_form_normalize_ru_company_phone(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        $digits = preg_replace('/\D+/', '', $raw);
        if ($digits === '') {
            return '';
        }

        // Артефакт: два кода страны подряд (+7 и затем 8…)
        if (preg_match('/^78(\d{10})$/', $digits, $m)) {
            $digits = '7' . $m[1];
        }

        if ($digits[0] === '8') {
            $digits = '7' . substr($digits, 1);
        }

        if (strlen($digits) === 10 && $digits[0] === '9') {
            $digits = '7' . $digits;
        }

        if ($digits[0] !== '7' && strlen($digits) >= 10) {
            $digits = '7' . $digits;
        }

        return '+' . $digits;
    }
}

if (!function_exists('order_form_company_phone_raw_from_ib')) {
    /**
     * Сырые строки телефона из карточки компании (ИБ), как в get-company-data.
     *
     * @param array<string, mixed> $companyData
     */
    function order_form_company_phone_raw_from_ib(array $companyData): string
    {
        $phoneRaw = trim((string)($companyData['LEGAN_ENTITY_PHONE'] ?? ''));
        if ($phoneRaw === '') {
            $phoneRaw = trim((string)($companyData['OS_COMPANY_PHONE'] ?? ''));
        }
        if ($phoneRaw === '') {
            $mainPh = trim((string)($companyData['LEGAN_MAIN_PHONE'] ?? ''));
            $mobilePh = trim((string)($companyData['LEGAN_MOBILE_PHONE'] ?? ''));
            $phoneRaw = $mainPh !== '' ? $mainPh : $mobilePh;
        }

        return $phoneRaw;
    }
}
