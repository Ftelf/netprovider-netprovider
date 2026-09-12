<?php
/**
 * ChargeDAO tests.
 */

class ChargeDAOTest extends TestCase
{
    public function testGetChargeCountIssuesCountQuery(): void
    {
        $this->db->seedResult(12);
        $this->assertSame(12, ChargeDAO::getChargeCount());
        $this->assertStringContainsString('SELECT count(*) FROM `charge`', $this->db->lastQuery());
    }

    public function testGetChargeArrayLimitsOptional(): void
    {
        $this->db->seedObjectList([]);
        ChargeDAO::getChargeArray();
        $this->assertSame('SELECT * FROM `charge`', $this->db->lastQuery());
    }

    public function testGetChargeArrayWithLimit(): void
    {
        $this->db->seedObjectList([]);
        ChargeDAO::getChargeArray(0, 25);
        $this->assertSame('SELECT * FROM `charge` LIMIT 0,25', $this->db->lastQuery());
    }

    public function testGetChargeArrayByPeriod(): void
    {
        $this->db->seedObjectList([]);
        ChargeDAO::getChargeArrayByPeriod(Charge::PERIOD_MONTHLY);
        $this->assertStringContainsString("WHERE `CH_period`=" . Charge::PERIOD_MONTHLY, $this->db->lastQuery());
    }

    public function testGetChargeByIDReturnsPopulated(): void
    {
        $seeded = new Charge();
        $seeded->CH_chargeid = 9;
        $seeded->CH_name = 'Internet 100/100';
        $seeded->CH_period = Charge::PERIOD_MONTHLY;
        $this->db->seedObject($seeded);

        $charge = ChargeDAO::getChargeByID(9);
        $this->assertSame(9, $charge->CH_chargeid);
        $this->assertSame('Internet 100/100', $charge->CH_name);
        $this->assertStringContainsString("WHERE `CH_chargeid`='9'", $this->db->lastQuery());
    }

    public function testGetChargeByIDThrowsWithoutId(): void
    {
        $this->expectException(Exception::class);
        ChargeDAO::getChargeByID(0);
    }

    public function testRemoveChargeByID(): void
    {
        ChargeDAO::removeChargeByID(5);
        $this->assertStringContainsString("DELETE FROM `charge` WHERE `CH_chargeid`='5'", $this->db->lastQuery());
    }

    public function testRemoveChargeByIDThrowsWithoutId(): void
    {
        $this->expectException(Exception::class);
        ChargeDAO::removeChargeByID(0);
    }

    public function testGetUsedChargeArrayThrowsWithoutId(): void
    {
        $this->expectException(Exception::class);
        ChargeDAO::getUsedChargeArray(0);
    }
}
