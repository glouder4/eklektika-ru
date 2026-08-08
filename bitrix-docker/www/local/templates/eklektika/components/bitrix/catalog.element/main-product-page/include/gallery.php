<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$morePhotos = [];
$rawMorePhoto = $currentOffer['PROPERTIES']['MORE_PHOTO'] ?? null;
if (is_array($rawMorePhoto)) {
    if (isset($rawMorePhoto['VALUE']) && !isset($rawMorePhoto[0])) {
        $fileId = (int)$rawMorePhoto['VALUE'];
        if ($fileId > 0) {
            $morePhotos[] = $fileId;
        }
    } else {
        foreach ($rawMorePhoto as $galleryItem) {
            if (is_array($galleryItem)) {
                $fileId = (int)($galleryItem['VALUE'] ?? 0);
            } else {
                $fileId = (int)$galleryItem;
            }
            if ($fileId > 0) {
                $morePhotos[] = $fileId;
            }
        }
    }
} elseif ($rawMorePhoto !== null && $rawMorePhoto !== '') {
    $fileId = (int)$rawMorePhoto;
    if ($fileId > 0) {
        $morePhotos[] = $fileId;
    }
}

// MORE_PHOTO — полноразмерные; DETAIL_PICTURE часто дубль/превью того же кадра.
$galleryUrls = [];
if ($morePhotos !== []) {
    foreach ($morePhotos as $galleryFileId) {
        $path = (string)\CFile::GetPath($galleryFileId);
        if ($path !== '') {
            $galleryUrls[] = $path;
        }
    }
} else {
    $detailPicture = trim((string)($currentOffer['DETAIL_PICTURE'] ?? ''));
    if ($detailPicture !== '') {
        $galleryUrls[] = $detailPicture;
    }
}

$galleryUrls = array_values(array_unique($galleryUrls));
$showGalleryThumbs = count($galleryUrls) > 1;
$offerName = (string)($currentOffer['NAME'] ?? '');
?>
<div class="product-gallery">
    <div class="swiper-container gallery-top">
        <div class="swiper-wrapper">
            <?php foreach ($galleryUrls as $index => $galleryUrl) { ?>
                <a href="<?= htmlspecialcharsbx($galleryUrl); ?>"
                   class="swiper-slide fancybox-gallery<?= $index === 0 ? ' swiper-slide-active' : ''; ?>"
                   data-fancybox="gallery" title="<?= htmlspecialcharsbx($offerName); ?> фото"
                   style="width:428px;margin-right:10px">
                    <img src="<?= htmlspecialcharsbx($galleryUrl); ?>" alt="фото <?= htmlspecialcharsbx($offerName); ?>">
                </a>
            <?php } ?>
        </div>

        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
    </div>
    <?php if ($showGalleryThumbs) { ?>
    <div class="swiper-container gallery-thumbs">
        <div class="swiper-wrapper">
            <?php foreach ($galleryUrls as $galleryUrl) { ?>
                <div class="swiper-slide"><img src="<?= htmlspecialcharsbx($galleryUrl); ?>" alt=""></div>
            <?php } ?>
        </div>
    </div>
    <?php } ?>
</div>
