<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * @global CMain $APPLICATION
 * @var CBitrixComponent $component
 * @var array $arParams
 * @var array $arResult
 */

$basketAction = $arParams['SECTION_ADD_TO_BASKET_ACTION'] ?? '';

include __DIR__ . '/include/section-upper-description.php';
include __DIR__ . '/include/cats-menu-filter.php';
include __DIR__ . '/include/bootstrap-sort-filter.php';
include __DIR__ . '/include/smart-filter.php';
include __DIR__ . '/include/sorting-bar.php';
include __DIR__ . '/include/catalog-section.php';
include __DIR__ . '/include/sorting-url-script.php';
