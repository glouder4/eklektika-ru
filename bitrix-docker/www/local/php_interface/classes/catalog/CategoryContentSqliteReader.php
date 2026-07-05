<?php

/**
 * Чтение SEO-описаний категорий из upload/categories.sqlite.
 *
 * Бэкенды (по приоритету): PDO sqlite → ext-sqlite3 → sqlite3 CLI.
 */
final class CategoryContentSqliteReader
{
    /** @var list<string> */
    public const OG_SQLITE_COLUMNS = [
        'og_type',
        'og_title',
        'og_description',
        'og_site_name',
        'og_image',
    ];

    private string $sqlitePath;

    private ?\PDO $pdo = null;

    /** @var list<string>|null */
    private ?array $categoryContentColumns = null;

    public function __construct(string $sqlitePath)
    {
        if (!is_file($sqlitePath)) {
            throw new \InvalidArgumentException('SQLite file not found: ' . $sqlitePath);
        }

        $this->sqlitePath = $sqlitePath;
    }

    public static function resolveBackend(): string
    {
        if (extension_loaded('pdo_sqlite')) {
            return 'pdo_sqlite';
        }

        if (class_exists(\SQLite3::class, false)) {
            return 'sqlite3';
        }

        if (self::findSqlite3CliBinary() !== null) {
            return 'sqlite3_cli';
        }

        return '';
    }

    public static function driverMissingMessage(): string
    {
        return 'SQLite недоступен в PHP CLI. Варианты:'
            . PHP_EOL . '  1) yum install php-pdo php-sqlite3   (или dnf/apt аналог)'
            . PHP_EOL . '  2) yum install sqlite   (CLI sqlite3 для fallback)'
            . PHP_EOL . '  3) Проверьте: php -m | grep -i sqlite';
    }

    /**
     * Записи к обработке: new_url задан, content_html непустой.
     *
     * @return list<array{
     *     id:int,
     *     menu_title:?string,
     *     parent_title:?string,
     *     level:?string,
     *     old_url:string,
     *     new_url:string,
     *     content_html:string
     * }>
     */
    public function fetchProcessableRows(?int $limit = null): array
    {
        return $this->fetchRows(self::buildProcessableSql($limit), [$this, 'mapRows']);
    }

    /**
     * Записи к OG-импорту: new_url задан (og_* могут быть пустыми).
     *
     * @return list<array{
     *     id:int,
     *     menu_title:?string,
     *     parent_title:?string,
     *     level:?string,
     *     old_url:string,
     *     new_url:string,
     *     og_type:string,
     *     og_title:string,
     *     og_description:string,
     *     og_site_name:string,
     *     og_image:string
     * }>
     */
    public function fetchOgProcessableRows(?int $limit = null): array
    {
        return $this->fetchRows($this->buildOgProcessableSql($limit), [$this, 'mapOgRows']);
    }

    /**
     * @return list<string>
     */
    public function getOgColumnsPresent(): array
    {
        $present = [];
        foreach (self::OG_SQLITE_COLUMNS as $column) {
            if ($this->hasCategoryContentColumn($column)) {
                $present[] = $column;
            }
        }

        return $present;
    }

    /**
     * @return list<string>
     */
    public function getCategoryContentColumns(): array
    {
        if ($this->categoryContentColumns !== null) {
            return $this->categoryContentColumns;
        }

        $rows = $this->queryAll('PRAGMA table_info(category_content)');
        $columns = [];

        foreach ($rows as $row) {
            $name = trim((string)($row['name'] ?? ''));
            if ($name !== '') {
                $columns[] = $name;
            }
        }

        $this->categoryContentColumns = $columns;

        return $columns;
    }

    public function hasCategoryContentColumn(string $column): bool
    {
        return in_array($column, $this->getCategoryContentColumns(), true);
    }

