<?php
/**
 * Tests for Utils — ad-hoc helpers used in many request handlers.
 */

class UtilsTest extends TestCase
{
    public function testGetParamReturnsDefaultWhenMissing(): void
    {
        $arr = ['a' => 'A'];
        $this->assertSame('default', Utils::getParam($arr, 'b', 'default'));
        $this->assertNull(Utils::getParam($arr, 'b'));
    }

    public function testGetParamSanitisesString(): void
    {
        $arr = ['name' => '  <b>O\'Brien</b>  '];
        $val = Utils::getParam($arr, 'name');
        $this->assertSame("O\\'Brien", $val);
        // Side effect: original array is mutated to the sanitised value.
        $this->assertSame("O\\'Brien", $arr['name']);
    }

    public function testGetParamPassesThroughNonString(): void
    {
        $arr = ['n' => 42];
        $this->assertSame(42, Utils::getParam($arr, 'n'));
        $arr2 = ['n' => [1, 2, 3]];
        $this->assertSame([1, 2, 3], Utils::getParam($arr2, 'n'));
    }

    public function testGetMicrotimeReturnsFloat(): void
    {
        $t = Utils::getmicrotime();
        $this->assertIsFloat($t);
        $this->assertGreaterThan(0.0, $t);
    }

    public function testIsEmail(): void
    {
        $this->assertTrue(Utils::is_email('user@example.com'));
        $this->assertTrue(Utils::is_email('a.b-c@sub.domain.co.uk'));
        $this->assertFalse(Utils::is_email('not-an-email'));
        $this->assertFalse(Utils::is_email(''));
    }

    public function testStringAsLineArray(): void
    {
        $text = "line1\nline2\r\nline3\n\nline4";
        $arr = Utils::stringAsLineArray($text);
        $this->assertSame(['line1', 'line2', 'line3', 'line4'], $arr);
    }

    public function testStringAsLineArrayEmpty(): void
    {
        $this->assertSame([], Utils::stringAsLineArray(''));
    }
}
