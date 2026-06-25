<?php

declare(strict_types=1);

namespace OnlineService\Feed\Http;

/**
 * Отдача статического кэша фида без полного Bitrix prolog (обход OB + sproduction.integration).
 */
final class FeedHttpServe
{
    public static function clearOutputBuffers(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    public static function serveFile(
        string $absolutePath,
        string $contentType,
        ?\DateTimeImmutable $modifiedAt = null,
        int $fileSize = 0,
    ): void {
        self::clearOutputBuffers();

        header('X-Robots-Tag: noindex, nofollow', true);
        header('Content-Type: ' . $contentType);

        if ($modifiedAt !== null) {
            header('Last-Modified: ' . $modifiedAt->format('D, d M Y H:i:s') . ' GMT');
        }
        if ($fileSize > 0) {
            header('Content-Length: ' . $fileSize);
        }

        $bytes = readfile($absolutePath);
        if ($bytes === false) {
            http_response_code(500);
            exit;
        }

        exit;
    }

    public static function serveServiceUnavailable(string $message, int $retryAfterSeconds = 3600): void
    {
        self::clearOutputBuffers();

        http_response_code(503);
        header('Content-Type: text/plain; charset=UTF-8');
        header('Retry-After: ' . $retryAfterSeconds, true);
        header('X-Robots-Tag: noindex, nofollow', true);
        echo $message;
        exit;
    }
}
