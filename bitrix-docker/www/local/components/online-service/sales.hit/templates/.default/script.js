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