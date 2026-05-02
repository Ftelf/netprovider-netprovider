<?php
/**
 * ChargeEntryDAO tests.
 */

class ChargeEntryDAOTest extends TestCase
{
    public function testGetChargeEntryArrayByHasChargeIDThrowsWithoutId(): void
    {
        $this->expectException(Exception::class);
        ChargeEntryDAO::getChargeEntryArrayByHasChargeID(0);
    }

    public function testGetChargeEntryArrayByHasChargeIDOrders(): void
    {
        $this->db->seedObjectList([]);
        ChargeEntryDAO::getChargeEntryArrayByHasChargeID(33);
        $sql = $this->db->lastQuery();
        $this->assertStringContainsString("`CE_haschargeid`='33'", $sql);
        $this->assertStringContainsString('ORDER BY `CE_period_date` ASC', $sql);
    }

    public function testGetChargeEntryArrayByHasChargeIDDateFilters(): void
    {
        $this->db->seedObjectList([]);
        $from = new DateUtil('2026-01-01');
        $to   = new DateUtil('2026-12-31');
        ChargeEntryDAO::getChargeEntryArrayByHasChargeID(1, $from, $to);
        $sql = $this->db->lastQuery();
        $this->assertStringContainsString("date '2026-01-01' <= CE_period_date", $sql);
        $this->assertStringContainsString("CE_period_date <= date '2026-12-31'", $sql);
    }

    public function testRemoveChargeEntryByIDThrowsWithoutId(): void
    {
        $this->expectException(Exception::class);
        ChargeEntryDAO::removeChargeEntryByID(0);
    }
}