    public static function missingOgColumnsMessage(string $sqlitePath, array $present): string
    {
        $missing = array_values(array_diff(self::OG_SQLITE_COLUMNS, $present));

        return 'Файл categories.sqlite не содержит OG-колонок: '
            . implode(', ', $missing)
            . PHP_EOL . 'Путь: ' . $sqlitePath
            . PHP_EOL . 'Загрузите обновлённую версию upload/categories.sqlite на сервер.';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function rawQueryAll(string $sql): array
    {
        return $this->queryAll($sql);
    }

    /**
     * @param callable(list<array<string, mixed>>): list<array<string, mixed>> $mapper
     * @return list<array<string, mixed>>
     */
    private function fetchRows(string $sql, callable $mapper): array
    {
        $backend = self::resolveBackend();

        if ($backend === 'pdo_sqlite') {
            return $mapper($this->fetchViaPdo($sql));
        }

        if ($backend === 'sqlite3') {
            return $mapper($this->fetchViaSqlite3($sql));
        }

        if ($backend === 'sqlite3_cli') {
            return $mapper($this->fetchViaSqlite3Cli($sql));
        }

        throw new \RuntimeException(self::driverMissingMessage());
    }

    private static function buildProcessableSql(?int $limit): string
    {
        $sql = <<<'SQL'
SELECT id, menu_title, parent_title, level, url AS old_url, new_url, content_html
FROM category_content
WHERE new_url IS NOT NULL
  AND TRIM(new_url) != ''
  AND content_html IS NOT NULL
  AND TRIM(content_html) != ''
ORDER BY id
SQL;

        if ($limit !== null && $limit > 0) {
            $sql .= ' LIMIT ' . (int)$limit;
        }

        return $sql;
    }

    private function buildOgProcessableSql(?int $limit): string
    {
        $presentOgColumns = $this->getOgColumnsPresent();
        if ($presentOgColumns === []) {
            throw new \RuntimeException(self::missingOgColumnsMessage($this->sqlitePath, $presentOgColumns));
        }

        $selectParts = [
            'id',
            'menu_title',
            'parent_title',
            'level',
            'url AS old_url',
            'new_url',
        ];

        foreach (self::OG_SQLITE_COLUMNS as $column) {
            if ($this->hasCategoryContentColumn($column)) {
                $selectParts[] = $column;
            } else {
                $selectParts[] = "'' AS {$column}";
            }
        }

        $sql = 'SELECT ' . implode(', ', $selectParts) . '
FROM category_content
WHERE new_url IS NOT NULL
  AND TRIM(new_url) != \'\'
ORDER BY id';

        if ($limit !== null && $limit > 0) {
            $sql .= ' LIMIT ' . (int)$limit;
        }

        return $sql;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function queryAll(string $sql): array
    {
        $backend = self::resolveBackend();

        if ($backend === 'pdo_sqlite') {
            return $this->fetchViaPdo($sql);
        }

        if ($backend === 'sqlite3') {
            return $this->fetchViaSqlite3($sql);
        }

        if ($backend === 'sqlite3_cli') {
            return $this->fetchViaSqlite3Cli($sql);
        }

        throw new \RuntimeException(self::driverMissingMessage());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchViaPdo(string $sql): array
    {
        if ($this->pdo === null) {
            $this->pdo = new \PDO('sqlite:' . $this->sqlitePath, null, null, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);
        }

        $stmt = $this->pdo->query($sql);
        if ($stmt === false) {
            throw new \RuntimeException('PDO query failed');
        }

        return $stmt->fetchAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchViaSqlite3(string $sql): array
    {
        $db = new \SQLite3($this->sqlitePath, \SQLITE3_OPEN_READONLY);
        $result = $db->query($sql);
        if ($result === false) {
            $db->close();
            throw new \RuntimeException('SQLite3 query failed: ' . $db->lastErrorMsg());
        }

        $rows = [];
        while ($row = $result->fetchArray(\SQLITE3_ASSOC)) {
            if (is_array($row)) {
                $rows[] = $row;
            }
        }

        $result->finalize();
        $db->close();

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchViaSqlite3Cli(string $sql): array
    {
        $binary = self::findSqlite3CliBinary();
        if ($binary === null) {
            throw new \RuntimeException(self::driverMissingMessage());
        }

        $command = escapeshellarg($binary)
            . ' -json '
            . escapeshellarg($this->sqlitePath)
            . ' '
            . escapeshellarg($sql);

        $output = [];
        $exitCode = 1;
        exec($command . ' 2>&1', $output, $exitCode);

        if ($exitCode !== 0) {
            throw new \RuntimeException(
                'sqlite3 CLI failed (exit ' . $exitCode . '): ' . implode(PHP_EOL, $output)
            );
        }

        $json = trim(implode("\n", $output));
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('sqlite3 CLI returned invalid JSON');
        }

        if ($decoded !== [] && !isset($decoded[0])) {
            return [$decoded];
        }

        return $decoded;
    }

    private static function findSqlite3CliBinary(): ?string
    {
        foreach (['sqlite3', '/usr/bin/sqlite3', '/usr/local/bin/sqlite3'] as $candidate) {
            if ($candidate === 'sqlite3') {
                $which = trim((string)shell_exec('command -v sqlite3 2>/dev/null'));
                if ($which !== '' && is_executable($which)) {
                    return $which;
                }
                continue;
            }

            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array{
     *     id:int,
     *     menu_title:?string,
     *     parent_title:?string,
     *     level:?string,
     *     old_url:string,
     *     new_url:string,
     *     content_html:string
     * }>
     */
    private function mapRows(array $rows): array
    {
        $mapped = [];

        foreach ($rows as $row) {
            $mapped[] = [
                'id' => (int)$row['id'],
                'menu_title' => $row['menu_title'] !== null ? (string)$row['menu_title'] : null,
                'parent_title' => $row['parent_title'] !== null ? (string)$row['parent_title'] : null,
                'level' => $row['level'] !== null ? (string)$row['level'] : null,
                'old_url' => (string)$row['old_url'],
                'new_url' => trim((string)$row['new_url']),
                'content_html' => (string)$row['content_html'],
            ];
        }

        return $mapped;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array{
     *     id:int,
     *     menu_title:?string,
     *     parent_title:?string,
     *     level:?string,
     *     old_url:string,
     *     new_url:string,
     *     og_type:string,
     *     og_title:string,
     *     og_description:string,
     *     og_site_name:string,
     *     og_image:string
     * }>
     */
    private function mapOgRows(array $rows): array
    {
        $mapped = [];

        foreach ($rows as $row) {
            $mapped[] = [
                'id' => (int)$row['id'],
                'menu_title' => $row['menu_title'] !== null ? (string)$row['menu_title'] : null,
                'parent_title' => $row['parent_title'] !== null ? (string)$row['parent_title'] : null,
                'level' => $row['level'] !== null ? (string)$row['level'] : null,
                'old_url' => (string)$row['old_url'],
                'new_url' => trim((string)$row['new_url']),
                'og_type' => trim((string)($row['og_type'] ?? '')),
                'og_title' => trim((string)($row['og_title'] ?? '')),
                'og_description' => trim((string)($row['og_description'] ?? '')),
                'og_site_name' => trim((string)($row['og_site_name'] ?? '')),
                'og_image' => trim((string)($row['og_image'] ?? '')),
            ];
        }

        return $mapped;
    }
}
