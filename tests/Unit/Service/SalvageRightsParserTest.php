<?php

namespace App\Tests\Unit\Service;

use App\Service\SalvageRightsParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SalvageRightsParser::class)]
class SalvageRightsParserTest extends TestCase
{
    private SalvageRightsParser $parser;

    protected function setUp(): void
    {
        $this->parser = new SalvageRightsParser();
    }

    // ── Parse Tests ───────────────────────────────────────────────────────

    public function testParseNumericSalvageRights(): void
    {
        $this->assertEquals(3, $this->parser->parse('3'));
        $this->assertEquals(4, $this->parser->parse('4'));
        $this->assertEquals(50, $this->parser->parse('50'));
    }

    public function testParseNumericWithWhitespace(): void
    {
        $this->assertEquals(3, $this->parser->parse(' 3 '));
        $this->assertEquals(50, $this->parser->parse(' 50'));
    }

    public function testParseExchangeReturnsNull(): void
    {
        $this->assertNull($this->parser->parse('Exchange'));
        $this->assertNull($this->parser->parse('exchange'));
        $this->assertNull($this->parser->parse(' EXCHANGE '));
    }

    public function testParseExchangeWithPercentReturnsNull(): void
    {
        $this->assertNull($this->parser->parse('Exchange/50%'));
        $this->assertNull($this->parser->parse('exchange/25%'));
        $this->assertNull($this->parser->parse('Exchange/100%'));
    }

    public function testParseNoneReturnsZero(): void
    {
        $this->assertEquals(0, $this->parser->parse('None'));
        $this->assertEquals(0, $this->parser->parse('none'));
        $this->assertEquals(0, $this->parser->parse(' NONE '));
    }

    public function testParseDashReturnsZero(): void
    {
        $this->assertEquals(0, $this->parser->parse('—'));
        $this->assertEquals(0, $this->parser->parse('-'));
    }

    public function testParseNullReturnsZero(): void
    {
        $this->assertEquals(0, $this->parser->parse(null));
    }

    public function testParseEmptyStringReturnsZero(): void
    {
        $this->assertEquals(0, $this->parser->parse(''));
    }

    public function testParseMixedCaseExchangeWithPercent(): void
    {
        $this->assertNull($this->parser->parse('exchange/50%'));
        $this->assertNull($this->parser->parse('EXCHANGE/75%'));
    }

    public function testParseUnknownFormatReturnsZero(): void
    {
        $this->assertEquals(0, $this->parser->parse('random text'));
        $this->assertEquals(0, $this->parser->parse(''));
    }

    // ── Acquisition Allowed Tests ──────────────────────────────────────────

    public function testIsAcquisitionAllowedReturnsTrueForNumeric(): void
    {
        $this->assertTrue($this->parser->isAcquisitionAllowed('3'));
        $this->assertTrue($this->parser->isAcquisitionAllowed('4'));
        $this->assertTrue($this->parser->isAcquisitionAllowed('50'));
    }

    public function testIsAcquisitionAllowedReturnsFalseForExchange(): void
    {
        $this->assertFalse($this->parser->isAcquisitionAllowed('Exchange'));
        $this->assertFalse($this->parser->isAcquisitionAllowed('exchange'));
    }

    public function testIsAcquisitionAllowedReturnsFalseForExchangeWithPercent(): void
    {
        $this->assertFalse($this->parser->isAcquisitionAllowed('Exchange/50%'));
        $this->assertFalse($this->parser->isAcquisitionAllowed('exchange/25%'));
    }

    public function testIsAcquisitionAllowedReturnsFalseForNone(): void
    {
        $this->assertFalse($this->parser->isAcquisitionAllowed('None'));
        $this->assertFalse($this->parser->isAcquisitionAllowed('none'));
    }

    public function testIsAcquisitionAllowedReturnsFalseForDash(): void
    {
        $this->assertFalse($this->parser->isAcquisitionAllowed('—'));
    }

    public function testIsAcquisitionAllowedReturnsFalseForNull(): void
    {
        $this->assertFalse($this->parser->isAcquisitionAllowed(null));
    }

    public function testIsAcquisitionAllowedReturnsFalseForEmpty(): void
    {
        $this->assertFalse($this->parser->isAcquisitionAllowed(''));
    }

    // ── Format Description Tests ───────────────────────────────────────────

    public function testFormatDescriptionReturnsNoneForNull(): void
    {
        $this->assertEquals('None', $this->parser->formatDescription(null));
    }

    public function testFormatDescriptionReturnsNoneForEmpty(): void
    {
        $this->assertEquals('None', $this->parser->formatDescription(''));
    }

    public function testFormatDescriptionReturnsNoneForDash(): void
    {
        $this->assertEquals('None', $this->parser->formatDescription('—'));
    }

    public function testFormatDescriptionReturnsExchangeLabel(): void
    {
        $this->assertEquals('Exchange (no acquisition)', $this->parser->formatDescription('Exchange'));
    }

    public function testFormatDescriptionReturnsExchangePercentLabel(): void
    {
        $this->assertEquals('Exchange/50', $this->parser->formatDescription('Exchange/50%'));
    }

    public function testFormatDescriptionReturnsNumericAsIs(): void
    {
        $this->assertEquals('3', $this->parser->formatDescription('3'));
        $this->assertEquals('50', $this->parser->formatDescription('50'));
    }

    public function testFormatDescriptionReturnsRawForUnknown(): void
    {
        $this->assertEquals('custom terms', $this->parser->formatDescription('custom terms'));
    }
}
