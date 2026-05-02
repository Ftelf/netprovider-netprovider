<?php
/**
 * Tests for NumberFormat — money / SI parsing & formatting helpers.
 */

class NumberFormatTest extends TestCase
{
    public function testFormatMoneyUsesCommaDecimalAndSpaceThousands(): void
    {
        $this->assertSame('1 234,56', NumberFormat::formatMoney(1234.56));
        $this->assertSame('0,00',     NumberFormat::formatMoney(0));
        $this->assertSame('-1 234,56',NumberFormat::formatMoney(-1234.56));
    }

    public function testParseMoneyAcceptsLocalizedNumber(): void
    {
        $this->assertSame('1234.56', NumberFormat::parseMoney('1 234,56'));
        $this->assertSame('0',       NumberFormat::parseMoney('0'));
        $this->assertSame('-99.99',  NumberFormat::parseMoney('-99,99'));
    }

    public function testParseMoneyThrowsOnGarbage(): void
    {
        $this->expectException(Exception::class);
        NumberFormat::parseMoney('not a number');
    }

    public function testParseIntegerAcceptsNumeric(): void
    {
        $this->assertSame(42, NumberFormat::parseInteger('42'));
        $this->assertSame(-7, NumberFormat::parseInteger('-7'));
    }

    public function testParseIntegerThrowsOnGarbage(): void
    {
        $this->expectException(Exception::class);
        NumberFormat::parseInteger('abc');
    }

    public function testFormatMB(): void
    {
        $this->assertSame('1,00 MB',  NumberFormat::formatMB(1048576));
        $this->assertSame('2,00 MB',  NumberFormat::formatMB(2 * 1048576));
    }

    public function testFormatMBps(): void
    {
        $this->assertSame('1,00 KBps', NumberFormat::formatMBps(1024));
    }

    public function testFormatMbitps(): void
    {
        $this->assertSame('1,00 Mbps', NumberFormat::formatMbitps(131072));
    }

    public function testParseSIRecognisesPrefixes(): void
    {
        $this->assertEquals(1.0,        NumberFormat::parseSI('1bps', 'bps'));
        $this->assertEquals(1000.0,     NumberFormat::parseSI('1Kbps', 'bps'));
        $this->assertEquals(1000.0,     NumberFormat::parseSI('1kbps', 'bps'));
        $this->assertEquals(1_000_000.0, NumberFormat::parseSI('1Mbps', 'bps'));
        $this->assertEquals(1_000_000_000.0, NumberFormat::parseSI('1Gbps', 'bps'));
        $this->assertEquals(1_000_000_000_000.0, NumberFormat::parseSI('1Tbps', 'bps'));
    }

    public function testParseSIReturnsNullOnMalformed(): void
    {
        $this->assertNull(NumberFormat::parseSI('Xbps', 'bps'));
        $this->assertNull(NumberFormat::parseSI('1Zbps', 'bps'));
    }

    public function testInstancePropertiesPreserved(): void
    {
        $nf = new NumberFormat(123, 'CZK');
        $this->assertSame(123,   $nf->_value);
        $this->assertSame('CZK', $nf->_unit);
    }
}
