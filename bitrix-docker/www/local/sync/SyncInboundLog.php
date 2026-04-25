<?php

namespace OnlineService\Sync;

/**
 * Входящий канал CRM → сайт: строковый лог в local/logs/inbound-b24.log
 * (см. подключение модулей в local/php_interface/init.php → local/classes/requires.php).
 */
class SyncInboundLog
{
    public static function line(string $message): void
    {
        if (!SyncTrace::enabled()) {
            return;
        }
        $dRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        if ($dRoot === '') {
            return;
        }
        $dir = $dRoot . '/local/logs';
        if (!@is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $path = $dir . '/inbound-b24.log';
        $line = date('Y-m-d H:i:s') . ' ' . $message . PHP_EOL;
        @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
    }
}
