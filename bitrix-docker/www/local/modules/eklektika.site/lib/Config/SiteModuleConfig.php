<?php

namespace OnlineService\Site\Config;

final class SiteModuleConfig
{
    public const PAGE_SETTINGS_DEFAULT_IBLOCK_ID = 60;

    public const SEARCH_HANDLER_CLASS = '\OnlineService\Classes\Handlers\Search\Stemming';
    public const SEARCH_HANDLER_METHOD = 'BeforeIndexHandler';
    public const SEARCH_HANDLER_FILE = '/local/php_interface/classes/handlers/search/stemming.php';
}
