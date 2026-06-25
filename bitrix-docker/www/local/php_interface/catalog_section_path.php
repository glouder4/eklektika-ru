<?php

/**
 * Резолв раздела каталога по цепочке символьных кодов: parent/child/.
 */
function catalogResolveSectionIdByCodePath(int $iblockId, string $codePath): int
{
    if ($iblockId <= 0) {
        return 0;
    }

    $codePath = trim($codePath, '/');
    if ($codePath === '') {
        return 0;
    }

    if (!\Bitrix\Main\Loader::includeModule('iblock')) {
        return 0;
    }

    if (\is_callable(['CIBlockFindTools', 'GetSectionIDByCodePath'])) {
        return (int)\CIBlockFindTools::GetSectionIDByCodePath($iblockId, $codePath);
    }

    $segments = array_values(array_filter(explode('/', $codePath), static function (string $part): bool {
        return $part !== '';
    }));
    if ($segments === []) {
        return 0;
    }

    $parentSectionId = 0;
    $sectionId = 0;

    foreach ($segments as $code) {
        $filter = [
            'IBLOCK_ID' => $iblockId,
            'CODE' => $code,
            'ACTIVE' => 'Y',
            'GLOBAL_ACTIVE' => 'Y',
        ];
        if ($parentSectionId > 0) {
            $filter['SECTION_ID'] = $parentSectionId;
        } else {
            $filter['SECTION_ID'] = false;
        }

        $rs = \CIBlockSection::GetList(
            ['ID' => 'ASC'],
            $filter,
            false,
            ['ID'],
            ['nTopCount' => 1]
        );
        $row = $rs->Fetch();
        if (!\is_array($row)) {
            return 0;
        }

        $sectionId = (int)($row['ID'] ?? 0);
        if ($sectionId <= 0) {
            return 0;
        }
        $parentSectionId = $sectionId;
    }

    return $sectionId;
}

function catalogResolveParentSectionIdByCodePath(int $iblockId, string $codePath): int
{
    $codePath = trim($codePath, '/');
    if ($codePath === '' || !str_contains($codePath, '/')) {
        return 0;
    }

    $parentPath = substr($codePath, 0, (int)strrpos($codePath, '/'));

    return catalogResolveSectionIdByCodePath($iblockId, $parentPath);
}

function catalogElementExistsByCode(int $iblockId, string $elementCode, int $sectionId = 0): bool
{
    if ($iblockId <= 0 || $elementCode === '') {
        return false;
    }

    $filter = [
        'IBLOCK_ID' => $iblockId,
        'CODE' => $elementCode,
        'ACTIVE' => 'Y',
        'ACTIVE_DATE' => 'Y',
    ];
    if ($sectionId > 0) {
        $filter['SECTION_ID'] = $sectionId;
        $filter['INCLUDE_SUBSECTIONS'] = 'N';
    }

    $rs = \CIBlockElement::GetList([], $filter, false, ['nTopCount' => 1], ['ID']);

    return (bool)$rs->Fetch();
}

/**
 * SEF матчит /catalog/parent/child/ как товар child в parent, хотя child — подраздел.
 *
 * @param array<string, mixed> $arVariables
 */
function catalogReconcileComponentPath(string &$componentPage, array &$arVariables, int $iblockId, string $relativePath): void
{
    $relativePath = trim($relativePath, '/');
    if ($relativePath === '' || $iblockId <= 0) {
        return;
    }

    $segments = array_values(array_filter(explode('/', $relativePath)));
    if ($segments === []) {
        return;
    }

    if (\count($segments) >= 2) {
        $sectionIdByPath = catalogResolveSectionIdByCodePath($iblockId, $relativePath);
        if ($sectionIdByPath > 0) {
            catalogApplySectionVariables($arVariables, $sectionIdByPath, $relativePath, $segments);
            $componentPage = 'section';

            return;
        }
    }

    if ($componentPage === 'element') {
        $elementCode = trim((string)($arVariables['ELEMENT_CODE'] ?? ''));
        if ($elementCode === '') {
            return;
        }

        $parentSectionId = (int)($arVariables['SECTION_ID'] ?? 0);
        if ($parentSectionId <= 0 && \count($segments) >= 2) {
            $parentPath = implode('/', \array_slice($segments, 0, -1));
            $parentSectionId = catalogResolveSectionIdByCodePath($iblockId, $parentPath);
        }

        if (catalogElementExistsByCode($iblockId, $elementCode, $parentSectionId)) {
            return;
        }

        $sectionIdByPath = catalogResolveSectionIdByCodePath($iblockId, $relativePath);
        if ($sectionIdByPath > 0) {
            catalogApplySectionVariables($arVariables, $sectionIdByPath, $relativePath, $segments);
            $componentPage = 'section';
        }

        return;
    }

    if ($componentPage === 'section' && (int)($arVariables['SECTION_ID'] ?? 0) <= 0) {
        $codePath = trim((string)($arVariables['SECTION_CODE_PATH'] ?? $relativePath), '/');
        if ($codePath === '' && !empty($arVariables['SECTION_CODE'])) {
            $codePath = trim((string)$arVariables['SECTION_CODE'], '/');
        }

        $sectionIdByPath = catalogResolveSectionIdByCodePath($iblockId, $codePath);
        if ($sectionIdByPath > 0) {
            $pathSegments = array_values(array_filter(explode('/', $codePath)));
            catalogApplySectionVariables($arVariables, $sectionIdByPath, $codePath, $pathSegments);
        }
    }
}

/**
 * @param array<string, mixed> $arVariables
 * @param list<string> $segments
 */
function catalogApplySectionVariables(array &$arVariables, int $sectionId, string $codePath, array $segments): void
{
    $arVariables['SECTION_ID'] = $sectionId;
    $arVariables['SECTION_CODE'] = (string)($segments !== [] ? end($segments) : '');
    $arVariables['SECTION_CODE_PATH'] = trim($codePath, '/');
    unset($arVariables['ELEMENT_CODE'], $arVariables['ELEMENT_ID']);
}

function catalogGetRelativePathFromSefFolder(string $currentPath, string $sefFolder): string
{
    $currentPath = rtrim($currentPath, '/');
    $sefFolder = rtrim($sefFolder, '/');

    if ($sefFolder !== '' && str_starts_with($currentPath, $sefFolder)) {
        return trim(substr($currentPath, strlen($sefFolder)), '/');
    }

    return '';
}
