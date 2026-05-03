<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

if (isset($currentOffer['RELATED_OFFERS']) && count($currentOffer['RELATED_OFFERS']) > 0) {
    ?>
    <ul class="color-menu">
        <?php
        foreach ($currentOffer['RELATED_OFFERS'] as $color_offer) { ?>
            <li class="<?= ($color_offer['ID'] == $currentOffer['ID']) ? 'active' : null; ?>">
                <a href="<?= $color_offer['DETAIL_URL'] ?>">
                    <img src="<?= $color_offer['PREVIEW_PICTURE'] ?>" title="" alt="">
                </a>
            </li>
        <?php } ?>
    </ul>
    <?php
}
