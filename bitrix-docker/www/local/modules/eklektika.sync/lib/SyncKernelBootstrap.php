<?php

/**
 * Инициализация $GLOBALS['EKLEKTIKA_SYNC_CONFIG'] и merge с config.local.php.
 * Канонический конфиг рядом с модулем: `local/modules/eklektika.sync/config.local.php`.
 */

$GLOBALS['EKLEKTIKA_SYNC_CONFIG'] = [
    'inbound_secret' => '',
    /**
     * Общий отладочный режим sync: логи, trace, диагностические остановки в сценариях B24
     * (inbound, ЛК → crm.* и т.д.) — см. {@see \OnlineService\Sync\SyncTrace::enabled()}.
     */
    'sync_debug' => false,
    /**
     * Жёсткая остановка: только при sync_debug=true и совпадении строки с {@see \OnlineService\Sync\SyncPrimitiveBreakpoint::hit()}.
     */
    'sync_primitive_breakpoint_step' => '',
    /**
     * Path после base для registration_webhook_* (если задан только n8n_registration_http_base). Перекрывается в config.local.php.
     *
     * @var array<string, string>
     */
    'registration_webhook_path_suffixes' => [
        'registration_webhook_unique_url' => 'registration/crm-check-unique-contact-v1',
        'registration_webhook_inn_url' => 'registration/crm-check-inn-v1',
        'registration_webhook_company_add_url' => 'registration/crm-company-add-v1',
        'registration_webhook_contact_add_url' => 'registration/crm-contact-add-v1',
        'registration_webhook_crm_company_get_url' => 'registration/crm-company-get-v1',
        'registration_webhook_crm_company_update_url' => 'registration/crm-company-update-v1',
        'registration_webhook_crm_contact_company_add_url' => 'registration/crm-contact-company-add-v1',
        'registration_webhook_crm_company_contact_add_url' => 'registration/crm-company-contact-add-v1',
        'registration_webhook_crm_requisite_list_url' => 'registration/crm-requisite-list-v1',
        'registration_webhook_crm_requisite_update_url' => 'registration/crm-requisite-update-v1',
        'registration_webhook_crm_requisite_add_url' => 'registration/crm-requisite-add-v1',
        'registration_webhook_crm_contact_list_url' => 'registration/crm-contact-list-v1',
        'registration_webhook_crm_contact_get_url' => 'registration/crm-contact-get-v1',
        'registration_webhook_crm_contact_update_url' => 'registration/crm-contact-update-v1',
        'registration_webhook_company_updates_url' => 'registration/check-crm-company-updates-v1',
    ],
    /**
     * Префикс до path узла Webhook (без завершающего /): скопировать из n8n «Production URL»
     * до последнего сегмента path. Пусто — только полные URL в registration_webhook_* / async_post_register_webhook_url.
     */
    'n8n_registration_http_base' => '',
    /**
     * Универсальный вебхук n8n (WH legacy): JSON { METHOD, PARAMS } → Bitrix24 REST.
     * Задаётся здесь или через env EKLEKTIKA_N8N_CRM_WEBHOOK_URL.
     */
    'n8n_crm_rest_proxy_webhook_url' => '',
    'registration_webhook_crm_company_get_url' => '',
    'registration_webhook_crm_company_update_url' => '',
    'registration_webhook_crm_contact_company_add_url' => '',
    'registration_webhook_crm_company_contact_add_url' => '',
    'registration_webhook_crm_requisite_list_url' => '',
    'registration_webhook_crm_requisite_update_url' => '',
    'registration_webhook_crm_requisite_add_url' => '',
    'registration_webhook_crm_contact_list_url' => '',
    'registration_webhook_crm_contact_get_url' => '',
    'registration_webhook_crm_contact_update_url' => '',
];

$moduleConfig = dirname(__DIR__) . '/config.local.php';

$configLocal = is_file($moduleConfig) ? $moduleConfig : null;

if ($configLocal !== null) {
    $local = include $configLocal;
    if (is_array($local)) {
        $GLOBALS['EKLEKTIKA_SYNC_CONFIG'] = array_replace_recursive(
            $GLOBALS['EKLEKTIKA_SYNC_CONFIG'],
            $local
        );
    }
}
