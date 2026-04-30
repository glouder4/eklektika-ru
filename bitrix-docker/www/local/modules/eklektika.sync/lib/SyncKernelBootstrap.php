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
