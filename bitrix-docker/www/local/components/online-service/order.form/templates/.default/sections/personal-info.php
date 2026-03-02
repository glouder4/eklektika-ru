<?php

global $USER;
$name = $email = $workPhone = $workCompany = '';
if ($USER->IsAuthorized()) {
    $userFields = CUser::GetByID($USER->GetID())->Fetch();
    $name        = $userFields['NAME'] ?? '';
    $lastName    = $userFields['LAST_NAME'] ?? '';
    $email       = $userFields['EMAIL'] ?? '';
    $workPhone   = $userFields['WORK_PHONE'] ?? '';
    $workCompany = $userFields['WORK_COMPANY'] ?? '';
}

// Данные выбранной компании (из company-selector) — приоритет над данными пользователя
$companyDataForForm = $companyDataForForm ?? null;

foreach ($arResult['ORDER_PROPERTIES'] as $code => $prop):
    $isRequired = $prop['REQUIRED'] === 'Y';
    $value = htmlspecialchars($arResult['FIELDS'][$code] ?? '');
    if( $code == "off_name" ){
        $value = htmlspecialchars($arResult['FIELDS']['NAME']);
    }
    elseif( $code == "off_email" ){
        $value = !empty($companyDataForForm['off_email']) ? htmlspecialchars($companyDataForForm['off_email']) : htmlspecialchars($email);
    }
    elseif( $code == "off_company" ){
        $value = !empty($companyDataForForm['off_company']) ? htmlspecialchars($companyDataForForm['off_company']) : htmlspecialchars($workCompany);
    }
    elseif( $code == "off_phone" ){
        if (!empty($companyDataForForm['off_phone'])) {
            $value = htmlspecialchars($companyDataForForm['off_phone']);
        } else {
            $phone = trim((string)$workPhone);
            $value = preg_match('/^(\+7|7|8)/', $phone) ? htmlspecialchars($phone) : '+7' . htmlspecialchars($phone);
        }
    }
    elseif( $code == "off_requisites" ){
        if (!empty($companyDataForForm['off_requisites'])) {
            $value = htmlspecialchars($companyDataForForm['off_requisites']);
        }
    }

    $label = htmlspecialchars($prop['NAME']);
    $description = !empty($prop['DESCRIPTION']) ? '<span>' . htmlspecialchars($prop['DESCRIPTION']) . '</span>' : '';
    $companyFieldKey = $code === 'off_requisites' ? 'off_requisites' : $code;
    $companyVal = trim((string)(($companyDataForForm ?? [])[$companyFieldKey] ?? ''));
    $isFromCompany = in_array($code, ['off_company', 'off_phone', 'off_email', 'off_requisites'])
        && $companyVal !== '';
    $readonlyAttr = $isFromCompany ? ' readonly' : '';
    $prefilledClass = $isFromCompany ? ' order-field-prefilled' : '';
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
                       class="<?= $prefilledClass ?>"
                    <?= $isRequired ? 'required' : '' ?><?= $readonlyAttr ?>
                       placeholder="<?= htmlspecialchars($prop['DEFAULT_VALUE'] ?? '') ?>">
            <?php elseif ($prop['TYPE'] === 'TEXTAREA'): ?>
                <textarea name="<?= htmlspecialchars($code) ?>"
                          class="<?= $prefilledClass ?>"
                          <?= $isRequired ? 'required' : '' ?><?= $readonlyAttr ?>
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
                <input type="text" name="<?= htmlspecialchars($code) ?>" value="<?= $value ?>" class="<?= $prefilledClass ?>" <?= $isRequired ? 'required' : '' ?><?= $readonlyAttr ?>>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>