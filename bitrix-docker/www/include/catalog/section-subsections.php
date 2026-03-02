<?php
/**
 * Оптимизированный вывод подразделов для страницы раздела каталога.
 * Один запрос вместо 16+ у catalog.section.list.
 * Переменные: $iblockId, $sectionId, $sectionCode, $folder, $urlTemplate
 */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$iblockId = (int)($iblockId ?? 13);
$sectionId = (int)($sectionId ?? 0);
$sectionCode = trim((string)($sectionCode ?? ''));
$folder = $folder ?? '/catalog/';
$urlTemplate = $urlTemplate ?? '#SECTION_CODE_PATH#/';

// Разрешаем SECTION_CODE в ID если нужно
if ($sectionId <= 0 && $sectionCode !== '' && \Bitrix\Main\Loader::includeModule('iblock')) {
    $res = CIBlockSection::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => $sectionCode], false, ['ID'], ['nTopCount' => 1]);
    if ($ar = $res->Fetch()) {
        $sectionId = (int)$ar['ID'];
    }
}
$parentId = $sectionId;

$cacheId = 'catalog_subsections_' . $iblockId . '_' . $parentId;
$cachePath = '/catalog/subsections/';
$cacheTtl = 3600; // 1 час

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

    $filter = ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y'];
    if ($parentId > 0) {
        $filter['SECTION_ID'] = $parentId;
    } else {
        $filter['SECTION_ID'] = false;
    }

    $rs = CIBlockSection::GetList(
        ['LEFT_MARGIN' => 'ASC'],
        $filter,
        false,
        ['ID', 'NAME', 'CODE', 'IBLOCK_SECTION_ID'],
        ['nTopCount' => 100]
    );

    $parentCodePath = '';
    if ($parentId > 0) {
        $chain = [];
        $navRes = CIBlockSection::GetNavChain($iblockId, $parentId, ['ID', 'CODE']);
        while ($p = $navRes->Fetch()) {
            $chain[] = $p['CODE'] ?: $p['ID'];
        }
        $parentCodePath = implode('/', $chain) . '/';
    }

    while ($ar = $rs->Fetch()) {
        $codePath = $parentCodePath . ($ar['CODE'] ?: $ar['ID']);
        $fullUrl = rtrim($folder, '/') . '/' . $codePath . '/';
        $sections[] = [
            'ID' => $ar['ID'],
            'NAME' => $ar['NAME'],
            'SECTION_PAGE_URL' => $fullUrl,
        ];
    }

    if (is_object($CACHE_MANAGER)) {
        $CACHE_MANAGER->EndTagCache();
    }
    $obCache->endDataCache($sections);
} else {
    $obCache->abortDataCache();
}
?>
<div class="row">
    <div class="col-sm-12">
        <ul class="category">
            <?php foreach ($sections as $arSection): ?>
            <li><a href="<?= htmlspecialchars($arSection['SECTION_PAGE_URL']) ?>"><?= htmlspecialchars($arSection['NAME']) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
