<?php

namespace OnlineService\Catalog\Import1c;

use OnlineService\Catalog\Import1c\Config\PostImportConfig;

/**
 * Постобработка каталога после импорта из 1С: сбор типов нанесения в свойство APPLICATION_TYPES.
 */
final class PostImportHandler
{
    /**
     * Обработчик события catalog / OnSuccessCatalogImport1C (ранее updateProperties в init.php).
     */
    public static function onSuccessCatalogImport(): void
    {
        if (!\CModule::IncludeModule('iblock') || !\CModule::IncludeModule('catalog')) {
            return;
        }

        $dbFields = \CIBlockElement::GetList(
            ['ID' => 'ASC'],
            ['IBLOCK_ID' => PostImportConfig::IBLOCK_ID_1C],
            false,
            false,
            [
                'ID',
                'IBLOCK_ID',
                'PROPERTY_TAMPOPECHAT',
                'PROPERTY_SHELKOGRAFIYA',
                'PROPERTY_FLEKSOGRAFIYA',
                'PROPERTY_LAZERNAYA_GRAVIROVKA',
                'PROPERTY_UF_PECHAT',
                'PROPERTY_POLIMERNAYA_NAKLEYKA',
                'PROPERTY_VYSHIVKA',
                'PROPERTY_SHEVRON',
                'PROPERTY_PRYAMAYA_TSIFROVAYA_PECHAT',
                'PROPERTY_SUBLIMATSIONNAYA_PECHAT',
                'PROPERTY_DEKOLIROVANIE',
                'PROPERTY_SHILDY_I_NAKLEYKI',
                'PROPERTY_TISNENIE',
                'PROPERTY_TERMOTRANSFER',
                'PROPERTY_ZALIVKA_POLIMERNOY_SMOLOY',
                'PROPERTY_POLIGRAFICHESKAYA_VSTAVKA',
                'PROPERTY_TSIFROVAYA_PECHAT',
            ]
        );
        $propertyToTitleMap = PostImportConfig::getApplicationTypePropertyMap();
        $arUpdateProp = [];
        while ($arFields = $dbFields->Fetch()) {
            foreach ($propertyToTitleMap as $sourceProperty => $targetTitle) {
                if (!empty($arFields[$sourceProperty])) {
                    $arUpdateProp[] = $targetTitle;
                }
            }
            \CIBlockElement::SetPropertyValuesEx(
                $arFields['ID'],
                PostImportConfig::IBLOCK_ID_1C,
                [PostImportConfig::TARGET_APPLICATION_TYPES_PROPERTY => $arUpdateProp]
            );
            $arUpdateProp = [];
        }
        unset($arUpdateProp);
    }

    /**
     * Вспомогательный метод для закомментированного сценария updateSections (ранее actionSection в init.php).
     *
     * @param mixed $section ID родительской секции для CIBlockSection::Add
     * @return int|string|null
     */
    public static function actionSection($name = false, $section = false)
    {
        $rsSections = \CIBlockSection::GetList(
            [],
            [
                'IBLOCK_ID' => PostImportConfig::IBLOCK_ID_1C,
                'NAME' => trim($name),
            ]
        );

        $sectionId = null;
        if ($arSection = $rsSections->Fetch()) {
            $sectionId = $arSection['ID'];
        }

        if (empty($sectionId)) {
            $bs = new \CIBlockSection();
            $arFields = [
                'ACTIVE' => 'Y',
                'IBLOCK_SECTION_ID' => $section,
                'IBLOCK_ID' => PostImportConfig::IBLOCK_ID_1C,
                'NAME' => $name,
                'CODE' => \Cutil::translit($name, 'ru', ['replace_space' => '_', 'replace_other' => '_']),
            ];
            $sectionId = $bs->Add($arFields);
        }

        return $sectionId;
    }
}
