<?php

declare(strict_types=1);

namespace OnlineService\Feed\Yml;

final class FeedGenerationProgress
{
    /** @var callable(string): void|null */
    private $writer;

    /**
     * @param callable(string): void|null $writer
     */
    public function __construct(?callable $writer = null)
    {
        $this->writer = $writer;
    }

    public function step(string $message): void
    {
        $line = '[' . date('H:i:s') . '] ' . $message;

        if ($this->writer !== null) {
            ($this->writer)($line);

            return;
        }

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, $line . PHP_EOL);

            return;
        }

        error_log($line);
    }
}
