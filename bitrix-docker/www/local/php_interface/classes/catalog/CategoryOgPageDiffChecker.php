<?php

/**
 * Сравнение OG из categories.sqlite с meta property="og:*" на живой странице.
 */
final class CategoryOgPageDiffChecker
{
    /** @var list<string> */
    public const COMPARE_FIELDS = [
        'og_title',
        'og_description',
    ];

    /** @var array<string, string> sqlite column => meta property */
    private const FIELD_TO_PROPERTY = [
        'og_type' => 'og:type',
        'og_title' => 'og:title',
        'og_description' => 'og:description',
        'og_site_name' => 'og:site_name',
        'og_image' => 'og:image',
        'og_url' => 'og:url',
    ];

    private string $baseUrl;

    private int $timeoutSeconds;

    private string $userAgent;

    /** @var callable|null */
    private $logger;

    public function __construct(
        string $baseUrl = '',
        int $timeoutSeconds = 20,
        string $userAgent = 'EklektikaOgDiffBot/1.0',
        ?callable $logger = null
    ) {
        $this->baseUrl = rtrim(trim($baseUrl), '/');
        $this->timeoutSeconds = max(1, $timeoutSeconds);
        $this->userAgent = $userAgent;
        $this->logger = $logger;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @param list<string> $fields
     * @return array{
     *     total:int,
     *     compared:int,
     *     matched:int,
     *     differed:int,
     *     skipped_no_og:int,
     *     skipped_no_url:int,
     *     fetch_errors:int,
     *     details:list<array<string, mixed>>
     * }
     */
    public function compare(array $rows, array $fields = self::COMPARE_FIELDS, bool $onlyDiff = true): array
    {
        $fields = self::normalizeFields($fields);

        $stats = [
            'total' => count($rows),
            'compared' => 0,
            'matched' => 0,
            'differed' => 0,
            'skipped_no_og' => 0,
            'skipped_no_url' => 0,
            'fetch_errors' => 0,
            'details' => [],
        ];

        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            $menuTitle = (string)($row['menu_title'] ?? '');
            $newUrl = trim((string)($row['new_url'] ?? ''));
            $fetchUrl = $this->resolveFetchUrl($newUrl);

            if ($fetchUrl === '') {
                ++$stats['skipped_no_url'];
                $this->log(sprintf('[skip no url] id=%d menu=%s', $id, $menuTitle));
                continue;
            }

            $expected = self::extractExpectedOg($row, $fields);
            if ($expected === []) {
                ++$stats['skipped_no_og'];
                $this->log(sprintf('[skip no og] id=%d url=%s', $id, $fetchUrl));
                continue;
            }

            try {
                $html = $this->fetchHtml($fetchUrl);
                $actual = self::parseOgMetaFromHtml($html);
            } catch (\Throwable $e) {
                ++$stats['fetch_errors'];
                $detail = [
                    'id' => $id,
                    'menu_title' => $menuTitle,
                    'url' => $fetchUrl,
                    'status' => 'fetch_error',
                    'error' => $e->getMessage(),
                    'diffs' => [],
                ];
                $stats['details'][] = $detail;
                $this->log(sprintf('[fetch error] id=%d url=%s: %s', $id, $fetchUrl, $e->getMessage()));
                continue;
            }

            ++$stats['compared'];
            $diffs = self::diffOgValues($expected, $actual, $fields);

            if ($diffs === []) {
                ++$stats['matched'];
                if (!$onlyDiff) {
                    $stats['details'][] = [
                        'id' => $id,
                        'menu_title' => $menuTitle,
                        'url' => $fetchUrl,
                        'status' => 'match',
                        'diffs' => [],
                    ];
                    $this->log(sprintf('[match] id=%d url=%s', $id, $fetchUrl));
                }
                continue;
            }

            ++$stats['differed'];
            $detail = [
                'id' => $id,
                'menu_title' => $menuTitle,
                'url' => $fetchUrl,
                'status' => 'diff',
                'diffs' => $diffs,
            ];
            $stats['details'][] = $detail;
            $this->log(self::formatDiffLine($detail));
        }

        return $stats;
    }

