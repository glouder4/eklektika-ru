<?php

use Bitrix\Main\Loader;

/**
 * Импорт detail_text из SQLite в описание разделов каталога (IBLOCK 13, поле DESCRIPTION).
 */
final class CategoryDetailTextImporter
{
    public const CATALOG_IBLOCK_ID = 13;

    /** @var callable|null */
    private $logger;

    public function __construct(?callable $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array{total:int,updated:int,skipped_non_catalog:int,skipped_no_section:int,skipped_empty:int,errors:int,details:list<string>}
     */
    public function import(array $rows, bool $dryRun = true): array
    {
        if (!Loader::includeModule('iblock')) {
            throw new \RuntimeException('Module iblock is not available');
        }

        require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/catalog_section_path.php';
        require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/classes/catalog/CategoryUpperDescImporter.php';

        $stats = [
            'total' => count($rows),
            'updated' => 0,
            'skipped_non_catalog' => 0,
            'skipped_no_section' => 0,
            'skipped_empty' => 0,
            'errors' => 0,
            'details' => [],
        ];

        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            $newUrl = trim((string)($row['new_url'] ?? ''));
            $menuTitle = (string)($row['menu_title'] ?? '');
            $detailText = (string)($row['detail_text'] ?? '');

            $codePath = CategoryUpperDescImporter::extractCatalogCodePath($newUrl);
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

            $html = CategoryUpperDescImporter::normalizeContentHtml($detailText);
            if ($html === '') {
                ++$stats['skipped_empty'];
                $msg = sprintf('[skip empty detail_text] id=%d section=%d', $id, $sectionId);
                $stats['details'][] = $msg;
                $this->log($msg);
                continue;
            }

            if ($dryRun) {
                ++$stats['updated'];
                $this->log(sprintf(
                    '[dry-run] id=%d section=%d path=%s menu=%s bytes=%d',
                    $id,
                    $sectionId,
                    $codePath,
                    $menuTitle,
                    strlen($html)
                ));
                continue;
            }

            $section = new \CIBlockSection();
            $ok = $section->Update($sectionId, [
                'DESCRIPTION' => $html,
                'DESCRIPTION_TYPE' => 'html',
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

    private function log(string $message): void
    {
        if ($this->logger !== null) {
            ($this->logger)($message);
        }
    }
}
