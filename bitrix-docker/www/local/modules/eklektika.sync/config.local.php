<?php
/**
 * Скопировать в config.local.php (не коммитить секреты).
 * inbound_secret: общий секрет для запросов с CRM на inbound endpoint (см. CrmInboundEndpoint::HTTP_PATH)
 * Передача: заголовок X-Sync-Token или параметр sync_token (POST).
 */
return [
    // Заполняется вручную в локальном/серверном config.local.php (файл не коммитится с секретами).
    'inbound_secret' => '6f8d4b86c3b74b2ea4f2d7a18a6e1f5c9d3b7e1a4c6f8d2b5e7a9c1d3f4b6e8',
    // Переключатель расширенного логирования обмена с B24 (request/response).
    // true/1/on/yes — включено; false/0/off/no — выключено.
    'sync_debug' => false,
    // Legacy синхронная регистрация CRM в OnAfterUserRegister (true по умолчанию для обратной совместимости).
    'sync_legacy' => true,
    // Дополнительный async webhook после локальной регистрации (тихая дозапись contact_id/company_id).
    'async_post_register' => false,
    // Общий префикс HTTP до path узла Webhook: возьмите из n8n «Copy URL» и обрежьте path после последнего '/'.
    // На разных стендах бывает /webhook, /webhook-test или без этого сегмента — см. S3 в docs/tasks/2026-04-30-n8n-crm-bridge-registration/task.md
    // Канонические path совпадают с docs/n8n/workflow-site-to-crm-multihook.sdk.ts (workflow gGtsrfCPP9t3OyLj).
    'n8n_registration_http_base' => 'http://localhost:5678/webhook',
    // Полные URL (непустые) перекрывают base. Оставьте '' — URL соберётся как base + '/' + path из PHP.
    // Если в вашем n8n другие path (не registration/crm-*-v1) — пропишите полный URL в нужном ключе.
    // Глобальный webhook для модулей вне registration (если используется).
    'n8n_crm_rest_proxy_webhook_url' => '',
    'async_post_register_webhook_url' => 'http://localhost:5678/webhook/registration/crm-register-post-sync-v1',
    // Ранний ajax-precheck: registration_webhook_unique_url + registration_webhook_inn_url.
    // Каждый ключ — массив: url (n8n), b24_rest_prefix (входящий вебхук B24, можно ''), crm_method (ожидаемый crm.* для сценария).
    // PHP в JSON добавляет B24_REST_PREFIX (если непусто) и CRM_METHOD.
    'registration_webhook_unique_url' => [
        'url' => 'http://localhost:5678/webhook/registration/crm-check-unique-contact-v1',
        'b24_rest_prefix' => 'https://bitrix.eklektika.ru/rest/1/0tmpyxh1usrz25ga/', // 13
        'crm_method' => 'crm.contact.list',
    ],
    'registration_webhook_inn_url' => [
        'url' => 'http://localhost:5678/webhook/registration/crm-check-inn-v1',
        'b24_rest_prefix' => 'https://bitrix.eklektika.ru/rest/1/bpivo04w4po4h4aj/', //5
        'crm_method' => 'crm.requisite.list',
    ],
    'registration_webhook_company_add_url' => [
        'url' => 'http://localhost:5678/webhook/registration/crm-company-add-v1',
        'b24_rest_prefix' => 'https://bitrix.eklektika.ru/rest/1/ylb52v0hrr51051h/', //9
        'crm_method' => 'crm.company.add',
    ],
    'registration_webhook_contact_add_url' => [
        'url' => 'http://localhost:5678/webhook/registration/crm-contact-add-v1',
        'b24_rest_prefix' => 'https://bitrix.eklektika.ru/rest/1/sht13sf225dmadel/', //6
        'crm_method' => 'crm.contact.add',
    ],
    'registration_webhook_crm_company_get_url' => [
        'url' => 'http://localhost:5678/webhook/registration/crm-company-get-v1',
        'b24_rest_prefix' => 'https://bitrix.eklektika.ru/rest/1/zhqaotu5xfitz1au/', //21
        'crm_method' => 'crm.company.get',
    ],
    'registration_webhook_crm_company_update_url' => [
        'url' => 'http://localhost:5678/webhook/registration/crm-company-update-v1',
        'b24_rest_prefix' => 'https://bitrix.eklektika.ru/rest/1/ympbrjy8hs3yqcnj/', //14
        'crm_method' => 'crm.company.update',
    ],
    'registration_webhook_crm_contact_company_add_url' => [
        'url' => 'http://localhost:5678/webhook/registration/crm-contact-company-add-v1',
        'b24_rest_prefix' => 'https://bitrix.eklektika.ru/rest/1/vtd9rs2a8a34elna/', //15
        'crm_method' => 'crm.contact.company.add',
    ],
    'registration_webhook_crm_company_contact_add_url' => [
        'url' => 'http://localhost:5678/webhook/registration/crm-company-contact-add-v1',
        'b24_rest_prefix' => 'https://bitrix.eklektika.ru/rest/1/e7ww3e3ws2brh4xh/', //16
        'crm_method' => 'crm.company.contact.add',
    ],
    'registration_webhook_crm_requisite_list_url' => [
        'url' => 'http://localhost:5678/webhook/registration/crm-check-inn-v1',
        'b24_rest_prefix' => 'https://bitrix.eklektika.ru/rest/1/bpivo04w4po4h4aj/', //5
        'crm_method' => 'crm.requisite.list',
    ],
    'registration_webhook_crm_requisite_update_url' => [
        // n8n Test: /webhook-test/… ; Active production: /webhook/… — должен совпадать с «Copy URL» в узле.
        'url' => 'http://localhost:5678/webhook/registration/crm-requisite-update-v1',
        'b24_rest_prefix' => 'https://bitrix.eklektika.ru/rest/1/rzm6ejo1q57msxwr/', //17
        'crm_method' => 'crm.requisite.update',
    ],
    'registration_webhook_crm_requisite_add_url' => [
        'url' => 'http://localhost:5678/webhook/registration/crm-requisite-add-v1',
        'b24_rest_prefix' => 'https://bitrix.eklektika.ru/rest/1/4ug1rbzxgikc6opm/', //18
        'crm_method' => 'crm.requisite.add',
    ],
    'registration_webhook_crm_contact_list_url' => [
        'url' => 'http://localhost:5678/webhook/registration/crm-contact-list-v1',
        'b24_rest_prefix' => 'https://bitrix.eklektika.ru/rest/1/o9jqicxe7rfua909/', //19
        'crm_method' => 'crm.contact.list',
    ],
    'registration_webhook_crm_contact_update_url' => [
        'url' => 'http://localhost:5678/webhook/registration/crm-contact-update-v1',
        'b24_rest_prefix' => 'https://bitrix.eklektika.ru/rest/1/z3i7lih053c9kis4/', //20
        'crm_method' => 'crm.contact.update',
    ],
    // Составной сценарий n8n; типичное чтение компании — crm.company.get (подстройте под свой workflow).
    'registration_webhook_company_updates_url' => [
        'url' => 'http://localhost:5678/webhook/registration/check-crm-company-updates-v1',
        'b24_rest_prefix' => 'https://bitrix.eklektika.ru/rest/1/zhqaotu5xfitz1au/', //21
        'crm_method' => 'crm.company.get',
    ],
];
