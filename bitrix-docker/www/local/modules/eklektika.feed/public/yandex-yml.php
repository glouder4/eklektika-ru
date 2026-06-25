<?php

/**
 * Публичная отдача предсгенерированного YML-фида.
 * Канонический ЧПУ: {@see \OnlineService\Feed\Config\FeedConfig::PUBLIC_PATH}
 *
 * Без prolog_before: Bitrix OB (sproduction.integration) поглощает readfile().
 */

use OnlineService\Feed\Http\FeedHttpServe;
use OnlineService\Feed\Yml\YandexYmlFeedStorage;

if (!defined('B_PROLOG_INCLUDED')) {
    define('B_PROLOG_INCLUDED', true);
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/modules/eklektika.feed/include.php';

if (!YandexYmlFeedStorage::exists()) {
    FeedHttpServe::serveServiceUnavailable(
        'YML feed is not generated yet. Run: php local/modules/eklektika.feed/tools/regenerate_yandex_yml.php'
    );
}

FeedHttpServe::serveFile(
    YandexYmlFeedStorage::resolveAbsolutePath(),
    'application/xml; charset=UTF-8',
    YandexYmlFeedStorage::getModifiedAt(),
    YandexYmlFeedStorage::getFileSize()
);