    /**
     * @param list<string> $fields
     * @return list<string>
     */
    public static function normalizeFields(array $fields): array
    {
        $normalized = [];
        foreach ($fields as $field) {
            $field = trim((string)$field);
            if ($field !== '' && isset(self::FIELD_TO_PROPERTY[$field])) {
                $normalized[] = $field;
            }
        }

        return $normalized !== [] ? array_values(array_unique($normalized)) : self::COMPARE_FIELDS;
    }

    public function resolveFetchUrl(string $newUrl): string
    {
        $newUrl = trim($newUrl);
        if ($newUrl === '') {
            return '';
        }

        if (preg_match('~^https?://~i', $newUrl) === 1) {
            return $newUrl;
        }

        if ($this->baseUrl === '') {
            return $newUrl;
        }

        if (!str_starts_with($newUrl, '/')) {
            $newUrl = '/' . $newUrl;
        }

        return $this->baseUrl . $newUrl;
    }

    /**
     * @param array<string, mixed> $row
     * @param list<string> $fields
     * @return array<string, string>
     */
    public static function extractExpectedOg(array $row, array $fields): array
    {
        $expected = [];
        foreach ($fields as $field) {
            $value = self::normalizeOgText((string)($row[$field] ?? ''));
            if ($value !== '') {
                $expected[$field] = $value;
            }
        }

        return $expected;
    }

    /**
     * @return array<string, string> property => content
     */
    public static function parseOgMetaFromHtml(string $html): array
    {
        $result = [];

        if ($html === '') {
            return $result;
        }

        if (preg_match_all(
            '/<meta\b[^>]*>/i',
            $html,
            $metaTags
        ) !== false) {
            foreach ($metaTags[0] as $tag) {
                $property = self::extractMetaAttribute($tag, 'property');
                if ($property === '' || !str_starts_with(strtolower($property), 'og:')) {
                    $property = self::extractMetaAttribute($tag, 'name');
                }
                if ($property === '' || !str_starts_with(strtolower($property), 'og:')) {
                    continue;
                }

                $content = self::extractMetaAttribute($tag, 'content');
                $property = strtolower(trim($property));
                if (!isset($result[$property])) {
                    $result[$property] = self::normalizeOgText($content);
                }
            }
        }

        return $result;
    }

    /**
     * @param array<string, string> $expected field => value
     * @param array<string, string> $actual property => value
     * @param list<string> $fields
     * @return list<array{field:string, property:string, sqlite:string, page:string}>
     */
    public static function diffOgValues(array $expected, array $actual, array $fields): array
    {
        $diffs = [];

        foreach ($fields as $field) {
            if (!isset($expected[$field])) {
                continue;
            }

            $property = self::FIELD_TO_PROPERTY[$field];
            $sqliteValue = $expected[$field];
            $pageValue = self::normalizeOgText((string)($actual[$property] ?? ''));

            // Сравниваем только теги, которые реально есть на странице.
            if ($pageValue === '') {
                continue;
            }

            if (self::ogValuesEqual($field, $sqliteValue, $pageValue)) {
                continue;
            }

            $diffs[] = [
                'field' => $field,
                'property' => $property,
                'sqlite' => $sqliteValue,
                'page' => $pageValue,
            ];
        }

        return $diffs;
    }

    public static function ogValuesEqual(string $field, string $sqliteValue, string $pageValue): bool
    {
        if ($sqliteValue === $pageValue) {
            return true;
        }

        // og:image часто отличается хостом/путём к той же картинке — сравниваем path+query.
        if ($field === 'og_image') {
            return self::normalizeImageKey($sqliteValue) === self::normalizeImageKey($pageValue)
                && self::normalizeImageKey($sqliteValue) !== '';
        }

        // «Эклектика» vs Эклектика и т.п. — не считаем отличием.
        return self::normalizeForCompare($sqliteValue) === self::normalizeForCompare($pageValue);
    }

