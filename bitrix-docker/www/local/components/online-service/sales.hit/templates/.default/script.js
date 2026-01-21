$(document).on('click', '.product-item_gallery .change-image-url', function (ev) {
    var id = $(this).data('id');
    var self = this;
    var $productItem = $(self).closest('.product-item').find('.infos').find('.info-in-card[data-id="' + id + '"]')

    $(self).closest('.product-item').find('.infos').find('.info-in-card').hide();
    $productItem.show();

    let discountPercent = $productItem.data('discount-percent');
    if( parseFloat(discountPercent) > 0 ){
        $(self).closest('.product-item').find('.sale-size').show();
        $(self).closest('.product-item').find('.label-sale').show();
        $(self).closest('.product-item').find('.sale-size')[0].innerHTML = '-'+discountPercent + '<sub>%</sub>';
    }
    else{
        $(self).closest('.product-item').find('.sale-size').hide();
        $(self).closest('.product-item').find('.label-sale').hide();
    }

});

document.addEventListener('DOMContentLoaded', function() {
    // Контейнер уведомлений (создаём, если ещё не существует)
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }

    // Храним ID последних уведомлений, чтобы избежать дублей (опционально)
    const shownProducts = new Set();

    window.showAddToCartToast = function (productName, imageUrl, options = {}) {
        const duration = options.duration || 5000; // по умолчанию 5 сек
        const preventDuplicates = options.preventDuplicates !== false; // true по умолчанию

        // Опционально: избегаем дублей за 2 секунды
        if (preventDuplicates) {
            const key = productName + '|' + imageUrl;
            if (shownProducts.has(key)) return;
            shownProducts.add(key);
            setTimeout(() => shownProducts.delete(key), 2000);
        }

        // Создаём элемент уведомления
        const toast = document.createElement('div');
        toast.className = 'toast';

        // Изображение
        const img = document.createElement('img');
        img.className = 'product-image';
        img.src = imageUrl || '/local/templates/eklektika/components/bitrix/catalog.section/main-catalog-section/images/no_photo.png';
        img.alt = productName;
        img.onerror = () => {
            img.src = '/local/templates/eklektika/components/bitrix/catalog.section/main-catalog-section/images/no_photo.png';
        };

        // Сообщение
        const message = document.createElement('div');
        message.className = 'message';
        message.innerHTML = `<strong>${escapeHtml(productName)}</strong><br>добавлен в корзину!`;

        // Кнопка закрытия
        const closeBtn = document.createElement('button');
        closeBtn.className = 'close-btn';
        closeBtn.innerHTML = '&times;';
        closeBtn.setAttribute('aria-label', 'Закрыть уведомление');

        // Обработчик закрытия
        const removeToast = () => {
            toast.classList.remove('show');
            setTimeout(() => {
                if (toast.parentNode === container) {
                    container.removeChild(toast);
                }
            }, 300); // совпадает с длительностью CSS-анимации
        };

        closeBtn.addEventListener('click', removeToast);

        // Собираем всё вместе
        toast.appendChild(img);
        toast.appendChild(message);
        toast.appendChild(closeBtn);

        // Добавляем в DOM
        container.appendChild(toast);

        // Запускаем анимацию появления
        setTimeout(() => toast.classList.add('show'), 10);

        // Автоматическое удаление
        const autoHideTimer = setTimeout(removeToast, duration);

        // При наведении — останавливаем таймер (опционально, но удобно)
        toast.addEventListener('mouseenter', () => clearTimeout(autoHideTimer));
        toast.addEventListener('mouseleave', () =>
            setTimeout(removeToast, 2000) // даём 2 сек после ухода курсора
        );
    };

    // Вспомогательная функция для экранирования HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});

// код добавления товаров в корзину с малой/большой карточки товара
$(function () {
    $(document).on('click', '.global-add', function (e) {
        e.preventDefault();

        var $button = $(this);
        var productId = $button.data('product-id');
        var cartAddUrl = $button.data('url');
        var offerId = $button.data('offer-id');
        var quantity = $($button.closest('form.product-item_tooltip').find('input.item_quantity')).val() || 1;

        var productImage = $button.data('product-image');
        var productName = $button.data('product-name');


        $mainButton = $button.closest('.product-item_buttons').find('.btn-to-cart-small')[0];
        const originalText = $mainButton.innerHTML.trim();
        const form = $mainButton.closest('form'); // если нужно отправить форму

        // === ВКЛЮЧАЕМ ЛОАДЕР ===
        $mainButton.disabled = true;
        $mainButton.innerHTML = '<span class="btn-loader"></span>Добавляем...';

        $.ajax({
            url:cartAddUrl,
            type: 'POST',
            data: {
                'productId': productId,
                'offerId': offerId,
                'quantity': quantity,
                'ajax_basket': 'Y'
            },
        })
            .done(function(data) {
                var data = $.parseJSON(data);

                if(data.success){
                    showAddToCartToast(productName, productImage);
                    BX.onCustomEvent('OnBasketChange'); // Обновляем корзину

                    // Анимация добавления товара
                    $($mainButton).addClass('added');
                    showAddToCartToast(productName, productImage);
                    setTimeout(function() {
                        $($mainButton).removeClass('added');
                    }, 1500);

                    console.log('Товар добавлен в корзину');
                }
            })
            .fail(function() {
                console.log("error");
            })
            .always(function() {
                // === ВОССТАНАВЛИВАЕМ КНОПКУ ===
                $mainButton.disabled = false;
                $mainButton.innerHTML = originalText;
            });

    });
});