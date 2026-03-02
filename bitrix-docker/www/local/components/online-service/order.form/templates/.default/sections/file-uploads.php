<?php
$companyDataForForm = $companyDataForForm ?? null;
$isRequisitesFile = function($code) { return (stripos($code, 'requisites') !== false || stripos($code, 'реквизит') !== false); };
?>
<?php foreach ($arResult['ORDER_PROPERTIES_FILES'] as $code => $prop): ?>
    <?php $showRequisitesHint = $isRequisitesFile($code) && !empty($companyDataForForm['requisites_file_id']); ?>
    <div class="row">
        <div class="col-md-4">
            <label>
                <?= htmlspecialchars($prop['NAME']) ?>:
                <?php if (!empty($prop['DESCRIPTION'])): ?>
                    <span><?= htmlspecialchars($prop['DESCRIPTION']) ?></span>
                <?php endif; ?>
            </label>
        </div>
        <div class="col-md-8">
            <div class="input-box">
                <input type="file" name="<?= htmlspecialchars($code) ?>" id="<?= htmlspecialchars($code) ?>" class="inputfile inputfile-btn<?= ($isRequisitesFile($code) && $showRequisitesHint) ? ' order-field-prefilled' : '' ?>"<?= ($isRequisitesFile($code) && $showRequisitesHint) ? ' disabled' : '' ?>>
                <label for="<?= htmlspecialchars($code) ?>"><span><?= ($isRequisitesFile($code) && $showRequisitesHint) ? htmlspecialchars($companyDataForForm['requisites_file_name'] ?? '') : 'Выбрать файл' ?></span></label>
            </div>
        </div>
    </div>
<?php endforeach; ?>