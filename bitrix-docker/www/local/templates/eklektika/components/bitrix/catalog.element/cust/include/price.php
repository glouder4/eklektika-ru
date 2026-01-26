<div class="col-6">
    <div class="small-title">Артикул</div>
    <!-- here -->
    <div class="article"><?= htmlspecialcharsbx($ARTICLE) ?></div>
</div>
<div class="col-6">
    <link itemprop="url" href="<?= $APPLICATION->GetCurPage() ?>">
    <link itemprop="availability" href="http://schema.org/InStock"><!-- В наличии -->
    <link itemprop="availability" href="http://schema.org/OutOfStock"><!-- Отсутствует -->
    <meta itemprop="availability" content="https://schema.org/PreOrder"><!-- Предзаказ -->
    <div class="small-title">Цена оптовая:</div>
        <div class="price-big price-throug" itemprop="price"> <?=$price_parts[0]?>,<sub><?=$price_parts[1]?></sub><span style="font-size: 19px;"> ₽</span></div>
        <br><br>
        <div class="small-title red">Скидка -3%:</div>
        <div class="price-sale"> 209,<sub>00 ₽</sub></div>
        <br>
    </div>
</div>