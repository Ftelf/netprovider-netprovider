<?php
/**
 * Tests for DiacriticsUtil — strips Czech/Slovak/German diacritics.
 */

class DiacriticsUtilTest extends TestCase
{
    public function testRemovesCzechDiacritics(): void
    {
        $u = new DiacriticsUtil();
        $this->assertSame('Prilis zlutoucky kun upel dabelske ody',
            $u->removeDiacritic('Příliš žluťoučký kůň úpěl ďábelské ódy'));
    }

    public function testPreservesAscii(): void
    {
        $u = new DiacriticsUtil();
        $this->assertSame('hello world', $u->removeDiacritic('hello world'));
    }

    public function testHandlesEmpty(): void
    {
        $u = new DiacriticsUtil();
        $this->assertSame('', $u->removeDiacritic(''));
    }

    public function testHandlesUppercaseDiacritics(): void
    {
        $u = new DiacriticsUtil();
        $this->assertSame('ACDENRSTUYZ', $u->removeDiacritic('ÁČĎĚŇŘŠŤŮÝŽ'));
    }
}
