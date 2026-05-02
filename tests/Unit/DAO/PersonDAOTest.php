<?php
/**
 * PersonDAO tests — assert SQL shape and parameter handling.
 *
 * DAOs talk to a global $database that we've replaced with DatabaseStub
 * in TestCase::setUp(). Tests seed expected return values, call the DAO,
 * then inspect $this->db->recordedQueries / lastQuery().
 */

class PersonDAOTest extends TestCase
{
    public function testGetPersonCountIssuesSelectCount(): void
    {
        $this->db->seedResult(7);
        $count = PersonDAO::getPersonCount();
        $this->assertSame(7, $count);
        $this->assertStringContainsString('SELECT count(*) FROM `person`', $this->db->lastQuery());
    }

    public function testGetPersonCountAppliesSearchAndGroupAndStatus(): void
    {
        $this->db->seedResult(3);
        PersonDAO::getPersonCount('john', 5, Person::STATUS_ACTIVE);
        $sql = $this->db->lastQuery();
        $this->assertStringContainsString("LIKE '%john%'", $sql);
        $this->assertStringContainsString("`PE_groupid`='5'", $sql);
        $this->assertStringContainsString("`PE_status`='" . Person::STATUS_ACTIVE . "'", $sql);
    }

    public function testGetPersonArrayOrdersBySurnameAndAppliesLimit(): void
    {
        $this->db->seedObjectList([]);
        PersonDAO::getPersonArray('', 0, -1, 0, 10);
        $sql = $this->db->lastQuery();
        $this->assertStringContainsString('ORDER BY `PE_surname`, `PE_firstname`', $sql);
        $this->assertStringContainsString('LIMIT 0, 10', $sql);
    }

    public function testGetPersonByIDReturnsPopulatedPerson(): void
    {
        $seeded = new Person();
        $seeded->PE_personid = 42;
        $seeded->PE_firstname = 'John';
        $seeded->PE_surname = 'Doe';
        $this->db->seedObject($seeded);

        $person = PersonDAO::getPersonByID(42);
        $this->assertSame(42,    $person->PE_personid);
        $this->assertSame('John', $person->PE_firstname);
        $this->assertStringContainsString("WHERE `PE_personid`='42'", $this->db->lastQuery());
    }

    public function testGetPersonByIDThrowsWhenIdNull(): void
    {
        $this->expectException(Exception::class);
        PersonDAO::getPersonByID(null);
    }

    public function testGetPersonArrayByGroupIDThrowsWithoutId(): void
    {
        $this->expectException(Exception::class);
        PersonDAO::getPersonArrayByGroupID(0);
    }

    public function testRemovePersonByIDIssuesDelete(): void
    {
        PersonDAO::removePersonByID(7);
        $this->assertStringContainsString("DELETE FROM `person` WHERE `PE_personid`='7'", $this->db->lastQuery());
    }

    public function testRemovePersonByIDThrowsWithoutId(): void
    {
        $this->expectException(Exception::class);
        PersonDAO::removePersonByID(0);
    }

    public function testGetPersonByIPThrowsWithoutIp(): void
    {
        $this->expectException(Exception::class);
        PersonDAO::getPersonByIP(null);
    }

    public function testGetPersonWithAccountArrayForAccountingBuildsExpectedJoin(): void
    {
        $this->db->seedObjectList([]);
        PersonDAO::getPersonWithAccountArrayForAccounting('1234', '0308', '999');
        $sql = $this->db->lastQuery();
        $this->assertStringContainsString("`PA_variablesymbol`='1234'", $sql);
        $this->assertStringContainsString("`PA_constantsymbol`='0'", $sql);
        $this->assertStringContainsString("`PA_constantsymbol`='0308'", $sql);
        $this->assertStringContainsString("`PA_specificsymbol`='0'", $sql);
        $this->assertStringContainsString("`PA_specificsymbol`='999'", $sql);
    }

    public function testGetPersonArrayForQOSFiltersByActiveStatus(): void
    {
        $this->db->seedObjectList([]);
        PersonDAO::getPersonArrayForQOS();
        $sql = $this->db->lastQuery();
        $this->assertStringContainsString("`PE_status`='" . Person::STATUS_ACTIVE . "'", $sql);
    }
}
