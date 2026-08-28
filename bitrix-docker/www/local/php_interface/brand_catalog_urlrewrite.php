<?php

/**
 * ЧПУ посадочных брендов: /{CODE}/ → /brendy/detail.php
 * Правила собираются из карты + ИБ 20. Карта достаточна даже без Loader/iblock,
 * иначе корневые URL вроде /victorinox/ проваливаются в 404.
 */
try {
    require_once __DIR__ . '/brand_catalog.php';
    if (\function_exists('brandCatalogBuildUrlRewriteRules')) {
        $rules = brandCatalogBuildUrlRewriteRules();
        if (\is_array($rules) && $rules !== []) {
            return $rules;
        }
    }
} catch (\Throwable $e) {
}

$mapPath = __DIR__ . '/brand_catalog_map.php';
$map = \is_file($mapPath) ? require $mapPath : [];
if (!\is_array($map) || $map === []) {
    return [];
}

$quoted = [];
foreach (\array_keys($map) as $slug) {
    $slug = \trim((string) $slug);
    if ($slug === '' || \preg_match('/^[a-zA-Z0-9_-]+$/', $slug) !== 1) {
        continue;
    }
    $quoted[] = \preg_quote($slug, '#');
}

if ($quoted === []) {
    return [];
}

return [
    [
        'CONDITION' => '#^/(' . \implode('|', $quoted) . ')(/.*)?(?:\\?.*)?$#',
        'RULE' => 'ELEMENT_CODE=$1',
        'ID' => null,
        'PATH' => '/brendy/detail.php',
        'SORT' => 90,
    ],
];
