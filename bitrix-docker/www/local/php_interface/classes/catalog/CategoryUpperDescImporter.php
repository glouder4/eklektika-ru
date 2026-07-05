<?php

use Bitrix\Main\Loader;

/**
 * Импорт content_html из SQLite в UF_CATALOG_UPPER_DESC разделов каталога (IBLOCK 13).
 */
final class CategoryUpperDescImporter
{
    public const CATALOG_IBLOCK_ID = 13;
    public const CATALOG_URL_PREFIX = '/catalog/';

    /** @var callable|null */
    private $logger;

    public function __construct(?callable $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array{total:int,updated:int,skipped_non_catalog:int,skipped_no_section:int,errors:int,details:list<string>}
     */
    public function import(array $rows, bool $dryRun = true): array
    {
        if (!Loader::includeModule('iblock')) {
            throw new \RuntimeException('Module iblock is not available');
        }

        $stats = [
            'total' => count($rows),
            'updated' => 0,
            'skipped_non_catalog' => 0,
            'skipped_no_section' => 0,
            'errors' => 0,
            'details' => [],
        ];

        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            $newUrl = trim((string)($row['new_url'] ?? ''));
            $menuTitle = (string)($row['menu_title'] ?? '');
            $contentHtml = (string)($row['content_html'] ?? '');

            $codePath = self::extractCatalogCodePath($newUrl);
            if ($codePath === null) {
                ++$stats['skipped_non_catalog'];
                $this->log(sprintf('[skip non-catalog] id=%d menu=%s new_url=%s', $id, $menuTitle, $newUrl));
                continue;
            }

            $sectionId = catalogResolveSectionIdByCodePath(self::CATALOG_IBLOCK_ID, $codePath);
            if ($sectionId <= 0) {
                ++$stats['skipped_no_section'];
                $msg = sprintf('[skip no section] id=%d menu=%s path=%s', $id, $menuTitle, $codePath);
                $stats['details'][] = $msg;
                $this->log($msg);
                continue;
            }

            $html = self::normalizeContentHtml($contentHtml);
            if ($html === '') {
                ++$stats['errors'];
                $msg = sprintf('[error empty html] id=%d section=%d', $id, $sectionId);
                $stats['details'][] = $msg;
                $this->log($msg);
                continue;
            }

            if ($dryRun) {
                ++$stats['updated'];
                $this->log(sprintf('[dry-run] id=%d section=%d path=%s menu=%s', $id, $sectionId, $codePath, $menuTitle));
                continue;
            }

            $section = new \CIBlockSection();
            $ok = $section->Update($sectionId, [
                CatalogSectionUpperDescription::UF_CODE => $html,
            ]);

            if (!$ok) {
                ++$stats['errors'];
                $msg = sprintf(
                    '[error update] id=%d section=%d: %s',
                    $id,
                    $sectionId,
                    (string)$section->LAST_ERROR
                );
                $stats['details'][] = $msg;
                $this->log($msg);
                continue;
            }

            ++$stats['updated'];
            $this->log(sprintf('[updated] id=%d section=%d path=%s', $id, $sectionId, $codePath));
        }

        return $stats;
    }

    /**
     * Из new_url извлекает цепочку кодов раздела (без /catalog/).
     * Возвращает null, если URL не ведёт в каталог.
     */
    public static function extractCatalogCodePath(string $newUrl): ?string
    {
        $path = self::extractUrlPath($newUrl);
        if ($path === '') {
            return null;
        }

        if (!str_starts_with($path, self::CATALOG_URL_PREFIX)) {
            return null;
        }

        $codePath = trim(substr($path, strlen(self::CATALOG_URL_PREFIX)), '/');

        return $codePath !== '' ? $codePath : null;
    }

    public static function extractUrlPath(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, '/')) {
            return rtrim($url, '/') . '/';
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            return '';
        }

        $path = (string)($parts['path'] ?? '');
        if ($path === '') {
            return '';
        }

        return rtrim($path, '/') . '/';
    }

    /**
     * Убирает обёртку <div class="content"> — шаблон добавляет свою.
     */
    public static function normalizeContentHtml(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        if (preg_match('~^<div\s+class=["\']content["\']\s*>(.*)</div>\s*$~is', $html, $m)) {
            return trim($m[1]);
        }

        return $html;
    }

    private function log(string $message): void
    {
        if ($this->logger !== null) {
            ($this->logger)($message);
        }
    }
}
