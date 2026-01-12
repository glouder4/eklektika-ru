window.reinitFilterJS = function() {
    // filtr
    // 23.09.2019
    $('.category .select-ul button').on('click', function (e) {
        $('.category .select-ul').removeClass('active');
        if (!$(e.target).is('a')) {
            $(this).closest('.select-ul').toggleClass('active');
        }
    });

    // hide select ul
    $(document).mouseup(function (e) {
        let container = $(".category .select-ul");
        if (container.has(e.target).length === 0) {
            container.removeClass('active');
        }
    });
};