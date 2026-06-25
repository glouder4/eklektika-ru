function initStockFilterInput() {
    var stockInput = document.getElementById('stock_filter_available');
    if (!stockInput || stockInput.dataset.stockBound === '1') {
        return;
    }
  
    stockInput.dataset.stockBound = '1';

    var normalizeStockValue = function() {
        var minVal = parseInt(stockInput.getAttribute('data-min-val') || '0', 10);
        var maxVal = parseInt(stockInput.getAttribute('data-max-val') || '0', 10);

        if (stockInput.value === '') {
            return;
        }

        var current = parseInt(stockInput.value, 10);
        if (isNaN(current)) {
            stockInput.value = '';
            return;
        }

        if (!isNaN(maxVal) && maxVal > 0 && current > maxVal) {
            stockInput.value = String(maxVal);
        }

        if (!isNaN(minVal) && current < minVal) {
            stockInput.value = String(minVal);
        }
    };

    var triggerFilter = function() {
        if (typeof smartFilter !== 'undefined' && smartFilter) {
            smartFilter.keyup(stockInput);
        }
    };

    stockInput.addEventListener('input', function() {
        normalizeStockValue();
        triggerFilter();
    });

    stockInput.addEventListener('change', function() {
        normalizeStockValue();
        triggerFilter();
    });
}

function triggerBrandFilter(input) {
    if (!input || typeof smartFilter === 'undefined' || !smartFilter) {
        return;
    }

    if (typeof smartFilter.reloadBrandFilter === 'function') {
        smartFilter.reloadBrandFilter(input);
    } else {
        smartFilter.click(input);
    }
}

function triggerCategoryParentFilter(input) {
    if (!input || typeof smartFilter === 'undefined' || !smartFilter) {
        return;
    }

    if (typeof smartFilter.reloadCategoryParentFilter === 'function') {
        smartFilter.reloadCategoryParentFilter(input);
    } else {
        smartFilter.click(input);
    }
}

function triggerCategorySubFilter(input) {
    if (!input || typeof smartFilter === 'undefined' || !smartFilter) {
        return;
    }

    if (typeof smartFilter.reloadCategorySubFilter === 'function') {
        smartFilter.reloadCategorySubFilter(input);
    } else {
        smartFilter.click(input);
    }
}

function initBrandFilterInput() {
    if (window.__eFiltrBrandFilterBound === '1') {
        return;
    }

    window.__eFiltrBrandFilterBound = '1';

    $(document).on('click', '#eFiltr #brand_filter_block label.bx-filter-param-label', function(e) {
        e.stopPropagation();

        var input = this.querySelector('input[type="radio"]');
        if (!input) {
            return;
        }

        $(this).closest('.select-ul').removeClass('active bx-active');

        window.setTimeout(function() {
            triggerBrandFilter(input);
        }, 0);
    });

    $(document).on('click', '#eFiltr #brand_filter_block input[type="radio"][name$="_brand"]', function(e) {
        e.stopPropagation();

        $(this).closest('.select-ul').removeClass('active bx-active');

        window.setTimeout(function() {
            triggerBrandFilter(this);
        }.bind(this), 0);
    });
}

function initCategoryFilterInput() {
    if (window.__eFiltrCategoryFilterBound === '1') {
        return;
    }

    window.__eFiltrCategoryFilterBound = '1';

    $(document).on('click', '#eFiltr #category_parent_filter_block label.bx-filter-param-label', function(e) {
        e.stopPropagation();

        var input = this.querySelector('input[type="radio"]');
        if (!input) {
            return;
        }

        $(this).closest('.select-ul').removeClass('active bx-active');

        window.setTimeout(function() {
            triggerCategoryParentFilter(input);
        }, 0);
    });

    $(document).on('click', '#eFiltr #category_parent_filter_block input[type="radio"][name$="_section_parent"]', function(e) {
        e.stopPropagation();

        $(this).closest('.select-ul').removeClass('active bx-active');

        window.setTimeout(function() {
            triggerCategoryParentFilter(this);
        }.bind(this), 0);
    });

    $(document).on('click', '#eFiltr #category_sub_filter_block label.bx-filter-param-label', function(e) {
        e.stopPropagation();

        var input = this.querySelector('input[type="radio"]');
        if (!input) {
            return;
        }

        $(this).closest('.select-ul').removeClass('active bx-active');

        window.setTimeout(function() {
            triggerCategorySubFilter(input);
        }, 0);
    });

    $(document).on('click', '#eFiltr #category_sub_filter_block input[type="radio"][name$="_section_sub"]', function(e) {
        e.stopPropagation();

        $(this).closest('.select-ul').removeClass('active bx-active');

        window.setTimeout(function() {
            triggerCategorySubFilter(this);
        }.bind(this), 0);
    });
}

function initFilterDropdowns() {
    if (window.__eFiltrDropdownsBound === '1') {
        return;
    }

    window.__eFiltrDropdownsBound = '1';

    $(document).on('click', '#eFiltr .select-ul > button.select-ul-btn', function(e) {
        if ($(e.target).is('a')) {
            return;
        }

        var $selectUl = $(this).closest('.select-ul');
        var wasActive = $selectUl.hasClass('active');

        $('#eFiltr .select-ul').removeClass('active bx-active');
        if (!wasActive) {
            $selectUl.addClass('active');
        }
    });

    $(document).on('click', function(e) {
        if ($(e.target).closest('#eFiltr .select-ul').length === 0) {
            $('#eFiltr .select-ul').removeClass('active bx-active');
        }
    });
}

window.reinitFilterJS = function() {
    initStockFilterInput();
    initBrandFilterInput();
    initCategoryFilterInput();
    initFilterDropdowns();

    if (typeof smartFilter !== 'undefined' && smartFilter && typeof smartFilter.updateCustomFilterUi === 'function') {
        smartFilter.updateCustomFilterUi();
    }
};

$(function() {
    initStockFilterInput();
    initBrandFilterInput();
    initCategoryFilterInput();
    initFilterDropdowns();
});
