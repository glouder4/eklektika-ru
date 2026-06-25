<?php
/**
 * Динамическое меню каталога для header.
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/classes/header_catalog/HeaderCatalogMenuProvider.php';

$sections = HeaderCatalogMenuProvider::getMenuSections();
?>
<ul class="catalog">
    <li class="back-link-catalog">&#8592; Назад</li>
    <?php foreach ($sections as $arSection): ?>
    <li><a href="<?= htmlspecialcharsbx($arSection['SECTION_PAGE_URL']) ?>"><?= htmlspecialcharsbx($arSection['NAME']) ?></a>
        <?php if (!empty($arSection['SECTIONS'])): ?>
        <ul class="subcatalog">
            <li class="back-link">&#8592; Назад</li>
            <?php foreach ($arSection['SECTIONS'] as $arSubSection): ?>
            <li><a href="<?= htmlspecialcharsbx($arSubSection['SECTION_PAGE_URL']) ?>"><?= htmlspecialcharsbx($arSubSection['NAME']) ?></a></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </li>
    <?php endforeach; ?>
</ul>
