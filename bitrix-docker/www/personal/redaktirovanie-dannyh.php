<?
$GLOBALS['ADDITIONAL_WRAPPER_CLASSES'] = 'content';
$GLOBALS['SHOW_SYSTEM_TITLE'] = "N";

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Редактирование данных");
$APPLICATION->AddChainItem("Редактирование данных", "/personal/lichnyj-kabinet.php");

$APPLICATION->SetPageProperty("title", "Редактирование данных купить оптом в Москве | Эклектика – нанесение логотипов на заказ");
$APPLICATION->SetPageProperty("description", "Компания Эклектика предлагает Регистрация оптом под нанесение логотипа. ✓ Низкие цены. ✓ Доставка по России. ☎ 8(800) 777-4723");


global $USER;
if (!$USER->IsAuthorized()) {
    header("Location: /");
    exit();
}

require_once $_SERVER["DOCUMENT_ROOT"] . "/personal/ajax/get-company-by-inn.php";

$userId = (int)$USER->GetID();
$userFields = CUser::GetByID($userId)->Fetch();

$name        = $userFields['NAME'] ?? '';
$lastName    = $userFields['LAST_NAME'] ?? '';
$email       = $userFields['EMAIL'] ?? '';
$phone       = $userFields['PERSONAL_PHONE'] ?? '';
$workPhone   = $userFields['WORK_PHONE'] ?? '';
$yurAdress   = $userFields['UF_UR_ADRES'] ?? $userFields['PERSONAL_STREET'] ?? '';
$inn         = preg_replace('/\D/', '', (string)($userFields['UF_INN'] ?? ''));
$workProfile = $userFields['WORK_PROFILE'] ?? '';
$workCompany = $userFields['WORK_COMPANY'] ?? '';
$workWWW     = $userFields['WORK_WWW'] ?? '';

