// Делегирование на document
document.addEventListener('change', function(e) {
    if (!e.target.matches('.item-quantity')) return;

    const input = e.target;
    let qty = parseInt(input.value) || 1;
    if (qty < 1) qty = 1;

    const offerId = input.dataset.offerId;

    // Найдём контейнер корзины
    const cartContainer = document.querySelector('#my_cart');

    // Покажем лоадер
    let loader = cartContainer.querySelector('.cart-loader');
    if (!loader) {
        loader = document.createElement('div');
        loader.className = 'cart-loader';
        cartContainer.style.position = 'relative'; // важно для абсолютного позиционирования
        cartContainer.appendChild(loader);
    }
    loader.classList.add('active');

    fetch('/local/ajax/update_basket.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `offerId=${offerId}&quantity=${qty}&ajax_basket=Y`
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                return updateCartInfo();
            } else {
                throw new Error(data.error);
            }
        })
        .then(data => {
            data = JSON.parse(data);
            // Обновляем ОБА блока
            document.querySelector('#my_cart').innerHTML = data.cart_html;
            document.querySelector('#cart-totals').innerHTML = data.totals_html;
        })
        .catch(err => {
            alert(err.message || 'Ошибка');
            loader.classList.remove('active');
        });


    function updateCartInfo() {
        return fetch('/local/templates/eklektika/components/bitrix/sale.basket.basket/main-basket/ajax-update-template.php', {
            credentials: 'same-origin'
        }).then(res => res.text());
    }
});


document.addEventListener("DOMContentLoaded", function () {
// Удаление товара из корзины
    document.addEventListener('click', async function (e) {
        const button = e.target.closest('.cart-product-remove');
        if (!button || button.classList.contains('disabled')) return;

        const productId = button.dataset.productId;
        if (!productId) return;

        const cartRow = button.closest('.cart-product-row');
        if (!cartRow) return;

        // === ВКЛЮЧАЕМ ЛОАДЕР ===
        button.classList.add('btn-remove-loading', 'disabled');

        try {
            const formData = new FormData();
            formData.append('ajax_basket', 'Y');
            formData.append('action', 'remove');
            formData.append('offerId', productId);

            const response = await fetch('/local/ajax/update_basket.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (result.success && result.action === 'removed') {
                // Плавно удаляем строку
                cartRow.classList.add('removing');
                setTimeout(() => cartRow.remove(), 300);

                // Опционально: обновить итоги
                updateCartTotals();

                // Опционально: уведомление
                if (typeof showAddToCartToast === 'function') {
                    showAddToCartToast('Товар удалён из корзины', '', '❌');
                }
            } else {
                throw new Error(result.error || 'Неизвестная ошибка');
            }

        } catch (error) {
            console.error('Ошибка удаления:', error);
            alert('Не удалось удалить товар. Попробуйте позже.');
            button.classList.remove('btn-remove-loading', 'disabled');
        }
    });

// Функция для обновления итогов (реализуй по своему)
    function updateCartTotals() {
        const rows = document.querySelectorAll('.cart-product-row');
        console.log(rows)
        const totalItems = rows.length;

        // Обновляем общее количество товаров (если есть элемент для этого)
        const totalItemsEl = document.querySelector('.cart-total-count');
        if (totalItemsEl) {
            totalItemsEl.textContent = totalItems;
        }

        // Обновляем общую сумму (пример — если сумма считается вручную)
        let totalSum = 0;
        rows.forEach(row => {
            const sumText = row.querySelector('.cart-product-summ').textContent.trim();
            const sum = parseFloat(sumText.replace(/[^\d,]/g, '').replace(',', '.'));
            if (!isNaN(sum)) totalSum += sum;
        });

        const totalSumEl = document.querySelector('.cart-total-sum');
        if (totalSumEl) {
            totalSumEl.textContent = totalSum.toFixed(2).replace('.', ',') + ' ₽';
        }

        // Если корзина пуста — показываем сообщение
        if (totalItems === 0) {
            //document.querySelector('.cart-empty')?.style.display = 'block';
            //document.querySelector('.cart-items-list')?.style.display = 'none';
        }
    }
});