<?php

$mapPath = __DIR__ . '/brand_catalog_map.php';
if (!is_file($mapPath)) {
    return [];
}

$map = require $mapPath;
if (!is_array($map) || $map === []) {
    return [];
}

$slugs = array_keys($map);
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