$companyEditable = false;
$company = getCompanyByInn($inn, $userId);
if ($company) {
    $workCompany = $company['name'];
    $yurAdress   = $company['address'];
    $workProfile = $company['activity'];
    $workWWW     = $company['sait'];
    $companyEditable = $company['isBoss'];
}
?>
<div class="content cart-order" style="margin:0;">
    <br>
    <a href="/personal/lichnyj-kabinet.php">Главная страница личного кабинета</a> &nbsp; &nbsp;
    <a href="/personal/redaktirovanie-dannyh.php">Редактировать данные</a> &nbsp; &nbsp;
    <a href="/personal/prosmotr-zakazov.php">Просмотр заказов</a> &nbsp; &nbsp;
    <h1>Редактирование данных</h1>
    <font color="red"><div class="errors"></div></font>
    <form name="perosnal-profile-form" class="cart-order left6 reg-form edit-form" enctype="multipart/form-data">
        <?=bitrix_sessid_post()?>
        <input type="hidden" name="company_editable" value="<?= $companyEditable ? '1' : '0' ?>">

        <div class="reg-form-section">
            <h3 class="reg-form-section__title">Данные контактного лица</h3>
            <div class="row">
                <div class="col-md-4">
                    <label>Имя <font color="red">*</font><span class="help-block text-error"></span></label>
                </div>
                <div class="col-md-8">
                    <input required maxlength="100" name="name" id="name" type="text" value="<?=htmlspecialchars($name)?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <label>Фамилия <font color="red">*</font><span class="help-block text-error"></span></label>
                </div>
                <div class="col-md-8">
                    <input required maxlength="100" name="lastname" id="lastname" type="text" value="<?=htmlspecialchars($lastName)?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <label for="phone">Телефон c указанием кода региона без скобок и пробелов<font color="red">*</font> <span class="error"></span></label>
                </div>
                <div class="col-md-8">
                    <input required name="phone" inputmode="tel" id="phone" type="text" value="<?=htmlspecialchars($workPhone)?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <label for="mobilephone">Мобильный телефон <span class="error"></span></label>
                </div>
                <div class="col-md-8">
                    <input maxlength="20" name="mobilephone" id="mobilephone" type="text" inputmode="tel" class="input-number" value="<?=htmlspecialchars($phone)?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <label for="email">E-mail <span class="error"></span></label>
                </div>
                <div class="col-md-8">
                    <input name="email" type="email" id="email" maxlength="100" value="<?=htmlspecialchars($email)?>">
                </div>
            </div>
        </div>

        <div class="reg-form-section" id="company-section">
            <h3 class="reg-form-section__title">Данные компании</h3>
            <div class="row">
                <div class="col-md-4">
                    <label for="fax">ИНН организации <font color="red">*</font> <span class="error"></span></label>
                </div>
                <div class="col-md-8">
                    <input required name="inn" type="text" id="fax" value="<?=htmlspecialchars($inn)?>" maxlength="12" inputmode="numeric" class="company-field" <?= $companyEditable ? '' : 'readonly' ?>>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <label for="state">Название юридического лица<font color="red">*</font> <span class="error"></span></label>
                </div>
                <div class="col-md-8">
                    <input required maxlength="100" id="state" type="text" name="name_company" value="<?=htmlspecialchars($workCompany)?>" class="company-field" <?= $companyEditable ? '' : 'readonly' ?>>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <label for="address">Юридический адрес <span class="error"></span></label>
                </div>
                <div class="col-md-8">
                    <input maxlength="100" name="address" type="text" id="address" value="<?=htmlspecialchars($yurAdress)?>" class="company-field" <?= $companyEditable ? '' : 'readonly' ?>>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <label for="activities">Сфера деятельности <span class="error"></span></label>
                </div>
                <div class="col-md-8">
                    <input maxlength="50" name="activities" type="text" id="activities" value="<?=htmlspecialchars($workProfile)?>" class="company-field" <?= $companyEditable ? '' : 'readonly' ?>>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <label for="sait">WEB сайт Вашей компании <span class="error"></span></label>
                </div>
                <div class="col-md-8">
                    <input name="sait" inputmode="url" maxlength="50" type="text" id="sait" placeholder="example.com" value="<?=htmlspecialchars($workWWW)?>" class="company-field" <?= $companyEditable ? '' : 'readonly' ?>>
                </div>
            </div>
            <?php if (!$companyEditable && $company): ?>
            <p class="text-muted" style="font-size:13px; margin-top:8px;">Данные компании доступны только для просмотра. Редактирование возможно только для руководителя организации.</p>
            <?php endif; ?>
        </div>

        <div class="row reg-form-section reg-form-section--submit">
            <input type="button" id="save-form" value="Сохранить" class="btn btn-round btn-shadow btn-blue" />
        </div>
    </form>
</div>

