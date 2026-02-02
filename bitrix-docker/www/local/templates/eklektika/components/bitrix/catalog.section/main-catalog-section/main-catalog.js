
var locked = false;
$(document).on('click', '.change-image-url', function (ev) {
    if( locked) return;
    var id = $(this).data('id');
    var self = this;
    var cacheId = $(self).closest('.product-item').find('.infos').data('cacheid');
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

//  меняет цены в корзине на дилерские/розничный когда пользователь авторизировался/разлогинился
function reversePricesInCart(diler){
    console.log('reverseCart');
    var ls =  JSON.parse(window.localStorage.getItem('evoShop_items'));
    console.log(ls);
    lsIsEmpty =ls==null ||  Object.keys(ls).length === 0  ;
    var total = 0;
    console.log(ls);
    for(var key in ls){
        var element = ls[key];
        // console.log(element)
        // console.log(element.pricera)
        // console.log(element.pricedefault)
        var diffprices
        if(element.diffprices) {
            diffprices = JSON.parse(element.diffprices)
            console.log('has diffprices ',diffprices)
            var price = element.price;
            var found=0;
            if(diler==0){
                diffprices.forEach(function (element2) {
                    if (element2.price_d == price) {
                        console.log(element2);
                        element.price = parseFloat(element2.price)
                        element.priceconst = element.pricedefault
                        found=1
                    }
                });
                if(!found){
                    console.log('not found ')
                    if(element.price==element.pricera){
                        element.price = parseFloat(element.pricedefault)
                        element.priceconst = element.pricedefault
                    }
                    else{
                        console.log('error')
                    }
                }
            }
            else {
                diffprices.forEach(function (element2) {
                    if (element2.price == price) {
                        // console.log(element2);
                        element.price = parseFloat(element2.price_d)
                        element.priceconst = element.pricera
                        found = 1
                    }
                });
                if (!found) {
                    console.log('not found ')
                    if (element.price == element.pricera) {
                        element.price = parseFloat(element.pricedefault)
                        element.priceconst = element.pricedefault
                    } else {
                        console.log('error')
                    }
                }
            }
        }
        else{
            if(diler==0){
                console.log('reverse to default prices')
                element.price = parseFloat(element.pricedefault);
                // console.log(element)
            }else{
                console.log('reverse to dillers prices')
                element.price =parseFloat(element.pricera);
                // console.log(element)
            }
        }
    }
    // console.log(ls);
    window.localStorage.setItem('evoShop_items',JSON.stringify(ls));
}
function printcart(){
    var ls =  JSON.parse(window.localStorage.getItem('evoShop_items'));
    // console.log(ls);
    // lsIsEmpty =ls==null ||  Object.keys(ls).length === 0  ;
    //
    //
    // if(!lsIsEmpty){
    //     console.log('active');
    $('#no-active-cart').hide();
    $('#active-cart').show();
    // }else{
    //     console.log('no active')
    //
    //     $('#no-active-cart').show();
    //     $('#active-cart').hide();
    //
    // }
    var n=0;
    var total = 0;
    console.log('printcart2121');
    var  str='';
    // console.log(ls);
    for(var key in ls){
        var element = ls[key];
        n++;
        let t = element.quantity * element.price;
        // ls.forEach(function(element) {
        str+='<div class="product-mini">\n' +
            '            <a href="'+element.url+'" class="product-mini_img"><img src="'+element.image+'" alt=""></a>\n' +
            '            <div class="product-mini_fields">\n' +
            '                <p><span>'+ element.name +'</span></p>\n' +
            '                <p><span>Артикул:</span> '+element.artikul+ '</p>\n' +
            '                <p><span>Тираж:</span> '+ element.quantity +' шт.</p>\n' +
            '                <p><span>Цена:</span> '+ element.price.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$& ') +'</p>\n' +
            //       '                <p><span>Нанесение:</span> 000 000,00</p>\n' +
            '            </div>\n' +
            '            <div class="product-mini_price">\n' +
            t.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$& ') +'<sub></sub>\n' +
            '            </div>\n' +
            '        </div>';
        total+=t;
        // break;
    }
    console.log(2);
    str+='<span class="icon-cart"></span>';
    str+='<span><span style="font-weiht:bold;">'+total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$& ')+'</span>руб.</span>';
    str+='<div class="cart-side-buttons">'+
        '<a href="/cart.php" class="btn btn-blue btn-round">Купить</a>'+
        '</div>';
    console.log($('#scrollbar-cart'));
    $('#scrollbar-cart').html(str);
}
function formatNumber(number) {
    var v = number.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$& ');
    return v.split('.');
}
$(function(){
    reversePricesInCart(parseInt("0"))
    $('#logout-button').on('click', function (ev) {
        ev.preventDefault();
        window.location.href = $(this).attr('href');
    });
    var ls =  JSON.parse(window.localStorage.getItem('evoShop_items'));
    console.log(ls);
    lsIsEmpty =ls==null ||  Object.keys(ls).length === 0  ;
    if(!lsIsEmpty){
        console.log('active');
        $('#no-active-cart').hide();
        $('#active-cart').show();
        printcart();
    }else{
        console.log('no active')
        $('#no-active-cart').show();
        $('#active-cart').hide();
    }
    var n=0;
    var total = 0;

    if(!lsIsEmpty){
        var ls =  JSON.parse(window.localStorage.getItem('evoShop_items'));
        // lsIsEmpty =ls==null ||  Object.keys(ls).length === 0  ;
        var total = 0;
        console.log(ls);
        for(var key in ls){
            var element = ls[key];
            n++;
            let t = parseInt(element.quantity) * parseFloat(element.price);
            // ls.forEach(function(element) {
            total+=t;
        }
        var fp = formatNumber(total)
        $('#cart-menu-btn').html(
            '<span class="cart-icon"><span class="top-cart-count">'+n+'</span></span>'+
            '<span class="summ-cart">'+fp[0]+' р.</span><br><span class="cart-title">Корзина</span>'
        );
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


function updatePrice(){
    return;
}

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
                console.log(data)

                if(data.success){
                    showAddToCartToast(productName, productImage);
                    BX.onCustomEvent('OnBasketChange'); // Обновляем корзину

                    // Анимация добавления товара
                    $($mainButton).addClass('added');
                    showAddToCartToast(productName, productImage);
                    updateBasketPrice();
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