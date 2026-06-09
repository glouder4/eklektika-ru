 
document.addEventListener("DOMContentLoaded", function () {

    function getNanesenieValuesByOffer(offerId) {
        const container = document.querySelector(`.item_nanesenie_chek[data-offer-id="${offerId}"]`);
        if (window.EklektikaNanesenie) {
            return window.EklektikaNanesenie.getContainerValues(container);
        }
        return ['Без нанесения'];
    }

    function buildUpdateBasketBody(offerId, qty, nanesenieValues) {
        const parts = [
            `offerId=${encodeURIComponent(offerId)}`,
            `quantity=${encodeURIComponent(qty)}`,
            `ajax_basket=Y`
        ];

        if (window.EklektikaNanesenie) {
            window.EklektikaNanesenie.appendValuesToBody(nanesenieValues, parts);
        } else {
            parts.push(`nanesenie[]=${encodeURIComponent('Без нанесения')}`);
        }

        return parts.join('&');
    }

    function initCartNanesenie(root) {
        if (window.EklektikaNanesenie) {
            window.EklektikaNanesenie.init(root || document);
        }
    }

    initCartNanesenie(document.querySelector('#my_cart'));

    document.addEventListener('change', function(e) {
        if (!e.target.matches('.item-quantity')) return;

        const input = e.target;
        let qty = parseInt(input.value) || 1;
        if (qty < 1) qty = 1;

        const offerId = input.dataset.offerId;
        const cartContainer = document.querySelector('#my_cart');
        let loader = cartContainer.querySelector('.cart-loader');
        if (!loader) {
            loader = document.createElement('div');
            loader.className = 'cart-loader';
            cartContainer.style.position = 'relative';
            cartContainer.appendChild(loader);
        }
        loader.classList.add('active');

        fetch('/local/ajax/update_basket.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: buildUpdateBasketBody(offerId, qty, getNanesenieValuesByOffer(offerId))
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
                document.querySelector('#my_cart').innerHTML = data.cart_html;
                document.querySelector('#cart-totals').innerHTML = data.totals_html;
                initCartNanesenie(document.querySelector('#my_cart'));
                updateCartTotals();
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

    document.addEventListener('change', function(e) {
        if (!e.target.matches('.item_nanesenie_chek .item_nanesenie-option')) return;

        const container = e.target.closest('.item_nanesenie_chek');
        if (!container) return;

        const offerId = container.dataset.offerId;
        if (!offerId) return;

        const nanesenieValues = window.EklektikaNanesenie
            ? window.EklektikaNanesenie.getContainerValues(container)
            : ['Без нанесения'];
        const qtyInput = document.querySelector(`.item-quantity[data-offer-id="${offerId}"]`);
        const qty = qtyInput ? (parseInt(qtyInput.value) || 1) : 1;

        fetch('/local/ajax/update_basket.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: buildUpdateBasketBody(offerId, qty, nanesenieValues)
        })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.error || 'Ошибка обновления нанесения');
                }
            })
            .catch(err => {
                alert(err.message || 'Ошибка');
            });
    });


// Удаление товара из корзины
    document.addEventListener('click', async function (e) {
        const button = e.target.closest('.cart-product-remove');
        if (!button || button.classList.contains('disabled')) return;

        const productId = button.dataset.productId;
        if (!productId) return;

        const cartRow = button.closest('.cart-product-row');
        if (!cartRow) return;

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
                cartRow.classList.add('removing');
                setTimeout(() => cartRow.remove(), 300);
                updateCartTotals();

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

    function updateCartTotals() {
        let totalsContainer = document.querySelector('#shopCart');
        let totalValue = $(totalsContainer).data('total-sum');

        if( parseFloat(totalValue) >= 5000 ){
            $('#order-block').show();
            $('#order-block-minprice').hide();
        }
        else{
            $('#order-block').hide();
            $('#order-block-minprice').show();
        }
    }

});
