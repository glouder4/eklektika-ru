<?php
/**
 * Оптимизированный вывод разделов каталога для главной страницы.
 * Один запрос вместо 16+ у catalog.section.list.
 */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$iblockId = 13;
$cacheId = 'mainpage_catalog_sections_' . $iblockId;
$cachePath = '/mainpage/catalog_sections/';
$cacheTtl = 86400; // 24 часа

$obCache = \Bitrix\Main\Data\Cache::createInstance();
$sections = [];

if ($obCache->initCache($cacheTtl, $cacheId, $cachePath)) {
    $sections = $obCache->getVars();
} elseif ($obCache->startDataCache() && \Bitrix\Main\Loader::includeModule('iblock')) {
    global $CACHE_MANAGER;
    if (is_object($CACHE_MANAGER)) {
        $CACHE_MANAGER->StartTagCache($cachePath);
        $CACHE_MANAGER->RegisterTag('iblock_id_' . $iblockId);
    }
    $rs = CIBlockSection::GetList(
        ['LEFT_MARGIN' => 'ASC'],
        ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y'],
        false,
        ['ID', 'NAME', 'CODE', 'IBLOCK_SECTION_ID', 'DEPTH_LEVEL', 'LEFT_MARGIN', 'RIGHT_MARGIN'],
        ['nTopCount' => 200]
    );

    $flat = [];
    while ($ar = $rs->Fetch()) {
        $flat[$ar['ID']] = [
            'ID' => $ar['ID'],
            'NAME' => $ar['NAME'],
            'CODE' => $ar['CODE'] ?: $ar['ID'],
            'IBLOCK_SECTION_ID' => (int)$ar['IBLOCK_SECTION_ID'],
            'DEPTH_LEVEL' => (int)$ar['DEPTH_LEVEL'],
            'SECTION_PAGE_URL' => '',
            'SECTIONS' => [],
        ];
    }

    // Строим SECTION_CODE_PATH для каждого раздела (путь из кодов)
    foreach ($flat as $id => &$item) {
        $pathParts = [$item['CODE']];
        $pid = $item['IBLOCK_SECTION_ID'];
        while ($pid > 0 && isset($flat[$pid])) {
            array_unshift($pathParts, $flat[$pid]['CODE']);
            $pid = $flat[$pid]['IBLOCK_SECTION_ID'];
        }
        $item['SECTION_PAGE_URL'] = '/catalog/' . implode('/', $pathParts) . '/';
    }
    unset($item);

    // Иерархия: родители с детьми (только 2 уровня — родитель и подразделы)
    $sections = [];
    foreach ($flat as $id => $item) {
        if ($item['DEPTH_LEVEL'] == 1) {
            $sections[$id] = $item;
        } elseif ($item['IBLOCK_SECTION_ID'] > 0) {
            $pid = $item['IBLOCK_SECTION_ID'];
            while ($pid > 0 && isset($flat[$pid])) {
                if ($flat[$pid]['DEPTH_LEVEL'] == 1) {
                    $sections[$pid]['SECTIONS'][] = $item;
                    break;
                }
                $pid = $flat[$pid]['IBLOCK_SECTION_ID'];
            }
        }
    }
    $sections = array_values($sections);

    if (is_object($CACHE_MANAGER)) {
        $CACHE_MANAGER->EndTagCache();
    }
    $obCache->endDataCache($sections);
} else {
    $obCache->abortDataCache();
}
?>
<!-- BEGIN catalog cats -->
<div class="catalog-cats">
    <div class="flex-wrap d-flex">
        <?php foreach ($sections as $arSection): ?>
        <div class="catalog-cat-item-wrap">
            <div class="catalog-cat">
                <a href="<?= htmlspecialchars($arSection['SECTION_PAGE_URL']) ?>" class="catalog-cat-icon"></a>
                <a href="<?= htmlspecialchars($arSection['SECTION_PAGE_URL']) ?>" class="catalog-cat-title"><?= htmlspecialchars($arSection['NAME']) ?></a>
                <?php if (!empty($arSection['SECTIONS'])): ?>
                <ul>
                    <?php foreach ($arSection['SECTIONS'] as $arSubSection): ?>
                    <li><a href="<?= htmlspecialchars($arSubSection['SECTION_PAGE_URL']) ?>"><?= htmlspecialchars($arSubSection['NAME']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<!-- END catalog cats -->
