<?php
/**
 * Скопировать в config.local.php (не коммитить секреты).
 * inbound_secret: общий секрет для запросов с CRM на inbound endpoint (см. CrmInboundEndpoint::HTTP_PATH)
 * Передача: заголовок X-Sync-Token или параметр sync_token (POST).
 */
return [
    'inbound_secret' => '6f8d4b86c3b74b2ea4f2d7a18a6e1f5c9d3b7e1a4c6f8d2b5e7a9c1d3f4b6e8',
    // Переключатель расширенного логирования обмена с B24 (request/response).
    // true/1/on/yes — включено; false/0/off/no — выключено.
    'sync_debug' => false,
];
