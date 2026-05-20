<?php

namespace OnlineService\Sync;

/**
 * Разбор тела входящего запроса inbound_crm (curl, n8n HTTP Request, form-urlencoded).
 */
final class InboundRequestParser
{
    /** @var array<string, mixed> */
    private static array $lastMeta = [];

    /** Снимок php://input до prolog (Bitrix иногда читает поток раньше). */
    private static ?string $capturedRawBody = null;

    public static function captureRawBodyFromInput(): void
    {
        $method = \strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (!\in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            self::$capturedRawBody = '';

            return;
        }

        $raw = \file_get_contents('php://input');
        self::$capturedRawBody = \is_string($raw) ? $raw : '';
    }

    /**
     * @return array<string, mixed>
     */
    public static function getLastMeta(): array
    {
        return self::$lastMeta;
    }

    /**
     * GET без тела и без полезных query-параметров (пустой вызов n8n).
     */
    public static function isEmptyBodyGetRequest(): bool
    {
        $method = \strtoupper((string) (self::$lastMeta['method'] ?? ''));
        $rawLen = (int) (self::$lastMeta['raw_len'] ?? 0);

        return \in_array($method, ['GET', 'HEAD'], true)
            && $rawLen === 0
            && !self::queryHasInboundPayload($_GET);
    }

    /**
     * @return array<string, mixed>
     */
    public static function parse(): array
    {
        self::$lastMeta = [
            'content_type' => (string) ($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? ''),
            'method' => (string) ($_SERVER['REQUEST_METHOD'] ?? ''),
            'request_uri' => (string) ($_SERVER['REQUEST_URI'] ?? ''),
            'raw_len' => 0,
            'raw_prefix' => '',
            'parse_source' => 'request',
            'post_keys' => \array_keys($_POST),
            'query_keys' => \array_keys($_GET),
        ];

        $raw = self::$capturedRawBody;
        if ($raw === null) {
            $raw = \file_get_contents('php://input');
        }
        if (\is_string($raw)) {
            self::$lastMeta['raw_len'] = \strlen($raw);
            self::$lastMeta['raw_prefix'] = \substr(\ltrim($raw), 0, 200);
        }

        $payload = self::tryDecodeJson(is_string($raw) ? $raw : '');
        if ($payload !== null) {
            self::$lastMeta['parse_source'] = 'php_input_json';

            return self::mergeQueryParams(self::unwrapInboundListEnvelope($payload), $_GET);
        }

        foreach ($_POST as $key => $value) {
            if (!\is_string($value)) {
                continue;
            }
            $fromField = self::tryDecodeJson($value);
            if ($fromField !== null) {
                self::$lastMeta['parse_source'] = 'post_json_field:' . (string) $key;

                return self::mergeQueryParams(self::unwrapInboundListEnvelope($fromField), $_GET);
            }
        }

        if ($_POST !== []) {
            self::$lastMeta['parse_source'] = 'post';

            return self::mergeQueryParams(self::flattenPostPayload($_POST), $_GET);
        }

        $fromQueryJson = self::tryParseQueryJsonEnvelope($_GET);
        if ($fromQueryJson !== null) {
            self::$lastMeta['parse_source'] = 'query_json';

            return self::mergeQueryParams(self::unwrapInboundListEnvelope($fromQueryJson), $_GET);
        }

        if (self::queryHasInboundPayload($_GET)) {
            self::$lastMeta['parse_source'] = 'query';

            return self::mergeQueryParams($_GET, []);
        }

        return self::mergeQueryParams($_REQUEST, $_GET);
    }

