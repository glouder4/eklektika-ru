<?php

/**
 * OG-шаблоны dwstroy.opengraph для каталога (IBLOCK 13, вкладка catalog).
 * Маски совместимы с Bitrix SEO ({=this.Name}, {=PICTURE}, {=DETAIL_PICTURE}).
 */
final class CatalogOgTemplateDefaults
{
    public const CATALOG_IBLOCK_ID = 13;
    public const OG_TAB_CODE = 'catalog';
    public const OG_LOCALE = 'ru';

    public const OG_SITE_NAME = 'Эклектика:корпоративные подарки и сувенирная продукция с логотипом';

    public const SECTION_OG_TYPE = 'website';
    public const ELEMENT_OG_TYPE = 'product';

    public const SECTION_OG_TITLE_FALLBACK =
        '{=this.Name} купить оптом в Москве | Эклектика – нанесение логотипов на заказ';

    public const SECTION_OG_DESCRIPTION_FALLBACK =
        'Компания Эклектика предлагает {=this.Name} оптом под нанесение логотипа. ✓ Низкие цены. ✓ Доставка по России. ☎ 8(800) 777-4723';

    public const ELEMENT_OG_TITLE =
        '{=this.Name} купить оптом в Москве | Эклектика – нанесение логотипов на заказ';

    public const ELEMENT_OG_DESCRIPTION =
        'Компания Эклектика предлагает {=this.Name} оптом под нанесение логотипа. ✓ Низкие цены. ✓ Доставка по России. ☎ 8(800) 777-4723';

    public const SECTION_OG_IMAGE = '{=PICTURE}';
    public const ELEMENT_OG_IMAGE = '{=DETAIL_PICTURE}';

    /**
     * @return array<string, string>
     */
    public static function sectionComputedTemplates(): array
    {
        return [
            'SECTION_OG_IMAGE_SECURE_URL' => self::SECTION_OG_IMAGE,
            'SECTION_OG_IMAGE_TYPE' => '{=this.picture.type}',
            'SECTION_OG_IMAGE_WIDTH' => '{=this.picture.width}',
            'SECTION_OG_IMAGE_HEIGHT' => '{=this.picture.height}',
            'SECTION_OG_IMAGE_ALT' => '{=this.Name}',
            'SECTION_OG_LOCALE' => self::OG_LOCALE,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function elementIblockTemplates(): array
    {
        return [
            'ELEMENT_OG_TYPE' => self::ELEMENT_OG_TYPE,
            'ELEMENT_OG_TITLE' => self::ELEMENT_OG_TITLE,
            'ELEMENT_OG_DESCRIPTION' => self::ELEMENT_OG_DESCRIPTION,
            'ELEMENT_OG_IMAGE' => self::ELEMENT_OG_IMAGE,
            'ELEMENT_OG_IMAGE_SECURE_URL' => self::ELEMENT_OG_IMAGE,
            'ELEMENT_OG_IMAGE_TYPE' => '{=this.detail_picture.type}',
            'ELEMENT_OG_IMAGE_WIDTH' => '{=this.detail_picture.width}',
            'ELEMENT_OG_IMAGE_HEIGHT' => '{=this.detail_picture.height}',
            'ELEMENT_OG_IMAGE_ALT' => '{=this.Name}',
            'ELEMENT_OG_LOCALE' => self::OG_LOCALE,
            'ELEMENT_OG_SITE_NAME' => self::OG_SITE_NAME,
        ];
    }
}
