<?php

namespace OnlineService\Site\Config;

final class SiteModuleConfig
{
    public const PAGE_SETTINGS_DEFAULT_IBLOCK_ID = 60;

    /** Инфоблок кастомных страниц сайта (`pages`), публичный URL `/pages/#ELEMENT_CODE#/`. */
    public const PAGES_IBLOCK_ID = 25;

    /** Модуль / ключ опции Bitrix: Настройки → Настройки продукта → Настройки модулей → eklektika.site */
    public const MODULE_ID = 'eklektika.site';
    public const MIN_ORDER_SUM_OPTION = 'min_order_sum';
    /** Как на проде (шапка + порог оформления корзины). */
    public const MIN_ORDER_SUM_DEFAULT = 50000;

    public const SEARCH_HANDLER_CLASS = '\OnlineService\Classes\Handlers\Search\Stemming';
    public const SEARCH_HANDLER_METHOD = 'BeforeIndexHandler';
    public const SEARCH_HANDLER_FILE = '/local/php_interface/classes/handlers/search/stemming.php';
}
