$(function() {
	if ($(window).width() >= 991) {
		$('.desktop-catalog .catalog > li:not(.back-link-catalog)').first().addClass('highlight');
	}
	$('.main .tablet-hide').append('<span class="show_more js-show_more">Читать далее</span>');	

});
$('.show-all-catalog').click(function (e) {
    /*var block = document.querySelector('.custom-wrap-head .desktop-catalog ul');
    block.scrollTop = block.scrollHeight;
    $('.custom-wrap-head .desktop-catalog > ul').css('height','auto');*/
});



$('.desktop-catalog .catalog > li').on('mouseenter', function () {
    $(this).siblings('.highlight').removeClass('highlight')
    $(this).addClass('highlight');
});
$('.menu-catalog-container .btn-menu-catalog').click(function (e) {
    e.preventDefault();
    $(this).parents('.menu-catalog-container').toggleClass('active');
    window.maxHeightCatalogMenu = document.querySelector('.custom-wrap-head .desktop-catalog ul').scrollHeight;
});   
jQuery(function($){
	$(document).mouseup( function(e){ 
		var div = $( ".menu-catalog-container" ); 
		if ( !div.is(e.target) 
		    && div.has(e.target).length === 0 ) {
			$('.menu-catalog-container').removeClass('active');
		}
	});
});

$(document).ready(function(){
  var btnTop = $('.js-btn-up');
  var btnDown = $('.js-btn-down');
  var block = $('.custom-wrap-head .desktop-catalog ul');
  var maxHeight = document.querySelector('.custom-wrap-head .desktop-catalog ul').scrollHeight;

  btnTop.hide();
  function animateScroll2(direct) {
    console.log(direct);
    var scrollValue = direct == 'up' ? block.scrollTop() + 1000 : block.scrollTop() - 1000;

    block.animate({
      scrollTop: scrollValue
    }, 5000, 'linear');
  }

  function animateScroll2Stop() {
    
    if (block.scrollTop() == 0) {
      btnTop.hide();
    }
    else  btnTop.show();
    if (block.scrollTop() + block.height() >= window.maxHeightCatalogMenu - 10) {
      btnDown.hide();
    }
    else btnDown.show();
    block.stop(true);
  }

  $('.js-btn-down').on("mouseenter", function(){animateScroll2('up')}).on("mouseleave", function(){animateScroll2Stop()});
  $('.js-btn-up').on("mouseenter", function(){animateScroll2('down')}).on("mouseleave", function(){animateScroll2Stop()});

})
$('.js-show-mob-catalog').click(function (e) {
    $('.custom-wrap-head .desktop-catalog ul.catalog').addClass('show-for-mob');
    $('.custom-wrap-head .desktop-catalog ul.mob-menu-pages').hide();
    $(this).hide();
});
$('.back-link-catalog').click(function (e) {
    e.preventDefault();
    e.stopPropagation();
    $('.custom-wrap-head .desktop-catalog ul.catalog').removeClass('show-for-mob');
    $('.custom-wrap-head .desktop-catalog ul.mob-menu-pages').show();
    $('.js-show-mob-catalog').show();
});

//Старый функционал перелистывания по клику
// $( document ).ready(function() {
//   var btnTop = $('.js-btn-up');
//   var btnDown = $('.js-btn-down');
//   var block = $('.custom-wrap-head .desktop-catalog ul');
//   var maxHeight = document.querySelector('.custom-wrap-head .desktop-catalog ul').scrollHeight;
  
//   // по умолчанию скрываем верхнюю кнопку
//   btnTop.hide(); 

//   btnDown.click(function (e) {
//     btnTop.show();

//     if (block.scrollTop() + block.height() >= window.maxHeightCatalogMenu - 10) {
//       btnDown.hide();
//     }
//     console.log('maxHeight', maxHeight);
//     console.log('block.scrollTop()', block.scrollTop());
//     console.log('block.height()', block.height());
//     animateScroll('up');
//   });

//   btnTop.click(function (e) {
//     btnDown.show();

//     if (block.scrollTop() == 0) {
//       btnTop.hide();
//     }

//     animateScroll('down');      
//   });

//   function animateScroll(direct) {
//     var scrollValue = direct == 'up' ? block.scrollTop() + 100 : block.scrollTop() - 100;

//     block.animate({
//       scrollTop: scrollValue
//     }, 500, 'linear');
//   }
// });

$(document).on('click', '.open-catalog ul.catalog li', function (e) {
    var subCatalog = $(this).children('ul');
    if (subCatalog.length) {
        e.preventDefault();
        $(this).siblings('.highlight').removeClass('highlight')
        $(this).addClass('highlight');
    } else {
        window.location.href = $(this).children('a').attr("href");
        return false;
    }
});

$('.subcatalog > li').click(function (e) {
    var subFilters = $(this).children('ul');
    if ($(this).hasClass('back-link')) {
        $(this).parents('.highlight').removeClass('highlight');
        return false;
    }
    if (subFilters.length) {
        e.preventDefault();
        subFilters.toggleClass('active')
    }
});

if ($(window).width() <= 1024) {
	$('.main .main-hide-text-second').append('<span class="show_more js-show_more">Читать далее</span>');
}
/*
$( window ).resize(function() {
  $( ".main-hide-text-first *" ).removeAttr( 'style' );
  $( ".mobile-hide *" ).removeAttr( 'style' );
  $( ".main-hide-text-second *" ).removeAttr( 'style' );
});*/

if ($(window).width() >= 768) {
	$(document).on("click",".mobile-hide .js-show_more", function(){
		$(this).parent().find('p:last-of-type').show();		
		$(this).remove();		
	});
	$(document).on("click",".main-hide-text-second .js-show_more", function(){
		$(this).parent().find('p:last-of-type').show();		
		$(this).parent().find('.hide-text').show();	
		$(this).remove();				
	});
}
if ($(window).width() < 768) {
	$(document).on("click",".mobile-hide .js-show_more", function(){
		$(this).parent().find('p').show();		
		$(this).parents('.flex-wrapper.mobile-hide').find('.two-colomn-text:first-child p:last-child').show();	
		$(this).remove();		
	});
	$(document).on("click",".main-hide-text-second .js-show_more", function(){
			
		$(this).parent().find('p').show();		
		$(this).parent().find('.hide-text').show();		
		$(this).parents('.main').find('.main-hide-text-first').find('*:last-child').show();		
		$(this).parents('.main').find('.main-hide-text-first').find('*:nth-last-child(2)').show();	
		$(this).remove();	
	});
}
$('.subcatalog > li > ul').click(function (e) {
    e.stopPropagation();
});


$(function() {
    var mainSlider = new Swiper('.main-slider', {
        slidesPerView: 1,
        autoplay: {
            delay: 15000,
          },
          pagination: {
            el: '.swiper-pagination',
            type: 'bullets',
            clickable: true
          },
    });
});


document.addEventListener("DOMContentLoaded", function () {
  const banner = document.querySelector('.new-banner');
  if (!banner) return;

  const smoothLinks = banner.querySelectorAll('a[href^="#"]:not([href="#"])');

  smoothLinks.forEach(link => {
    link.addEventListener("click", function (e) {
      const hash = this.getAttribute("href");
      const targetId = hash.slice(1);
      const targetElement = document.getElementById(targetId);

      if (targetElement) {
        e.preventDefault();

        targetElement.scrollIntoView({ behavior: "smooth", block: "start" });

        const newUrl = window.location.pathname + hash;
        history.pushState(null, null, newUrl);
      }
    });
  });
});