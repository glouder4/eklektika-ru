<?php

defined('B_PROLOG_INCLUDED') || die();

$feedModuleClassMap = [
    \OnlineService\Feed\Config\FeedConfig::class => '/local/modules/eklektika.feed/lib/Config/FeedConfig.php',
    \OnlineService\Feed\Config\FeedIntegrationConfig::class => '/local/modules/eklektika.feed/lib/Config/FeedIntegrationConfig.php',
    \OnlineService\Feed\Yml\YmlXml::class => '/local/modules/eklektika.feed/lib/Yml/YmlXml.php',
    \OnlineService\Feed\Yml\YandexYmlFeedGenerator::class => '/local/modules/eklektika.feed/lib/Yml/YandexYmlFeedGenerator.php',
    \OnlineService\Feed\Yml\YandexYmlFeedStorage::class => '/local/modules/eklektika.feed/lib/Yml/YandexYmlFeedStorage.php',
    \OnlineService\Feed\Yml\YandexYmlFeedRegenerator::class => '/local/modules/eklektika.feed/lib/Yml/YandexYmlFeedRegenerator.php',
    \OnlineService\Feed\Yml\FeedCatalogBatchLoader::class => '/local/modules/eklektika.feed/lib/Yml/FeedCatalogBatchLoader.php',
    \OnlineService\Feed\Yml\FeedOfferPriceResolver::class => '/local/modules/eklektika.feed/lib/Yml/FeedOfferPriceResolver.php',
    \OnlineService\Feed\Yml\FeedGenerationProgress::class => '/local/modules/eklektika.feed/lib/Yml/FeedGenerationProgress.php',
    \OnlineService\Feed\Bootstrap\FeedCliBootstrap::class => '/local/modules/eklektika.feed/lib/Bootstrap/FeedCliBootstrap.php',
    \OnlineService\Feed\Http\FeedHttpServe::class => '/local/modules/eklektika.feed/lib/Http/FeedHttpServe.php',
];

if (class_exists(\Bitrix\Main\Loader::class)) {
    \Bitrix\Main\Loader::registerAutoLoadClasses(null, $feedModuleClassMap);
} else {
    $documentRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
    foreach ($feedModuleClassMap as $className => $relativePath) {
        if (!class_exists($className)) {
            require_once $documentRoot . $relativePath;
        }
    }
}
