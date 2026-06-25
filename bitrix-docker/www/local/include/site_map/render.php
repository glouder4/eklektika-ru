<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/classes/site_map/CatalogSectionTreeBuilder.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/classes/site_map/SiteMapMenuProvider.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/classes/site_map/SiteMapRenderer.php';

global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/local/include/site_map/site-map.css');

$catalogTree = CatalogSectionTreeBuilder::getTree();
$menuSections = SiteMapMenuProvider::getSections();
?>
<div class="site-map" itemprop="articleBody">
    <section class="site-map__section">
        <h2 class="site-map__section-title">Каталог товаров</h2>
        <?php if ($catalogTree !== []): ?>
            <?php SiteMapRenderer::renderTree($catalogTree); ?>
        <?php else: ?>
            <p><a href="/catalog/">Перейти в каталог</a></p>
        <?php endif; ?>
    </section>

    <?php foreach ($menuSections as $section): ?>
        <section class="site-map__section">
            <h2 class="site-map__section-title"><?= htmlspecialcharsbx($section['TITLE']) ?></h2>
            <?php SiteMapRenderer::renderFlatLinks($section['LINKS']); ?>
        </section>
    <?php endforeach; ?>
</div>
