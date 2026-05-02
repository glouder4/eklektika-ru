<?php

declare(strict_types=1);

namespace Eklektika\Tests\EklektikaSync;

use OnlineService\Sync\FromCrm\CrmInboundUfMap;
use PHPUnit\Framework\TestCase;

final class CrmInboundUfMapTest extends TestCase
{
    public function testMarketingInboundSignalAbsent(): void
    {
        self::assertTrue(CrmInboundUfMap::marketingInboundSignalAbsent(null));
        self::assertTrue(CrmInboundUfMap::marketingInboundSignalAbsent(''));
        self::assertTrue(CrmInboundUfMap::marketingInboundSignalAbsent('   '));
        self::assertFalse(CrmInboundUfMap::marketingInboundSignalAbsent(0));
    }

    public function testMarketingInboundSignalTrue(): void
    {
        self::assertTrue(CrmInboundUfMap::marketingInboundSignalTrue(true));
        self::assertTrue(CrmInboundUfMap::marketingInboundSignalTrue(1));
        self::assertTrue(CrmInboundUfMap::marketingInboundSignalTrue('1'));
        self::assertTrue(CrmInboundUfMap::marketingInboundSignalTrue('Y'));
        self::assertTrue(CrmInboundUfMap::marketingInboundSignalTrue('Да'));
        self::assertTrue(CrmInboundUfMap::marketingInboundSignalTrue('on'));
    }

    public function testMarketingInboundSignalFalse(): void
    {
        self::assertTrue(CrmInboundUfMap::marketingInboundSignalFalse(false));
        self::assertTrue(CrmInboundUfMap::marketingInboundSignalFalse(0));
        self::assertTrue(CrmInboundUfMap::marketingInboundSignalFalse('0'));
        self::assertTrue(CrmInboundUfMap::marketingInboundSignalFalse('N'));
        self::assertTrue(CrmInboundUfMap::marketingInboundSignalFalse('Нет'));
        self::assertTrue(CrmInboundUfMap::marketingInboundSignalFalse('off'));
        self::assertFalse(CrmInboundUfMap::marketingInboundSignalFalse('maybe'));
    }

    public function testPeekMarketingAgentRawValue_prefersIsMarketingAgent(): void
    {
        $uf = CrmInboundUfMap::CONTACT_ADVERTISING_AGENT_UF;
        $row = [
            'IS_MARKETING_AGENT' => 1,
            $uf => 'should-not-win',
        ];
        self::assertSame(1, CrmInboundUfMap::peekMarketingAgentRawValue($row));
    }

    public function testPeekMarketingAgentRawValue_isMarketingAgentAbsentReturnsNull(): void
    {
        self::assertNull(CrmInboundUfMap::peekMarketingAgentRawValue(['IS_MARKETING_AGENT' => null]));
        self::assertNull(CrmInboundUfMap::peekMarketingAgentRawValue(['IS_MARKETING_AGENT' => '']));
    }

    public function testPeekMarketingAgentRawValue_contactAdvertisingUf(): void
    {
        $uf = CrmInboundUfMap::CONTACT_ADVERTISING_AGENT_UF;
        self::assertSame('x', CrmInboundUfMap::peekMarketingAgentRawValue([$uf => 'x']));
    }

    public function testPeekMarketingAgentRawValue_legacyUFAdvertstering(): void
    {
        self::assertSame(2, CrmInboundUfMap::peekMarketingAgentRawValue(['UF_ADVERSTERING_AGENT' => 2]));
    }

    public function testPeekMarketingAgentRawValue_noKeys(): void
    {
        self::assertNull(CrmInboundUfMap::peekMarketingAgentRawValue([]));
    }

    public function testUserDirectorUfToCrmInt(): void
    {
        self::assertSame(1, CrmInboundUfMap::userDirectorUfToCrmInt('Y'));
        self::assertSame(0, CrmInboundUfMap::userDirectorUfToCrmInt('N'));
    }

    public function testPrepareUserUpdatePayload_mapsAndStripsCrmUf(): void
    {
        $adv = CrmInboundUfMap::CONTACT_ADVERTISING_AGENT_UF;
        $dir = CrmInboundUfMap::CONTACT_IS_DIRECTOR_UF;
        $fields = [
            $adv => '1',
            $dir => '0',
            'UF_CRM_SHOULD_REMOVE' => 'noise',
            'KEEP' => 'ok',
        ];
        CrmInboundUfMap::prepareUserUpdatePayload($fields);
        self::assertArrayNotHasKey($adv, $fields);
        self::assertArrayNotHasKey($dir, $fields);
        self::assertArrayNotHasKey('UF_CRM_SHOULD_REMOVE', $fields);
        self::assertSame(1, $fields['UF_ADVERSTERING_AGENT']);
        self::assertSame(0, $fields['UF_IS_DIRECTOR']);
        self::assertSame('ok', $fields['KEEP']);
    }

    public function testPrepareUserUpdatePayload_absentMarketingLeavesUnset(): void
    {
        $adv = CrmInboundUfMap::CONTACT_ADVERTISING_AGENT_UF;
        $fields = [$adv => '  '];
        CrmInboundUfMap::prepareUserUpdatePayload($fields);
        self::assertArrayNotHasKey($adv, $fields);
        self::assertArrayNotHasKey('UF_ADVERSTERING_AGENT', $fields);
    }

    public function testPrepareUserUpdatePayload_absentDirectorLeavesUnset(): void
    {
        $dir = CrmInboundUfMap::CONTACT_IS_DIRECTOR_UF;
        $fields = [$dir => null];
        CrmInboundUfMap::prepareUserUpdatePayload($fields);
        self::assertArrayNotHasKey($dir, $fields);
        self::assertArrayNotHasKey('UF_IS_DIRECTOR', $fields);
    }
}
