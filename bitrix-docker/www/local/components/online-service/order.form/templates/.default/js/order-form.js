// Применяет readonly/prefilled к полям на основе данных компании
function applyCompanyFieldsState(d) {
    if (!d || typeof d !== 'object') return;
    function hasCompanyVal(key) {
        var v = d[key];
        if (v == null || v === undefined) return false;
        return String(v).trim().length > 0;
    }
    var form = document.getElementById('order-form');
    ['off_company', 'off_phone', 'off_email', 'off_requisites'].forEach(function (name) {
        var el = form ? form.querySelector('[name="' + name + '"]') : document.querySelector('[name="' + name + '"]');
        if (el && el.type !== 'file' && el.tagName !== 'SELECT') {
            var hasVal = hasCompanyVal(name);
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
                var emailEl = form && form.querySelector('[name="off_email"]');
                var companyEl = form && form.querySelector('[name="off_company"]');
                var requisitesEl = form && form.querySelector('[name="off_requisites"]');
                var requisitesFileIdEl = document.getElementById('order_company_requisites_file_id');
                // Не присваивать value для file input — только для text/textarea
                if (phoneEl && phoneEl.type !== 'file') phoneEl.value = d.off_phone || '';
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
    let x = e.target.value.replace(/\D/g, '').match(/(\d{0,1})(\d{0,3})(\d{0,3})(\d{0,2})(\d{0,2})/);
    e.target.value = !x[2] ? x[1] : '+7 (' + x[2] + ') ' + x[3] + (x[4] ? '-' + x[4] : '') + (x[5] ? '-' + x[5] : '');
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