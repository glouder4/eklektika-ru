(function() {
    $(document).ready(function() {
        var searchTimeout;
        
        // Функция для выполнения поиска
        function performSearch() {
            var fd;
            var priceFrom, priceTo, quantity;
            var formData;

            fd = $('#main-search-form input[name="q"]').val() || '';
            
            // Получаем значения дополнительных полей
            priceFrom = $('#main-search-form input[name="s_price_from"]').val() || '';
            priceTo = $('#main-search-form input[name="s_price_to"]').val() || '';
            quantity = $('#main-search-form input[name="kolvo"]').val() || '';

            // Проверяем, есть ли хотя бы один параметр для поиска
            if (fd.length < 1 && !priceFrom && !priceTo && !quantity) {
                $("#kategort").html('');
                $("#tovart").html('');
                return;
            }

            // Формируем данные для отправки
            formData = "term=" + encodeURIComponent(fd);
            if (priceFrom) {
                formData += "&s_price_from=" + encodeURIComponent(priceFrom);
            }
            if (priceTo) {
                formData += "&s_price_to=" + encodeURIComponent(priceTo);
            }
            if (quantity) {
                formData += "&kolvo=" + encodeURIComponent(quantity);
            }

            $.ajax({
                type: "POST",
                url: "/local/ajax/catalog_search.php",
                dataType: "json",
                data: formData,
                success: function (res) {
                    if (res && res.length >= 2) {
                        // Категории в #kategort
                        $("#kategort").html(res[0] || '');
                        // Товары в #tovart
                        $("#tovart").html(res[1] || '');
                        // Показываем результаты после загрузки
                        if ((res[0] && res[0].trim().length > 0) || (res[1] && res[1].trim().length > 0)) {
                            $('body').addClass('search-results-active');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Ошибка AJAX:', status, error);
                    console.log('Response:', xhr.responseText);
                }
            });
        }

        // Обработчик для поля поиска с задержкой (debounce)
        // Используем input вместо keyup, чтобы срабатывало при вставке и автозаполнении
        $(document).on('input', '#main-search-form input[name="q"]', function (e) {
            e.stopPropagation();
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                performSearch();
            }, 300); // Задержка 300мс
        });

        $(document).on('keyup paste', '#main-search-form input[name="q"]', function (e) {
            e.stopPropagation();
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                performSearch();
            }, 300);
        });

        // Обработчик focus - показываем результаты и дополнительные поля, если они уже загружены
        $(document).on('focus', '#main-search-form input[name="q"]', function (e) {
            // Показываем дополнительные поля поиска (цена от, цена до, остаток)
            $('#main-search-form .search-sub').show();
            var hasResults = $('#kategort').html().trim().length > 0 || $('#tovart').html().trim().length > 0;
            var searchValue = $(this).val();
            // Показываем результаты, если они есть или если есть текст в поле
            if (hasResults || (searchValue && searchValue.length > 0)) {
                $('body').addClass('search-results-active');
            }
        });
        
        // Скрываем дополнительные поля при потере фокуса (если не фокусируемся на них)
        $(document).on('blur', '#main-search-form input[name="q"]', function (e) {
            // Не скрываем сразу, даем время для перехода фокуса на другие поля формы
            setTimeout(function() {
                // Проверяем, не находится ли фокус на других полях формы
                var activeElement = document.activeElement;
                var isInForm = $(activeElement).closest('#main-search-form').length > 0;
                if (!isInForm) {
                    $('#main-search-form .search-sub').hide();
                }
            }, 200);
        });
        
        // Показываем дополнительные поля при фокусе на них
        $(document).on('focus', '#main-search-form .search-sub input', function (e) {
            $('#main-search-form .search-sub').show();
        });

        // Обработчики для дополнительных полей (цена от, цена до, остаток)
        // Поиск работает даже без текстового запроса, только по фильтрам
        // Используем input вместо keyup, чтобы срабатывало при вставке и автозаполнении
        $(document).on('input', '#main-search-form input[name="s_price_from"], #main-search-form input[name="s_price_to"], #main-search-form input[name="kolvo"]', function (e) {
            e.stopPropagation();
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                performSearch();
            }, 300);
        });

        $(document).on('keyup paste', '#main-search-form input[name="s_price_from"], #main-search-form input[name="s_price_to"], #main-search-form input[name="kolvo"]', function (e) {
            e.stopPropagation();
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                performSearch();
            }, 300);
        });



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
    });
})();