<?php
/**
 * IpDAO tests — IP allocation and reverse-lookup queries.
 */

class IpDAOTest extends TestCase
{
    public function testGetIpArrayByPersonID(): void
    {
        $this->db->seedObjectList([]);
        IpDAO::getIpArrayByPersonID(7);
        $this->assertStringContainsString("WHERE `IP_personid`='7'", $this->db->lastQuery());
    }

    public function testGetIpArrayByPersonIDThrowsWithoutId(): void
    {
        $this->expectException(Exception::class);
        IpDAO::getIpArrayByPersonID(0);
    }

    public function testGetIpWithPersonArraySortsByFirstname(): void
    {
        $this->db->seedObjectList([]);
        IpDAO::getIpWithPersonArray(IpDAO::PE_firstname, '');
        $this->assertStringContainsString('ORDER BY PE_firstname,PE_surname,PE_nick ASC', $this->db->lastQuery());
    }

    public function testGetIpWithPersonArraySortsByIpAddressUsesInetAton(): void
    {
        $this->db->seedObjectList([]);
        IpDAO::getIpWithPersonArray(IpDAO::IP_address, '');
        $this->assertStringContainsString('ORDER BY INET_ATON(IP_address) ASC', $this->db->lastQuery());
    }

    public function testGetIpWithPersonArrayAppliesSearch(): void
    {
        $this->db->seedObjectList([]);
        IpDAO::getIpWithPersonArray(IpDAO::PE_surname, 'kowalski', 0, 50);
        $sql = $this->db->lastQuery();
        $this->assertStringContainsString("LIKE '%kowalski%'", $sql);
        $this->assertStringContainsString('LIMIT 0, 50', $sql);
    }

    public function testIsAnyIpInNetworkReturnsTrueWhenObjectLoaded(): void
    {
        $seeded = new Ip();
        $seeded->IP_ipid = 1;
        $this->db->seedObject($seeded);
        $this->assertTrue(IpDAO::isAnyIpInNetwork(5));
    }

    public function testIsAnyIpInNetworkReturnsFalseWhenObjectMissing(): void
    {
        // No object seeded → loadObject() throws → DAO catches and returns false.
        $this->assertFalse(IpDAO::isAnyIpInNetwork(5));
    }

    public function testIsAnyIpInNetworkThrowsWithoutId(): void
    {
        $this->expectException(Exception::class);
        IpDAO::isAnyIpInNetwork(0);
    }

    public function testGetIpByIPThrowsWithoutAddress(): void
    {
        $this->expectException(Exception::class);
        IpDAO::getIpByIP('');
    }

    public function testRemoveIpByID(): void
    {
        IpDAO::removeIpByID(99);
        $this->assertStringContainsString("DELETE FROM `ip` WHERE `IP_ipid`='99'", $this->db->lastQuery());
    }
}
