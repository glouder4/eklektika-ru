<?php

namespace OnlineService\Site;

use OnlineService\Site\Config\SiteModuleConfig;

/**
 * Регистрация обработчика полнотекстового индекса (до выделения модуля eklektika.search).
 */
final class SearchIndexingBootstrap
{
    public static function register(): void
    {
        \Bitrix\Main\EventManager::getInstance()->addEventHandler(
            'search',
            'BeforeIndex',
            [SiteModuleConfig::SEARCH_HANDLER_CLASS, SiteModuleConfig::SEARCH_HANDLER_METHOD]
        );

        \Bitrix\Main\Loader::registerAutoLoadClasses(null, [
            SiteModuleConfig::SEARCH_HANDLER_CLASS => SiteModuleConfig::SEARCH_HANDLER_FILE,
        ]);
    }
}
