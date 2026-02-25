<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Регистрация");
$APPLICATION->AddChainItem("Регистрация", "/personal/registraciya.php");
$APPLICATION->SetPageProperty("title", "Регистрация купить оптом в Москве | Эклектика – нанесение логотипов на заказ");
$APPLICATION->SetPageProperty("description", "Компания Эклектика предлагает Регистрация оптом под нанесение логотипа. ✓ Низкие цены. ✓ Доставка по России. ☎ 8(800) 777-4723");

global $USER;
if ($USER->IsAuthorized()) {
    header("Location: /personal/lichnyj-kabinet.php");
    exit();
}

$regCompanies = require $_SERVER["DOCUMENT_ROOT"] . "/personal/ajax/get-companies-list.php";
?>

    <div class="content"><font color="FF0000"></font>
        <form name="registration-form" class="cart-order left6 reg-form" enctype="multipart/form-data">
            <?=bitrix_sessid_post()?>

            <div class="reg-form-section">
                <h3 class="reg-form-section__title">Данные контактного лица</h3>
                <div class="row">
                    <div class="col-md-4">
                        <label>Имя <font color="red">*</font><span class="help-block text-error"></span></label>
                    </div>
                    <div class="col-md-8">
                        <input required maxlength="100" name="name" id="name" type="text" value="">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <label>Фамилия <font color="red">*</font><span class="help-block text-error"></span></label>
                    </div>
                    <div class="col-md-8">
                        <input required maxlength="100" name="lastname" id="lastname" type="text" value="">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <label for="phone">Телефон c указанием кода региона без скобок и пробелов<font color="red">*</font> <span class="error"></span></label>
                    </div>
                    <div class="col-md-8">
                        <input required name="main-phone" inputmode="tel" id="main-phone" type="text" value="">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <label for="mobilephone">Мобильный телефон <span class="error"></span></label>
                    </div>
                    <div class="col-md-8">
                        <input maxlength="20" name="mobilephone" id="mobilephone" type="text" inputmode="tel" class="input-number" value="">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <label for="email">E-mail <span class="error"></span></label>
                    </div>
                    <div class="col-md-8">
                        <input name="email" type="email" id="email" maxlength="100" value="">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <label for="password">Пароль <font color="red">*</font> <span class="error"></span></label>
                    </div>
                    <div class="col-md-8">
                        <input required name="password" type="password" id="password" autocomplete="new-password" value="">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <label for="password_confirm">Подтверждение пароля <font color="red">*</font> <span class="error"></span></label>
                    </div>
                    <div class="col-md-8">
                        <input required name="password_confirm" type="password" id="password_confirm" autocomplete="new-password" value="">
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
                        <div class="inn-autocomplete">
                            <input required name="inn" type="text" id="fax" value="" placeholder="Введите ИНН (10–12 цифр)" autocomplete="off" maxlength="12" inputmode="numeric" data-company-selected="0">
                            <div class="inn-autocomplete__dropdown" id="inn-dropdown"></div>
                            <div class="inn-autocomplete__clear" id="inn-clear" style="display:none;">— Указать другую компанию</div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <label for="state">Название юридического лица<font color="red">*</font> <span class="error"></span></label>
                    </div>
                    <div class="col-md-8">
                        <input required maxlength="100" id="state" type="text" name="name_company" value="" class="company-field">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <label for="address">Юридический адрес <span class="error"></span></label>
                    </div>
                    <div class="col-md-8">
                        <input maxlength="100" name="address" type="text" id="address" value="" class="company-field">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <label for="activities">Сфера деятельности <span class="error"></span></label>
                    </div>
                    <div class="col-md-8">
                        <input maxlength="50" name="activities" type="text" id="activities" value="" class="company-field">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <label for="sait">WEB сайт Вашей компании <span class="error"></span></label>
                    </div>
                    <div class="col-md-8">
                        <input name="sait" inputmode="url" maxlength="50" type="text" id="sait" placeholder="example.com" value="" class="company-field">
                    </div>
                </div>
            </div>

            <div class="row reg-form-section reg-form-section--submit">
                <input type="button" id="register-btn" value="Зарегистрироваться" class="btn btn-round btn-shadow btn-blue" />
            </div>
        </form>
    </div>

    <style>
        .reg-form .field-error { border-color: #e74c3c !important; box-shadow: 0 0 0 1px #e74c3c; }
        .reg-form .field-valid { border-color: #27ae60 !important; }
        .reg-form .help-block.text-error,
        .reg-form .error { color: #e74c3c; font-size: 12px; margin-top: 4px; display: block; }
        .reg-form .error:empty { display: none; }
        .reg-form .validation-summary { background: #fdf2f2; border: 1px solid #e74c3c; border-radius: 6px; padding: 12px 16px; margin-bottom: 20px; color: #c0392b; font-size: 14px; display: none; }
        .reg-form .validation-summary.visible { display: block; }
        .reg-form .validation-summary ul { margin: 0; padding-left: 20px; }
        .reg-form-section { margin-bottom: 32px; padding-bottom: 24px; border-bottom: 1px solid #e5e5e5; }
        .reg-form-section:last-of-type { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .reg-form-section__title { margin: 0 0 16px 0; font-size: 16px; font-weight: 600; color: #333; }
        .reg-form-section--submit { border-bottom: none; padding-top: 8px; margin-bottom: 0; }
        .inn-autocomplete { position: relative; }
        .inn-autocomplete__dropdown { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #ddd; border-top: none; max-height: 220px; overflow-y: auto; z-index: 100; display: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .inn-autocomplete__dropdown.visible { display: block; }
        .inn-autocomplete__item { padding: 10px 12px; cursor: pointer; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
        .inn-autocomplete__item:hover { background: #f5f5f5; }
        .inn-autocomplete__item:last-child { border-bottom: none; }
        .inn-autocomplete__item strong { color: #333; }
        .inn-autocomplete__item small { color: #777; font-size: 12px; display: block; margin-top: 2px; }
        .inn-autocomplete__empty { padding: 12px; color: #777; font-size: 14px; }
        .inn-autocomplete__clear { margin-top: 8px; font-size: 13px; color: #666; cursor: pointer; text-decoration: underline; }
        .inn-autocomplete__clear:hover { color: #333; }
        .reg-form .company-field--locked { background: #f5f5f5 !important; color: #555; cursor: not-allowed; }
    </style>
    <script type="text/javascript">
        (function() {
            var $form = $('form[name="registration-form"]');

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
            function setValid($field) {
                var $row = $field.closest('.row');
                $row.find('.help-block.text-error, .error').first().text('').hide();
                $field.removeClass('field-error').addClass('field-valid');
            }

            var rules = {
                name: { required: true, msg: 'Укажите имя' },
                lastname: { required: true, msg: 'Укажите фамилию' },
                'main-phone': {
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
                },
                password: {
                    required: true,
                    msg: 'Укажите пароль',
                    validate: function(val) {
                        if (!val || val.length < 6) return 'Пароль должен быть не менее 6 символов';
                        return null;
                    }
                },
                password_confirm: {
                    required: true,
                    msg: 'Подтвердите пароль',
                    validate: function(val) {
                        var pwd = $form.find('[name="password"]').val();
                        if (val !== pwd) return 'Пароли не совпадают';
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
                        if (err) {
                            showError($el, err);
                            errors.push(err);
                        } else if (val) setValid($el);
                    } else if (val) setValid($el);
                });

                $form.find('input[name="inn"], input[name="sait"], input[name="email"], input[name="password"], input[name="password_confirm"]').each(function() {
                    var $el = $(this);
                    var name = $el.attr('name');
                    var val = $el.val();
                    if (typeof val === 'string') val = val.trim();
                    var rule = rules[name];
                    if (rule && rule.validate && val) {
                        var err = rule.validate(val);
                        if (err) {
                            showError($el, err);
                            if (errors.indexOf(err) === -1) errors.push(err);
                        } else setValid($el);
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

            $form.find('input').on('input blur', function() {
                var $el = $(this);
                var name = $el.attr('name');
                var rule = rules[name];
                if (!rule) return;
                var val = $el.val();
                if (typeof val === 'string') val = val.trim();
                if (rule.required && !val) { clearError($el); return; }
                if (rule.validate) {
                    var err = rule.validate(val);
                    if (err) showError($el, err);
                    else if (val) setValid($el);
                    else clearError($el);
                }
            });

            $(document).on('click', '#register-btn', function(e) {
                e.preventDefault();
                if (!validateForm()) return;

                var formData = $form.serialize();

                $.ajax({
                    url: '/personal/ajax/ajax-register-action..php',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        } else {
                            alert(response.message || 'Регистрация успешно завершена');
                        }
                    } else {
                        // Ошибка на стороне сервера
                        alert('Ошибка: ' + (response.error || 'Неизвестная ошибка'));
                        console.error('Ошибка обновления:', response.error);
                    }
                },
                error: function(xhr, status, error) {
                    // Сетевая ошибка или невалидный JSON
                    let errorMsg = 'Сетевая ошибка';
                    try {
                        const resp = JSON.parse(xhr.responseText);
                        errorMsg = resp.error || 'Ошибка сервера';
                    } catch (e) {
                        // Если ответ не JSON — возможно, фатальная ошибка PHP или 500
                        errorMsg = 'Ошибка сервера (неверный формат ответа). Проверьте логи.';
                    }
                    alert('Не удалось обновить профиль: ' + errorMsg);
                    console.error('AJAX error:', error, xhr.responseText);
                }
            });
        });
        })();
    </script>

    <script src="/ds-comf/ds-form/js/jquery.mask.min.js"></script>
    <script>
        $(`#mobilephone`).mask(`+9 (999) 999-99-99`);
        $('#main-phone').mask(`+9 (999) 999-99-99`);
    </script>
    <script>
        (function() {
            var companies = <?= json_encode($regCompanies, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
            var $innInput = $('#fax');
            var $innDropdown = $('#inn-dropdown');
            var $innClear = $('#inn-clear');
            var $companyFields = $('.company-field');
            var companySelected = null;

            function filterCompanies(digits) {
                digits = (digits || '').replace(/\D/g, '');
                return companies.filter(function(c) {
                    return !digits || c.inn.indexOf(digits) === 0;
                }).slice(0, 20);
            }

            function renderDropdown(items) {
                if (!items.length) {
                    $innDropdown.removeClass('visible').html('<div class="inn-autocomplete__empty">Нет подходящих организаций</div>');
                    $innDropdown.addClass('visible');
                    return;
                }
                var html = items.map(function(c) {
                    return '<div class="inn-autocomplete__item" data-inn="' + (c.inn || '') + '" data-name="' + (c.name || '').replace(/"/g, '&quot;') + '" data-address="' + (c.address || '').replace(/"/g, '&quot;') + '" data-activity="' + (c.activity || '').replace(/"/g, '&quot;') + '" data-sait="' + (c.sait || '').replace(/"/g, '&quot;') + '">' +
                        '<strong>' + (c.name || 'Без названия') + '</strong>' +
                        '<small>ИНН: ' + (c.inn || '') + '</small></div>';
                }).join('');
                $innDropdown.html(html).addClass('visible');
            }

            function selectCompany(c) {
                companySelected = c;
                $innInput.val(c.inn).attr('readonly', true).addClass('company-field--locked').attr('data-company-selected', '1');
                $('#state').val(c.name || '').attr('readonly', true).addClass('company-field--locked');
                $('#address').val(c.address || '').attr('readonly', true).addClass('company-field--locked');
                $('#activities').val(c.activity || '').attr('readonly', true).addClass('company-field--locked');
                $('#sait').val(c.sait || '').attr('readonly', true).addClass('company-field--locked');
                $innDropdown.removeClass('visible').empty();
                $innClear.show();
            }

            function clearCompany() {
                companySelected = null;
                $innInput.val('').attr('readonly', false).removeClass('company-field--locked').attr('data-company-selected', '0');
                $('#state').val('').attr('readonly', false).removeClass('company-field--locked');
                $('#address').val('').attr('readonly', false).removeClass('company-field--locked');
                $('#activities').val('').attr('readonly', false).removeClass('company-field--locked');
                $('#sait').val('').attr('readonly', false).removeClass('company-field--locked');
                $innClear.hide();
                $innInput.focus();
            }

            $innInput.on('input', function() {
                if ($innInput.attr('readonly')) return;
                var v = $(this).val().replace(/\D/g, '');
                $(this).val(v);
                renderDropdown(filterCompanies(v));
                if (v) $innDropdown.addClass('visible');
            }).on('focus', function() {
                if ($innInput.attr('readonly')) return;
                var q = $innInput.val();
                renderDropdown(filterCompanies(q));
                $innDropdown.addClass('visible');
            }).on('blur', function() {
                setTimeout(function() { $innDropdown.removeClass('visible'); }, 200);
            });

            $innDropdown.on('click', '.inn-autocomplete__item', function() {
                var $el = $(this);
                selectCompany({
                    inn: $el.data('inn'),
                    name: $el.data('name'),
                    address: $el.data('address'),
                    activity: $el.data('activity'),
                    sait: $el.data('sait')
                });
            });

            $innClear.on('click', clearCompany);

            $(document).on('click', function(e) {
                if (!$(e.target).closest('.inn-autocomplete').length) {
                    $innDropdown.removeClass('visible');
                }
            });
        })();
    </script>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>