    /**
     * n8n при Method=GET часто кладёт поля в URL (?ACTION=…&LEGAN_MAIN_PHONE=…), а не в body.
     *
     * @param array<string, mixed> $query
     */
    private static function queryHasInboundPayload(array $query): bool
    {
        foreach (['ACTION', 'action', 'OS_COMPANY_B24_ID', 'COMPANY_ID', 'CONTACT_ID', 'LEGAN_ENTITY_INN', 'LEGAN_MAIN_PHONE', 'TITLE'] as $key) {
            if (isset($query[$key]) && $query[$key] !== '' && $query[$key] !== null) {
                return true;
            }
        }

        foreach (['payload', 'data', 'json', 'body', 'FIELDS'] as $wrapKey) {
            if (!isset($query[$wrapKey]) || !\is_string($query[$wrapKey]) || \trim($query[$wrapKey]) === '') {
                continue;
            }
            if (self::tryDecodeJson($query[$wrapKey]) !== null) {
                return true;
            }
        }

        $meaningful = 0;
        foreach ($query as $key => $value) {
            if ($key === 'sync_token' || $key === 'sync_debug') {
                continue;
            }
            if ($value !== '' && $value !== null) {
                $meaningful++;
            }
        }

        return $meaningful > 0;
    }

    /**
     * ?json=[{...}] или ?payload={"ACTION":...} — целый конверт в одном query-параметре.
     *
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>|null
     */
    private static function tryParseQueryJsonEnvelope(array $query): ?array
    {
        foreach (['json', 'payload', 'data', 'body'] as $key) {
            if (!isset($query[$key]) || !\is_scalar($query[$key])) {
                continue;
            }
            $decoded = self::tryDecodeJson((string) $query[$key]);
            if ($decoded !== null) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * n8n form-urlencoded: FIELDS[LEGAN_MAIN_PHONE]=… без верхнего ACTION.
     *
     * @param array<string, mixed> $post
     *
     * @return array<string, mixed>
     */
    private static function flattenPostPayload(array $post): array
    {
        $flat = $post;
        foreach (['FIELDS', 'fields', 'body', 'data', 'json', 'payload'] as $wrapKey) {
            if (!isset($flat[$wrapKey])) {
                continue;
            }
            $inner = $flat[$wrapKey];
            unset($flat[$wrapKey]);
            if (\is_string($inner)) {
                $decoded = self::tryDecodeJson($inner);
                if ($decoded !== null) {
                    $inner = $decoded;
                }
            }
            if (\is_array($inner)) {
                $flat = \array_merge($inner, $flat);
            }
        }

        return self::unwrapInboundListEnvelope($flat);
    }

    /**
     * `[{ "ACTION": "...", "FIELDS": { ... } }]` сразу после json_decode.
     *
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private static function unwrapInboundListEnvelope(array $payload): array
    {
        for ($depth = 0; $depth < 4; $depth++) {
            if (
                $payload !== []
                && \array_keys($payload) === \range(0, \count($payload) - 1)
                && isset($payload[0])
                && \is_array($payload[0])
            ) {
                if (\count($payload) === 1) {
                    $payload = $payload[0];
                    continue;
                }
                self::$lastMeta['list_items'] = \count($payload);
                $payload = $payload[0];
                break;
            }
            break;
        }

        return $payload;
    }

    /**
     * Query не должен затирать ACTION из JSON пустой строкой (?ACTION=).
     *
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    private static function mergeQueryParams(array $payload, array $query): array
    {
        foreach ($query as $key => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            $payload[$key] = $value;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function tryDecodeJson(string $raw): ?array
    {
        $raw = \trim($raw);
        if ($raw === '') {
            return null;
        }

        $first = $raw[0];
        if ($first !== '{' && $first !== '[' && $first !== '"') {
            return null;
        }

        $decoded = \json_decode($raw, true);
        if (\json_last_error() === \JSON_ERROR_NONE && \is_array($decoded)) {
            return $decoded;
        }

        if ($first === '"') {
            $inner = \json_decode($raw, true);
            if (\is_string($inner)) {
                $decoded2 = \json_decode($inner, true);
                if (\json_last_error() === \JSON_ERROR_NONE && \is_array($decoded2)) {
                    return $decoded2;
                }
            }
        }

        return null;
    }
}
