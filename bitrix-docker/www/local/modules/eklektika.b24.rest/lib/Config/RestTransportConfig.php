<?php

namespace OnlineService\B24\Config;

final class RestTransportConfig
{
    public const REQUEST_TIMEOUT_SECONDS = 30;
    public const CONNECT_TIMEOUT_SECONDS = 10;

    public const SITE_AJAX_PROXY_PATH = '/local/classes/ajax.php';
    public const SITE_REQUESTS_HANDLER_PATH = '/local/sync/from-site/site_requests_handler.php';

    public const MAIN_WEBHOOK_SCOPE = '1';
    public const KIT_WEBHOOK_SCOPE = '1';

    public static function buildMainWebhookMethodUrl(string $method): string
    {
        return URL_B24
            . 'rest/'
            . self::MAIN_WEBHOOK_SCOPE
            . '/'
            . B24_REST_WEBHOOK_MAIN
            . '/'
            . $method
            . '.json';
    }

    public static function buildKitWebhookPrefix(): string
    {
        return URL_B24 . 'rest/' . self::KIT_WEBHOOK_SCOPE . '/' . B24_REST_WEBHOOK_KIT . '/';
    }
}
