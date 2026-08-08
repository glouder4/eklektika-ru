<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

if (empty($currentOffer['SIZE_MENU']) || !is_array($currentOffer['SIZE_MENU'])) {
    return;
}

$sizeMenuItems = [];
foreach ($currentOffer['SIZE_MENU'] as $size_offer) {
    $itemSize = trim((string)($size_offer['RAZMER_ODEZHDY'] ?? ''));
    if ($itemSize === '') {
        continue;
    }
    $sizeMenuItems[] = $size_offer;
}

if ($sizeMenuItems === []) {
    return;
}

$currentOfferId = (int)($currentOffer['ID'] ?? 0);
$currentSize = trim((string)($currentOffer['RAZMER_ODEZHDY'] ?? ''));
?>
    <ul class="size-menu">
        <?php
        foreach ($sizeMenuItems as $size_offer) {
            $itemId = (int)($size_offer['ID'] ?? 0);
            $itemSize = trim((string)($size_offer['RAZMER_ODEZHDY'] ?? ''));
            $isActive = ($itemId > 0 && $itemId === $currentOfferId)
                || ($currentSize !== '' && $itemSize === $currentSize);
            ?>
            <li class="<?= $isActive ? 'active' : null; ?>">
                <a href="<?= htmlspecialcharsbx((string)($size_offer['DETAIL_URL'] ?? '')); ?>"><?= htmlspecialcharsbx($itemSize); ?></a>
            </li>
        <?php } ?>
    </ul>
<?php
