<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$colorMenuItems = [];
if (!empty($currentOffer['COLOR_MENU']) && is_array($currentOffer['COLOR_MENU'])) {
    $colorMenuItems = $currentOffer['COLOR_MENU'];
} elseif (!empty($currentOffer['RELATED_OFFERS']) && is_array($currentOffer['RELATED_OFFERS'])) {
    $colorMenuItems = $currentOffer['RELATED_OFFERS'];
}

if ($colorMenuItems === []) {
    return;
}

$currentOfferId = (int)($currentOffer['ID'] ?? 0);
$currentTsvet = trim((string)($currentOffer['TSVET'] ?? ''));
?>
    <ul class="color-menu">
        <?php
        foreach ($colorMenuItems as $color_offer) {
            $itemId = (int)($color_offer['ID'] ?? 0);
            $itemTsvet = trim((string)($color_offer['TSVET'] ?? ''));
            $isActive = ($itemId > 0 && $itemId === $currentOfferId)
                || ($currentTsvet !== '' && $itemTsvet !== '' && $itemTsvet === $currentTsvet);
            ?>
            <li class="<?= $isActive ? 'active' : null; ?>">
                <a href="<?= htmlspecialcharsbx((string)($color_offer['DETAIL_URL'] ?? '')); ?>">
                    <img src="<?= htmlspecialcharsbx((string)($color_offer['PREVIEW_PICTURE'] ?? '')); ?>" title="" alt="">
                </a>
            </li>
        <?php } ?>
    </ul>
<?php
