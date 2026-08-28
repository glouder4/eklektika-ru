<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arResult */

$this->setFrameMode(true);

$detailText = (string)($arResult['DETAIL_TEXT'] ?? '');
$previewText = (string)($arResult['PREVIEW_TEXT'] ?? '');
?>
<div class="content custom-page">
    <?php if ($detailText !== ''): ?>
        <?= $detailText ?>
    <?php elseif ($previewText !== ''): ?>
        <?= $previewText ?>
    <?php endif; ?>
</div>