    public static function normalizeOgText(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = str_replace("\xc2\xa0", ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    /**
     * Нормализация только для сравнения (в отчёте показываем исходные строки).
     */
    public static function normalizeForCompare(string $value): string
    {
        $value = self::normalizeOgText($value);
        $value = str_replace(['ё', 'Ё'], ['е', 'Е'], $value);
        // Типографские/обычные кавычки часто есть в scrape и отсутствуют на новой странице.
        $value = preg_replace('/[«»„“”"\'‚‘’‹›]/u', '', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    public static function normalizeImageKey(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            return strtolower($url);
        }

        $path = (string)($parts['path'] ?? '');
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';

        return strtolower($path . $query);
    }

    /**
     * @param array{
     *     id:int,
     *     menu_title:string,
     *     url:string,
     *     status:string,
     *     diffs:list<array{field:string, property:string, sqlite:string, page:string}>
     * } $detail
     */
    public static function formatDiffLine(array $detail): string
    {
        $parts = [];
        foreach ($detail['diffs'] as $diff) {
            $parts[] = sprintf(
                '%s: sqlite=%s | page=%s',
                $diff['property'],
                self::shorten($diff['sqlite']),
                self::shorten($diff['page'] !== '' ? $diff['page'] : '<empty>')
            );
        }

        return sprintf(
            '[diff] id=%d menu=%s url=%s | %s',
            $detail['id'],
            $detail['menu_title'],
            $detail['url'],
            implode('; ', $parts)
        );
    }

    public static function shorten(string $value, int $max = 120): string
    {
        if (mb_strlen($value) <= $max) {
            return $value;
        }

        return mb_substr($value, 0, $max - 3) . '...';
    }

    private function fetchHtml(string $url): string
    {
        if (function_exists('curl_init')) {
            return $this->fetchViaCurl($url);
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $this->timeoutSeconds,
                'header' => "User-Agent: {$this->userAgent}\r\nAccept: text/html\r\n",
                'follow_location' => 1,
                'max_redirects' => 5,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $html = @file_get_contents($url, false, $context);
        if ($html === false) {
            throw new \RuntimeException('HTTP request failed (file_get_contents)');
        }

        $status = self::extractHttpStatusFromHeaders($http_response_header ?? []);
        if ($status > 0 && ($status < 200 || $status >= 400)) {
            throw new \RuntimeException('HTTP status ' . $status);
        }

        return (string)$html;
    }

    private function fetchViaCurl(string $url): string
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('curl_init failed');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => $this->timeoutSeconds,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_HTTPHEADER => ['Accept: text/html'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $html = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0 || $html === false) {
            throw new \RuntimeException('curl error: ' . ($error !== '' ? $error : 'code ' . $errno));
        }

        if ($status < 200 || $status >= 400) {
            throw new \RuntimeException('HTTP status ' . $status);
        }

        return (string)$html;
    }

    /**
     * @param list<string> $headers
     */
    private static function extractHttpStatusFromHeaders(array $headers): int
    {
        foreach ($headers as $header) {
            if (preg_match('~^HTTP/\S+\s+(\d{3})~', $header, $m) === 1) {
                return (int)$m[1];
            }
        }

        return 0;
    }

    private static function extractMetaAttribute(string $tag, string $name): string
    {
        $pattern = sprintf(
            '/%s\s*=\s*(["\'])(.*?)\1/i',
            preg_quote($name, '/')
        );
        if (preg_match($pattern, $tag, $m) === 1) {
            return html_entity_decode((string)$m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return '';
    }

    private function log(string $message): void
    {
        if ($this->logger !== null) {
            ($this->logger)($message);
        }
    }
}
