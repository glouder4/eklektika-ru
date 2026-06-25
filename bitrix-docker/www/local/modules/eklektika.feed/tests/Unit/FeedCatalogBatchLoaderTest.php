<?php

declare(strict_types=1);

namespace Eklektika\Tests\EklektikaFeed;

use OnlineService\Feed\Yml\FeedCatalogBatchLoader;
use PHPUnit\Framework\TestCase;

final class FeedCatalogBatchLoaderTest extends TestCase
{
    public function testBuildFileSrcWithSubdir(): void
    {
        $this->assertSame(
            '/upload/iblock/abc/photo.jpg',
            FeedCatalogBatchLoader::buildFileSrc('upload/iblock/abc', 'photo.jpg')
        );
    }

    public function testBuildFileSrcWithoutSubdir(): void
    {
        $this->assertSame('/photo.jpg', FeedCatalogBatchLoader::buildFileSrc('', 'photo.jpg'));
    }

    public function testBuildFileSrcReturnsEmptyForBlankFileName(): void
    {
        $this->assertSame('', FeedCatalogBatchLoader::buildFileSrc('upload', ''));
    }
}
