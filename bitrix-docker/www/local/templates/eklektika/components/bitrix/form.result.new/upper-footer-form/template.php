<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arResult
 */
?>

<form enctype="multipart/form-data" id="footer_form-form" method="POST" novalidate="">
    <div class="field-0">
        <div class="form-head"></div>
    </div>

    <div class="field-1">
        <div></div>

        <div class="field-2">
            <div></div>

            <div class="field-3"> <label for="field-id639159">Контактные данные</label> <input id="field-id639159" name="name" pattern="" placeholder="Имя *" required="" type="text" value="" /> </div>

            <div class="field-4"> <input id="field-id334168" name="email" pattern="^([a-z,A-Z,._,.\-,0-9])+@([a-z,A-Z,._,.\-,0-9])+(\.([a-z,A-Z])+)+$" placeholder="E-mail *" required="" type="text" value="" /> </div>

            <div class="field-5"> <input id="field-id829" name="phone" pattern="^\+?[\d,\-,(,),\s]+$" placeholder="Телефон *" required="" type="text" value="" /> </div>

            <div class="field-6"> </div>
        </div>

        <div class="field-7"> <label for="field-id616691">Сообщение</label> <textarea id="field-id616691" name="message" placeholder="Сообщение"></textarea> </div>

        <div class="field-8"> </div>
    </div>

    <div class="field-9">
        <div></div>

        <div class="field-10 buttonform"> <input type="submit" value="Отправить" /> </div>

        <div class="field-11"> </div>
    </div>

    <div class="field-12">
        <div>
            <br />

            <br />
        </div>
    </div>

    <div class="field-13">
        <div></div>

            <div class="field-14"> <input class="obrabotkap1" name="personal_data" type="checkbox" value="Y" required /> Настоящим подтверждаю, что я ознакомлен и согласен с условиями <a href="/oferta/" >политики конфиденциальности</a></div>

        <div class="field-15"> </div>
    </div>

    <div class="field-16">
        <div></div>
    </div>

    <div class="field-17">
        <div></div>

            <div class="field-18"> <input class="obrabotkap1" name="mailing" type="checkbox" value="Y" /> Я даю <a href="/sogl.docx" > согласие </a> на получение email рассылки, рассылки в мессенджерах и sms с новинками, скидками и специальными предложениями </div>

        <div class="field-19"> </div>
    </div>

    <div class="field-20">
        <div class="error_form"></div>
    </div>
</form>

