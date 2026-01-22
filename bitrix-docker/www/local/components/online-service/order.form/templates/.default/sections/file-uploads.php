<?php foreach ($arResult['ORDER_PROPERTIES_FILES'] as $code => $prop): ?>
    <div class="row">
        <div class="col-md-4">
            <label>
                <?= htmlspecialchars($prop['NAME']) ?>:
                <?php if (!empty($prop['DESCRIPTION'])): ?>
                    <span><?= htmlspecialchars($prop['DESCRIPTION']) ?></span>
                <?php endif; ?>
            </label>
        </div>
        <div class="col-md-8">
            <div class="input-box">
                <input type="file" name="<?= htmlspecialchars($code) ?>" id="<?= htmlspecialchars($code) ?>" class="inputfile inputfile-btn">
                <label for="<?= htmlspecialchars($code) ?>"><span>Выбрать файл</span></label>
            </div>
        </div>
    </div>
<?php endforeach; ?>