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