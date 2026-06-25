<?php

declare(strict_types=1);

namespace Eklektika\Tests\EklektikaFeed;

use OnlineService\Feed\Yml\YandexYmlFeedStorage;
use PHPUnit\Framework\TestCase;

final class YandexYmlFeedStorageTest extends TestCase
{
    private string $tempRoot;

    protected function setUp(): void
    {
        $this->tempRoot = sys_get_temp_dir() . '/eklektika-feed-test-' . bin2hex(random_bytes(4));
        mkdir($this->tempRoot, 0755, true);
    }

    protected function tearDown(): void
    {
        $cacheDir = YandexYmlFeedStorage::resolveCacheDirectory($this->tempRoot);
        $this->removeDirectory($cacheDir);
        @rmdir($this->tempRoot);
    }

    public function testWriteAtomicCreatesReadableFile(): void
    {
        $xml = '<?xml version="1.0"?><yml_catalog></yml_catalog>';

        YandexYmlFeedStorage::writeAtomic($xml, $this->tempRoot);

        $this->assertTrue(YandexYmlFeedStorage::exists($this->tempRoot));
        $this->assertSame($xml, YandexYmlFeedStorage::read($this->tempRoot));
        $this->assertGreaterThan(0, YandexYmlFeedStorage::getFileSize($this->tempRoot));
        $this->assertInstanceOf(\DateTimeImmutable::class, YandexYmlFeedStorage::getModifiedAt($this->tempRoot));
    }

    public function testExistsReturnsFalseWhenMissing(): void
    {
        $this->assertFalse(YandexYmlFeedStorage::exists($this->tempRoot));
        $this->assertNull(YandexYmlFeedStorage::read($this->tempRoot));
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
