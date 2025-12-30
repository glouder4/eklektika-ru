
    var locked = false;
    $(document).on('click', '.change-image-url', function (ev) {
    if( locked) return;
    var id = $(this).data('id');
    var self = this;
    var cacheId = $(self).closest('.product-item').find('.infos').data('cacheid');
    var $productItem = $(self).closest('.product-item').find('.infos').find('.info-in-card[data-id="' + id + '"]')
    var isLoaded = $productItem.hasClass('loaded')
    console.log('isLoaded=' + isLoaded);
    if (isLoaded == true) {
    console.log('returned');
    $(self).closest('.product-item').find('.infos').find('.info-in-card').hide();
    console.log(id)
    $(self).closest('.product-item').find('.infos').find('*[data-id="'+id+'"]').show();

    return;
}
    console.log('id=' + id);
    var tovarId = $(this).data('tovar');
    var tId = $(this).data('tid');
    var url = "ajax_tovar.php";
    console.log(url);
    console.log(tovarId);

    // if($(this).closest('.related-list').length==0) {
    console.log(123);
    locked = true;

    $.ajax({
    type: 'get',
    url: url,
    data: {id: tovarId, cacheId: cacheId, tid: tId},
    success: function (data) {
    // console.log("reponse = "+data);
    var height =   $(self).closest('.product-item').find('.info-in-card.loaded').find('.product-item_fields').css('height');
    $(self).closest('.product-item').find('.infos').find('.info-in-card').hide();


    $productItem.append(data)
    //console.log(data)
    $productItem.addClass('loaded')

    $(self).closest('.product-item').find('.info-in-card.loaded').find('.product-item_fields').css('height',height);

    $(self).closest('.product-item').find('.infos').find('*[data-id="'+id+'"]').show();

    locked=  false
    // $('.info-in-card.loaded').each(function(){

    // var height = $(this).find('.product-item_fields').style.height;
    // console.log(height)
    // $(this).closest('.infos').find('.product-item_fields').attr('height',height)
    // });


}
});
    // }
});