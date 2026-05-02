<?php

declare(strict_types=1);

namespace Eklektika\Tests\EklektikaB24Registration;

use OnlineService\B24\Registration\AjaxRegister\AjaxRegisterPostParser;
use PHPUnit\Framework\TestCase;

final class AjaxRegisterPostParserTest extends TestCase
{
    public function testNormalizeInn_stripsNonDigits(): void
    {
        self::assertSame('7707083893', AjaxRegisterPostParser::normalizeInn('77 070 838 93'));
        self::assertSame('1234567890', AjaxRegisterPostParser::normalizeInn('abc12x34y56z7890'));
    }

    public function testNormalizeInn_emptyAfterStrip(): void
    {
        self::assertSame('', AjaxRegisterPostParser::normalizeInn(''));
        self::assertSame('', AjaxRegisterPostParser::normalizeInn('абв'));
    }

    public function testCollectMissingRequiredFields_noneMissing(): void
    {
        $post = [
            'name' => 'A',
            'lastname' => 'B',
            'phone' => '1',
            'email' => 'e@e.ru',
            'name_company' => 'C',
            'inn' => '12',
            'password' => 'p',
        ];
        self::assertSame([], AjaxRegisterPostParser::collectMissingRequiredFields($post));
    }

    public function testCollectMissingRequiredFields_allRequiredEmpty(): void
    {
        $post = [
            'name' => '',
            'lastname' => '',
            'phone' => '',
            'email' => '',
            'name_company' => '',
            'inn' => '',
            'password' => '',
        ];
        $missing = AjaxRegisterPostParser::collectMissingRequiredFields($post);
        self::assertCount(7, $missing);
        self::assertContains('Имя', $missing);
        self::assertContains('ИНН организации', $missing);
    }

    public function testCollectMissingRequiredFields_partial(): void
    {
        $post = [
            'name' => 'A',
            'lastname' => '',
            'phone' => '1',
            'email' => '',
            'name_company' => 'C',
            'inn' => '12',
            'password' => 'p',
        ];
        $missing = AjaxRegisterPostParser::collectMissingRequiredFields($post);
        self::assertSame(['Фамилия', 'E-mail'], $missing);
    }
}
