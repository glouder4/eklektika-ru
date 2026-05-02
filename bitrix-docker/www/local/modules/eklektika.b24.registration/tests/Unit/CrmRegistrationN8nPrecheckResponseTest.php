<?php

declare(strict_types=1);

namespace Eklektika\Tests\EklektikaB24Registration;

use OnlineService\B24\Registration\AjaxRegister\CrmRegistrationN8nPrecheckResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CrmRegistrationN8nPrecheckResponseTest extends TestCase
{
    public function testRegistrationPrecheckResponseIndicatesSuccess_topLevelSuccess(): void
    {
        $data = ['success' => 1];
        self::assertTrue(
            CrmRegistrationN8nPrecheckResponse::registrationPrecheckResponseIndicatesSuccess($data, null)
        );
    }

    public function testRegistrationPrecheckResponseIndicatesSuccess_nestedResultSuccess(): void
    {
        $data = ['success' => 0];
        $result = ['success' => 1];
        self::assertTrue(
            CrmRegistrationN8nPrecheckResponse::registrationPrecheckResponseIndicatesSuccess($data, $result)
        );
    }

    public function testRegistrationPrecheckResponseIndicatesSuccess_falseWhenNoSuccessFlags(): void
    {
        $data = ['success' => 0];
        $result = ['success' => 0];
        self::assertFalse(
            CrmRegistrationN8nPrecheckResponse::registrationPrecheckResponseIndicatesSuccess($data, $result)
        );
    }

    public function testUnwrapWebhookResult_nonArrayReturnsNull(): void
    {
        self::assertNull(CrmRegistrationN8nPrecheckResponse::unwrapWebhookResult(null));
        self::assertNull(CrmRegistrationN8nPrecheckResponse::unwrapWebhookResult('x'));
    }

    public function testUnwrapWebhookResult_successZeroReturnsArray(): void
    {
        $decoded = ['success' => 0, 'message' => 'fail'];
        self::assertSame(
            $decoded,
            CrmRegistrationN8nPrecheckResponse::unwrapWebhookResult($decoded)
        );
    }

    public function testUnwrapWebhookResult_unwrapsResultKey(): void
    {
        $inner = ['ok' => 1];
        $decoded = ['success' => 1, 'result' => $inner];
        self::assertSame(
            $inner,
            CrmRegistrationN8nPrecheckResponse::unwrapWebhookResult($decoded)
        );
    }

    public function testUnwrapWebhookResult_noResultKeyReturnsArray(): void
    {
        $decoded = ['foo' => 'bar'];
        self::assertSame(
            $decoded,
            CrmRegistrationN8nPrecheckResponse::unwrapWebhookResult($decoded)
        );
    }

    /** @return iterable<string, array{0: array<string, mixed>, 1: string}> */
    public static function formatRejectionMessageProvider(): iterable
    {
        yield 'error_description' => [
            ['error_description' => '  desc  ', 'message' => 'ignored'],
            'desc',
        ];
        yield 'error' => [
            ['error' => 500, 'message' => 'm'],
            '500',
        ];
        yield 'message' => [
            ['message' => 'hello'],
            'hello',
        ];
        yield 'hint' => [
            ['hint' => ' try '],
            'try',
        ];
    }

    #[DataProvider('formatRejectionMessageProvider')]
    public function testFormatCrmPrecheckRejectionMessage_prefersFirstNonEmptyKey(
        array $result,
        string $expected
    ): void {
        self::assertSame(
            $expected,
            CrmRegistrationN8nPrecheckResponse::formatCrmPrecheckRejectionMessage($result)
        );
    }

    public function testFormatCrmPrecheckRejectionMessage_fallback(): void
    {
        self::assertSame(
            'Проверка в CRM завершилась с ошибкой.',
            CrmRegistrationN8nPrecheckResponse::formatCrmPrecheckRejectionMessage([])
        );
    }

    public function testIsProbableN8nErrorResponseBody_emptyOrNonArray(): void
    {
        self::assertFalse(CrmRegistrationN8nPrecheckResponse::isProbableN8nErrorResponseBody([]));
        self::assertFalse(CrmRegistrationN8nPrecheckResponse::isProbableN8nErrorResponseBody(null));
    }

    public function testIsProbableN8nErrorResponseBody_contractShapeRejected(): void
    {
        self::assertFalse(CrmRegistrationN8nPrecheckResponse::isProbableN8nErrorResponseBody([
            'success' => 0,
        ]));
        self::assertFalse(CrmRegistrationN8nPrecheckResponse::isProbableN8nErrorResponseBody([
            'result' => [],
        ]));
    }

    public function testIsProbableN8nErrorResponseBody_n8nStyleWithCode(): void
    {
        self::assertTrue(CrmRegistrationN8nPrecheckResponse::isProbableN8nErrorResponseBody([
            'message' => 'Not found',
            'code' => 404,
        ]));
    }

    public function testIsProbableN8nErrorResponseBody_hintOrStacktrace(): void
    {
        self::assertTrue(CrmRegistrationN8nPrecheckResponse::isProbableN8nErrorResponseBody([
            'message' => 'x',
            'hint' => 'h',
        ]));
        self::assertTrue(CrmRegistrationN8nPrecheckResponse::isProbableN8nErrorResponseBody([
            'message' => 'x',
            'stacktrace' => 's',
        ]));
    }
}
