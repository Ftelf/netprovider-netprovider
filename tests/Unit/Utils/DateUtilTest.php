<?php
/**
 * Tests for DateUtil — the calendar wrapper used across billing.
 */

class DateUtilTest extends TestCase
{
    public function testConstructWithoutArgUsesNow(): void
    {
        $d = new DateUtil();
        $this->assertEqualsWithDelta(time(), $d->getTime(), 5);
    }

    public function testConstructWithDbNullDateYieldsNullTimestamp(): void
    {
        $d = new DateUtil(DateUtil::DB_NULL_DATE);
        $this->assertNull($d->getTime());
    }

    public function testConstructWithDbNullDatetimeYieldsNullTimestamp(): void
    {
        $d = new DateUtil(DateUtil::DB_NULL_DATETIME);
        $this->assertNull($d->getTime());
    }

    public function testConstructParsesDbDate(): void
    {
        $d = new DateUtil('2026-05-01');
        $this->assertSame(2026, $d->get(DateUtil::YEAR));
        $this->assertSame(5,    $d->get(DateUtil::MONTH));
        $this->assertSame(1,    $d->get(DateUtil::DAY));
    }

    public function testGetReturnsNullWhenTimestampNull(): void
    {
        $d = new DateUtil(DateUtil::DB_NULL_DATE);
        $this->assertNull($d->get(DateUtil::YEAR));
    }

    public function testSetMutatesIndividualField(): void
    {
        $d = new DateUtil('2020-01-15 12:30:45');
        $d->set(DateUtil::YEAR, 2026);
        $this->assertSame(2026, $d->get(DateUtil::YEAR));
        $d->set(DateUtil::MONTH, 6);
        $this->assertSame(6, $d->get(DateUtil::MONTH));
        $d->set(DateUtil::DAY, 7);
        $this->assertSame(7, $d->get(DateUtil::DAY));
        $d->set(DateUtil::HOUR, 1);
        $this->assertSame(1, $d->get(DateUtil::HOUR));
        $d->set(DateUtil::MINUTES, 2);
        $this->assertSame(2, $d->get(DateUtil::MINUTES));
        $d->set(DateUtil::SECONDS, 3);
        $this->assertSame(3, $d->get(DateUtil::SECONDS));
    }

    public function testAddMonthAdvancesDate(): void
    {
        $d = new DateUtil('2026-01-31 00:00:00');
        $d->add(DateUtil::MONTH, 1);
        // Month rollover: PHP normalises 2026-02-31 → 2026-03-03.
        $this->assertSame(3, $d->get(DateUtil::MONTH));
    }

    public function testAddDayWrapsMonthBoundary(): void
    {
        $d = new DateUtil('2026-01-31 00:00:00');
        $d->add(DateUtil::DAY, 1);
        $this->assertSame(2026, $d->get(DateUtil::YEAR));
        $this->assertSame(2,    $d->get(DateUtil::MONTH));
        $this->assertSame(1,    $d->get(DateUtil::DAY));
    }

    public function testAddYearAndHourAndMinuteAndSecond(): void
    {
        $d = new DateUtil('2026-05-01 10:00:00');
        $d->add(DateUtil::YEAR, 1);
        $this->assertSame(2027, $d->get(DateUtil::YEAR));
        $d->add(DateUtil::HOUR, 5);
        $this->assertSame(15, $d->get(DateUtil::HOUR));
        $d->add(DateUtil::MINUTES, 30);
        $this->assertSame(30, $d->get(DateUtil::MINUTES));
        $d->add(DateUtil::SECONDS, 45);
        $this->assertSame(45, $d->get(DateUtil::SECONDS));
    }

    public function testAfterAndBeforeAndCompareTo(): void
    {
        $a = new DateUtil('2026-01-01');
        $b = new DateUtil('2026-12-31');
        $this->assertTrue($b->after($a));
        $this->assertFalse($a->after($b));
        $this->assertTrue($a->before($b));
        $this->assertFalse($b->before($a));
        $this->assertSame(-1, $a->compareTo($b));
        $this->assertSame(1,  $b->compareTo($a));
        $this->assertSame(0,  $a->compareTo(new DateUtil('2026-01-01')));
    }

