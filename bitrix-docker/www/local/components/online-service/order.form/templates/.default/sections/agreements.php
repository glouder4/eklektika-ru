<div class="agree-block">
    <?php
    foreach ($arResult['CHECKBOX_PROPERTIES'] as $code => $prop):
        $isRequired = $prop['REQUIRED'] === 'Y';
        $isChecked = $prop['DEFAULT_VALUE'] === 'Y';

        $text = '';
        if ($prop['ID'] == 8) {
            $text = 'Настоящим подтверждаю, что я ознакомлен и согласен с условиями <a target="_blank" href="oferta/">политики конфиденциальности.</a>';
        } elseif ($prop['ID'] == 9) {
            $text = 'Я даю согласие на получение email рассылки с новинками, скидки и специальными предложениями.';
        }
        ?>
        <label class="checkbox">
            <input type="checkbox"
                   name="<?= htmlspecialchars($code) ?>"
                   value="Y"
                <?= $isChecked ? 'checked' : '' ?>
                <?= $isRequired ? 'required' : '' ?>>
            <span>
            <span class="star"><?= $isRequired ? '*' : '' ?></span>
            <?= $text ?>
        </span>
        </label>
    <?php endforeach; ?>
</div>