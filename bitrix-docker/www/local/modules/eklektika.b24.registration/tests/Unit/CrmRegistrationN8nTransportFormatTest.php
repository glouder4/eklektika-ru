<?php

declare(strict_types=1);

namespace Eklektika\Tests\EklektikaB24Registration;

use OnlineService\B24\Registration\AjaxRegister\CrmRegistrationN8nTransport;
use PHPUnit\Framework\TestCase;

/**
 * Только «чистое» форматирование сообщений об ошибке webhook — без HTTP и Bitrix Application.
 */
final class CrmRegistrationN8nTransportFormatTest extends TestCase
{
    public function testFormatRegistrationWebhookFailureMessage_httpStatusOnly(): void
    {
        $msg = CrmRegistrationN8nTransport::formatRegistrationWebhookFailureMessage([
            'status' => 502,
        ]);
        self::assertStringContainsString('HTTP 502', $msg);
    }

    public function testFormatRegistrationWebhookFailureMessage_errorField(): void
    {
        $msg = CrmRegistrationN8nTransport::formatRegistrationWebhookFailureMessage([
            'error' => 'connection reset',
        ]);
        self::assertStringContainsString('connection reset', $msg);
    }

    public function testFormatRegistrationWebhookFailureMessage_dataMessage(): void
    {
        $msg = CrmRegistrationN8nTransport::formatRegistrationWebhookFailureMessage([
            'data' => ['message' => 'payload fail'],
        ]);
        self::assertStringContainsString('payload fail', $msg);
    }

    public function testFormatRegistrationWebhookFailureMessage_messageAndHint(): void
    {
        $msg = CrmRegistrationN8nTransport::formatRegistrationWebhookFailureMessage([
            'status' => 400,
            'data' => ['message' => 'bad', 'hint' => 'fix'],
        ]);
        self::assertStringContainsString('HTTP 400', $msg);
        self::assertStringContainsString('bad', $msg);
        self::assertStringContainsString('fix', $msg);
    }

    public function testFormatRegistrationWebhookFailureMessage_rawPreviewTruncated(): void
    {
        $long = str_repeat('x', 250);
        $msg = CrmRegistrationN8nTransport::formatRegistrationWebhookFailureMessage([
            'raw_preview' => $long,
        ]);
        self::assertSame(200, mb_strlen($msg));
        self::assertStringStartsWith('x', $msg);
    }

    public function testFormatRegistrationWebhookFailureMessage_fallbackWhenEmpty(): void
    {
        $msg = CrmRegistrationN8nTransport::formatRegistrationWebhookFailureMessage([]);
        self::assertSame('Ошибка запроса к n8n (webhook).', $msg);
    }
}