    public function testCompareToWithNonDateUtilReturnsNull(): void
    {
        $d = new DateUtil('2026-01-01');
        $this->assertNull($d->compareTo("not a DateUtil"));
    }

    public function testAfterThrowsForNonDateUtil(): void
    {
        $this->expectException(Exception::class);
        (new DateUtil('2026-01-01'))->after('foo');
    }

    public function testBeforeThrowsForNonDateUtil(): void
    {
        $this->expectException(Exception::class);
        (new DateUtil('2026-01-01'))->before('foo');
    }

    public function testAfterThrowsWhenEitherTimestampNull(): void
    {
        $this->expectException(Exception::class);
        (new DateUtil(DateUtil::DB_NULL_DATE))->after(new DateUtil('2026-01-01'));
    }

    public function testGetFormattedDateRoundTrip(): void
    {
        $d = new DateUtil('2026-05-01 10:20:30');
        $this->assertSame('2026-05-01', $d->getFormattedDate(DateUtil::DB_DATE));
        $this->assertSame('2026-05-01 10:20:30', $d->getFormattedDate(DateUtil::DB_DATETIME));
        $this->assertSame('05/2026', $d->getFormattedDate(DateUtil::FORMAT_MONTHLY));
    }

    public function testGetFormattedDateNullCases(): void
    {
        $d = new DateUtil(DateUtil::DB_NULL_DATE);
        $this->assertSame(DateUtil::DB_NULL_DATE,     $d->getFormattedDate(DateUtil::DB_DATE));
        $this->assertSame(DateUtil::DB_NULL_DATETIME, $d->getFormattedDate(DateUtil::DB_DATETIME));
        $this->assertSame('', $d->getFormattedDate(DateUtil::FORMAT_DATE));
        $this->assertSame('', $d->getFormattedDate(null));
    }

    public function testParseDateMonthly(): void
    {
        $d = new DateUtil(DateUtil::DB_NULL_DATE);
        $d->parseDate('05/2026', DateUtil::FORMAT_MONTHLY);
        $this->assertSame(2026, $d->get(DateUtil::YEAR));
        $this->assertSame(5,    $d->get(DateUtil::MONTH));
        $this->assertSame(1,    $d->get(DateUtil::DAY));
    }

    public function testParseDateFull(): void
    {
        $d = new DateUtil(DateUtil::DB_NULL_DATE);
        $d->parseDate('10:20:30 01.05.2026', DateUtil::FORMAT_FULL);
        $this->assertSame(2026, $d->get(DateUtil::YEAR));
        $this->assertSame(10,   $d->get(DateUtil::HOUR));
    }

    public function testParseDateDDMMYYYY(): void
    {
        $d = new DateUtil(DateUtil::DB_NULL_DATE);
        $d->parseDate('01.05.2026', DateUtil::FORMAT_DATE);
        $this->assertSame('2026-05-01', $d->getFormattedDate(DateUtil::DB_DATE));
    }

    public function testParseDateThrowsOnMalformed(): void
    {
        $d = new DateUtil(DateUtil::DB_NULL_DATE);
        $this->expectException(Exception::class);
        $d->parseDate('garbage', DateUtil::FORMAT_DATE);
    }

    public function testParseDateThrowsOnUnsupportedFormat(): void
    {
        $d = new DateUtil(DateUtil::DB_NULL_DATE);
        $this->expectException(Exception::class);
        $d->parseDate('2026-05-01', 'unsupported-format');
    }

    public function testToString(): void
    {
        $d = new DateUtil('2026-05-01 10:20:30');
        $this->assertSame('2026-05-01 10:20:30', (string) $d);
    }

    public function testSetTimeAndGetTime(): void
    {
        $d = new DateUtil(DateUtil::DB_NULL_DATE);
        $d->setTime(1234567890);
        $this->assertSame(1234567890, $d->getTime());
    }
}
