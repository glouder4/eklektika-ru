// Применяет readonly/prefilled к полям на основе данных компании
function applyCompanyFieldsState(d) {
    if (!d || typeof d !== 'object') return;
    function hasCompanyVal(key) {
        var v = d[key];
        if (v == null || v === undefined) return false;
        return String(v).trim().length > 0;
    }
    function isCompanyMode() {
        var sel = document.getElementById('order_company');
        return !!(sel && String(sel.value || '').trim().length > 0);
    }
    var form = document.getElementById('order-form');
    ['off_company', 'off_phone', 'off_inn', 'off_email', 'off_requisites'].forEach(function (name) {
        var el = form ? form.querySelector('[name="' + name + '"]') : document.querySelector('[name="' + name + '"]');
        if (el && el.type !== 'file' && el.tagName !== 'SELECT') {
            var hasVal = hasCompanyVal(name);
            // Если бекенд не вернул значение, но поле уже заполнено (например, из PHP префилла),
            // то не считаем его пустым в режиме компании.
            if (!hasVal && isCompanyMode()) {
                var currentVal = String(el.value || '').trim();
                if (currentVal.length > 0) hasVal = true;
            }
            // Эти поля должны оставаться "компанийскими" даже при пустых значениях
            if (!hasVal && isCompanyMode() && (name === 'off_phone' || name === 'off_inn')) {
                hasVal = true;
            }
            if (hasVal) {
                el.setAttribute('readonly', 'readonly');
                el.classList.add('order-field-prefilled');
            } else {
                el.removeAttribute('readonly');
                el.classList.remove('order-field-prefilled');
            }
            el.readOnly = hasVal;
        }
    });
}

// Обновление полей при смене компании
function updateCompanyFields() {
    var sel = document.getElementById('order_company');
    var companyId = sel && sel.value;
    var url = sel && sel.dataset.companyDataUrl;
    if (!companyId || !url) return;
    fetch(url + '?company_id=' + encodeURIComponent(companyId))
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success && data.data) {
                var d = data.data;
                var form = document.getElementById('order-form');
                var phoneEl = form && form.querySelector('[name="off_phone"]');
                var innEl = form && form.querySelector('[name="off_inn"]');
                var emailEl = form && form.querySelector('[name="off_email"]');
                var companyEl = form && form.querySelector('[name="off_company"]');
                var requisitesEl = form && form.querySelector('[name="off_requisites"]');
                var requisitesFileIdEl = document.getElementById('order_company_requisites_file_id');
                // Не присваивать value для file input — только для text/textarea
                if (phoneEl && phoneEl.type !== 'file') {
                    var phone = (d.off_phone == null) ? '' : String(d.off_phone).trim();
                    phoneEl.value = phone;
                }
                if (innEl && innEl.type !== 'file') innEl.value = (d.off_inn == null) ? '' : String(d.off_inn).trim();
                if (emailEl && emailEl.type !== 'file') emailEl.value = d.off_email || '';
                if (companyEl && companyEl.type !== 'file') companyEl.value = d.off_company || '';
                if (requisitesEl && requisitesEl.type !== 'file') requisitesEl.value = d.off_requisites || '';
                if (requisitesFileIdEl) requisitesFileIdEl.value = String(d.requisites_file_id || '');
                applyCompanyFieldsState(d);
                document.querySelectorAll('input[type="file"]').forEach(function (inp) {
                    if (inp.name && inp.name.toLowerCase().indexOf('requisites') !== -1) {
                        var lbl = document.querySelector('label[for="' + inp.id + '"] span');
                        if (lbl) lbl.textContent = (d.requisites_file_id && d.requisites_file_name) ? d.requisites_file_name : 'Выбрать файл';
                        var isDisabled = !!(d.requisites_file_id && d.requisites_file_name);
                        inp.disabled = isDisabled;
                        inp.classList.toggle('order-field-prefilled', isDisabled);
                    }
                });
            }
        })
        .catch(function () {});
}
document.getElementById('order_company')?.addEventListener('change', updateCompanyFields);

// При загрузке — запрашивать данные выбранной компании и применить readonly
function initCompanyFields() {
    var sel = document.getElementById('order_company');
    if (sel && sel.value) updateCompanyFields();
}
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCompanyFields);
} else {
    initCompanyFields();
}

// Маска телефона
document.querySelector('[name="off_phone"]')?.addEventListener('input', function (e) {
    if (e.target && (e.target.readOnly || e.target.classList.contains('order-field-prefilled'))) return;
    let value = e.target.value;

    // Сохраняем позицию курсора
    let cursorPos = e.target.selectionStart;

    // Удаляем всё кроме цифр
    let digits = value.replace(/\D/g, '');

    // Если первая цифра 8 и нет плюса, заменяем на 7 для единого формата
    if (digits.length > 0 && digits[0] === '8') {
        digits = '7' + digits.slice(1);
    }

    // Форматируем
    let formatted = '';
    if (digits.length === 0) {
        formatted = '';
    } else if (digits.length <= 1) {
        formatted = '+' + digits[0];
    } else if (digits.length <= 4) {
        formatted = '+7 (' + digits.slice(1);
    } else if (digits.length <= 7) {
        formatted = '+7 (' + digits.slice(1, 4) + ') ' + digits.slice(4);
    } else if (digits.length <= 9) {
        formatted = '+7 (' + digits.slice(1, 4) + ') ' + digits.slice(4, 7) + '-' + digits.slice(7);
    } else {
        formatted = '+7 (' + digits.slice(1, 4) + ') ' + digits.slice(4, 7) + '-' + digits.slice(7, 9) + '-' + digits.slice(9, 11);
    }
    console.log(formatted)

    e.target.value = formatted;

    // Восстанавливаем позицию курсора
    let newCursorPos = cursorPos + (formatted.length - value.length);
    e.target.setSelectionRange(newCursorPos, newCursorPos);
});

document.getElementById('order-form')?.addEventListener('submit', function (e) {
    e.preventDefault();

    const form = this;
    const submitBtn = form.querySelector('#submit-order');
    if (submitBtn.disabled) return;

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="btn-loader"></span> Оформляем...';

    const formData = new FormData(form);
    const actionUrl = form.dataset.action || window.location.href; // ← берём из data-action

    fetch(actionUrl, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'  // ← добавь эту строку
        }
    })
        .then(response => {
            // Проверяем, что ответ — JSON (а не HTML с ошибкой)
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Сервер вернул не JSON. Возможно, ошибка 500 или редирект.');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect;
            } else {
                alert(data.message || 'Произошла ошибка при оформлении заказа');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Оформить заказ';
            }
        })
        .catch(error => {
            console.error('Ошибка отправки:', error);
            alert('Не удалось отправить заказ. Проверьте соединение или обновите страницу.');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Оформить заказ';
        });
});