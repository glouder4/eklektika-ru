<div class="b-popup" id="popup1">
    <div class="b-popup-content">
        <h3>Товар успешно добавлен в корзину</h3>
        <div class="popup-info">
            <img src="<?= htmlspecialcharsbx($allPhotos[0]['src'] ?? '') ?>" 
                    alt="<?= htmlspecialcharsbx($allPhotos[0]['alt'] ?? '') ?>">
            <div>
                <p><?= htmlspecialcharsbx($arResult['NAME'] ?? '') ?></p>
                <p>Цена за штуку: 209<span style="font-size: 18px;"> ₽</span></p>
                <p>Тираж: <span class="count-prod2"></span></p>
                <p style="color: red">Внимание! Стоимость нанесения рассчитывается менеджером после оформления заказа.</p>
                <div class="form-group col-6" style="padding: 0;">
                    <label style="font: 16px Ubuntu;color: unset;">Метод нанесения:</label>
                    <select name="spaceSelect" class="form-control" style="margin-top: 4px; padding: 0; height: 30px;" id="exampleFormControlSelect2_1327959">
                        <option class="item_nanesenie2 cart-product-nanesenie" value="Лазерная гравировка">Лазерная гравировка</option>
                        <option class="item_nanesenie2 cart-product-nanesenie" value="УФ-печать">УФ-печать</option>
                        <option class="item_nanesenie2" value="Без нанесения">Без нанесения</option>                        
                    </select>
                </div>
                <p>Итого: <span class="count-price2"></span><span style="font-size: 18px;"> ₽</span></p>
            </div>
        </div>
        <div class="popup-buttons">
            <button class="hide-pop-up btn btn-gray btn-round ">Вернуться к покупкам</button>
            <button class="go-to-card btn btn-gray btn-round ">Продолжить оформление</button>
        </div>
    </div>
</div>
<script src="/pop-up.js"></script>