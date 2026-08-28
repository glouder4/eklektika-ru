<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $item */
?>
<ul class="color-menu">
    <div class="swiper-container gallery-thumbs">
        <div class="swiper-wrapper">
            <?php
            require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/catalog_list_item_properties.php';
            $galleryOfferIndexes = catalogListBuildUniqueColorOfferIndexes($item);
            foreach ($galleryOfferIndexes as $key) {
                $offer = $item['OFFERS'][$key] ?? null;
                if (!is_array($offer)) {
                    continue;
                }
                ?>
                <div class="swiper-slide" id="big-card-switcher">
                    <a data-id="<?= (int)$key; ?>" data-tovar="<?= (int)$offer['ID']; ?>" data-tid="<?= (int)$offer['ID']; ?>"
                       href="<?= htmlspecialchars($offer['PREVIEW_PICTURE']['SRC'] ?? '#'); ?>">
                        <img src="<?= htmlspecialchars($offer['PREVIEW_PICTURE']['SRC'] ?? ''); ?>" alt="">
                    </a>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
</ul>
