<?php

declare(strict_types=1);

namespace Eklektika\Tests\EklektikaB24Rest;

use OnlineService\B24\Config\RestTransportConfig;
use PHPUnit\Framework\TestCase;

/**
 * Сборка URL вебхуков Bitrix24 — зависит от глобальных констант ядра (в рантайме задаются Bitrix).
 */
final class RestTransportConfigTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!defined('URL_B24')) {
            define('URL_B24', 'https://example.bitrix24.ru/');
        }
        if (!defined('B24_REST_WEBHOOK_MAIN')) {
            define('B24_REST_WEBHOOK_MAIN', 'mainhooktoken');
        }
        if (!defined('B24_REST_WEBHOOK_KIT')) {
            define('B24_REST_WEBHOOK_KIT', 'kithooktoken');
        }
    }

    public function testBuildMainWebhookMethodUrl(): void
    {
        $url = RestTransportConfig::buildMainWebhookMethodUrl('crm.contact.list');
        self::assertStringEndsWith('/crm.contact.list.json', $url);
        self::assertStringContainsString('/rest/' . RestTransportConfig::MAIN_WEBHOOK_SCOPE . '/', $url);
        self::assertStringContainsString((string) B24_REST_WEBHOOK_MAIN, $url);
        self::assertStringStartsWith((string) URL_B24, $url);
    }

    public function testBuildKitWebhookPrefix(): void
    {
        $prefix = RestTransportConfig::buildKitWebhookPrefix();
        self::assertStringStartsWith((string) URL_B24, $prefix);
        self::assertStringContainsString('/rest/' . RestTransportConfig::KIT_WEBHOOK_SCOPE . '/', $prefix);
        self::assertStringEndsWith('/' . (string) B24_REST_WEBHOOK_KIT . '/', $prefix);
    }
}
