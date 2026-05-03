<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
?>
<script src="/ds-comf/ds-form/js/jquery.mask.min.js"></script>
<script>
    $('#mobilephone').mask('+9 (999) 999-99-99');
    $('#phone').mask('+9 (999) 999-99-99');
</script>
<script type="text/javascript">
    (function () {
        var $form = $('form[name="perosnal-profile-form"]');

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
            name: {required: true, msg: 'Укажите имя'},
            lastname: {required: true, msg: 'Укажите фамилию'},
            phone: {
                required: true,
                msg: 'Укажите телефон',
                validate: function (val) {
                    if (!val || val.trim() === '') return 'Укажите телефон';
                    var digits = val.replace(/\D/g, '');
                    if (digits.length < 10) return 'Телефон должен содержать минимум 10 цифр';
                    return null;
                }
            },
            email: {
                required: false,
                validate: function (val) {
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

            $form.find('input[required], input[name="phone"], input[name="email"]').each(function () {
                var $el = $(this);
                var name = $el.attr('name');
                var val = $el.val();
                if (typeof val === 'string') val = val.trim();
                var rule = rules[name];
                if (rule && rule.required && (!val || val === '')) {
                    showError($el, rule.msg || 'Заполните поле');
                    errors.push(rule.msg || 'Заполните поле');
                } else if (rule && rule.validate && val) {
                    var err = rule.validate(val);
                    if (err) {
                        showError($el, err);
                        errors.push(err);
                    }
                }
            });

            var $summary = $form.find('.validation-summary');
            if (!$summary.length) {
                $summary = $('<div class="validation-summary"><strong>Пожалуйста, исправьте ошибки:</strong><ul></ul></div>');
                $form.prepend($summary);
            }
            if (errors.length) {
                $summary.find('ul').html(errors.map(function (e) {
                    return '<li>' + e + '</li>';
                }).join(''));
                $summary.addClass('visible');
                $('html, body').animate({scrollTop: $form.offset().top - 20}, 300);
                return false;
            }
            $summary.removeClass('visible');
            return true;
        }

        var $saveBtn = $('#save-form');
        var $saveLabel = $saveBtn.find('.reg-form__submit-label');
        var $saveLoader = $saveBtn.find('.reg-form__submit-loader');
        var saveDefaultLabel = ($saveBtn.data('defaultLabel') || $saveLabel.text() || 'Сохранить').toString();

        function setProfileSaveLoading(active) {
            if (active) {
                $saveBtn.prop('disabled', true).addClass('is-loading').attr('aria-busy', 'true');
                $saveLoader.attr('aria-hidden', 'false');
            } else {
                $saveBtn.prop('disabled', false).removeClass('is-loading').attr('aria-busy', 'false');
                $saveLabel.text(saveDefaultLabel);
                $saveLoader.attr('aria-hidden', 'true');
            }
        }

        $(document).on('click', '#save-form', function (e) {
            e.preventDefault();
            if ($saveBtn.prop('disabled')) return;
            if (!validateForm()) return;

            setProfileSaveLoading(true);

            $.ajax({
                url: '/personal/ajax/ajax-edit-company.php',
                method: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        alert('Профиль успешно обновлён!');
                    } else {
                        alert('Ошибка: ' + (response.error || 'Неизвестная ошибка'));
                    }
                },
                error: function (xhr) {
                    var errorMsg = 'Сетевая ошибка';
                    try {
                        var resp = JSON.parse(xhr.responseText);
                        errorMsg = resp.error || 'Ошибка сервера';
                    } catch (err) {
                    }
                    alert('Не удалось обновить профиль: ' + errorMsg);
                },
                complete: function () {
                    setProfileSaveLoading(false);
                }
            });
        });
    })();
</script>
