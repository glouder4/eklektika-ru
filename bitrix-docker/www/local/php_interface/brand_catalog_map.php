<?php
/**
 * Fallback-карта брендов (slug => настройки), если в инфоблоке 20 не заполнены свойства.
 * Основной источник — элементы инфоблока 20 (тип sliders): CODE = slug URL, /{CODE}/.
 *
 * Свойства элемента (рекомендуемые):
 * - BRENDY_DLYA_WEB — значение для фильтра каталога
 * - PAGE_TITLE, META_DESCRIPTION — SEO
 * - UPPER_DESCRIPTION — HTML над каталогом
 * - SEO_DESCRIPTION_BOTTOM — HTML под каталогом (fallback: DETAIL_TEXT)
 */
return [
    'altavolo' => [
        'BRENDY_DLYA_WEB' => 'Altavolo',
        'TITLE' => 'Altavolo',
    ],
    'brunovisconti' => [
        'BRENDY_DLYA_WEB' => 'BrunoVisconti',
        'TITLE' => 'Bruno Visconti',
    ],
    'enote' => [
        'BRENDY_DLYA_WEB' => 'Enote',
        'TITLE' => 'Enote',
    ],
    'lettertone' => [
        'BRENDY_DLYA_WEB' => 'Lettertone',
        'TITLE' => 'Lettertone',
    ],
    'open' => [
        'BRENDY_DLYA_WEB' => 'oPen',
        'TITLE' => 'Open',
    ],
    'portobello-trend' => [
        'BRENDY_DLYA_WEB' => 'Portobello',
        'TITLE' => 'Portobello Trend',
    ],
    'prodir' => [
        'BRENDY_DLYA_WEB' => 'Prodir',
        'TITLE' => 'Prodir',
    ],
    'senator' => [
        'BRENDY_DLYA_WEB' => 'Senator',
        'TITLE' => 'Senator',
    ],
    'sols' => [
        'BRENDY_DLYA_WEB' => 'Sols',
        'TITLE' => 'Sols',
    ],
    'victorinox' => [
        'BRENDY_DLYA_WEB' => 'Victorinox',
        'TITLE' => 'Victorinox',
    ],
    'xiaomi' => [
        'BRENDY_DLYA_WEB' => 'Xiaomi',
        'TITLE' => 'Xiaomi',
    ],
    'yoliba' => [
        'BRENDY_DLYA_WEB' => 'YOLIBA',
        'TITLE' => 'Yoliba',
    ],
];