<style>
    .reg-form .field-error { border-color: #e74c3c !important; box-shadow: 0 0 0 1px #e74c3c; }
    .reg-form .field-valid { border-color: #27ae60 !important; }
    .reg-form .help-block.text-error, .reg-form .error { color: #e74c3c; font-size: 12px; margin-top: 4px; display: block; }
    .reg-form .error:empty { display: none; }
    .reg-form .validation-summary { background: #fdf2f2; border: 1px solid #e74c3c; border-radius: 6px; padding: 12px 16px; margin-bottom: 20px; color: #c0392b; font-size: 14px; display: none; }
    .reg-form .validation-summary.visible { display: block; }
    .reg-form .validation-summary ul { margin: 0; padding-left: 20px; }
    .reg-form-section { margin-bottom: 32px; padding-bottom: 24px; border-bottom: 1px solid #e5e5e5; }
    .reg-form-section:last-of-type { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    .reg-form-section__title { margin: 0 0 16px 0; font-size: 16px; font-weight: 600; color: #333; }
    .reg-form-section--submit { border-bottom: none; padding-top: 8px; margin-bottom: 0; }
    .reg-form .company-field[readonly] { background: #f5f5f5; color: #555; cursor: not-allowed; }
</style>

<script src="/ds-comf/ds-form/js/jquery.mask.min.js"></script>
<script>
    $('#mobilephone').mask('+9 (999) 999-99-99');
    $('#phone').mask('+9 (999) 999-99-99');
</script>
<script type="text/javascript">
(function() {
    var $form = $('form[name="perosnal-profile-form"]');
    var companyEditable = $form.find('[name="company_editable"]').val() === '1';

    function showError($field, message) {
        var $row = $field.closest('.row');
        var $err = $row.find('.help-block.text-error, .error').first();
        $err.text(message).show();
        $field.addClass('field-error').removeClass('field-valid');
    }
    function clearError($field) {
        var $row = $field.closest('.row');
        $row.find('.help-block.text-error, .error').first().text('').hide();
        $field.removeClass('field-error field-valid');
    }

    var rules = {
        name: { required: true, msg: 'Укажите имя' },
        lastname: { required: true, msg: 'Укажите фамилию' },
        phone: {
            required: true,
            msg: 'Укажите телефон',
            validate: function(val) {
                if (!val || val.trim() === '') return 'Укажите телефон';
                var digits = val.replace(/\D/g, '');
                if (digits.length < 10) return 'Телефон должен содержать минимум 10 цифр';
                return null;
            }
        },
        name_company: { required: true, msg: 'Укажите название юридического лица' },
        inn: {
            required: true,
            msg: 'Укажите ИНН организации',
            validate: function(val) {
                if (!val || val.trim() === '') return 'Укажите ИНН организации';
                var digits = val.replace(/\D/g, '');
                if (digits.length < 10 || digits.length > 12) return 'ИНН организации должен содержать от 10 до 12 цифр';
                return null;
            }
        },
        sait: {
            required: false,
            validate: function(val) {
                if (!val || val.trim() === '') return null;
                var url = val.trim();
                if (!/^https?:\/\//i.test(url)) url = 'https://' + url;
                if (!/^(https?:\/\/)?[a-z0-9][a-z0-9.-]*\.[a-z]{2,}(\/.*)?$/i.test(url)) {
                    return 'Введите корректный адрес сайта (например: example.com или qqqqq.ru)';
                }
                return null;
            }
        },
        email: {
            required: false,
            validate: function(val) {
                if (!val || val.trim() === '') return null;
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) return 'Введите корректный e-mail';
                return null;
            }
        }
    };

    function validateForm() {
        var errors = [];
        $form.find('.field-error, .field-valid').removeClass('field-error field-valid');
        $form.find('.help-block.text-error, .error').text('').hide();

        $form.find('input[required]').each(function() {
            var $el = $(this);
            var name = $el.attr('name');
            var val = $el.val();
            if (typeof val === 'string') val = val.trim();
            var rule = rules[name];
            if (rule && rule.required && (!val || val === '')) {
                showError($el, rule.msg || 'Заполните поле');
                errors.push(rule.msg || 'Заполните поле');
            } else if (rule && rule.validate) {
                var err = rule.validate(val);
                if (err) { showError($el, err); errors.push(err); }
            }
        });

        $form.find('input[name="inn"], input[name="sait"], input[name="email"]').each(function() {
            var $el = $(this);
            var name = $el.attr('name');
            var val = $el.val();
            if (typeof val === 'string') val = val.trim();
            var rule = rules[name];
            if (rule && rule.validate && val) {
                var err = rule.validate(val);
                if (err) { showError($el, err); if (errors.indexOf(err) === -1) errors.push(err); }
            }
        });

        var $summary = $form.find('.validation-summary');
        if (!$summary.length) {
            $summary = $('<div class="validation-summary"><strong>Пожалуйста, исправьте ошибки:</strong><ul></ul></div>');
            $form.prepend($summary);
        }
        if (errors.length) {
            $summary.find('ul').html(errors.map(function(e){ return '<li>' + e + '</li>'; }).join(''));
            $summary.addClass('visible');
            $('html, body').animate({ scrollTop: $form.offset().top - 20 }, 300);
            return false;
        }
        $summary.removeClass('visible');
        return true;
    }

    $(document).on('click', '#save-form', function(e) {
        e.preventDefault();
        if (!validateForm()) return;

        $.ajax({
            url: '/personal/ajax/ajax-edit-company.php',
            method: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Профиль успешно обновлён!');
                } else {
                    alert('Ошибка: ' + (response.error || 'Неизвестная ошибка'));
                }
            },
            error: function(xhr) {
                var errorMsg = 'Сетевая ошибка';
                try {
                    var resp = JSON.parse(xhr.responseText);
                    errorMsg = resp.error || 'Ошибка сервера';
                } catch (e) {}
                alert('Не удалось обновить профиль: ' + errorMsg);
            }
        });
    });
})();
</script>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
