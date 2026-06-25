<?php

declare(strict_types=1);

namespace Eklektika\Tests\EklektikaFeed;

use OnlineService\Feed\Config\FeedConfig;
use OnlineService\Feed\Yml\FeedCatalogBatchLoader;
use OnlineService\Feed\Yml\FeedOfferPriceResolver;
use PHPUnit\Framework\TestCase;

final class FeedOfferPriceResolverTest extends TestCase
{
    public function testResolveUsesAdvertisingAndWholesalePrices(): void
    {
        $result = FeedOfferPriceResolver::resolve([
            [
                'PRICE' => 500.0,
                'CURRENCY' => 'RUB',
                'CATALOG_GROUP_ID' => FeedConfig::ADVERTISING_PRICE_TYPE_ID,
            ],
            [
                'PRICE' => 700.0,
                'CURRENCY' => 'RUB',
                'CATALOG_GROUP_ID' => FeedConfig::BASE_PRICE_TYPE_ID,
            ],
        ]);

        $this->assertNotNull($result);
        $this->assertSame(500.0, $result['MAIN']);
        $this->assertSame(700.0, $result['OLD']);
        $this->assertSame('RUB', $result['CURRENCY']);
    }

    public function testResolveReturnsNullWhenNoMainPrice(): void
    {
        $this->assertNull(FeedOfferPriceResolver::resolve([
            [
                'PRICE' => 0.0,
                'CURRENCY' => 'RUB',
                'CATALOG_GROUP_ID' => FeedConfig::ADVERTISING_PRICE_TYPE_ID,
            ],
        ]));
    }

    public function testExtractPropertyDisplayValueSupportsScalarAndArray(): void
    {
        $this->assertSame('red', FeedCatalogBatchLoader::extractPropertyDisplayValue('red'));
        $this->assertSame('blue', FeedCatalogBatchLoader::extractPropertyDisplayValue(['VALUE' => 'blue']));
        $this->assertSame('a, b', FeedCatalogBatchLoader::extractPropertyDisplayValue(['a', 'b']));
    }
}
