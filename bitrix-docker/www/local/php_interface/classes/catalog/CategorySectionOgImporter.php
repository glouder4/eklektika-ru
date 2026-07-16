<?php

use Bitrix\Main\Loader;

require_once __DIR__ . '/CatalogOgTemplateDefaults.php';
require_once __DIR__ . '/CatalogOgIpropertyWriter.php';

/**
 * Импорт OG-шаблонов разделов каталога из SQLite в dwstroy.opengraph IPROPERTY.
 *
 * Поля админки:
 * - IPROPERTY_TEMPLATES_SECTION_OG_TYPE_catalog
 * - IPROPERTY_TEMPLATES_SECTION_OG_TITLE_catalog
 * - IPROPERTY_TEMPLATES_SECTION_OG_DESCRIPTION_catalog
 * - IPROPERTY_TEMPLATES_SECTION_OG_SITE_NAME_catalog
 * - IPROPERTY_TEMPLATES_SECTION_OG_IMAGE_catalog
 *
 * Из колонки og_description также пишется нативный SEO:
 * - IPROPERTY_TEMPLATES[SECTION_META_DESCRIPTION]
 */
final class CategorySectionOgImporter
{
    public const CATALOG_IBLOCK_ID = 13;
    public const OG_TAB_CODE = 'catalog';

    /** @var array<string, string> SQLite column => IPROPERTY template code */
    private const FIELD_MAP = [
        'og_type' => 'SECTION_OG_TYPE',
        'og_title' => 'SECTION_OG_TITLE',
        'og_description' => 'SECTION_OG_DESCRIPTION',
        'og_site_name' => 'SECTION_OG_SITE_NAME',
        'og_image' => 'SECTION_OG_IMAGE',
    ];

    /** @var list<string> */
    private const LEGACY_OG_IMAGE_HOSTS = [
        'eklektika.ru',
        'www.eklektika.ru',
    ];

    private const GENERIC_OG_LOGO_SUFFIX = '/assets/images/akcii/logo7.png';

    public const OG_IMAGE_SECTION_PICTURE_TEMPLATE = '{=PICTURE}';

    /** @var callable|null */
    private $logger;

    public function __construct(?callable $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array{
     *     total:int,
     *     updated:int,
     *     skipped_non_catalog:int,
     *     skipped_no_section:int,
     *     skipped_empty_og:int,
     *     og_image_to_template:int,
     *     meta_description_set:int,
     *     errors:int,
     *     details:list<string>
     * }
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
            'skipped_empty_og' => 0,
            'og_image_to_template' => 0,
            'meta_description_set' => 0,
            'errors' => 0,
            'details' => [],
        ];

        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            $newUrl = trim((string)($row['new_url'] ?? ''));
            $menuTitle = (string)($row['menu_title'] ?? '');

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

            $templates = self::buildTemplatesFromRow($row);
            $templates = self::enrichSectionComputedTemplates($sectionId, $templates);
            if ($templates === []) {
                ++$stats['skipped_empty_og'];
                $this->log(sprintf('[skip empty og] id=%d section=%d path=%s', $id, $sectionId, $codePath));
                continue;
            }

            if (
                isset($templates['SECTION_OG_IMAGE'])
                && $templates['SECTION_OG_IMAGE'] === self::OG_IMAGE_SECTION_PICTURE_TEMPLATE
                && trim((string)($row['og_image'] ?? '')) !== ''
            ) {
                ++$stats['og_image_to_template'];
            }

            $metaDescription = self::extractMetaDescriptionFromRow($row);
            if ($metaDescription !== '') {
                ++$stats['meta_description_set'];
            }

            $fieldsLog = self::formatTemplatesForLog($templates, $metaDescription !== '');

            if ($dryRun) {
                ++$stats['updated'];
                $this->log(sprintf(
                    '[dry-run] id=%d section=%d path=%s menu=%s fields=%s',
                    $id,
                    $sectionId,
                    $codePath,
                    $menuTitle,
                    $fieldsLog
                ));
                continue;
            }

            try {
                $ok = self::saveSectionOgTemplates($sectionId, $templates);
                if ($ok && $metaDescription !== '') {
                    CatalogOgIpropertyWriter::saveSectionMetaDescriptionTemplate(
                        self::CATALOG_IBLOCK_ID,
                        $sectionId,
                        $metaDescription
                    );
                }
            } catch (\Throwable $e) {
                $ok = false;
                $errorText = $e->getMessage();
            }

            if (!$ok) {
                ++$stats['errors'];
                $msg = sprintf(
                    '[error update] id=%d section=%d: %s',
                    $id,
                    $sectionId,
                    $errorText ?? 'unknown error'
                );
                $stats['details'][] = $msg;
                $this->log($msg);
                continue;
            }

