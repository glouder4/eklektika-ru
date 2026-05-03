<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
?>
<div class="product-gallery">
    <div class="swiper-container gallery-top">
        <div class="swiper-wrapper">

            <a href="<?= $currentOffer['DETAIL_PICTURE']; ?>" class="swiper-slide fancybox-gallery swiper-slide-active"
               data-fancybox="gallery" title="<?= $currentOffer['NAME']; ?> фото"
               style="width:428px;margin-right:10px">
                <img src="<?= $currentOffer['DETAIL_PICTURE']; ?>" alt="фото <?= $currentOffer['NAME']; ?>">
            </a>

            <?php foreach ($currentOffer['PROPERTIES']['PHOTOS'] as $key => $galleryItem) { ?>
                <a href="<?= \CFile::GetPath($galleryItem['VALUE']); ?>" class="swiper-slide fancybox-gallery"
                   data-fancybox="gallery" title="<?= $currentOffer['NAME']; ?> фото"
                   style="width:428px;margin-right:10px">
                    <img src="<?= \CFile::GetPath($galleryItem['VALUE']); ?>" alt="фото <?= $currentOffer['NAME']; ?>">
                </a>
            <?php } ?>
        </div>

        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
    </div>
    <div class="swiper-container gallery-thumbs">
        <div class="swiper-wrapper">
            <div class="swiper-slide"><img src="<?= $currentOffer['DETAIL_PICTURE']; ?>" alt=""></div>

            <?php foreach ($currentOffer['PROPERTIES']['PHOTOS'] as $key => $galleryItem) { ?>
                <div class="swiper-slide"><img src="<?= \CFile::GetPath($galleryItem['VALUE']); ?>" alt=""></div>
            <?php } ?>
        </div>
    </div>
</div>
