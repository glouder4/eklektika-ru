<?php

declare(strict_types=1);

namespace OnlineService\Feed\Config;

final class FeedIntegrationConfig
{
    /**
     * @return array{regenerate_token?: string, site_base_url?: string}
     */
    public static function load(): array
    {
        $defaults = [
            'regenerate_token' => '',
            'site_base_url' => 'https://eklektika.ru',
        ];

        $paths = [
            $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/feed_integration_config.php',
        ];

        if (!empty($_SERVER['DOCUMENT_ROOT'])) {
            $paths[] = rtrim((string)$_SERVER['DOCUMENT_ROOT'], '/\\') . '/local/php_interface/feed_integration_config.php';
        }

        foreach ($paths as $path) {
            if (!is_file($path)) {
                continue;
            }
            $loaded = require $path;
            if (is_array($loaded)) {
                return array_merge($defaults, $loaded);
            }
        }

        return $defaults;
    }

    public static function getRegenerateToken(): string
    {
        return trim((string)(self::load()['regenerate_token'] ?? ''));
    }

    public static function getSiteBaseUrl(): string
    {
        $url = trim((string)(self::load()['site_base_url'] ?? ''));

        return rtrim($url, '/');
    }
}
