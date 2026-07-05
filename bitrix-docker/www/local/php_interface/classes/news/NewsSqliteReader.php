<?php

require_once __DIR__ . '/../catalog/CategoryContentSqliteReader.php';

/**
 * Чтение новостей из upload/news.sqlite (таблицы news, news_images).
 */
final class NewsSqliteReader
{
    /** @var list<string> */
    public const REQUIRED_NEWS_COLUMNS = [
        'title',
        'preview_text',
        'preview_picture_upload_path',
        'published_at',
        'content_html',
        'url',
    ];

    private CategoryContentSqliteReader $reader;

    /** @var list<string>|null */
    private ?array $newsColumns = null;

    public function __construct(string $sqlitePath)
    {
        $this->reader = new CategoryContentSqliteReader($sqlitePath);
    }

    public static function resolveBackend(): string
    {
        return CategoryContentSqliteReader::resolveBackend();
    }

    public static function driverMissingMessage(): string
    {
        return CategoryContentSqliteReader::driverMissingMessage();
    }

    /**
     * @return list<string>
     */
    public function getNewsColumns(): array
    {
        if ($this->newsColumns !== null) {
            return $this->newsColumns;
        }

        $rows = $this->reader->rawQueryAll('PRAGMA table_info(news)');
        $columns = [];
        foreach ($rows as $row) {
            $name = trim((string)($row['name'] ?? ''));
            if ($name !== '') {
                $columns[] = $name;
            }
        }

        $this->newsColumns = $columns;

        return $columns;
    }

    public function validateSchema(): void
    {
        $present = $this->getNewsColumns();
        if ($present === []) {
            throw new \RuntimeException('Таблица news не найдена в SQLite');
        }

        $missing = array_values(array_diff(self::REQUIRED_NEWS_COLUMNS, $present));
        if ($missing !== []) {
            throw new \RuntimeException('В таблице news отсутствуют колонки: ' . implode(', ', $missing));
        }
    }

    /**
     * @return list<array{
     *     id:int,
     *     title:string,
     *     preview_text:string,
     *     preview_picture_upload_path:string,
     *     published_at:string,
     *     content_html:string,
     *     url:string
     * }>
     */
    public function fetchNewsRows(?int $limit = null): array
    {
        $this->validateSchema();

        $sql = <<<'SQL'
SELECT
    id,
    title,
    preview_text,
    preview_picture_upload_path,
    published_at,
    content_html,
    url
FROM news
WHERE title IS NOT NULL
  AND TRIM(title) != ''
ORDER BY published_at DESC, id ASC
SQL;

        if ($limit !== null && $limit > 0) {
            $sql .= ' LIMIT ' . (int)$limit;
        }

        $rows = $this->reader->rawQueryAll($sql);

        return array_map([$this, 'mapNewsRow'], $rows);
    }

    public function countNewsRows(): int
    {
        $this->validateSchema();
        $rows = $this->reader->rawQueryAll(
            'SELECT COUNT(*) AS cnt FROM news WHERE title IS NOT NULL AND TRIM(title) != \'\''
        );

        return (int)($rows[0]['cnt'] ?? 0);
    }

    /**
     * @param array<string, mixed> $row
     * @return array{
     *     id:int,
     *     title:string,
     *     preview_text:string,
     *     preview_picture_upload_path:string,
     *     published_at:string,
     *     content_html:string,
     *     url:string
     * }
     */
    private function mapNewsRow(array $row): array
    {
        return [
            'id' => (int)($row['id'] ?? 0),
            'title' => trim((string)($row['title'] ?? '')),
            'preview_text' => trim((string)($row['preview_text'] ?? '')),
            'preview_picture_upload_path' => trim((string)($row['preview_picture_upload_path'] ?? '')),
            'published_at' => trim((string)($row['published_at'] ?? '')),
            'content_html' => (string)($row['content_html'] ?? ''),
            'url' => trim((string)($row['url'] ?? '')),
        ];
    }
}
