<?php
/**
 * Точка входа для входящего канала CRM → сайт и общей конфигурации sync/.
 * Подключается из ajax.php и при необходимости из других точек.
 */
$GLOBALS['EKLEKTIKA_SYNC_CONFIG'] = [
    'inbound_secret' => '',
];

$configLocal = __DIR__ . '/config.local.php';
if (is_file($configLocal)) {
    $local = include $configLocal;
    if (is_array($local)) {
        $GLOBALS['EKLEKTIKA_SYNC_CONFIG'] = array_replace_recursive(
            $GLOBALS['EKLEKTIKA_SYNC_CONFIG'],
            $local
        );
    }
}

require_once __DIR__ . '/InboundSecurity.php';
require_once __DIR__ . '/from-crm/InboundGateway.php';
