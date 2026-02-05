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

function updateBasketPrice() {
    console.log('updateBasketPrice');

    $.ajax({
        url: "/local/ajax/get_basket_price.php",
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.error) {
                console.error('Ошибка корзины:', response.error);
                return;
            }

            var n = response.count || 0;
            var total = response.total || 0;
            var fp = response.formatted || [0, '00'];

            // Форматируем сумму как "1 234 р." (с пробелами)
            var formattedSum = fp[0].toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ") + ' р.';

            $('#cart-menu-btn').html(
                '<span class="cart-icon"><span class="top-cart-count">' + (n > 0 ? n : '') + '</span></span>' +
                '<span class="summ-cart">' + (n > 0 ? formattedSum : 'Корзина пуста') + '</span><br>' +
                '<span class="cart-title">Корзина</span>'
            );
        },
        error: function(xhr, status, error) {
            console.error('AJAX ошибка при загрузке корзины:', error);
            // Опционально: скрыть корзину или показать ошибку
        }
    });
}

$(document).ready(function(){
    function formatNumber(number) {
        var v = number.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$& ');
        return v.split('.');
    }

    $('.item_quantity').on('input', function (e) {

        var total_price = 0;
        var chosen = parseInt(e.target.value);
        var count = parseInt($(this).closest('.evoShop_shelfItem').find('.item_inventory').text());
        if (chosen > count) {
            $(this).val(count);
            chosen = count;
        }

        // цена
        var price = parseFloat($(this).closest('.evoShop_shelfItem').find('.item_price').text());

        var total = $('#total-sum');
        var newTotal=parseFloat(total.text()||0);


        if($(this).data('val')){
            var p  = parseInt($(this).data('val')) * price;
            newTotal -= p ;
        }
        if(chosen){
            newTotal+= price * chosen;
        }
        total.text(newTotal);
        var formattedPrice = formatNumber(newTotal);
        $('#total-sum-formatted').html(formattedPrice[0]+'<sub>,'+formattedPrice[1]+'</sub>');

        $(this).data('val', chosen||0);

    });

    $(document).on('click', '.global-add', function (e) {
        e.preventDefault();

        var $button = $(this);
        var productId = $button.data('product-id');
        var cartAddUrl = $button.data('url');
        var offerId = $button.data('offer-id');
        var quantity = $($button.closest('form.product-item_tooltip').find('input.item_quantity')).val() || 1;

        var productImage = $button.data('product-image');
        var productName = $button.data('product-name');


        const originalText = $button[0].innerHTML.trim();
        const form = $button.closest('form'); // если нужно отправить форму

        // === ВКЛЮЧАЕМ ЛОАДЕР ===
        $button[0].disabled = true;
        $button[0].innerHTML = '<span class="btn-loader"></span>Добавляем...';

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
                    $($button).addClass('added');
                    showAddToCartToast(productName, productImage);
                    updateBasketPrice();
                    setTimeout(function() {
                        $($button).removeClass('added');
                    }, 1500);

                    console.log('Товар добавлен в корзину');
                }
            })
            .fail(function() {
                console.log("error");
            })
            .always(function() {
                // === ВОССТАНАВЛИВАЕМ КНОПКУ ===
                $button[0].disabled = false;
                $button[0].innerHTML = originalText;
            });

    });
});