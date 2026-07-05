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
     * @param array<string, string> $templates
     */
    public static function saveSectionOgTemplates(
        int $iblockId,
        int $sectionId,
        array $templates,
        string $tabCode = CatalogOgTemplateDefaults::OG_TAB_CODE
    ): void {
        if ($sectionId <= 0 || $templates === []) {
            throw new \InvalidArgumentException('Section OG templates: empty section or templates');
        }

        if (
            Loader::includeModule('dwstroy.opengraph')
            && class_exists(\Dwstroy\OpenGraph\InheritedProperty\SectionTemplates::class)
        ) {
            $sectionTemplates = new \Dwstroy\OpenGraph\InheritedProperty\SectionTemplates(
                $iblockId,
                $sectionId,
                $tabCode
            );
            $sectionTemplates->set($templates);

            return;
        }

        $ipropTemplates = [];
        foreach ($templates as $code => $value) {
            $ipropTemplates[$code] = [$tabCode => $value];
        }

        $section = new \CIBlockSection();
        $ok = $section->Update($sectionId, ['IPROPERTY_TEMPLATES' => $ipropTemplates]);
        if (!$ok) {
            throw new \RuntimeException((string)$section->LAST_ERROR);
        }
    }

    /**
     * @param array<string, string> $templates
     */
    public static function saveIblockOgTemplates(
        int $iblockId,
        array $templates,
        string $tabCode = CatalogOgTemplateDefaults::OG_TAB_CODE
    ): void {
        if ($iblockId <= 0 || $templates === []) {
            throw new \InvalidArgumentException('Iblock OG templates: empty iblock or templates');
        }

        if (
            Loader::includeModule('dwstroy.opengraph')
            && class_exists(\Dwstroy\OpenGraph\InheritedProperty\IblockTemplates::class)
        ) {
            $iblockTemplates = new \Dwstroy\OpenGraph\InheritedProperty\IblockTemplates($iblockId, $tabCode);
            $iblockTemplates->set($templates);

            return;
        }

        $ipropTemplates = [];
        foreach ($templates as $code => $value) {
            $ipropTemplates[$code] = [$tabCode => $value];
        }

        $iblock = new \CIBlock();
        $ok = $iblock->Update($iblockId, ['IPROPERTY_TEMPLATES' => $ipropTemplates]);
        if (!$ok) {
            throw new \RuntimeException((string)$iblock->LAST_ERROR);
        }
    }
}
