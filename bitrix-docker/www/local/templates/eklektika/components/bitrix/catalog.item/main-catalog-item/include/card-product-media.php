<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * @var array $item
 * @var callable $buildOfferUrl
 * @var float $firstOfferDiscount
 */
?>
<div class="label label-sale" style="display: <?= ($firstOfferDiscount > 0) ? 'block' : 'none'; ?>;">Скидка</div>
<div class="sale-size" style="display: <?= ($firstOfferDiscount > 0) ? 'block' : 'none'; ?>;">-<?= htmlspecialchars((string)$firstOfferDiscount); ?><sub>%</sub></div>
<div class="product-item_images">
    <div class="product-item_img">
        <a class="changed-url" href="<?= htmlspecialchars($buildOfferUrl($item['DETAIL_PAGE_URL'], $item['OFFERS'][0]['ID'] ?? 0)); ?>">
            <?php
            $file = null;

            if (!empty($item['OFFERS']) && !empty($item['OFFERS'][0]['PREVIEW_PICTURE'])) {
                $previewId = $item['OFFERS'][0]['PREVIEW_PICTURE']['ID'] ?? null;
                if ($previewId) {
                    $file = CFile::ResizeImageGet(
                        $previewId,
                        ['width' => 160, 'height' => 160],
                        BX_RESIZE_IMAGE_PROPORTIONAL,
                        true
                    );
                }
            }

            if (!$file || !isset($file['src'])) {
                $file = [
                    'src' => '/local/templates/eklektika/components/bitrix/catalog.section/main-catalog-section/images/no_photo.png',
                    'width' => 160,
                    'height' => 160,
                ];
            }
            ?>
            <img itemprop="image"
                 width="<?= (int)$file['width']; ?>"
                 height="<?= (int)$file['height']; ?>"
                 data-src="<?= htmlspecialchars($file['src']); ?>"
                 src="<?= htmlspecialchars($file['src']); ?>"
                 class="lazy-loaded"
                 alt="<?= htmlspecialchars(html_entity_decode((string)($item['NAME'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')); ?>">
        </a>
    </div>
    <ul class="product-item_gallery">
        <?php
        require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/catalog_list_item_properties.php';
        $galleryOfferIndexes = catalogListBuildUniqueColorOfferIndexes($item);
        foreach ($galleryOfferIndexes as $key):
            $offer = $item['OFFERS'][$key] ?? null;
            if (!is_array($offer)) {
                continue;
            }
            if (!empty($offer['PREVIEW_PICTURE']) && isset($offer['PREVIEW_PICTURE']['ID']) && $offer['PREVIEW_PICTURE']['ID'] > 0) {
                $thumbnail = CFile::ResizeImageGet($offer['PREVIEW_PICTURE']['ID'], ['width' => 50, 'height' => 50], BX_RESIZE_IMAGE_PROPORTIONAL, true);
                $detailPicture = CFile::ResizeImageGet($offer['PREVIEW_PICTURE']['ID'], ['width' => 160, 'height' => 160], BX_RESIZE_IMAGE_PROPORTIONAL, true);
            } elseif (!empty($offer['PREVIEW_PICTURE'])) {
                $thumbnail['src'] = $offer['PREVIEW_PICTURE']['SRC'];
                $detailPicture['src'] = $offer['PREVIEW_PICTURE']['SRC'];
            } else {
                $thumbnail['src'] = '/local/templates/eklektika/components/bitrix/catalog.section/main-catalog-section/images/no_photo.png';
                $detailPicture['src'] = '/local/templates/eklektika/components/bitrix/catalog.section/main-catalog-section/images/no_photo.png';
            }
            ?>
            <li>
                <a class="change-image-url" data-id="<?= (int)$key; ?>" data-tid="<?= (int)$offer['ID']; ?>"
                   data-tovar="<?= (int)$offer['ID']; ?>"
                   data-link="<?= htmlspecialchars($buildOfferUrl($item['DETAIL_PAGE_URL'], $offer['ID'] ?? 0)); ?>"
                   href="<?= htmlspecialchars($detailPicture['src']); ?>">
                    <img data-src="<?= htmlspecialchars($thumbnail['src']); ?>" itemprop="image"
                         src="<?= htmlspecialchars($thumbnail['src']); ?>" class="lazy-loaded" alt="">
                </a>
            </li>
            <?php
            unset($thumbnail, $detailPicture);
        endforeach;
        ?>
    </ul>
</div>
