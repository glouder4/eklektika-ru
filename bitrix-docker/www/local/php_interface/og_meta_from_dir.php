<?php

if (!function_exists('resolveOgMetaEntityName')) {
    /**
     * Имя сущности для подстановки {=this.Name} в шаблонах og:* из .section.php.
     * Источники: DWSTROY_* page properties (catalog-section.php / element.php) или GLOBALS каталога.
     */
    function resolveOgMetaEntityName(\CMain $application): string
    {
        $iblockId = (int)$application->GetPageProperty('DWSTROY_OG_TWITTER_IBLOCK_ID');
        $elementId = (int)$application->GetPageProperty('DWSTROY_OG_TWITTER_ELEMENT_ID');
        $sectionId = (int)$application->GetPageProperty('DWSTROY_OG_TWITTER_SECTION_ID');

        if ($elementId <= 0 && !empty($GLOBALS['CATALOG_CURRENT_ELEMENT_ID'])) {
            $elementId = (int)$GLOBALS['CATALOG_CURRENT_ELEMENT_ID'];
        }
        if ($sectionId <= 0 && !empty($GLOBALS['CATALOG_CURRENT_SECTION_ID'])) {
            $sectionId = (int)$GLOBALS['CATALOG_CURRENT_SECTION_ID'];
        }

        if (!\Bitrix\Main\Loader::includeModule('iblock')) {
            return trim((string)$application->GetTitle());
        }

        if ($elementId > 0) {
            if ($iblockId > 0 && class_exists(\Bitrix\Iblock\InheritedProperty\ElementValues::class)) {
                $ipropValues = (new \Bitrix\Iblock\InheritedProperty\ElementValues($iblockId, $elementId))->getValues();
                $pageTitle = trim((string)($ipropValues['ELEMENT_PAGE_TITLE'] ?? ''));
                if ($pageTitle !== '') {
                    return $pageTitle;
                }
            }

            $elementRes = \CIBlockElement::GetList(
                [],
                ['ID' => $elementId],
                false,
                false,
                ['NAME']
            );
            if ($element = $elementRes->GetNext()) {
                $name = trim((string)($element['NAME'] ?? ''));
                if ($name !== '') {
                    return $name;
                }
            }
        }

        if ($sectionId > 0) {
            if ($iblockId > 0 && class_exists(\Bitrix\Iblock\InheritedProperty\SectionValues::class)) {
                $ipropValues = (new \Bitrix\Iblock\InheritedProperty\SectionValues($iblockId, $sectionId))->getValues();
                $pageTitle = trim((string)($ipropValues['SECTION_PAGE_TITLE'] ?? ''));
                if ($pageTitle !== '') {
                    return $pageTitle;
                }
            }

            $sectionFilter = ['ID' => $sectionId];
            if ($iblockId > 0) {
                $sectionFilter['IBLOCK_ID'] = $iblockId;
            }

            $sectionRes = \CIBlockSection::GetList([], $sectionFilter, false, ['NAME']);
            if ($section = $sectionRes->GetNext()) {
                $name = trim((string)($section['NAME'] ?? ''));
                if ($name !== '') {
                    return $name;
                }
            }
        }

        return trim((string)$application->GetTitle());
    }
}

if (!function_exists('applyOgMetaTemplate')) {
    function applyOgMetaTemplate(string $template, string $entityName): string
    {
        if ($template === '' || $entityName === '') {
            return $template;
        }

        return str_replace(
            ['{=this.Name}', '{=this.name}', '#NAME#'],
            $entityName,
            $template
        );
    }
}

if (!function_exists('resolveOgMetaPropertyValue')) {
    /**
     * Значение og/twitter-свойства: явный SetPageProperty → шаблон из GetDirProperty (.section.php) → подстановка {=this.Name}.
     */
    function resolveOgMetaPropertyValue(\CMain $application, string $property): string
    {
        $value = trim((string)$application->GetPageProperty($property, ''));
        if ($value === '') {
            $value = trim((string)$application->GetDirProperty($property));
        }

        if ($value === '') {
            return '';
        }

        if (
            strpos($value, '{=this.') !== false
            || strpos($value, '#NAME#') !== false
        ) {
            $value = applyOgMetaTemplate($value, resolveOgMetaEntityName($application));
        }

        return $value;
    }
}

if (!function_exists('normalizeOgMetaTextValue')) {
    function normalizeOgMetaTextValue(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/["\'\x{00AB}\x{00BB}\x{201C}\x{201D}\x{2018}\x{2019}]/u', '', $value);

        return (string)preg_replace('/\s+/u', ' ', trim($value));
    }
}
