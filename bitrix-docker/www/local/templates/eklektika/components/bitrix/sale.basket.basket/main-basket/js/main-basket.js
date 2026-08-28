
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

    function getCartLoader() {
        const cartContainer = document.querySelector('#my_cart');
        if (!cartContainer) {
            return null;
        }

        let loader = cartContainer.querySelector('.cart-loader');
        if (!loader) {
            loader = document.createElement('div');
            loader.className = 'cart-loader';
            cartContainer.style.position = 'relative';
            cartContainer.appendChild(loader);
        }

        return loader;
    }

    function updateCartInfo() {
        return fetch('/local/templates/eklektika/components/bitrix/sale.basket.basket/main-basket/ajax-update-template.php', {
            credentials: 'same-origin'
        }).then(function (res) {
            return res.json();
        });
    }

    function applyCartRefresh(data) {
        const cartNode = document.querySelector('#my_cart');
        const totalsNode = document.querySelector('#cart-totals');

        if (cartNode && typeof data.cart_html === 'string') {
            cartNode.innerHTML = data.cart_html;
            initCartNanesenie(cartNode);
        }

        if (totalsNode && typeof data.totals_html === 'string') {
            totalsNode.innerHTML = data.totals_html;
        }

        updateCartTotals();

        if (typeof BX !== 'undefined' && BX.onCustomEvent) {
            BX.onCustomEvent('OnBasketChange');
        }
        if (typeof refreshMiniCart === 'function') {
            refreshMiniCart();
        }
        if (typeof updateBasketPrice === 'function') {
            updateBasketPrice();
        }
    }

    function refreshCartUi() {
        return updateCartInfo().then(applyCartRefresh);
    }

    initCartNanesenie(document.querySelector('#my_cart'));

    document.addEventListener('change', function(e) {
        if (!e.target.matches('.item-quantity')) return;

        const input = e.target;
        let qty = parseInt(input.value) || 1;
        if (qty < 1) qty = 1;

        const offerId = input.dataset.offerId;
        const loader = getCartLoader();
        if (loader) {
            loader.classList.add('active');
        }

        fetch('/local/ajax/update_basket.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: buildUpdateBasketBody(offerId, qty, getNanesenieValuesByOffer(offerId))
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) {
                    throw new Error(data.error);
                }
                return refreshCartUi();
            })
            .catch(function (err) {
                alert(err.message || 'Ошибка');
            })
            .finally(function () {
                if (loader) {
                    loader.classList.remove('active');
                }
            });
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
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) {
                    throw new Error(data.error || 'Ошибка обновления нанесения');
                }
            })
            .catch(function (err) {
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
        const loader = getCartLoader();
        if (loader) {
            loader.classList.add('active');
        }

        try {
            const formData = new FormData();
            formData.append('ajax_basket', 'Y');
            formData.append('action', 'remove');
            formData.append('offerId', productId);

            const response = await fetch('/local/ajax/update_basket.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (!(result.success && result.action === 'removed')) {
                throw new Error(result.error || 'Неизвестная ошибка');
            }

            await refreshCartUi();

            if (typeof showAddToCartToast === 'function') {
                showAddToCartToast('Товар удалён из корзины', '', '❌');
            }
        } catch (error) {
            console.error('Ошибка удаления:', error);
            alert('Не удалось удалить товар. Попробуйте позже.');
            button.classList.remove('btn-remove-loading', 'disabled');
        } finally {
            if (loader) {
                loader.classList.remove('active');
            }
        }
    });

    function updateCartTotals() {
        const totalsContainer = document.querySelector('#shopCart');
        if (!totalsContainer) {
            return;
        }

        const totalValue = parseFloat($(totalsContainer).data('total-sum')) || 0;
        let minOrderSum = parseFloat($(totalsContainer).data('min-order-sum'));
        if (!minOrderSum || minOrderSum <= 0) {
            minOrderSum = 50000;
        }

        if (totalValue >= minOrderSum) {
            $('#order-block').show();
            $('#order-block-minprice').hide();
        } else {
            $('#order-block').hide();
            $('#order-block-minprice').show();
        }
    }

});
