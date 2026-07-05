<?php

require_once __DIR__ . '/brand_catalog.php';

$slugs = brandCatalogGetEligibleBrandSlugs();
if ($slugs === []) {
    return [];
}

$pattern = implode('|', array_map(static function ($slug) {
    return preg_quote((string)$slug, '#');
}, $slugs));

if ($pattern === '') {
    return [];
}

return [
    [
        'CONDITION' => '#^/(' . $pattern . ')(/.*)?$#',
        'RULE' => 'ELEMENT_CODE=$1',
        'ID' => null,
        'PATH' => '/brendy/detail.php',
        'SORT' => 90,
    ],
];
