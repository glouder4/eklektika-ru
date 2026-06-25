<?php

class CatalogSectionTreeBuilder
{
    private const IBLOCK_ID = 13;
    private const CACHE_TTL = 86400;
    private const CACHE_PATH = '/site_map/catalog_tree/';

    /**
     * @return array<int, array{ID:int,NAME:string,URL:string,CHILDREN:array}>
     */
    public static function getTree(): array
    {
        $cacheId = 'catalog_sections_tree_v1_' . self::IBLOCK_ID;
        $cache = \Bitrix\Main\Data\Cache::createInstance();

        if ($cache->initCache(self::CACHE_TTL, $cacheId, self::CACHE_PATH)) {
            $tree = $cache->getVars();
            return is_array($tree) ? $tree : [];
        }

        if (!$cache->startDataCache() || !\Bitrix\Main\Loader::includeModule('iblock')) {
            $cache->abortDataCache();
            return [];
        }

        global $CACHE_MANAGER;
        if (is_object($CACHE_MANAGER)) {
            $CACHE_MANAGER->StartTagCache(self::CACHE_PATH);
            $CACHE_MANAGER->RegisterTag('iblock_id_' . self::IBLOCK_ID);
        }

        $flat = [];
        $rs = \CIBlockSection::GetList(
            ['LEFT_MARGIN' => 'ASC'],
            [
                'IBLOCK_ID' => self::IBLOCK_ID,
                'ACTIVE' => 'Y',
                'GLOBAL_ACTIVE' => 'Y',
            ],
            false,
            ['ID', 'NAME', 'CODE', 'IBLOCK_SECTION_ID', 'DEPTH_LEVEL'],
            false
        );

        while ($ar = $rs->Fetch()) {
            $id = (int)$ar['ID'];
            $flat[$id] = [
                'ID' => $id,
                'NAME' => (string)$ar['NAME'],
                'CODE' => (string)($ar['CODE'] ?: $id),
                'IBLOCK_SECTION_ID' => (int)$ar['IBLOCK_SECTION_ID'],
                'URL' => '',
                'CHILDREN' => [],
            ];
        }

        foreach ($flat as $id => $item) {
            $pathParts = [$item['CODE']];
            $pid = $item['IBLOCK_SECTION_ID'];
            while ($pid > 0 && isset($flat[$pid])) {
                array_unshift($pathParts, $flat[$pid]['CODE']);
                $pid = $flat[$pid]['IBLOCK_SECTION_ID'];
            }
            $flat[$id]['URL'] = '/catalog/' . implode('/', $pathParts) . '/';
        }

        $tree = [];
        foreach ($flat as $id => $item) {
            $parentId = $item['IBLOCK_SECTION_ID'];
            if ($parentId > 0 && isset($flat[$parentId])) {
                $flat[$parentId]['CHILDREN'][] = &$flat[$id];
            } else {
                $tree[] = &$flat[$id];
            }
        }
        unset($flat);

        $tree = self::normalizeTree($tree);

        if (is_object($CACHE_MANAGER)) {
            $CACHE_MANAGER->EndTagCache();
        }
        $cache->endDataCache($tree);

        return $tree;
    }

    /**
     * @param array<int, array<string, mixed>> $nodes
     * @return array<int, array{ID:int,NAME:string,URL:string,CHILDREN:array}>
     */
    private static function normalizeTree(array $nodes): array
    {
        $result = [];
        foreach ($nodes as $node) {
            $children = [];
            if (!empty($node['CHILDREN']) && is_array($node['CHILDREN'])) {
                $children = self::normalizeTree($node['CHILDREN']);
            }
            $result[] = [
                'ID' => (int)$node['ID'],
                'NAME' => (string)$node['NAME'],
                'URL' => (string)$node['URL'],
                'CHILDREN' => $children,
            ];
        }

        return $result;
    }
}
