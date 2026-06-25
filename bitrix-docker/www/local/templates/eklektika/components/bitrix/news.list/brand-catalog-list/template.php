<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arParams */
/** @var array $arResult */
/** @var CBitrixComponentTemplate $this */

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/brand_catalog.php';

$this->setFrameMode(true);
?>
<div class="clients">
    <div class="row">
        <?php foreach ($arResult['ITEMS'] as $arItem): ?>
            <?php
            $this->AddEditAction(
                $arItem['ID'],
                $arItem['EDIT_LINK'],
                CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT')
            );
            $this->AddDeleteAction(
                $arItem['ID'],
                $arItem['DELETE_LINK'],
                CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_DELETE'),
                ['CONFIRM' => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')]
            );

            $code = trim((string)($arItem['CODE'] ?? ''));
            $detailUrl = (string)($arItem['DETAIL_PAGE_URL'] ?? '');
            if ($detailUrl === '' && $code !== '') {
                $detailUrl = brandCatalogGetPageFolder($code);
            }

            $picture = $arItem['PREVIEW_PICTURE'] ?? $arItem['DETAIL_PICTURE'] ?? null;
            $pictureSrc = is_array($picture) ? (string)($picture['SRC'] ?? '') : '';
            ?>
            <div class="col-6 col-md-4 col-xl1-3" id="<?= $this->GetEditAreaId($arItem['ID']) ?>">
                <?php if ($detailUrl !== ''): ?>
                    <a href="<?= htmlspecialcharsbx($detailUrl) ?>" class="client">
                        <?php if ($pictureSrc !== ''): ?>
                            <img src="<?= htmlspecialcharsbx($pictureSrc) ?>" alt="<?= htmlspecialcharsbx($arItem['NAME'] ?? '') ?>">
                        <?php else: ?>
                            <span class="client__name"><?= htmlspecialcharsbx($arItem['NAME'] ?? '') ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
