<?php
/**
 * BankParserFactory tests — selects the right parser by datasource type.
 */

class BankParserFactoryTest extends TestCase
{
    public function testTxtTypeReturnsRBTXTParser(): void
    {
        $factory = new BankParserFactory(BankAccount::DATASOURCE_TYPE_RB_ATTACHMENT_TXT, '');
        $r = new \ReflectionClass($factory);
        $p = $r->getProperty('parser'); $p->setAccessible(true);
        $this->assertInstanceOf(RBTXTParser::class, $p->getValue($factory));
    }

    public function testIsoSepaXmlTypeReturnsIsoSepaXmlParser(): void
    {
        $factory = new BankParserFactory(BankAccount::DATASOURCE_TYPE_ISO_SEPA_XML, '<root/>');
        $r = new \ReflectionClass($factory);
        $p = $r->getProperty('parser'); $p->setAccessible(true);
        $this->assertInstanceOf(IsoSepaXmlParser::class, $p->getValue($factory));
    }

    public function testParseDelegatesToInnerParser(): void
    {
        $xml = file_get_contents(NP_TESTS_ROOT . 'Fixtures/bank/sepa_camt053_basic.xml');
        $factory = new BankParserFactory(BankAccount::DATASOURCE_TYPE_ISO_SEPA_XML, $xml);
        $factory->parse();
        $doc = $factory->getDocument();
        $this->assertCount(2, $doc['LIST']);
    }
}