<script type="text/javascript">
(function() {
    var form = document.getElementById('footer_form-form');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        var errorDiv = form.querySelector('.error_form');
        var submitBtn = form.querySelector('input[type="submit"]');
        var originalValue = submitBtn.value;
        
        // Получаем значения полей напрямую из формы
        var nameInput = form.querySelector('input[name="name"]');
        var emailInput = form.querySelector('input[name="email"]');
        var phoneInput = form.querySelector('input[name="phone"]');
        var messageInput = form.querySelector('textarea[name="message"]');
        var personalDataCheckbox = form.querySelector('input[name="personal_data"]');
        var mailingCheckbox = form.querySelector('input[name="mailing"]');
        
        // Получаем sessid
        var sessid = '';
        if (typeof BX !== 'undefined' && BX.bitrix_sessid) {
            sessid = BX.bitrix_sessid();
        } else if (typeof bitrix_sessid !== 'undefined') {
            sessid = bitrix_sessid();
        } else {
            var sessidInput = document.querySelector('input[name="sessid"]');
            if (sessidInput) sessid = sessidInput.value;
        }
        
        // Создаем FormData для правильной отправки данных
        var formData = new FormData();
        formData.append('WEB_FORM_ID', '1');
        if (sessid) formData.append('sessid', sessid);
        
        // Маппинг полей формы на ID полей вебформы
        if (nameInput) {
            formData.append('form_text_1', nameInput.value || '');
            formData.append('name', nameInput.value || '');
        }
        if (emailInput) {
            formData.append('form_text_2', emailInput.value || '');
            formData.append('email', emailInput.value || '');
        }
        if (phoneInput) {
            formData.append('form_text_3', phoneInput.value || '');
            formData.append('phone', phoneInput.value || '');
        }
        if (messageInput) {
            formData.append('form_textarea_4', messageInput.value || '');
            formData.append('message', messageInput.value || '');
        }
        
        // Для чекбоксов в Bitrix нужны ID вариантов ответов
        // Поле ID = 5: вариант ответа "Да" имеет ID = 5
        // Поле ID = 6: вариант ответа "Да" имеет ID = 6
        // Отправляем как строки, так как Bitrix ожидает строковые значения в массиве
        if (personalDataCheckbox && personalDataCheckbox.checked) {
            formData.append('form_checkbox_5[]', '5'); // ID варианта ответа (строка)
            formData.append('personal_data', 'Y');
        }
        if (mailingCheckbox && mailingCheckbox.checked) {
            formData.append('form_checkbox_6[]', '6'); // ID варианта ответа (строка)
            formData.append('mailing', 'Y');
        }
        
        // Блокируем кнопку
        submitBtn.disabled = true;
        submitBtn.value = 'Отправка...';
        if (errorDiv) errorDiv.innerHTML = '';
        
        // Отправляем AJAX запрос через XMLHttpRequest (надежнее для FormData)
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/local/ajax/form_submit.php', true);
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    var responseData = JSON.parse(xhr.responseText);
                    if (responseData.status === 'success') {
                        // Находим родительский блок #footer_form
                        var footerFormContainer = document.getElementById('footer_form');
                        if (footerFormContainer) {
                            // Заменяем содержимое на сообщение об успехе
                            footerFormContainer.innerHTML = '<div id="footer_formformmessagereport" class="report-message"><div class="form-head">Отправлено</div><div class="error-report"><div class="text-report"><p>Спасибо! Ваше сообщение отправлено.</p><p>Отправить <a href="#" class="repeatform">новое</a> сообщение?</p><p></p></div></div></div>';
                            
                            // Добавляем обработчик для ссылки "новое"
                            var repeatFormLink = footerFormContainer.querySelector('.repeatform');
                            if (repeatFormLink) {
                                repeatFormLink.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    // Перезагружаем страницу для восстановления формы
                                    location.reload();
                                });
                            }
                        } else {
                            // Fallback: если блок не найден, показываем сообщение в errorDiv
                            if (errorDiv) {
                                errorDiv.innerHTML = '<div style="color: green;">Спасибо! Ваше сообщение отправлено.</div>';
                            }
                            form.reset();
                        }
                    } else {
                        if (errorDiv) {
                            var errorMsg = responseData.message || 'Не удалось отправить форму';
                            if (responseData.debug) {
                                errorMsg += ' (Debug: ' + JSON.stringify(responseData.debug) + ')';
                            }
                            errorDiv.innerHTML = '<div style="color: red;">Ошибка: ' + errorMsg + '</div>';
                        }
                    }
                } catch(e) {
                    if (errorDiv) {
                        errorDiv.innerHTML = '<div style="color: red;">Ошибка обработки ответа сервера: ' + e.message + '<br>Ответ: ' + xhr.responseText.substring(0, 200) + '</div>';
                    }
                }
            } else {
                if (errorDiv) {
                    errorDiv.innerHTML = '<div style="color: red;">Ошибка отправки формы (HTTP ' + xhr.status + '). Попробуйте позже.</div>';
                }
            }
            submitBtn.disabled = false;
            submitBtn.value = originalValue;
        };
        
        xhr.onerror = function() {
            if (errorDiv) {
                errorDiv.innerHTML = '<div style="color: red;">Ошибка соединения с сервером.</div>';
            }
            submitBtn.disabled = false;
            submitBtn.value = originalValue;
        };
        
        xhr.send(formData);
    });
})();
</script>
