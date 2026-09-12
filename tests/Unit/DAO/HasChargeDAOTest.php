<?php
/**
 * HasChargeDAO tests — most billing logic queries flow through here.
 */

class HasChargeDAOTest extends TestCase
{
    public function testGetHasChargeArrayByPersonIDFiltersAndOrders(): void
    {
        $this->db->seedObjectList([]);
        HasChargeDAO::getHasChargeArrayByPersonID(7);
        $sql = $this->db->lastQuery();
        $this->assertStringContainsString("`HC_personid`='7'", $sql);
        $this->assertStringContainsString('ORDER BY CH_priority DESC', $sql);
    }

    public function testGetHasChargeArrayByPersonIDThrowsWithoutId(): void
    {
        $this->expectException(Exception::class);
        HasChargeDAO::getHasChargeArrayByPersonID(0);
    }

    public function testGetHasChargeWithInternetChargeOnlyByPersonIDFiltersByType(): void
    {
        $this->db->seedObjectList([]);
        HasChargeDAO::getHasChargeWithInternetChargeOnlyByPersonID(11);
        $sql = $this->db->lastQuery();
        $this->assertStringContainsString("hc.HC_personid='11'", $sql);
        $this->assertStringContainsString('CH_type=' . Charge::TYPE_INTERNET_PAYMENT, $sql);
        $this->assertStringContainsString('HC_actualstate=' . HasCharge::ACTUALSTATE_ENABLED, $sql);
    }

    public function testGetHasChargeReportArrayBuildsDateWindow(): void
    {
        $this->db->seedObjectList([]);
        $from = new DateUtil('2026-01-01');
        $to   = new DateUtil('2026-12-31');
        HasChargeDAO::getHasChargeReportArray(
            5,
            [1, 2, 3],
            HasCharge::STATUS_ENABLED,
            HasCharge::ACTUALSTATE_ENABLED,
            $from,
            $to
        );
        $sql = $this->db->lastQuery();
        $this->assertStringContainsString("p.PE_personid='5'", $sql);
        $this->assertStringContainsString('CH_chargeid IN (1,2,3)', $sql);
        $this->assertStringContainsString('hc.HC_status = ' . HasCharge::STATUS_ENABLED, $sql);
        $this->assertStringContainsString('hc.HC_actualstate = ' . HasCharge::ACTUALSTATE_ENABLED, $sql);
        $this->assertStringContainsString("'2026-01-01'", $sql);
        $this->assertStringContainsString("'2026-12-31'", $sql);
    }

    public function testGetHasChargeReportArrayThrowsWithoutPidOrChargeIds(): void
    {
        $this->expectException(Exception::class);
        HasChargeDAO::getHasChargeReportArray(0, [1]);
    }

    public function testRemoveHasChargeByID(): void
    {
        HasChargeDAO::removeHasChargeByID(99);
        $this->assertStringContainsString("DELETE FROM `hascharge` WHERE `HC_haschargeid`='99'", $this->db->lastQuery());
    }
}
