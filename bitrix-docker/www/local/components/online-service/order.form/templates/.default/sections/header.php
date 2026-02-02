<div class="cart-date">
    <span class="cart-back"><a href="/cart.php">← вернуться</a></span>

    <?php
        global $USER;
        if (!$USER->IsAuthorized()) { ?>
            Если у вас уже есть аккаунт у нас в магазине, то <a href="/personal/vhod.php">авторизуйтесь</a>, пожалуйста!
        <?php }
    ?>
</div>
<? if (!empty($arResult['ERRORS'])): ?>
    <div class="errors" style="color:red; margin:10px 0;">
        <?= implode('<br>', $arResult['ERRORS']) ?>
    </div>
<? endif; ?>