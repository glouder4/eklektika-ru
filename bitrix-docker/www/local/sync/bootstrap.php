<?php
/**
 * Точка входа для входящего канала CRM → сайт и общей конфигурации sync/.
 * Подключается из ajax.php и при необходимости из других точек.
 */
$GLOBALS['EKLEKTIKA_SYNC_CONFIG'] = [
    'inbound_secret' => '',
    /**
     * Общий отладочный режим sync: логи, trace, диагностические остановы в сценариях B24
     * (inbound, ЛК → crm.* и т.д.) — см. {@see \OnlineService\Sync\SyncTrace::enabled()}.
     * true/1/on/yes — включено; на проде false.
     */
    'sync_debug' => false,
    /**
     * Жёсткая остановка: срабатывает только при sync_debug=true и совпадении строки с вызовом SyncPrimitiveBreakpoint::hit().
     * Значение — один из литералов в комментарии к {@see \OnlineService\Sync\SyncPrimitiveBreakpoint}.
     */
    'sync_primitive_breakpoint_step' => '',
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

require_once __DIR__ . '/SyncInboundLog.php';
require_once __DIR__ . '/SyncTrace.php';
require_once __DIR__ . '/SyncPrimitiveBreakpoint.php';
require_once __DIR__ . '/InboundSecurity.php';
require_once __DIR__ . '/from-crm/InboundGateway.php';
