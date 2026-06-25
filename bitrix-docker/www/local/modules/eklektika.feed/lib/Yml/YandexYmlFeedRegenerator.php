<?php

declare(strict_types=1);

namespace OnlineService\Feed\Yml;

use OnlineService\Feed\Config\FeedIntegrationConfig;

final class YandexYmlFeedRegenerator
{
    private ?string $documentRoot;
    private ?string $siteBaseUrl;
    private ?FeedGenerationProgress $progress;

    public function __construct(
        ?string $documentRoot = null,
        ?string $siteBaseUrl = null,
        ?FeedGenerationProgress $progress = null
    ) {
        $this->documentRoot = $documentRoot;
        $this->siteBaseUrl = $siteBaseUrl;
        $this->progress = $progress;
    }

    /**
     * @return array{
     *     success: bool,
     *     bytes: int,
     *     path: string,
     *     duration_sec: float,
     *     generated_at: string,
     *     offers_in_feed?: int
     * }
     */
    public function regenerate(): array
    {
        $startedAt = microtime(true);
        $baseUrl = $this->resolveSiteBaseUrl();
        $this->progress?->step('Старт генерации YML-фида (' . $baseUrl . ')');

        $generator = new YandexYmlFeedGenerator($baseUrl, null, $this->progress);
        $tmpPath = YandexYmlFeedStorage::createTempWritePath($this->documentRoot);
        try {
            $offersInFeed = $generator->generateToFile($tmpPath);
            $this->progress?->step('Запись файла кэша...');
            YandexYmlFeedStorage::publishTempFile($tmpPath, $this->documentRoot);
        } catch (\Throwable $e) {
            @unlink($tmpPath);
            throw $e;
        }

        $path = YandexYmlFeedStorage::resolveAbsolutePath($this->documentRoot);
        $bytes = YandexYmlFeedStorage::getFileSize($this->documentRoot);
        $this->progress?->step('Готово: ' . $path . ' (' . $bytes . ' байт)');

        return [
            'success' => true,
            'bytes' => $bytes,
            'path' => $path,
            'duration_sec' => round(microtime(true) - $startedAt, 3),
            'generated_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
            'offers_in_feed' => $offersInFeed,
        ];
    }

    private function resolveSiteBaseUrl(): string
    {
        if ($this->siteBaseUrl !== null && $this->siteBaseUrl !== '') {
            return rtrim($this->siteBaseUrl, '/');
        }

        if (PHP_SAPI !== 'cli') {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
            if ($host !== '') {
                return $scheme . '://' . $host;
            }
        }

        return FeedIntegrationConfig::getSiteBaseUrl();
    }
}
