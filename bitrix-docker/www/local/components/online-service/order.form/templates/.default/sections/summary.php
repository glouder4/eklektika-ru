<div class="panel-cart">
    <div class="panel-cart-inner">
        <div class="panel-cart-title">Итого</div>
        <table>
            <tr><td>Наименований</td><td><span id="total-items"><?=$arResult['countItems']?></span></td></tr>
            <tr><td>Общий тираж</td><td><span id="total-quantity"><?=$arResult['totalQuantity']?></span> шт.</td></tr>
            <tr><td style="min-width:140px">Вес заказа(кг)</td><td><span id="total-ves"><?=$arResult['totalWeight']?></span></td></tr>
        </table>
        <div class="panel-cart-total">
            <span id="grand-total"><?=$arResult['integerPart']?><sub>,<?=$arResult['fractionPart']?></sub></span> руб.
        </div>
    </div>
</div>