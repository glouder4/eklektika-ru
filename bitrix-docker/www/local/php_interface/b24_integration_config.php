<?php
/**
 * Конфигурация интеграции с Bitrix24: базовый URL портала и токены входящих вебхуков.
 *
 * `base_url` → константа `URL_B24` в `php_interface/init.php` (только этот файл, без getenv / .env).
 * `rest_webhook_main` + `base_url` → полный {@see B24_REST_WEBHOOK} и токен {@see B24_REST_WEBHOOK_MAIN}.
 *
 * Переключение стенда: установите $useTestPortal = true и при необходимости замените URL/токены.
 * Документация: docs/features/b24_integration.md
 */
$useTestPortal = false;

return [
    'use_test_portal' => $useTestPortal,
    'base_url' => $useTestPortal
        ? 'https://bitrix.eklektika.ru/'
        : 'https://bitrix.eklektika.ru/',
    /** Сегмент URL rest/1/{token}/ для основных вызовов CRM (crm.*, user.get и т.д.) */
    'rest_webhook_main' => 'oak1tjz71elzz2xt',
    /** Сегмент URL для kit.productapplications.* */
    'rest_webhook_kit' => 'w8i2ce68y3wwps17',
];
