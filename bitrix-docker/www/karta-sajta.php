<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$APPLICATION->SetPageProperty(
    'title',
    'Карта сайта — интернет-магазин «Эклектика»'
);
$APPLICATION->SetPageProperty(
    'description',
    'Полная карта сайта компании «Эклектика»: каталог сувенирной продукции с нанесением логотипа и информационные разделы.'
);
$APPLICATION->SetTitle('Карта сайта');
$APPLICATION->AddChainItem('Карта сайта', '');
?>

<div class="middle-content content">
    <?php require $_SERVER['DOCUMENT_ROOT'] . '/local/include/site_map/render.php'; ?>
</div>

<?php require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'); ?>
