<?php

declare(strict_types=1);

namespace OnlineService\Sync;

/**
 * Общий отладочный режим обмена с B24: флаг `sync_debug` в $GLOBALS['EKLEKTIKA_SYNC_CONFIG']
 * (см. local/sync/config.local.php, подключение local/sync/bootstrap.php).
 *
 * При включении: пишет inbound-лог, `debug_trace` в JSON ответа inbound, и **должен использоваться
 * в других сценариях** (исходящий crm.* из ЛК, точечные pre+stop и т.д.) вместо отдельных флагов.
 *
 * @see self::enabled()
 */
final class SyncTrace
{
    private const MAX_LINES = 80;

    /** @var list<string> */
    private static array $buffer = [];

    public static function reset(): void
    {
        self::$buffer = [];
    }

    public static function enabled(): bool
    {
        $cfg = $GLOBALS['EKLEKTIKA_SYNC_CONFIG'] ?? [];
        $v = $cfg['sync_debug'] ?? false;

        return $v === true || $v === 1 || $v === '1' || $v === 'on' || $v === 'ON' || $v === 'yes' || $v === 'YES';
    }

    /**
     * Синоним {@see self::enabled()} — удобно в коде, где важен смысл «включён общий sync-дебаг».
     */
    public static function isDebugModeEnabled(): bool
    {
        return self::enabled();
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function add(string $step, array $context = []): void
    {
        if (!self::enabled()) {
            return;
        }
        $suffix = $context === [] ? '' : ' ' . \json_encode($context, \JSON_UNESCAPED_UNICODE | \JSON_INVALID_UTF8_SUBSTITUTE);
        $line = \date('c') . ' ' . $step . $suffix;
        if (\count(self::$buffer) >= self::MAX_LINES) {
            \array_shift(self::$buffer);
        }
        self::$buffer[] = $line;
        SyncInboundLog::line('[trace] ' . $step . $suffix);
    }

    /**
     * @return list<string>|null
     */
    public static function flushLines(): ?array
    {
        if (!self::enabled() || self::$buffer === []) {
            return null;
        }

        return self::$buffer;
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public static function summarizeRequest(array $request): array
    {
        $keys = \array_keys($request);
        \sort($keys, \SORT_STRING);

        return [
            'ACTION' => (string)($request['ACTION'] ?? ''),
            'param_keys' => $keys,
            'OS_COMPANY_B24_ID' => $request['OS_COMPANY_B24_ID'] ?? null,
            'ID' => $request['ID'] ?? null,
        ];
    }
}
