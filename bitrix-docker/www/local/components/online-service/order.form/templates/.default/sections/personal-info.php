<?php
// Определяем, какие поля относятся к "персональной информации"

foreach ($arResult['ORDER_PROPERTIES'] as $code => $prop):
    $isRequired = $prop['REQUIED'] === 'Y';
    $value = htmlspecialchars($arResult['FIELDS'][$code] ?? '');
    $label = htmlspecialchars($prop['NAME']);
    $description = !empty($prop['DESCRIPTION']) ? '<span>' . htmlspecialchars($prop['DESCRIPTION']) . '</span>' : '';
    ?>
    <div class="row">
        <div class="col-md-4">
            <label>
                <?= $label ?><?= $isRequired ? '<font color="red">*</font>' : '' ?>
                <?= $description ?>
            </label>
        </div>
        <div class="col-md-8">
            <?php if ($prop['TYPE'] === 'TEXT'): ?>
                <input type="<?= $prop['USER_PROPS'] === 'Y' && in_array($code, ['EMAIL', 'USER_EMAIL']) ? 'email' : 'text' ?>"
                       name="<?= htmlspecialchars($code) ?>"
                       value="<?= $value ?>"
                       maxlength="<?= (int)($prop['SIZE1'] ?: 255) ?>"
                    <?= $isRequired ? 'required' : '' ?>
                       placeholder="<?= htmlspecialchars($prop['DEFAULT_VALUE'] ?? '') ?>">
            <?php elseif ($prop['TYPE'] === 'TEXTAREA'): ?>
                <textarea name="<?= htmlspecialchars($code) ?>"
                          <?= $isRequired ? 'required' : '' ?>
                          placeholder="<?= htmlspecialchars($prop['DEFAULT_VALUE'] ?? '') ?>"><?= $value ?></textarea>
            <?php elseif ($prop['TYPE'] === 'SELECT'): ?>
                <div class="select">
                    <select name="<?= htmlspecialchars($code) ?>" <?= $isRequired ? 'required' : '' ?>>
                        <?php
                        $variants = unserialize($prop['DEFAULT_VALUE'], ['allowed_classes' => false]);
                        if (is_array($variants)) {
                            foreach ($variants as $val => $title) {
                                echo '<option value="' . htmlspecialchars($val) . '">' . htmlspecialchars($title) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            <?php else: ?>
                <!-- Для других типов (FILE, CHECKBOX и т.д. — обрабатываем отдельно) -->
                <input type="text" name="<?= htmlspecialchars($code) ?>" value="<?= $value ?>" <?= $isRequired ? 'required' : '' ?>>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>