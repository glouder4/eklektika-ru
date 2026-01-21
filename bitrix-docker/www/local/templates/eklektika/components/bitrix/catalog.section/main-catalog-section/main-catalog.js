
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
function updatePrice(){
    // $('#no-active-cart').hide();
    // $('#active-cart').show();
    var n=0;
    console.log('updatePrice');
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
    // if(!lsIsEmpty){
    var fp = formatNumber(total)
    $('#cart-menu-btn').html(
        '<span class="cart-icon"><span class="top-cart-count">'+n+'</span></span>'+
        '<span  class="summ-cart">'+fp[0]+' р.</span><br><span class="cart-title">Корзина</span>'
    );
    // }
    console.log('total='+total);
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

// код добавления товаров в корзину с малой/большой карточки товара
$(function () {
    $(document).on('click', '.global-add', function (e) {
        e.preventDefault();
        
        var $button = $(this);
        var productId = $button.data('product-id');
        var cartAddUrl = $button.data('url');
        var offerId = $button.data('offer-id');
        var quantity = $button.data('quantity') || 1;

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

                if(data.RESPONSE == 'OK'){
                    BX.onCustomEvent('OnBasketChange'); // Обновляем корзину

                    //здесь уже Ваше модальное окно с уведомлением об успешном добавлении
                    alert("Товар успешно добавлен в корзину");
                    ///   modal.style.display = "block";



                }
            })
            .fail(function() {
                //console.log("error");
            })
            .always(function() {
                //console.log("complete");
            });
        
        // Анимация добавления товара
        $button.addClass('added');
        setTimeout(function() {
            $button.removeClass('added');
        }, 1500);
        
        console.log('Товар добавлен в корзину');
    });
});