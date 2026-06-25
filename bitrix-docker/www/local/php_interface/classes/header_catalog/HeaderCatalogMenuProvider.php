<?php

require_once __DIR__ . '/../site_map/CatalogSectionTreeBuilder.php';

/**
 * Меню каталога в шапке: все активные разделы 1-го уровня + вложенные подпункты.
 */
final class HeaderCatalogMenuProvider
{
    /**
     * @return list<array{
     *   ID: int,
     *   NAME: string,
     *   SECTION_PAGE_URL: string,
     *   SECTIONS: list<array{ID:int,NAME:string,SECTION_PAGE_URL:string}>
     * }>
     */
    public static function getMenuSections(): array
    {
        if (!\Bitrix\Main\Loader::includeModule('iblock')) {
            return [];
        }

        return self::buildFromTree(CatalogSectionTreeBuilder::getTree());
    }

    /**
     * @param list<array<string, mixed>> $tree
     * @return list<array{ID:int,NAME:string,SECTION_PAGE_URL:string,SECTIONS:list}>
     */
    private static function buildFromTree(array $tree): array
    {
        $sections = [];

        foreach ($tree as $root) {
            if (!\is_array($root)) {
                continue;
            }

            $sections[] = [
                'ID' => (int)($root['ID'] ?? 0),
                'NAME' => (string)($root['NAME'] ?? ''),
                'SECTION_PAGE_URL' => (string)($root['URL'] ?? ''),
                'SECTIONS' => self::flattenSubSections(isset($root['CHILDREN']) && \is_array($root['CHILDREN']) ? $root['CHILDREN'] : []),
            ];
        }

        return $sections;
    }

    /**
     * Плоский список всех потомков корневого раздела (как в legacy header_catalog_menu).
     *
     * @param list<array<string, mixed>> $children
     * @return list<array{ID:int,NAME:string,SECTION_PAGE_URL:string}>
     */
    private static function flattenSubSections(array $children): array
    {
        $flat = [];

        foreach ($children as $child) {
            if (!\is_array($child)) {
                continue;
            }

            $flat[] = [
                'ID' => (int)($child['ID'] ?? 0),
                'NAME' => (string)($child['NAME'] ?? ''),
                'SECTION_PAGE_URL' => (string)($child['URL'] ?? ''),
            ];

            if (!empty($child['CHILDREN']) && \is_array($child['CHILDREN'])) {
                $flat = \array_merge($flat, self::flattenSubSections($child['CHILDREN']));
            }
        }

        return $flat;
    }
}
