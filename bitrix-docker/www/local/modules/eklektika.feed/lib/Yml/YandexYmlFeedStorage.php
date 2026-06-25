<?php

declare(strict_types=1);

namespace OnlineService\Feed\Yml;

use OnlineService\Feed\Config\FeedConfig;

final class YandexYmlFeedStorage
{
    public static function resolveAbsolutePath(?string $documentRoot = null): string
    {
        $root = rtrim($documentRoot ?? (string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');

        return $root . FeedConfig::CACHE_RELATIVE_PATH;
    }

    public static function resolveCacheDirectory(?string $documentRoot = null): string
    {
        $root = rtrim($documentRoot ?? (string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');

        return $root . FeedConfig::CACHE_DIR_RELATIVE_PATH;
    }

    public static function exists(?string $documentRoot = null): bool
    {
        $path = self::resolveAbsolutePath($documentRoot);

        return is_file($path) && filesize($path) > 0;
    }

    public static function getFileSize(?string $documentRoot = null): int
    {
        $path = self::resolveAbsolutePath($documentRoot);
        if (!is_file($path)) {
            return 0;
        }

        return (int)filesize($path);
    }

    public static function getModifiedAt(?string $documentRoot = null): ?\DateTimeImmutable
    {
        $path = self::resolveAbsolutePath($documentRoot);
        if (!is_file($path)) {
            return null;
        }

        $mtime = filemtime($path);
        if ($mtime === false) {
            return null;
        }

        return (new \DateTimeImmutable())->setTimestamp($mtime);
    }

    public static function read(?string $documentRoot = null): ?string
    {
        $path = self::resolveAbsolutePath($documentRoot);
        if (!is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false || $contents === '') {
            return null;
        }

        return $contents;
    }

    public static function writeAtomic(string $xml, ?string $documentRoot = null): void
    {
        $path = self::resolveAbsolutePath($documentRoot);
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Cannot create feed cache directory: ' . $dir);
        }

        $tmpPath = $path . '.tmp.' . getmypid() . '.' . bin2hex(random_bytes(4));
        $bytesWritten = file_put_contents($tmpPath, $xml, LOCK_EX);
        if ($bytesWritten === false) {
            throw new \RuntimeException('Cannot write temporary feed file: ' . $tmpPath);
        }

        self::publishTempFile($tmpPath, $documentRoot);
    }

    public static function createTempWritePath(?string $documentRoot = null): string
    {
        $dir = self::resolveCacheDirectory($documentRoot);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Cannot create feed cache directory: ' . $dir);
        }

        return $dir . '/yandex.yml.tmp.' . getmypid() . '.' . bin2hex(random_bytes(4));
    }

    public static function publishTempFile(string $tmpPath, ?string $documentRoot = null): void
    {
        $path = self::resolveAbsolutePath($documentRoot);
        if (!is_file($tmpPath)) {
            throw new \RuntimeException('Temporary feed file not found: ' . $tmpPath);
        }

        if (!rename($tmpPath, $path)) {
            @unlink($tmpPath);
            throw new \RuntimeException('Cannot publish feed file: ' . $path);
        }
    }
}
