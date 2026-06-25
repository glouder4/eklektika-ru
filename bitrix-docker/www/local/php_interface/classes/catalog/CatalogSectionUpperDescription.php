<?php

use Bitrix\Main\Loader;

/** 
 * UF-поле раздела каталога «Описание над каталогом».
 *
 * Создать в админке: Настройки → Настройки продукта → Пользовательские поля →
 * Объект IBLOCK_{ID}_SECTION, код UF_CATALOG_UPPER_DESC.
 */
final class CatalogSectionUpperDescription
{
    public const UF_CODE = 'UF_CATALOG_UPPER_DESC';

    public static function resolveSectionId(int $iblockId, int $sectionId, string $sectionCode = ''): int
    {
        if ($sectionId > 0) {
            return $sectionId;
        }

        $sectionCode = trim($sectionCode);
        if ($sectionCode === '' || $iblockId <= 0 || !Loader::includeModule('iblock')) {
            return 0;
        }

        $row = CIBlockSection::GetList(
            [],
            ['IBLOCK_ID' => $iblockId, 'CODE' => $sectionCode, 'ACTIVE' => 'Y'],
            false,
            ['ID'],
            ['nTopCount' => 1]
        )->Fetch();

        return $row ? (int)($row['ID'] ?? 0) : 0;
    }

    public static function getHtml(int $iblockId, int $sectionId, string $sectionCode = ''): string
    {
        $sectionId = self::resolveSectionId($iblockId, $sectionId, $sectionCode);
        if ($sectionId <= 0 || $iblockId <= 0 || !Loader::includeModule('iblock')) {
            return '';
        }

        $row = CIBlockSection::GetList(
            [],
            ['IBLOCK_ID' => $iblockId, 'ID' => $sectionId, 'ACTIVE' => 'Y'],
            false,
            ['ID', self::UF_CODE]
        )->Fetch();

        if (!$row) {
            return '';
        }

        return trim((string)($row[self::UF_CODE] ?? ''));
    }

    public static function render(int $iblockId, int $sectionId, string $sectionCode = ''): void
    {
        $html = self::getHtml($iblockId, $sectionId, $sectionCode);
        if ($html === '') {
            return;
        }
        ?>
        <div class="content catalog-section-seo catalog-section-seo--top">
            <?= $html ?>
        </div>
        <?php
    }
}
