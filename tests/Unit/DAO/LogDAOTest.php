<?php
/**
 * LogDAO tests — audit log queries.
 */

class LogDAOTest extends TestCase
{
    public function testGetLogArrayAppliesAllFilters(): void
    {
        $this->db->seedObjectList([]);
        LogDAO::getLogArray(2, 7, '2026-01-01 00:00:00', '2026-12-31 00:00:00', 0, 100);
        $sql = $this->db->lastQuery();
        $this->assertStringContainsString("`LO_level`='2'",      $sql);
        $this->assertStringContainsString("`LO_personid`='7'",   $sql);
        $this->assertStringContainsString("`LO_datetime`>='2026-01-01 00:00:00'", $sql);
        $this->assertStringContainsString("`LO_datetime`<'2026-12-31 00:00:00'",  $sql);
        $this->assertStringContainsString('ORDER BY `LO_datetime` ASC', $sql);
        $this->assertStringContainsString('LIMIT 0, 100', $sql);
    }

    public function testGetLogArraySkipsFiltersWhenDefaults(): void
    {
        $this->db->seedObjectList([]);
        LogDAO::getLogArray();
        $sql = $this->db->lastQuery();
        $this->assertStringNotContainsString('LO_level', $sql);
        $this->assertStringNotContainsString('LO_personid', $sql);
    }

    public function testGetLastLogArrayThrowsWithoutCount(): void
    {
        $this->expectException(Exception::class);
        LogDAO::getLastLogArray(0);
    }

    public function testGetLastLogArrayLimits(): void
    {
        $this->db->seedObjectList([]);
        LogDAO::getLastLogArray(50);
        $this->assertStringContainsString('LIMIT 50', $this->db->lastQuery());
    }

    public function testRemoveLogByPersonID(): void
    {
        LogDAO::removeLogByPersonID(11);
        $this->assertStringContainsString("`LO_personid`='11'", $this->db->lastQuery());
    }
}
