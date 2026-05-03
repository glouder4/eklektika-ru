<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @global CMain $APPLICATION */
?>
<nofollow>
    <div class="sorting">
        <div class="row align-items-center">
            <div class="col-md-6 col-lg-7 col-xl-7 col-xl1-5 no-space-md"
                 style="padding: 3px 4px;display: inline-block;bordur-color: #858592;color: #000;-webkit-border-radius: 4px;border-radius: 4px;">
                <span>
                    <span style="font-weiht:bold;margin-bottom:15px;display:block;">Сортировка</span>
                </span>
                <ul class="sort-list gblo">
                    <?php
                    // Определяем текущую активную сортировку
                    $currentSortField = isset($_GET['sort_field']) ? $_GET['sort_field'] : '';
                    $currentSortOrder = isset($_GET['sort_order']) ? strtolower($_GET['sort_order']) : 'asc';

                    // Определяем активные элементы на основе текущего поля сортировки
                    // Проверяем, соответствует ли текущее поле сортировки нашим опциям
                    $activePrice = false;
                    $activePagetitle = false;
                    $activeInventory = false;

                    // Если активен фильтр "Новинки", не активируем другие элементы
                    if (!$isNovinki) {
                        if ($currentSortField == 'price' || (empty($currentSortField) && strpos($sortField, 'CATALOG_PRICE_') === 0)) {
                            $activePrice = true;
                        } elseif ($currentSortField == 'pagetitle' || (empty($currentSortField) && $sortField == 'name')) {
                            $activePagetitle = true;
                        } elseif ($currentSortField == 'inventory' || (empty($currentSortField) && $sortField == 'CATALOG_QUANTITY')) {
                            $activeInventory = true;
                        }
                    }
                    ?>
                    <li style="margin: 3;" class="set-sort-field-custom <?= $activePrice ? 'active' : '' ?>" data-sort-field="price"
                        data-sort-order="<?= ($activePrice && $currentSortOrder == 'asc') ? 'desc' : 'asc' ?>">
                        <a href="javascript:void(0);">по цене</a>
                    </li>
                    <li style="margin: 3;" class="set-sort-field-custom <?= $activePagetitle ? 'active' : '' ?>"
                        data-sort-field="pagetitle"
                        data-sort-order="<?= ($activePagetitle && $currentSortOrder == 'asc') ? 'desc' : 'asc' ?>">
                        <a href="javascript:void(0);">по названию</a>
                    </li>
                    <li style="margin: 3;" class="set-sort-field-custom <?= $activeInventory ? 'active' : '' ?>"
                        data-sort-field="inventory"
                        data-sort-order="<?= ($activeInventory && $currentSortOrder == 'asc') ? 'desc' : 'asc' ?>">
                        <a href="javascript:void(0);">по количеству</a>
                    </li>
                </ul>
            </div>
            <div class="d-flex col-7 col-md-3 col-lg-3 col-xl-3 col-xl1-5 sort-links-cont">
                <ul class="sort-links">
                    <li><a href="javascript:void(0);" id="sort-novinki"
                           class="check-novinki <?= $isNovinki ? 'active' : '' ?>">Новинки</a></li>
                </ul>
            </div>

            <div class="sorting-vid col-5 col-md-3 col-lg-2 ">
                <div class="change-view">
                    <span>Вид</span>
                    <ul>
                        <?php
                        // Определяем текущий вид отображения
                        $currentView = isset($_GET['cat_view']) ? intval($_GET['cat_view']) : 1;
                        $isShortView = ($currentView == 1 || $currentView == 0 || !isset($_GET['cat_view']));

                        // Формируем URL для краткого вида (удаляем параметр cat_view)
                        $shortViewUrl = $APPLICATION->GetCurPage();
                        $shortViewParams = $_GET;
                        unset($shortViewParams['cat_view']);
                        if (!empty($shortViewParams)) {
                            $shortViewUrl .= '?' . http_build_query($shortViewParams);
                        }

                        // Формируем URL для детального вида (устанавливаем cat_view=2)
                        $detailedViewUrl = $APPLICATION->GetCurPage();
                        $detailedViewParams = $_GET;
                        $detailedViewParams['cat_view'] = '2';
                        $detailedViewUrl .= '?' . http_build_query($detailedViewParams);
                        ?>
                        <li class="<?= $isShortView ? 'active' : '' ?>">
                            <a href="<?= htmlspecialchars($shortViewUrl) ?>"><span class="icon-product-short"></span></a>
                        </li>
                        <li class="<?= !$isShortView ? 'active' : '' ?>">
                            <a href="<?= htmlspecialchars($detailedViewUrl) ?>"><span class="icon-product-details"></span></a>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</nofollow>
