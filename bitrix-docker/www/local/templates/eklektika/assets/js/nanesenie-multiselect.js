(function (window) {
    'use strict';

    var DEFAULT_OPTION = 'Без нанесения';

    function isDefaultOption(value) {
        return String(value || '').trim().toLowerCase() === DEFAULT_OPTION.toLowerCase();
    }

    function normalizeValues(values) {
        if (!Array.isArray(values)) {
            values = values ? [String(values)] : [];
        }

        values = values
            .map(function (value) { return String(value).trim(); })
            .filter(function (value) { return value !== ''; });

        values = values.filter(function (value, index) {
            return values.indexOf(value) === index;
        });

        if (!values.length) {
            return [DEFAULT_OPTION];
        }

        var withoutDefault = values.filter(function (value) {
            return !isDefaultOption(value);
        });

        return withoutDefault.length ? withoutDefault : [DEFAULT_OPTION];
    }

    function updateTriggerText(container) {
        if (!container) {
            return;
        }

        var triggerText = container.querySelector('.item_nanesenie-trigger-text');
        if (!triggerText) {
            return;
        }

        triggerText.textContent = getContainerValues(container).join(', ');
    }

    function getContainerValues(container) {
        if (!container) {
            return [DEFAULT_OPTION];
        }

        var checked = container.querySelectorAll('.item_nanesenie-option:checked');
        var values = Array.prototype.map.call(checked, function (input) {
            return input.value;
        });

        return normalizeValues(values);
    }

    function setContainerValues(container, values) {
        if (!container) {
            return;
        }

        values = normalizeValues(values);
        var onlyDefault = values.length === 1 && isDefaultOption(values[0]);

        container.querySelectorAll('.item_nanesenie-option').forEach(function (input) {
            if (isDefaultOption(input.value)) {
                input.checked = onlyDefault;
                return;
            }

            input.checked = !onlyDefault && values.indexOf(input.value) !== -1;
        });

        updateTriggerText(container);
    }

    function applyExclusiveChange(container, changedInput) {
        if (isDefaultOption(changedInput.value)) {
            if (changedInput.checked) {
                container.querySelectorAll('.item_nanesenie-option:not(.item_nanesenie-none)').forEach(function (input) {
                    input.checked = false;
                });
            }
            return;
        }

        if (changedInput.checked) {
            var noneInput = container.querySelector('.item_nanesenie-none');
            if (noneInput) {
                noneInput.checked = false;
            }
            return;
        }

        if (!container.querySelectorAll('.item_nanesenie-option:checked').length) {
            var fallback = container.querySelector('.item_nanesenie-none');
            if (fallback) {
                fallback.checked = true;
            }
        }
    }

    function resetPanelLayout(container) {
        var panel = container && container.querySelector('.item_nanesenie-panel');
        if (!panel) {
            return;
        }

        panel.style.minWidth = '';
        panel.style.width = '';
        panel.style.maxWidth = '';
        panel.style.maxHeight = '';
        panel.style.left = '';
        container.classList.remove('is-open-up');
    }

    function adjustPanelLayout(container) {
        var trigger = container.querySelector('.item_nanesenie-trigger');
        var panel = container.querySelector('.item_nanesenie-panel');
        if (!trigger || !panel) {
            return;
        }

        resetPanelLayout(container);

        var triggerWidth = trigger.getBoundingClientRect().width;
        var minWidth = Math.max(Math.round(triggerWidth), 320);
        var viewportPadding = 16;
        var preferredMaxHeight = 420;
        var minPanelHeight = 180;

        panel.style.minWidth = minWidth + 'px';
        panel.style.width = 'max-content';
        panel.style.maxWidth = Math.max(minWidth, window.innerWidth - viewportPadding) + 'px';

        var rect = trigger.getBoundingClientRect();
        var spaceBelow = window.innerHeight - rect.bottom - viewportPadding;
        var spaceAbove = rect.top - viewportPadding;
        var openUp = spaceBelow < minPanelHeight && spaceAbove > spaceBelow;

        if (openUp) {
            container.classList.add('is-open-up');
            panel.style.maxHeight = Math.max(minPanelHeight, Math.min(preferredMaxHeight, spaceAbove)) + 'px';
        } else {
            panel.style.maxHeight = Math.max(minPanelHeight, Math.min(preferredMaxHeight, spaceBelow)) + 'px';
        }

        window.requestAnimationFrame(function () {
            var panelRect = panel.getBoundingClientRect();
            var overflowRight = panelRect.right - (window.innerWidth - viewportPadding / 2);

            if (overflowRight > 0) {
                panel.style.left = (-overflowRight) + 'px';
                return;
            }

            if (panelRect.left < viewportPadding / 2) {
                panel.style.left = (viewportPadding / 2 - panelRect.left) + 'px';
            }
        });
    }

    function closeAllDropdowns(exceptContainer) {
        document.querySelectorAll('.item_nanesenie-dropdown.is-open').forEach(function (container) {
            if (exceptContainer && container === exceptContainer) {
                return;
            }

            container.classList.remove('is-open');
            resetPanelLayout(container);
            var trigger = container.querySelector('.item_nanesenie-trigger');
            var panel = container.querySelector('.item_nanesenie-panel');
            if (trigger) {
                trigger.setAttribute('aria-expanded', 'false');
            }
            if (panel) {
                panel.hidden = true;
            }
        });
    }

    function toggleDropdown(container) {
        var trigger = container.querySelector('.item_nanesenie-trigger');
        var panel = container.querySelector('.item_nanesenie-panel');
        if (!trigger || !panel) {
            return;
        }

        var willOpen = !container.classList.contains('is-open');
        closeAllDropdowns();

        if (willOpen) {
            container.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
            panel.hidden = false;
            adjustPanelLayout(container);
        }
    }

    function bindDropdownUi(container) {
        var trigger = container.querySelector('.item_nanesenie-trigger');
        var panel = container.querySelector('.item_nanesenie-panel');

        if (trigger) {
            trigger.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                toggleDropdown(container);
            });
        }

        if (panel) {
            panel.addEventListener('click', function (event) {
                event.stopPropagation();
            });
        }
    }

    function bindContainer(container) {
        if (!container || container.dataset.nanesenieBound === 'Y') {
            return;
        }

        container.dataset.nanesenieBound = 'Y';
        bindDropdownUi(container);
        updateTriggerText(container);

        container.addEventListener('change', function (event) {
            if (!event.target.matches('.item_nanesenie-option')) {
                return;
            }

            applyExclusiveChange(container, event.target);
            updateTriggerText(container);
            container.dispatchEvent(new CustomEvent('nanesenie:change', {
                bubbles: true,
                detail: { values: getContainerValues(container) }
            }));
        });
    }

    function init(scope) {
        var root = scope && scope.querySelectorAll ? scope : document;
        root.querySelectorAll('.item_nanesenie-multiselect').forEach(bindContainer);
    }

    function syncContainersByOfferId(offerId, values) {
        ['exampleFormControlSelect1_', 'exampleFormControlSelect2_'].forEach(function (prefix) {
            setContainerValues(document.getElementById(prefix + offerId), values);
        });
    }

    function appendValuesToBody(values, parts) {
        normalizeValues(values).forEach(function (value) {
            parts.push('nanesenie[]=' + encodeURIComponent(value));
        });
    }

    if (!window.__nanesenieDropdownDocBound) {
        window.__nanesenieDropdownDocBound = true;
        document.addEventListener('click', function () {
            closeAllDropdowns();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeAllDropdowns();
            }
        });
        window.addEventListener('resize', function () {
            document.querySelectorAll('.item_nanesenie-dropdown.is-open').forEach(function (container) {
                adjustPanelLayout(container);
            });
        });
    }

    window.EklektikaNanesenie = {
        DEFAULT_OPTION: DEFAULT_OPTION,
        normalizeValues: normalizeValues,
        getContainerValues: getContainerValues,
        setContainerValues: setContainerValues,
        bindContainer: bindContainer,
        init: init,
        syncContainersByOfferId: syncContainersByOfferId,
        appendValuesToBody: appendValuesToBody,
        getValuesFromRoot: function (root) {
            var container = root && root.closest
                ? root.closest('.item_nanesenie-multiselect')
                : null;

            if (!container && root && root.classList && root.classList.contains('item_nanesenie-multiselect')) {
                container = root;
            }

            if (!container && root && root.querySelector) {
                container = root.querySelector('.item_nanesenie-multiselect');
            }

            return getContainerValues(container);
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        init(document);
    });
})(window);
