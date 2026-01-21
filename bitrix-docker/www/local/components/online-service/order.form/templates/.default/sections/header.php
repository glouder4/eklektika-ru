<div class="cart-date">
    <span class="cart-back"><a href="/cart.php">← вернуться</a></span>
    &nbsp; &nbsp;
    Если у вас уже есть аккаунт у нас в магазине, то <a href="/vhod.php">авторизуйтесь</a>, пожалуйста!
</div>
<? if (!empty($arResult['ERRORS'])): ?>
    <div class="errors" style="color:red; margin:10px 0;">
        <?= implode('<br>', $arResult['ERRORS']) ?>
    </div>
<? endif; ?>