<?php

declare(strict_types=1);

namespace Eklektika\Tests\EklektikaB24Rest;

use OnlineService\B24\N8nCrmGateway;
use PHPUnit\Framework\TestCase;

final class N8nCrmGatewayEarlyExitTest extends TestCase
{
    public function testCallRestMethodWithWebhookUrl_emptyUrl(): void
    {
        $result = N8nCrmGateway::callRestMethodWithWebhookUrl('', 'crm.lead.list', []);
        self::assertSame([
            'success' => 0,
            'error' => 'n8n webhook URL is empty',
        ], $result);
    }

    public function testCallRestMethodWithWebhookUrl_jsonEncodeFails(): void
    {
        $handle = fopen('php://memory', 'r');
        self::assertNotFalse($handle);
        try {
            $result = N8nCrmGateway::callRestMethodWithWebhookUrl(
                'https://example.invalid/webhook',
                'crm.lead.list',
                ['_non_json' => $handle]
            );
            self::assertIsArray($result);
            self::assertSame(0, $result['success']);
            self::assertSame('json_encode_failed', $result['error']);
        } finally {
            fclose($handle);
        }
    }
}
