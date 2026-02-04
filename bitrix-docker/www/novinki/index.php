<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");?>
<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
$APPLICATION->SetPageProperty("title", "Новинки сувенирной (подарочной) продукции в каталоге на сайте компании «Эклектика»");
$APPLICATION->SetPageProperty("description", "Новинки сувенирной (подарочной) продукции. Большой ассортимент корпоративных сувениров в компании «Эклектика». Гибкий ценовой подход. Покупка оптом и изготовление на заказ. Срочно и качественно. Доставка по Москве и всей России.");
$pageH1 = 'Новинки'
?>
<div class="middle-content content" style="padding-bottom: 0px;">
    <h1 itemprop="headline"><?=$pageH1?></h1>
</div>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>