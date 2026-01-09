$(function() {
    /* scrollbar styler
    -------------------------------------------------------*/
    $('.scrollbar-inner').scrollbar();

    /* side banner slider
    ------------------------------------------------------ */
    if($('.swiper-side').length){

        var autoplayDelay = $('.swiper-side').data('autoplay') || 5000;

        var sideSwiper = new Swiper('.swiper-side', {
            slidesPerView: 1,
            spaceBetween: 30,
            loop: true,
            preloadImages: false,
            lazy: true,
            autoplay: {
                delay: autoplayDelay,
            },
            pagination: {
                el: '.side-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.side-button-next',
                prevEl: '.side-button-prev',
            },
        });

    }


    /* side banner slider
    ------------------------------------------------------ */

    $('.swiper-content-slider').each(function () {
        // console.log('-----slider ----');
        // console.log( $(this));
        // console.log( $(this).next());
        // console.log( $(this).next().find('.content-button-next'));
        //
        //     if ($(this).find('.gallery-top').length) {
        let contentSwiper = new Swiper($(this), {

            // var galleryTop = new Swiper($(this).find('.gallery-top').get(0), {
            //     spaceBetween: 10,
            //     navigation: {
            //         nextEl: $(this).find('.swiper-button-next').get(0),  //'.swiper-button-next',
            //         prevEl: $(this).find('.swiper-button-prev').get(0) //'.swiper-button-prev',


            slidesPerView: 1,
            spaceBetween: 135,
            preloadImages: false,
            lazy: true,
            navigation: {
                nextEl:  $(this).next().find('.content-button-next').get(0) ,//'.content-button-next',
                prevEl:  $(this).next().find('.content-button-prev').get(0) //'.content-button-prev',
            },
            breakpoints: {
                480: {
                    spaceBetween: 0,
                    slidesPerView: 1
                },
                640: {
                    spaceBetween: 135
                }
            }
        });
    });


    /* top search actions
    ------------------------------------------------------ */
    $('.search input').focus( function() {
        $('body').addClass('search-active');
        $('body').removeClass('open-catalog');
    });


    // BEGIN   26.12.2018
    $('.search input').blur( function() {
        var search = $(this).val();
        if(search.length < 3){
            $('body').removeClass('search-active');
        }
    });
    // END   26.12.2018

		$('body').mousedown(function(event){
    //event.preventDefault();
    if(event.button == 2){
      $('body').removeClass('search-results-active search-active'); 
    } 
});
jQuery(function($){
	$(document).mouseup( function(e){ 
		var div = $( ".search-head-wrap" ); 
		if ( !div.is(e.target) 
		    && div.has(e.target).length === 0 ) {
                $('body').removeClass('search-results-active search-active'); 
		}
	});
});
 

    $('.search input[name="search"]').keydown( function(e) {
        var search = $(this).val();
        if(search.length > 2){
            $('body').addClass('search-results-active');
        }else{
            $('body').removeClass('search-results-active');
        }
        if (e.which == 27||e.which == 3) $('body').removeClass('search-results-active search-active');
    });


    $('.btn-show-search').on('click', function(){
        $('body').toggleClass('show-search');
        $('.search input[name="search"]').focus();
        $('body').removeClass('open-catalog');
        return false;
    });



    /* show/hide catalog
    ------------------------------------------------------ */
    $('.btn-menu-catalog').on('click', function(){
		        if($(window).width() < 992) {
        $('html').animate({scrollTop:120},300);
        $('body').toggleClass('open-catalog');

        $('.catalog-cats img').lazyLoadXT({show: true});
				return false;}
    });

    /*
    $('.menu-catalog-cats').delegate('.arrow-submenu, .has-childs .catalog-cat-title','click',  function(e){
        $(this).closest('.catalog-cat').addClass('active');
        if($(window).outerWidth()<767){
            e.preventDefault();
            return false;
        }
    })

    $('.arrow-submenu, .has-childs .catalog-cat-title').on('click', function(e){
        
    });
   


    if($('.menu-catalog-cats').length){
        $('.menu-catalog-cats .catalog-cat ul').each(function(){
            var buttonBack = '<button class="menu-back">назад</button>';
            $(this).append(buttonBack);
        //    $(this).closest('.catalog-cat').addClass('has-childs');
        });
    } */

    $('.menu-back').on('click', function(){
        $(this).closest('.catalog-cat').removeClass('active');
    });


    /* button to top
    ------------------------------------------------------ */
    var mode = (window.opera) ? ((document.compatMode == "CSS1Compat") ? $('html') : $('body')) : $('html,body');
    $('.btn-to-top').click(function(){
        mode.animate({scrollTop:0},800);
        return false;
    });

/*    var menu_offset = $('.top-panel').offset().top;

    $(window).scroll(function(){
        if ($(window).scrollTop() > 500) {
            $('.btn-to-top').addClass('active');
        }
        else {
            $('.btn-to-top').removeClass('active');
        }

        if ($(window).scrollTop() > menu_offset-10) {
            $('body').addClass('nav-fixed');
        }
        else {
            $('body').removeClass('nav-fixed');
        }
    });
*/
    /* tabs
    ------------------------------------------------------ */
    $('ul.tabs__caption').on('click', 'li:not(.active)', function() {
        $(this)
            .addClass('active').siblings().removeClass('active')
            .closest('div.tabs').find('div.tabs__content').removeClass('active').eq($(this).index()).addClass('active');
    });

    $('ul.tabs__caption li:first-child').click();



    /* enable gallery in product
    ------------------------------------------------------ */
    $('.product-gallery').each(function () {

        if ($(this).find('.gallery-top').length) {
            var galleryTop = new Swiper($(this).find('.gallery-top').get(0), {
                spaceBetween: 10,
                navigation: {
                    nextEl: $(this).find('.swiper-button-next').get(0),  //'.swiper-button-next',
                    prevEl: $(this).find('.swiper-button-prev').get(0) //'.swiper-button-prev',
                }
            });

            var galleryThumbs = new Swiper($(this).find('.gallery-thumbs').get(0), {
                spaceBetween: 10,
                centeredSlides: true,
                slidesPerView: 1000,
                touchRatio: 0.2,
                slideToClickedSlide: true,
                on: {
                    init: function () {

                        // /  console.log('123');
                        console.log(this.imagesLoaded);
                        if (this.imagesLoaded < 2) {
                            // console.log('222');
                            //console.log(this);
                            //TODO hide thumbs
                            $(this).hide();
                        }
                    },
                }
            });

            galleryTop.controller.control = galleryThumbs;
            galleryThumbs.controller.control = galleryTop;


        }
    });


    $('.product-item_title').matchHeight({
        byRow: 0
    });


    // $('.product-item_title').each(function(){
    //     $(this).matchHeight({
    //             byRow: 0
    //          });
    //     //     if($(this).val() > 0){
    //     //         has_value = true;
    //     //     }
    //     });

    // each(function(){
    //     if($(this).val() > 0){
    //         has_value = true;
    //     }
    // });

    $('.product-item_fields').matchHeight();
    // $('.product-item_fields').each(function(){
    //     $(this).matchHeight({
    //         byRow: 0
    //     });
    //     //     if($(this).val() > 0){
    //     //         has_value = true;
    //     //     }
    // });

    /*$('.product-list .product-item, .product-list>*').matchHeight({
        property: 'min-height'
    });*/

    $('.related-list .row>*').matchHeight({
        property: 'min-height',
        byRow: false
    });



    /* gallery fix
    ------------------------------------------------------ */
    if($('.product-gallery').length){

        var gallery =  $('.product-gallery');
        var product = $('.product-data');
        var gallery_offset = gallery.offset().top - 110;
        var max_height = product.outerHeight() + 150;

        function winScroll(max_height){
            $(window).scroll(function(){
                var scroll = $(document).scrollTop();

                if(scroll > gallery_offset && scroll < max_height){
                    gallery.addClass('gallery-fix');
                }else{
                    gallery.removeClass('gallery-fix');
                }
            });
        }

        winScroll(max_height);

        $('.product-tabs li').on('click', function(){

            setTimeout(function(){
                var max_height = product.outerHeight() + 150;
                winScroll(max_height);
            },10);

        });
    }


    /* 
    ------------------------------------------------------ */
    // focus in
    $(document).on('focus', '.input-count',function(){
        $(this).closest('.count-block').addClass('active');
    });

    // focus out
    $('.input-count').on('blur', function(){
        var count = $(this).val();
        var has_value = false;

        var parent = $(this).closest('.count-block');

        $('.input-count', parent).each(function(){
            if($(this).val() > 0){
                has_value = true;
            }
        });

        if(!has_value){
            $(this).closest('.count-block').removeClass('active');
        }
    });


    // 
    $(document).on('keyup', '.input-count',function(e){

        var has_value = false;
        var parent = $(this).closest('.count-block');

        $('.input-count', parent).each(function(){
            if($(this).val() > 0){
                has_value = true;
            }
        });

        if(has_value){
            $(this).closest('.count-block').find('.btn-cart').attr('disabled', false);
            $(this).closest('.count-block').find('.product-button-cart').addClass('show-total');
            $('.quantity-title').addClass('quantity-title-white');
            $(this).closest('.count-block').find('.product-price-total').slideDown();
            recalcPrice();
        }else{
            $(this).closest('.count-block').find('.btn-cart').attr('disabled', true);
            $('.product-button-cart').removeClass('show-total');
            $('.quantity-title').removeClass('quantity-title-white');
            $(this).closest('.count-block').find('.product-price-total').slideUp();
        }

    });


    // validation for numbers
    $(document).on('keypress','.input-number', function (e) {

        //console.log(e.target.value)
        // if (!e.target.value.includes('_')) {
        //     $(this).attr('required', false)
        // }
        // else {
        //     $(this).attr('required', true)
        // }

        var charCode = (e.which) ? e.which : event.keyCode

        if (charCode > 31 && (charCode < 48 || charCode > 57))
            return false;

        return true;
    });





    function recalcPrice(){
        // set new price

        return ;
    }


    /* input required
    ------------------------------------------------------*/
    $('.product-button-cart .btn').on('click', function(){
        var has_value = false;
        var parent = $(this).closest('.count-block');

        $('.input-count', parent).each(function(){
            if($(this).val() > 0){
                has_value = true;
            }
        });

        if(has_value){
            $(this).closest('form').submit();
        }else{
            $(this).addClass('error').delay(1500).queue(function(){
                $(this).removeClass("error").dequeue();
            });
        }

        return false;
    });


    /* fancybox
    ------------------------------------------------------*/
    $('.fancybox-gallery').fancybox({
        thumbs : {
            autoStart : true,
        },
        baseClass: 'bg-white'
    });

    $('.fancybox').fancybox(
        {
         autoFocus: false,   
        }
    );


    /*
    ------------------------------------------------------ */
    $(document).on('click','.product-item_gallery a', function () {
        var img = $(this).attr('href');
        $(this).closest('.product-item').find('.product-item_img img').attr('src', img);

        // infos
        // var img = $(this).attr('href');

        var id1 = $(this).data('id');
        console.log('id1=' + id1);

        // console.log($(this).closest('.product-item').find('.infos').find('*[data-id="'+id1+'"]'));

        // $(this).closest('.product-item').find('.infos').find('.info-in-card').hide();

         // $(this).closest('.product-item').find('.infos').find('*[data-id="'+id1+'"]').show();
         console.log( $(this).data('link'));

         var imgMain = $(this).closest('.product-item').find('.changed-url');
        imgMain.attr('href', $(this).data('link'));

        return false;

    });


    $(document).on('click', '#big-card-switcher a',function () {

        var id1 = $(this).data('id');
        console.log('id1=' + id1);

        // console.log($(this).closest('.product-item').find('.infos').find('*[data-id="'+id1+'"]'));

        $(this).closest('.product-item').find('.infos').find('.info-in-card').hide();

        $(this).closest('.product-item').find('.infos').find('*[data-id="'+id1+'"]').show();

        return false;

    });




    $('.btn-toggle-gallery').on('click', function(){
        $(this).closest('.product-item_gallery').toggleClass('active');
    });



    /* svg img -> inline svg
    ------------------------------------------------------*/
    $('img.to-svg').filter(function() {
        return this.src.match(/.*\.svg$/);
    }).each(function(){
        var $img = $(this);
        var imgID = $img.attr('id');
        var imgClass = $img.attr('class');
        var imgURL = $img.attr('src');

        $.get(imgURL, function(data) {
            var $svg = $(data).find('svg');

            if(typeof imgID !== 'undefined') {
                $svg = $svg.attr('id', imgID);
            }

            if(typeof imgClass !== 'undefined') {
                $svg = $svg.attr('class', imgClass+' replaced-svg');
            }

            $svg = $svg.removeAttr('xmlns:a');
            $img.replaceWith($svg);

        }, 'xml');
    });




    /* product item cart: show count block
    ------------------------------------------------------ */

    $(document).on('click','.btn-to-cart-small' ,function(){
        $(this).closest('.button-cart').toggleClass('show');
        $('body').toggleClass('show-tooltip');
    });



    // hide tooltip cart on body click
    $(document).mouseup(function (e) {
        var container = $(".product-item_tooltip");

        if (container.has(e.target).length === 0){
            container.removeClass('active');
            $('.btn-cart').removeClass('show');
        }

        if ($(".info-tooltip").has(e.target).length === 0){
            $(".side-menu li").removeClass('show');
        }

        if ($(".product-item_tooltip").has(e.target).length === 0){
            $(".button-cart").removeClass('show');
            $('body').removeClass('show-tooltip');
        }

        if ($(".select-drop_list").has(e.target).length === 0){
            $(".select-drop_list").hide();
        }
    });


    // show/hide side cart
    $(document).on('click','.btn-cart',function(){
        $('.button-cart').removeClass('show');
        $(".side-menu li").removeClass('show');

        // не нужно открывать всплывающее окно 19.11.2019 
        //$.fancybox.open({
        //    src  : '#in-cart'
        //});

        return false;
    });

    $('.show-tooltip').on('click', function(){
        $(this).parent().toggleClass('show');

        return false;
    });

    $('.hide-tooltip').on('click', function(){
        $(this).closest('li').removeClass('show');
    });


    $('.price-info').on('click', function(){
        $(this).find('.product-tooltip').toggle();
    })




    /* red placeholder
    ------------------------------------------------------ */
    $('.placeholder').click(function() {
        $(this).siblings('input').focus();
    });

    $('.form-control').focus(function() {
        $(this).siblings('.placeholder').hide();
    });

    $('.form-control').blur(function() {
        var $this = $(this);
        if ($this.val().length == 0)
            $(this).siblings('.placeholder').show();
    });

    $('.form-control').blur();



    /* show-hide cats
    ------------------------------------------------------ */
    var max_li = 12;

    $('.cats li').each(function(){
        if($(this).index() > max_li){
            $(this).hide();
        }
    });

    $('.btn-show-cats').on('click', function(){
        $('.cats').fadeOut().delay(200).queue(function(){
            $('.cats').fadeIn({
                start: function () {
                    $(this).css({
                        display: "flex"
                    })
                }
            }).dequeue();
            $(this).closest('.cats-menu-filter').toggleClass('show')
        });
    });



    /* select styler
    ------------------------------------------------------ */
    $(".chosen-select").chosen({
        'disable_search': true,
        'disable_search_threshold': 100,
        //'width': '100%'
    });

    $(document).on("mouseenter", ".search-choice-close", function() {
        $(this).closest('li').addClass('remove-hover');
    });

    $(document).on("mouseleave", ".search-choice-close", function() {
        $(this).closest('li').removeClass('remove-hover');
    });



    /* on color select
    ------------------------------------------------------ */
    $(document).on('change','.color-row .chosen-color', function(evt, params) {

        if($(this).val() != ''){
            $(this).closest('.input-field').addClass('show-count');
        }else{
            $(this).closest('.input-field').removeClass('show-count');
        }

    });

    var num = 0;
    $('.add-color').on('click', function(){

        num++;

        var $colorBlock = $('.color-row:last').clone();
        $colorBlock.find('.chosen-container').remove();
        $colorBlock.find('.input-field').removeClass('show-count');
        $colorBlock.find('select').attr('name', 'color-'+num);
        $('.color-rows').append($colorBlock);

        $('.color-rows select').each(function(){
            $(this).chosen({
                'disable_search': true,
                'disable_search_threshold': 100,
                'width': '100%'
            });
        });

        return false;
    });


    $(document).delegate('.btn-remove-color', 'click', function() {
        var $parent = $(this).closest('.input-field');
        $parent.find('select').val('').change().trigger('chosen:updated');
    });





    /* select price
    ------------------------------------------------------ */
    $('.select-drop_title').on('click', function(){
        $(this).next('.select-drop_list').toggle();
    });


    /* sorting
    ------------------------------------------------------ */
    $('.change-view a').on('click', function(){
        $('.change-view li').removeClass('active');
        $(this).parent('li').addClass('active');
        return false;
    });



    /* cart-side
    ------------------------------------------------------ */
    $('.show-side').on('click', function(){
        $(this).parent().toggleClass('active white');
        $('body').toggleClass('show-side-cart');
        return false;
    });



    /* plus minus by buttons
    ------------------------------------------------------ */
    $('.bminus').on('click',function () {
        var $input = $(this).parent().find('input');
        if($input.val() == ''){
            var val = 0;
        }else{
            var val = $input.val();
        }
        var count = parseInt(val) - 1;
        count = count < 1 ? 1 : count;
        $input.val(count);
        $input.change();
        return false;
    });


    $('.bplus').on('click',function () {
        var $input = $(this).parent().find('input');
        if($input.val() == ''){
            var val = 0;
        }else{
            var val = $input.val();
        }
        $input.val(parseInt(val) + 1);
        $input.change();
        return false;
    });


    /* city modal 
    -----------------------------------------------*/
    $('.select-city-outer input').focus(function(){
        $(this).closest('form').addClass('active');
    });


    $('.select-city-outer input').blur(function() {
        $(this).closest('form').removeClass('active');
    });


    /* main product slider
    ------------------------------------------------------ */
    var contentProductSwiper = new Swiper('.minislider-products__two', {
        slidesPerView: 2,
        spaceBetween: 15,
        navigation: {
            nextEl: '.cp-button-next',
            prevEl: '.cp-button-prev',
        },
        breakpoints: {

        }
    });

    var contentProductSwiperOne = new Swiper('.minislider-products__one', {
        slidesPerView: 1,
        spaceBetween: 15,
        navigation: {
            nextEl: '.cp-button-next',
            prevEl: '.cp-button-prev',
        },
        breakpoints: {

        }
    });

    /*
    var relatedSwiperOne = new Swiper('.related-slider', {
        slidesPerView: 4,
        spaceBetween: 10, 
        navigation: {
            nextEl: '.cp-button-next',
            prevEl: '.cp-button-prev',
        },
        breakpoints: { 
            480: {  
                slidesPerView: 1
            }, 
            768: {  
                slidesPerView: 2
            }, 
            992: {  
                slidesPerView: 3
            }, 
            1400: { 
                slidesPerView: 3,
            }, 
            1900: { 
                slidesPerView: 4,
            }
        }
    });   
    */

    $(".related-slider-clients").each(function(indexClients, element){
        
        $(this).addClass("instance-1");

        $(this).closest('.portfolio').find(".cp-button-prev").addClass("btn-prev-1");
        $(this).closest('.portfolio').find(".cp-button-next").addClass("btn-next-1");

        var relatedSwiperTwo = new Swiper(".instance-1", {

            slidesPerView: 4,
            spaceBetween: 10,
            preloadImages: false,
            lazy: true,
            navigation: {
                nextEl: '.cp-button-next.btn-next-1',
                prevEl: '.cp-button-prev.btn-prev-1',
            },
            breakpoints: {
                480: {
                    slidesPerView: 1
                },
                768: {
                    slidesPerView: 2
                },
                992: {
                    slidesPerView: 3
                },
                1400: {
                    slidesPerView: 3,
                },
                1900: {
                    slidesPerView: 4,
                }
            }
        });
    });


    $(".related-slider").each(function(index, element){
        var $this = $(this);
        $this.addClass("instance-" + index);

        $this.closest('.related-list').find(".cp-button-prev").addClass("btn-prev-" + index);
        $this.closest('.related-list').find(".cp-button-next").addClass("btn-next-" + index);

        var relatedSwiperOne = new Swiper(".instance-" + index, {

            slidesPerView: 4,
            spaceBetween: 10,
            preloadImages: false,
            lazy: true,
            navigation: {
                nextEl: '.cp-button-next.btn-next-' + index,
                prevEl: '.cp-button-prev.btn-prev-' + index,
            },
            breakpoints: {
                480: {
                    slidesPerView: 1
                },
                768: {
                    slidesPerView: 2
                },
                992: {
                    slidesPerView: 3
                },
                1400: {
                    slidesPerView: 3,
                },
                1900: {
                    slidesPerView: 4,
                }
            }
        });
    });

    



    /* accordion
    ---------------------------------------------------*/
    $('.accordion-title').on('click', function(j) {
        var dropDown = $(this).closest('.accordion-item').find('.accordion-content');

        $(this).closest('.accordion').find('.accordion-content').not(dropDown).slideUp();

        if ($(this).hasClass('active')) {
            $(this).removeClass('active');
        } else {
            $(this).closest('.accordion').find('.accordion-title.active').removeClass('active');
            $(this).addClass('active');
        }

        dropDown.stop(false, true).slideToggle();

        j.preventDefault();
    });




    /* scroll to block
    -------------------------------------------------------*/
    $('[data-to]').on('click', function () {
        $('html,body').scrollTo($(this).data('to'), 500, {offset: -150});
        return false;
    });



    /* map
    -------------------------------------------------------*/
    $('.map-show').on('click', function(){
        $(this).closest('.map-panel').toggleClass('active');

        setTimeout(function(){
            myMap.container.fitToViewport();
        }, 300)
    });


    if($('#map').length>0){
        var myMap,
            myPlacemark;

        var data = [
            {"type": "Feature", "geometry": {"type": "Point", "coordinates": [55.831903, 37.411961]}},
            {"type": "Feature", "geometry": {"type": "Point", "coordinates": [56, 37]}},
            {"type": "Feature", "geometry": {"type": "Point", "coordinates": [52, 36]}},
            {"type": "Feature", "geometry": {"type": "Point", "coordinates": [58, 40]}},
            {"type": "Feature", "geometry": {"type": "Point", "coordinates": [55.2, 37.5]}},
            {"type": "Feature", "geometry": {"type": "Point", "coordinates": [54.2, 38.5]}},
            {"type": "Feature", "geometry": {"type": "Point", "coordinates": [56.2, 39.5]}},
        ]


        function init(){
            myMap = new ymaps.Map("map", {
                center: [55.02,37],
                zoom: 5,
                controls: ['smallMapDefaultSet']
            });

            myMap.behaviors.disable('scrollZoom');


            objectManager = new ymaps.ObjectManager({
                clusterize: true,
                gridSize: 32,
                clusterDisableClickZoom: true
            });

            objectManager.objects.options.set('preset', 'islands#blueDotIcon');
            objectManager.clusters.options.set('preset', 'islands#blueClusterIcons');
            myMap.geoObjects.add(objectManager);

            objectManager.add(data);

        }

        ymaps.ready(init);

    } // if




    /* map contacts
    ----------------------------------------------------*/
    if($('#map-one').length>0){
        var myMap,
            myPlacemark;

        var data = [
            {"type": "Feature", "geometry": {"type": "Point", "coordinates": [55.831903, 37.411961]}}
        ]

        function init(){
            myMap = new ymaps.Map("map-one", {
                center: [55.02,37],
                zoom: 5,
                controls: ['smallMapDefaultSet']
            });

            myMap.behaviors.disable('scrollZoom');


            objectManager = new ymaps.ObjectManager({
                clusterize: true,
                gridSize: 32,
                clusterDisableClickZoom: true
            });

            objectManager.objects.options.set('preset', 'islands#blueDotIcon');
            objectManager.clusters.options.set('preset', 'islands#blueClusterIcons');
            myMap.geoObjects.add(objectManager);

            objectManager.add(data);
        }

        ymaps.ready(init);

    } // if




    /* map contacts
    ----------------------------------------------------*/
    if($('#map1').length>0){
        var myMap,
            myPlacemark;

        var data = [
            {"type": "Feature", "geometry": {"type": "Point", "coordinates": [55.831903, 37.411961]}}
        ]


        function init(){
            myMap = new ymaps.Map("map1", {
                center: [55.02,37],
                zoom: 5,
                controls: ['smallMapDefaultSet']
            });

            myMap.behaviors.disable('scrollZoom');


            objectManager = new ymaps.ObjectManager({
                clusterize: true,
                gridSize: 32,
                clusterDisableClickZoom: true
            });

            objectManager.objects.options.set('preset', 'islands#blueDotIcon');
            objectManager.clusters.options.set('preset', 'islands#blueClusterIcons');
            myMap.geoObjects.add(objectManager);

            objectManager.add(data);
        }

        ymaps.ready(init);

    } // if




    /* map contacts 2
    ----------------------------------------------------*/
    if($('#map2').length>0){
        var myMap,
            myPlacemark;

        var data = [
            {"type": "Feature", "geometry": {"type": "Point", "coordinates": [55.831903, 37.411961]}}
        ]

        function init(){
            myMap = new ymaps.Map("map2", {
                center: [55.02,37],
                zoom: 5,
                controls: ['smallMapDefaultSet']
            });

            myMap.behaviors.disable('scrollZoom');


            objectManager = new ymaps.ObjectManager({
                clusterize: true,
                gridSize: 32,
                clusterDisableClickZoom: true
            });

            objectManager.objects.options.set('preset', 'islands#blueDotIcon');
            objectManager.clusters.options.set('preset', 'islands#blueClusterIcons');
            myMap.geoObjects.add(objectManager);

            objectManager.add(data);
        }

        ymaps.ready(init);

    } // if




    // BEGIN   26.12.2018
    //  
    if($('.product-item').length){
        var height = $('.product-item:first').outerHeight();
        $('.product-item:first').parent('div').css('min-height', height + 10+'px');
    }

    // phone mask
    //$('input[name="phone"]').mask("+7(999)999-99-99");
    // 26.02.2019 убраны ()
    $('input[name="phone"]').mask("+79999999999");

    // END  26.12.2018



    

    // lazy load
    $(window).lazyLoadXT();

});


'use strict';

;( function( $, window, document, undefined )
{
    $( '.inputfile' ).each( function()
    {
        var $input   = $( this ),
            $label   = $input.next( 'label' ),
            labelVal = $label.html();

        $input.on( 'change', function( e )
        {
            var fileName = '';

            if( this.files && this.files.length > 1 )
                fileName = ( this.getAttribute( 'data-multiple-caption' ) || '' ).replace( '{count}', this.files.length );
            else if( e.target.value )
                fileName = e.target.value.split( '\\' ).pop();

            if( fileName )
                $label.find( 'span' ).html( fileName );
            else
                $label.html( labelVal );
        });

        // Firefox bug fix
        $input
            .on( 'focus', function(){ $input.addClass( 'has-focus' ); })
            .on( 'blur', function(){ $input.removeClass( 'has-focus' ); });
    });



    // $('.es-buyBtn').preventDefault();
    $('#buy').on('submit',function (e){e.preventDefault()});

    if(window.location.href == "<?=SITE_URL?>/?test") {
        $(".related-list.portfolio").show();
    }

})( jQuery, window, document );

