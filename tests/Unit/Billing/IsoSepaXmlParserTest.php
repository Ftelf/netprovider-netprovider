<?php
/**
 * IsoSepaXmlParser tests — fixture-driven CAMT.053.001.02 parsing.
 *
 * Fixtures live under tests/Fixtures/bank/. Each test loads a fixture,
 * parses it, then asserts the resulting BankAccountEntry structure.
 */

class IsoSepaXmlParserTest extends TestCase
{
    private function fixture(string $name): string
    {
        $path = NP_TESTS_ROOT . 'Fixtures/bank/' . $name;
        return file_get_contents($path);
    }

    public function testParseValidStatementProducesTwoEntries(): void
    {
        $parser = new IsoSepaXmlParser($this->fixture('sepa_camt053_basic.xml'));
        $parser->parse();
        $doc = $parser->getDocument();

        $this->assertSame('42',  (string) $doc['LIST_NO']);
        $this->assertSame('CZK', $doc['CURRENCY']);
        $this->assertSame('CZ5555000000001234567890', $doc['IBAN']);
        // Bank code is IBAN chars 5-8 (after country + 2 check digits): CZ|55|5500|...
        $this->assertSame('5500', $doc['BANK_NUMBER']);
        $this->assertCount(2, $doc['LIST']);

        $credit = $doc['LIST'][0];
        $this->assertInstanceOf(BankAccountEntry::class, $credit);
        $this->assertSame(BankAccountEntry::TYPE_INCOMEPAYMENT, $credit->BE_typeoftransaction);
        $this->assertSame('500.00',     $credit->BE_amount);
        $this->assertSame('1234',       $credit->BE_variablesymbol);
        $this->assertSame('308',        $credit->BE_constantsymbol);
        $this->assertSame('Anna Novakova', $credit->BE_accountname);
        $this->assertSame('9876543210', $credit->BE_accountnumber);
        $this->assertSame('5500',       $credit->BE_banknumber);
        $this->assertSame('Internet payment', $credit->BE_message);
        $this->assertSame('2026-04-30 10:00:00', $credit->BE_datetime);

        $debit = $doc['LIST'][1];
        $this->assertSame(BankAccountEntry::TYPE_TRANSACTION, $debit->BE_typeoftransaction);
        $this->assertSame('-99.50', $debit->BE_amount, 'debit amount sign-flipped');
        $this->assertSame('Outgoing Payee', $debit->BE_accountname);
    }

    public function testWrongNamespaceThrows(): void
    {
        $parser = new IsoSepaXmlParser($this->fixture('sepa_camt053_wrong_namespace.xml'));
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/namespace/');
        $parser->parse();
    }

    public function testNonDailyStatementThrows(): void
    {
        $parser = new IsoSepaXmlParser($this->fixture('sepa_camt053_not_daily.xml'));
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/daily list/');
        $parser->parse();
    }

    public function testMalformedXmlThrowsOnParse(): void
    {
        $parser = new IsoSepaXmlParser('<this is>not<valid</xml');
        $this->expectException(Exception::class);
        @ $parser->parse();
    }
}
