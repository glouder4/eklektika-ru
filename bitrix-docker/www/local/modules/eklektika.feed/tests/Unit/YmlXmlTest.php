<?php

declare(strict_types=1);

namespace Eklektika\Tests\EklektikaFeed;

use OnlineService\Feed\Yml\YmlXml;
use PHPUnit\Framework\TestCase;

final class YmlXmlTest extends TestCase
{
    public function testEscapeEncodesSpecialXmlCharacters(): void
    {
        $this->assertSame('Tom &amp; Jerry &lt;3&gt;', YmlXml::escape('Tom & Jerry <3>'));
    }

    public function testFormatPriceUsesDotSeparator(): void
    {
        $this->assertSame('1234.56', YmlXml::formatPrice(1234.56));
        $this->assertSame('100', YmlXml::formatPrice(100.0));
        $this->assertSame('', YmlXml::formatPrice(0));
    }

    public function testRenderOfferContainsRequiredFields(): void
    {
        $xml = YmlXml::renderOffer(
            42,
            true,
            'Ручка Parker',
            'https://example.ru/catalog/ruchka/offer/42/',
            '500',
            'RUB',
            7,
            ['https://example.ru/img/1.jpg'],
            '600',
            'Описание',
            'Parker',
            'PK-001',
            ['Цвет' => 'синий']
        );

        $this->assertStringContainsString('<offer id="42" available="true">', $xml);
        $this->assertStringContainsString('<name>Ручка Parker</name>', $xml);
        $this->assertStringContainsString('<oldprice>600</oldprice>', $xml);
        $this->assertStringContainsString('<param name="Цвет">синий</param>', $xml);
    }

    public function testRenderOfferSkipsEmptyOldPrice(): void
    {
        $xml = YmlXml::renderOffer(
            1,
            false,
            'Товар',
            'https://example.ru/t/',
            '100',
            'RUB',
            1,
            ['https://example.ru/i.jpg']
        );

        $this->assertStringContainsString('available="false"', $xml);
        $this->assertStringNotContainsString('<oldprice>', $xml);
    }
}
