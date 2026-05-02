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
    // Ранний ajax-precheck использует registration_webhook_unique_url + registration_webhook_inn_url (отдельные узлы crm-check-*-v1), композитный webhook не нужен.
    'registration_webhook_unique_url' => 'http://localhost:5678/webhook/registration/crm-check-unique-contact-v1',
    'registration_webhook_inn_url' => 'http://localhost:5678/webhook/registration/crm-check-inn-v1',
    'registration_webhook_company_add_url' => 'http://localhost:5678/webhook/registration/crm-company-add-v1',
    'registration_webhook_contact_add_url' => 'http://localhost:5678/webhook/registration/crm-contact-add-v1',
    // Остальные `crm.*` из регистрации — по одному именованному webhook на метод (METHOD+PARAMS); см. docs/reference/registration-n8n-webhooks.md.
    'registration_webhook_crm_company_get_url' => 'http://localhost:5678/webhook/registration/crm-company-get-v1',
    'registration_webhook_crm_company_update_url' => 'http://localhost:5678/webhook/registration/crm-company-update-v1',
    'registration_webhook_crm_contact_company_add_url' => 'http://localhost:5678/webhook/registration/crm-contact-company-add-v1',
    'registration_webhook_crm_company_contact_add_url' => 'http://localhost:5678/webhook/registration/crm-company-contact-add-v1',
    'registration_webhook_crm_requisite_list_url' => 'http://localhost:5678/webhook/registration/crm-requisite-list-v1',
    'registration_webhook_crm_requisite_update_url' => 'http://localhost:5678/webhook/registration/crm-requisite-update-v1',
    'registration_webhook_crm_requisite_add_url' => 'http://localhost:5678/webhook/registration/crm-requisite-add-v1',
    'registration_webhook_crm_contact_list_url' => 'http://localhost:5678/webhook/registration/crm-contact-list-v1',
    'registration_webhook_crm_contact_update_url' => 'http://localhost:5678/webhook/registration/crm-contact-update-v1',
    'registration_webhook_crm_contact_company_delete_url' => 'http://localhost:5678/webhook/registration/crm-contact-company-delete-v1',
    // Опционально: сверка полей компании из CRM при уже известном COMPANY_ID (registration/check-crm-company-updates-v1).
    'registration_webhook_company_updates_url' => 'http://localhost:5678/webhook/registration/check-crm-company-updates-v1',
];
