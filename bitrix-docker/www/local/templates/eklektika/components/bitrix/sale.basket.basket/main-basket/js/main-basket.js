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