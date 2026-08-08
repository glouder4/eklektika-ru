<?php

use Bitrix\Iblock\InheritedProperty\IblockTemplates;
use Bitrix\Iblock\InheritedProperty\SectionTemplates;
use Bitrix\Main\Loader;

/**
 * Запись OG/SEO inherited property templates (Bitrix + dwstroy.opengraph).
 */
final class CatalogOgIpropertyWriter
{
    /**
     * @return array<string, string>
     */
    public static function resolveSectionMetaDescriptionTemplate(int $iblockId, int $sectionId): string
    {
        if (!Loader::includeModule('iblock')) {
            return '';
        }

        if ($sectionId > 0) {
            $sectionTemplates = new SectionTemplates($iblockId, $sectionId);
            $found = $sectionTemplates->findTemplates();
            $template = trim((string)($found['SECTION_META_DESCRIPTION']['TEMPLATE'] ?? ''));
            if ($template !== '') {
                return $template;
            }
        }

        $iblockTemplates = new IblockTemplates($iblockId);
        $found = $iblockTemplates->findTemplates();

        return trim((string)($found['SECTION_META_DESCRIPTION']['TEMPLATE'] ?? ''));
    }

    /**
     * Нативный Bitrix SEO: IPROPERTY SECTION_META_DESCRIPTION (без вкладки catalog).
     */
    public static function saveSectionMetaDescriptionTemplate(
        int $iblockId,
        int $sectionId,
        string $template
    ): void {
        if ($iblockId <= 0 || $sectionId <= 0) {
            throw new \InvalidArgumentException('Section META_DESCRIPTION: empty iblock/section');
        }

        if (!Loader::includeModule('iblock')) {
            throw new \RuntimeException('Module iblock is not available');
        }

        $template = trim($template);
        $sectionTemplates = new SectionTemplates($iblockId, $sectionId);
        $sectionTemplates->set([
            'SECTION_META_DESCRIPTION' => $template,
        ]);

        // Гасим stale VALUE в b_iblock_section_iprop после смены TEMPLATE.
        $valuesEntity = $sectionTemplates->getValuesEntity();
        if ($valuesEntity !== null && method_exists($valuesEntity, 'clearValues')) {
            $valuesEntity->clearValues();
        }
    }

    /**
     * Вкладки dwstroy.opengraph для инфоблока (как в админке OpenGraph (ru)).
     * Берём LANG_ID из conditionTable модуля; fallback — catalog/ru/LANGUAGE_ID.
     *
     * @return list<string>
     */
    public static function resolveOgTabCodes(int $iblockId = 0, ?string $primaryTab = null): array
    {
        $tabs = [];
        if ($primaryTab !== null && trim($primaryTab) !== '') {
            $tabs[] = trim($primaryTab);
        }

        if (
            $iblockId > 0
            && Loader::includeModule('dwstroy.opengraph')
            && class_exists(\Dwstroy\OpenGraph\COpenGraph::class)
        ) {
            $langs = \Dwstroy\OpenGraph\COpenGraph::getLang($iblockId, 'IBLOCK');
            if (is_array($langs)) {
                foreach ($langs as $lang) {
                    $langId = trim((string)($lang['LANG_ID'] ?? ''));
                    if ($langId !== '') {
                        $tabs[] = $langId;
                    }
                }
            }
        }

        foreach (CatalogOgTemplateDefaults::OG_TAB_CODES as $tab) {
            $tabs[] = $tab;
        }

        if (defined('LANGUAGE_ID')) {
            $lang = trim((string)LANGUAGE_ID);
            if ($lang !== '') {
                $tabs[] = $lang;
            }
        }

        return array_values(array_unique(array_filter($tabs)));
    }

    /**
     * @param array<string, string> $templates
     * @return list<string> tabs that were written
     */
    public static function saveSectionOgTemplates(
        int $iblockId,
        int $sectionId,
        array $templates,
        ?string $tabCode = null
    ): array {
        if ($sectionId <= 0 || $templates === []) {
            throw new \InvalidArgumentException('Section OG templates: empty section or templates');
        }

        $templates = self::normalizeTemplateMap($templates);
        $tabCodes = self::resolveOgTabCodes($iblockId, $tabCode);

        if (
            Loader::includeModule('dwstroy.opengraph')
            && class_exists(\Dwstroy\OpenGraph\InheritedProperty\SectionTemplates::class)
        ) {
            foreach ($tabCodes as $tab) {
                $sectionTemplates = new \Dwstroy\OpenGraph\InheritedProperty\SectionTemplates(
                    $iblockId,
                    $sectionId,
                    $tab
                );
                $sectionTemplates->set($templates);

                // BaseTemplate::set() чистит VALUE-кэш только при add новых CODE;
                // при update существующего DESCRIPTION кэш остаётся дырявым.
                $valuesEntity = $sectionTemplates->getValuesEntity();
                if ($valuesEntity !== null && method_exists($valuesEntity, 'clearValues')) {
                    $valuesEntity->clearValues();
                }
            }

            return $tabCodes;
        }

        throw new \RuntimeException(
            'Module dwstroy.opengraph is not available — cannot write OpenGraph (ru) fields'
        );
    }

    /**
     * @param array<string, string> $templates
     * @return list<string>
     */
    public static function saveIblockOgTemplates(
        int $iblockId,
        array $templates,
        ?string $tabCode = null
    ): array {
        if ($iblockId <= 0 || $templates === []) {
            throw new \InvalidArgumentException('Iblock OG templates: empty iblock or templates');
        }

        $templates = self::normalizeTemplateMap($templates);
        $tabCodes = self::resolveOgTabCodes($iblockId, $tabCode);

        if (
            Loader::includeModule('dwstroy.opengraph')
            && class_exists(\Dwstroy\OpenGraph\InheritedProperty\IblockTemplates::class)
        ) {
            foreach ($tabCodes as $tab) {
                $iblockTemplates = new \Dwstroy\OpenGraph\InheritedProperty\IblockTemplates($iblockId, $tab);
                $iblockTemplates->set($templates);
            }

            return $tabCodes;
        }

        throw new \RuntimeException(
            'Module dwstroy.opengraph is not available — cannot write OpenGraph iblock templates'
        );
    }

    /**
     * @param array<string, mixed> $templates
     * @return array<string, string>
     */
    private static function normalizeTemplateMap(array $templates): array
    {
        $normalized = [];
        foreach ($templates as $code => $value) {
            $code = trim((string)$code);
            $value = trim((string)$value);
            if ($code === '' || $value === '') {
                continue;
            }
            $normalized[$code] = $value;
        }

        return $normalized;
    }

    /**
     * Чтение шаблонов раздела по вкладке (для диагностики).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function readSectionOgTemplates(int $iblockId, int $sectionId, string $tabCode): array
    {
        if (
            !Loader::includeModule('dwstroy.opengraph')
            || !class_exists(\Dwstroy\OpenGraph\InheritedProperty\SectionTemplates::class)
        ) {
            throw new \RuntimeException('Module dwstroy.opengraph is not available');
        }

        $sectionTemplates = new \Dwstroy\OpenGraph\InheritedProperty\SectionTemplates(
            $iblockId,
            $sectionId,
            $tabCode
        );

        return $sectionTemplates->findTemplates();
    }
}
