<?php
/**
 * RBTXTParser smoke tests.
 *
 * The TXT statement format is column-oriented (specific byte offsets per
 * line). Producing a fully valid fixture is brittle, so these tests
 * verify the parts that are stable: header parsing, regex behaviour, and
 * exception paths.
 */

class RBTXTParserTest extends TestCase
{
    private function buildHeader(): string
    {
        // Bank header recognised by EBANKA / BANKOVNI_VYPIS / ZA + account header.
        return implode("\r\n", [
            'Raiffeisenbank a.s.',
            'Bankovní výpis č. 42',
            'za 30.04.2026',
            'Název účtu: Test account',
            'Číslo účtu: 1234567890/5500',
            'IBAN: CZ5555000000001234567890',
            'Měna: CZK',
            // 5x hard delimiter ("=====") — parser requires 5 to enter parseAccounts
            '=====',
            '=====',
            '=====',
            '=====',
            '=====',
        ]);
    }

    public function testParseHeaderAndStopsAtEmptyEntries(): void
    {
        // Append empty line right after the 5th delimiter so parseAccounts
        // bails out without trying to read entries (line_1 empty → return).
        $content = $this->buildHeader() . "\r\n\r\n";
        $parser = new RBTXTParser($content);
        $parser->parse();

        $doc = $parser->getDocument();
        $this->assertSame('Raiffeisenbank a.s.', $doc['BANK_NAME']);
        $this->assertSame('42',   $doc['LIST_NO']);
        $this->assertSame('2026-04-30', $doc['LIST_DATE_FROM']);
        $this->assertSame('2026-04-30', $doc['LIST_DATE_TO']);
        $this->assertSame('Test account', $doc['ACCOUNT_NAME']);
        $this->assertSame('1234567890', $doc['ACCOUNT_NUMBER']);
        $this->assertSame('5500', $doc['BANK_NUMBER']);
        $this->assertSame('CZK',  $doc['CURRENCY']);
        $this->assertSame([],     $doc['LIST']);
    }

    public function testParseRecognisesPeriodHeader(): void
    {
        $content = implode("\r\n", [
            'Raiffeisenbank a.s.',
            'Bankovní výpis č. 7',
            'Za období 01.04.2026/30.04.2026',
            'Název účtu: Acc',
            'Číslo účtu: 1/5500',
            'IBAN: CZX',
            'Měna: CZK',
            '=====', '=====', '=====', '=====', '=====',
            '',
        ]);
        $parser = new RBTXTParser($content);
        $parser->parse();
        $doc = $parser->getDocument();
        $this->assertSame('2026-04-01', $doc['LIST_DATE_FROM']);
        $this->assertSame('2026-04-30', $doc['LIST_DATE_TO']);
    }

    public function testEmptyContentParsesAsEmptyDocument(): void
    {
        $parser = new RBTXTParser('');
        $parser->parse();
        $doc = $parser->getDocument();
        $this->assertSame([], $doc['LIST']);
        $this->assertArrayNotHasKey('BANK_NAME', $doc);
    }

    public function testKnownTransactionArrayContainsExpectedTypes(): void
    {
        $r = new \ReflectionClass(RBTXTParser::class);
        $arr = $r->getStaticPropertyValue('KNOWN_TRANSACTION_ARRAY');
        $this->assertSame('Příchozí platba', $arr[2]);
        $this->assertSame('Trvalý převod',   $arr[4]);
    }
}
