<?php

use Bitrix\Main\Loader;

require_once __DIR__ . '/CatalogOgTemplateDefaults.php';
require_once __DIR__ . '/CatalogOgIpropertyWriter.php';

/**
 * Установка OG-шаблонов элементов каталога на уровне IBLOCK (наследуются всеми товарами).
 */
final class CatalogElementOgIblockImporter
{
    /** @var callable|null */
    private $logger;

    public function __construct(?callable $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * @return array{updated:int,errors:int,details:list<string>,templates:list<string>}
     */
    public function apply(bool $dryRun = true): array
    {
        if (!Loader::includeModule('iblock')) {
            throw new \RuntimeException('Module iblock is not available');
        }

        $templates = CatalogOgTemplateDefaults::elementIblockTemplates();
        $stats = [
            'updated' => 0,
            'errors' => 0,
            'details' => [],
            'templates' => array_keys($templates),
        ];

        $this->log(sprintf(
            '%s iblock=%d tab=%s templates: %s',
            $dryRun ? '[dry-run]' : '[apply]',
            CatalogOgTemplateDefaults::CATALOG_IBLOCK_ID,
            CatalogOgTemplateDefaults::OG_TAB_CODE,
            implode(', ', $stats['templates'])
        ));

        if ($dryRun) {
            ++$stats['updated'];
            return $stats;
        }

        try {
            CatalogOgIpropertyWriter::saveIblockOgTemplates(
                CatalogOgTemplateDefaults::CATALOG_IBLOCK_ID,
                $templates
            );
            ++$stats['updated'];
            $this->log('[updated] element OG iblock templates saved');
        } catch (\Throwable $e) {
            ++$stats['errors'];
            $msg = '[error] ' . $e->getMessage();
            $stats['details'][] = $msg;
            $this->log($msg);
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