            ++$stats['updated'];
            $this->log(sprintf(
                '[updated] id=%d section=%d path=%s fields=%s',
                $id,
                $sectionId,
                $codePath,
                $fieldsLog
            ));
        }

        return $stats;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function extractMetaDescriptionFromRow(array $row): string
    {
        return trim((string)($row['og_description'] ?? ''));
    }
    /**
     * @param array<string, mixed> $row
     * @return array<string, string>
     */
    public static function buildTemplatesFromRow(array $row): array
    {
        $templates = [];

        foreach (self::FIELD_MAP as $sqliteKey => $templateCode) {
            $value = trim((string)($row[$sqliteKey] ?? ''));
            if ($value === '') {
                continue;
            }

            if ($sqliteKey === 'og_image') {
                $value = self::normalizeOgImageTemplate($value);
                if ($value === null || $value === '') {
                    continue;
                }
            }

            $templates[$templateCode] = $value;
        }

        return $templates;
    }

    /**
     * Дополняет OG-шаблоны раздела вычисляемыми полями (нет колонок в SQLite).
     *
     * @param array<string, string> $templates
     * @return array<string, string>
     */
    public static function enrichSectionComputedTemplates(int $sectionId, array $templates): array
    {
        if (!isset($templates['SECTION_OG_DESCRIPTION']) || $templates['SECTION_OG_DESCRIPTION'] === '') {
            $metaDescription = CatalogOgIpropertyWriter::resolveSectionMetaDescriptionTemplate(
                self::CATALOG_IBLOCK_ID,
                $sectionId
            );
            if ($metaDescription === '') {
                $metaDescription = CatalogOgTemplateDefaults::SECTION_OG_DESCRIPTION_FALLBACK;
            }
            $templates['SECTION_OG_DESCRIPTION'] = $metaDescription;
        }

        if (!isset($templates['SECTION_OG_IMAGE']) || $templates['SECTION_OG_IMAGE'] === '') {
            $templates['SECTION_OG_IMAGE'] = CatalogOgTemplateDefaults::SECTION_OG_IMAGE;
        }

        foreach (CatalogOgTemplateDefaults::sectionComputedTemplates() as $code => $value) {
            if (!isset($templates[$code]) || $templates[$code] === '') {
                $templates[$code] = $value;
            }
        }

        if (!isset($templates['SECTION_OG_LOCALE']) || $templates['SECTION_OG_LOCALE'] === '') {
            $templates['SECTION_OG_LOCALE'] = CatalogOgTemplateDefaults::OG_LOCALE;
        }

        return $templates;
    }

    /**
     * Не импортируем прямые URL на legacy-домен или общий logo7.png —
     * вместо этого шаблон картинки раздела.
     */
    public static function normalizeOgImageTemplate(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        if (self::isGenericOgLogoUrl($raw) || self::isLegacyOgImageHost($raw)) {
            return self::OG_IMAGE_SECTION_PICTURE_TEMPLATE;
        }

        if (str_starts_with($raw, '/')) {
            if (self::isGenericOgLogoPath($raw)) {
                return self::OG_IMAGE_SECTION_PICTURE_TEMPLATE;
            }

            return $raw;
        }

        $parts = parse_url($raw);
        if (!is_array($parts)) {
            return null;
        }

        $host = strtolower((string)($parts['host'] ?? ''));
        if ($host !== '' && self::isLegacyHost($host)) {
            return self::OG_IMAGE_SECTION_PICTURE_TEMPLATE;
        }

        return $raw;
    }

    public static function isLegacyOgImageHost(string $url): bool
    {
        if (!str_contains($url, '://') && str_starts_with($url, '/')) {
            return false;
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            return false;
        }

        $host = strtolower((string)($parts['host'] ?? ''));

        return $host !== '' && self::isLegacyHost($host);
    }

    public static function isGenericOgLogoUrl(string $url): bool
    {
        $path = self::extractUrlPath($url);

        return self::isGenericOgLogoPath($path);
    }

    public static function isGenericOgLogoPath(string $path): bool
    {
        $path = strtolower(rawurldecode(trim($path)));
        if ($path === '') {
            return false;
        }

        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        return str_ends_with(rtrim($path, '/'), self::GENERIC_OG_LOGO_SUFFIX);
    }

    public static function extractUrlPath(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, '/')) {
            return $url;
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            return '';
        }

        return (string)($parts['path'] ?? '');
    }

    private static function isLegacyHost(string $host): bool
    {
        return in_array(strtolower($host), self::LEGACY_OG_IMAGE_HOSTS, true);
    }

    /**
     * @param array<string, string> $templates
     */
    private static function formatTemplatesForLog(array $templates, bool $withMetaDescription = false): string
    {
        $parts = [];
        foreach ($templates as $code => $value) {
            if ($code === 'SECTION_OG_IMAGE' && $value === self::OG_IMAGE_SECTION_PICTURE_TEMPLATE) {
                $parts[] = $code . '={=PICTURE}';
                continue;
            }

            $parts[] = $code;
        }

        if ($withMetaDescription) {
            $parts[] = 'SECTION_META_DESCRIPTION';
        }

        return implode(',', $parts);
    }

    /**
     * @param array<string, string> $templates
     */
    public static function saveSectionOgTemplates(int $sectionId, array $templates): bool
    {
        CatalogOgIpropertyWriter::saveSectionOgTemplates(self::CATALOG_IBLOCK_ID, $sectionId, $templates);

        return true;
    }

    private function log(string $message): void
    {
        if ($this->logger !== null) {
            ($this->logger)($message);
        }
    }
}
