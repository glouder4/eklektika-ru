<?php

declare(strict_types=1);

namespace Eklektika\Tests\EklektikaB24Registration\WebhookFixtures;

use OnlineService\B24\Registration\AjaxRegister\CrmRegistrationN8nPrecheckResponse;
use PHPUnit\Framework\TestCase;

/**
 * Регрессия контракта JSON по зафиксированным фикстурам из реальных прогонов (обезличенные samples).
 * Новые снимки — см. local/tests/fixtures/n8n-webhooks/README.md
 */
final class RegistrationWebhookSampleRegressionTest extends TestCase
{
    private static function fixturesDir(): string
    {
        return dirname(__DIR__, 4) . '/tests/fixtures/n8n-webhooks/samples';
    }

    /** @return array<string, mixed> */
    private static function loadJsonSample(string $filename): array
    {
        $path = self::fixturesDir() . '/' . $filename;
        self::assertFileExists($path, 'Fixture missing: ' . $path);
        $raw = file_get_contents($path);
        self::assertNotFalse($raw);
        $decoded = json_decode((string) $raw, true);
        self::assertIsArray($decoded);

        return $decoded;
    }

    public function testPrecheckNoDuplicate_sampleMatchesSuccessContract(): void
    {
        $data = self::loadJsonSample('precheck-no-duplicate.anon.json');
        $result = CrmRegistrationN8nPrecheckResponse::unwrapWebhookResult($data);
        self::assertTrue(
            CrmRegistrationN8nPrecheckResponse::registrationPrecheckResponseIndicatesSuccess($data, $result)
        );
        self::assertSame([], $result);
    }

    public function testPrecheckDuplicateHit_sampleStillIndicatesSuccess(): void
    {
        $data = self::loadJsonSample('precheck-duplicate-hit.anon.json');
        $result = CrmRegistrationN8nPrecheckResponse::unwrapWebhookResult($data);
        self::assertTrue(
            CrmRegistrationN8nPrecheckResponse::registrationPrecheckResponseIndicatesSuccess($data, $result)
        );
        self::assertIsList($result);
        self::assertNotEmpty($result);
    }

    public function testPrecheckCrmReject_sampleFormatsRejection(): void
    {
        $data = self::loadJsonSample('precheck-crm-reject.anon.json');
        $unwrapped = CrmRegistrationN8nPrecheckResponse::unwrapWebhookResult($data);
        self::assertIsArray($unwrapped);
        self::assertFalse(
            CrmRegistrationN8nPrecheckResponse::registrationPrecheckResponseIndicatesSuccess($data, $unwrapped)
        );
        self::assertSame(
            'Sample rejection for fixture regression',
            CrmRegistrationN8nPrecheckResponse::formatCrmPrecheckRejectionMessage($unwrapped)
        );
    }

    /** crm-check-inn-v1: пустой ИНН — успех и пустой result (как в реальном прогоне). */
    public function testInnPrecheckEmptyInn_sampleMatchesSuccessContract(): void
    {
        $data = self::loadJsonSample('inn-precheck-empty-inn.anon.json');
        $result = CrmRegistrationN8nPrecheckResponse::unwrapWebhookResult($data);
        self::assertTrue(
            CrmRegistrationN8nPrecheckResponse::registrationPrecheckResponseIndicatesSuccess($data, $result)
        );
        self::assertSame([], $result);
    }

    /** crm-check-inn-v1: ИНН найден в CRM — непустой список реквизитов. */
    public function testInnPrecheckRequisiteHit_sampleStillIndicatesSuccess(): void
    {
        $data = self::loadJsonSample('inn-precheck-requisite-hit.anon.json');
        $result = CrmRegistrationN8nPrecheckResponse::unwrapWebhookResult($data);
        self::assertTrue(
            CrmRegistrationN8nPrecheckResponse::registrationPrecheckResponseIndicatesSuccess($data, $result)
        );
        self::assertIsList($result);
        self::assertNotEmpty($result);
        self::assertIsArray($result[0] ?? null);
        self::assertSame(4, (int) ($result[0]['ENTITY_TYPE_ID'] ?? 0));
    }

    public function testInnPrecheckReject_sampleFormatsRejection(): void
    {
        $data = self::loadJsonSample('inn-precheck-reject.anon.json');
        $unwrapped = CrmRegistrationN8nPrecheckResponse::unwrapWebhookResult($data);
        self::assertIsArray($unwrapped);
        self::assertFalse(
            CrmRegistrationN8nPrecheckResponse::registrationPrecheckResponseIndicatesSuccess($data, $unwrapped)
        );
        self::assertSame(
            'Sample INN precheck rejection for fixture regression',
            CrmRegistrationN8nPrecheckResponse::formatCrmPrecheckRejectionMessage($unwrapped)
        );
    }
}
