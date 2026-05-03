<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
?>
<script>
    (function () {
        // Функция для обновления URL с параметрами сортировки
        function updateSortUrl(sortField, sortOrder) {
            var url = new URL(window.location.href);

            // Удаляем параметр novinki, если устанавливаем другую сортировку
            url.searchParams.delete('novinki');

            if (sortField) {
                url.searchParams.set('sort_field', sortField);
            } else {
                url.searchParams.delete('sort_field');
            }

            if (sortOrder) {
                url.searchParams.set('sort_order', sortOrder);
            } else {
                url.searchParams.delete('sort_order');
            }

            // Перезагружаем страницу с новыми параметрами
            window.location.href = url.toString();
        }

        // Обработчик для элементов сортировки
        document.addEventListener('DOMContentLoaded', function () {
            var sortElements = document.querySelectorAll('.set-sort-field-custom');

            sortElements.forEach(function (element) {
                element.addEventListener('click', function (e) {
                    e.preventDefault();

                    var sortField = this.getAttribute('data-sort-field');

                    // Получаем текущие параметры из URL
                    var url = new URL(window.location.href);
                    var currentSortField = url.searchParams.get('sort_field');
                    var currentSortOrder = url.searchParams.get('sort_order') || 'asc';

                    // Определяем новый порядок сортировки
                    var newSortOrder;

                    // Если кликнули на уже активный элемент, меняем порядок сортировки
                    if (this.classList.contains('active') && currentSortField === sortField) {
                        // Переключаем порядок: asc -> desc, desc -> asc
                        newSortOrder = (currentSortOrder === 'asc') ? 'desc' : 'asc';
                    } else {
                        // Если кликнули на неактивный элемент, начинаем с asc
                        newSortOrder = 'asc';
                    }

                    updateSortUrl(sortField, newSortOrder);
                });
            });

            // Обработчик для "Новинки"
            var novinkiElement = document.getElementById('sort-novinki');
            if (novinkiElement) {
                novinkiElement.addEventListener('click', function (e) {
                    e.preventDefault();

                    var url = new URL(window.location.href);

                    // Если уже активен, убираем фильтр
                    if (this.classList.contains('active')) {
                        url.searchParams.delete('novinki');
                        url.searchParams.delete('sort_field');
                        url.searchParams.delete('sort_order');
                    } else {
                        // Устанавливаем фильтр новинки
                        url.searchParams.set('novinki', '1');
                        url.searchParams.delete('sort_field');
                        url.searchParams.delete('sort_order');
                    }

                    window.location.href = url.toString();
                });
            }
        });
    })();
</script>
