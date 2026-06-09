<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var string[] $nanesenieOptions */
/** @var string[] $selectedNanesenieValues */
/** @var string $nanesenieContainerClass */
/** @var int|string|null $nanesenieOfferId */
/** @var string|null $nanesenieContainerId */

use OnlineService\Catalog\NanesenieOptionsResolver;

$nanesenieOptions = $nanesenieOptions ?? NanesenieOptionsResolver::getAllOptions();
$selectedNanesenieValues = $selectedNanesenieValues ?? [NanesenieOptionsResolver::DEFAULT_OPTION];
if (!is_array($selectedNanesenieValues)) {
    $selectedNanesenieValues = [(string)$selectedNanesenieValues];
}
$selectedNanesenieValues = NanesenieOptionsResolver::normalizeSubmittedValues($selectedNanesenieValues);
$nanesenieContainerClass = trim($nanesenieContainerClass ?? 'item_nanesenie');
$onlyDefaultSelected = count($selectedNanesenieValues) === 1
    && NanesenieOptionsResolver::isDefaultOption($selectedNanesenieValues[0]);
$triggerText = implode(', ', $selectedNanesenieValues);

$containerClass = trim($nanesenieContainerClass . ' item_nanesenie-multiselect item_nanesenie-dropdown');
?>
<div class="<?= htmlspecialcharsbx($containerClass) ?>"
    <?php if (!empty($nanesenieContainerId)): ?>id="<?= htmlspecialcharsbx((string)$nanesenieContainerId) ?>"<?php endif; ?>
    <?php if (!empty($nanesenieOfferId)): ?>data-offer-id="<?= (int)$nanesenieOfferId ?>"<?php endif; ?>>
    <button type="button" class="item_nanesenie-trigger" aria-haspopup="listbox" aria-expanded="false">
        <span class="item_nanesenie-trigger-text"><?= htmlspecialcharsbx($triggerText) ?></span>
        <span class="item_nanesenie-trigger-arrow" aria-hidden="true">▾</span>
    </button>
    <div class="item_nanesenie-panel" hidden>
        <div class="item_nanesenie-panel-inner" role="listbox" aria-multiselectable="true">
            <?php foreach ($nanesenieOptions as $optionLabel):
                $optionLabel = (string)$optionLabel;
                $isDefaultOption = NanesenieOptionsResolver::isDefaultOption($optionLabel);
                $isChecked = $isDefaultOption
                    ? $onlyDefaultSelected
                    : (!$onlyDefaultSelected && in_array($optionLabel, $selectedNanesenieValues, true));
                $inputClass = 'item_nanesenie-option' . ($isDefaultOption ? ' item_nanesenie-none' : '');
                ?>
                <label class="item_nanesenie-label">
                    <input type="checkbox"
                           class="<?= htmlspecialcharsbx($inputClass) ?>"
                           value="<?= htmlspecialcharsbx($optionLabel) ?>"<?= $isChecked ? ' checked' : '' ?>>
                    <?= htmlspecialcharsbx($optionLabel) ?>
                </label>
            <?php endforeach; ?>
        </div>
    </div>
</div>
