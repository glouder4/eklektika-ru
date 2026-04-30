<?php

declare(strict_types=1);

namespace OnlineService\Sync;

/**
 * Точечная остановка pre()+die() для отладки входящего sync (только при sync_debug + шаг в config.local.php).
 *
 * Шаги (литералы для sync_primitive_breakpoint_step):
 * - sync_bp_inbound_before_update_company
 * - sync_bp_inbound_after_update_company
 * - sync_bp_company_update_entry
 * - sync_bp_company_after_merge_property_values
 * - sync_bp_company_before_ciupdate
 */
final class SyncPrimitiveBreakpoint
{
    /**
     * @param array<string, mixed> $payload
     */
    public static function hit(string $stepId, array $payload = []): void
    {
        $cfg = $GLOBALS['EKLEKTIKA_SYNC_CONFIG'] ?? [];
        if (!SyncTrace::enabled()) {
            return;
        }
        $want = \trim((string)($cfg['sync_primitive_breakpoint_step'] ?? ''));
        if ($want === '' || $want !== $stepId) {
            return;
        }

        while (\ob_get_level() > 0) {
            \ob_end_clean();
        }
        if (!\headers_sent()) {
            \header('Content-Type: text/html; charset=UTF-8');
            \http_response_code(200);
        }

        $out = [
            'SYNC_PRIMITIVE_BREAKPOINT' => $stepId,
            'time' => \date('c'),
            'payload' => self::shallowSafe($payload),
        ];

        if (\function_exists('pre')) {
            pre($out);
        } else {
            echo '<pre>' . \htmlspecialchars(\print_r($out, true), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>';
        }

        die("\n<!-- sync_primitive_breakpoint_stop -->\n");
    }

    /**
     * Верхний уровень + краткое описание вложенных массивов без полного дампа.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private static function shallowSafe(array $payload, int $maxTopKeys = 60): array
    {
        $i = 0;
        $out = [];
        foreach ($payload as $k => $v) {
            if ($i >= $maxTopKeys) {
                $out['__truncated__'] = 'maxTopKeys=' . (string)$maxTopKeys;

                break;
            }
            $key = \is_string($k) ? $k : (string)$k;
            if (\is_array($v)) {
                $out[$key] = 'array(count=' . \count($v) . ')';
            } elseif (\is_string($v)) {
                $len = \strlen($v);
                $out[$key] = $len > 240 ? ('string(len=' . (string)$len . ') head=' . \substr($v, 0, 120)) : $v;
            } elseif (\is_scalar($v) || $v === null) {
                $out[$key] = $v;
            } else {
                $out[$key] = \is_object($v) ? ('object:' . \get_class($v)) : \gettype($v);
            }
            ++$i;
        }

        return $out;
    }
}
