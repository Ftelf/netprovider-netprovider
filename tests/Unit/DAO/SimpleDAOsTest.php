<?php
/**
 * Sweep coverage of "simple" DAOs that follow the standard
 * count / list / byID / removeByID quartet — one test class instead
 * of dozens of near-identical files.
 *
 * Each entry in the dataProvider says: which DAO, expected table, and
 * primary-key column. Tests assert the right SQL fires and IDs propagate.
 */

class SimpleDAOsTest extends TestCase
{
    public static function simpleDaoSpecs(): array
    {
        return [
            // [DAO class, table, pkColumn, daoIdMethod, daoListMethod, daoCountMethod, daoRemoveMethod]
            'BankAccount'    => [BankAccountDAO::class,    'bankaccount',     'BA_bankaccountid',    'getBankAccountByID',   'getBankAccountArray',   'getBankAccountCount',   'removeBankAccountByID'],
            'PersonAccount'  => [PersonAccountDAO::class,  'personaccount',   'PA_personaccountid',  'getPersonAccountByID', 'getPersonAccountArray', 'getPersonAccountCount', 'removePersonAccountByID'],
            'Group'          => [GroupDAO::class,          'group',           'GR_groupid',          'getGroupByID',         'getGroupArray',         'getGroupCount',         'removeGroupByID'],
            'Role'           => [RoleDAO::class,           'role',            'RO_roleid',           'getRoleByID',          'getRoleArray',          'getRoleCount',          'removeRoleByID'],
            'HandleEvent'    => [HandleEventDAO::class,    'handleevent',     'HE_handleeventid',    'getHandleEventByID',   'getHandleEventArray',   'getHandleEventCount',   'removeHandleEventByID'],
            'Internet'       => [InternetDAO::class,       'internet',        'IN_internetid',       'getInternetByID',      'getInternetArray',      'getInternetCount',      'removeInternetByID'],
        ];
    }

    /** @dataProvider simpleDaoSpecs */
    public function testCountIssuesSelectCount(string $dao, string $table, string $pk, string $byId, string $list, string $count, string $remove): void
    {
        $this->db->seedResult(0);
        $dao::$count();
        $this->assertStringContainsString("SELECT count(*) FROM `$table`", $this->db->lastQuery());
    }

    /** @dataProvider simpleDaoSpecs */
    public function testListIssuesSelectStar(string $dao, string $table, string $pk, string $byId, string $list, string $count, string $remove): void
    {
        $this->db->seedObjectList([]);
        $dao::$list();
        $this->assertStringContainsString("SELECT * FROM `$table`", $this->db->lastQuery());
    }

    /**
     * @dataProvider simpleDaoSpecs
     *
     * Guards the LIMIT-append path: the clause must be concatenated onto the
     * base SELECT, not assigned over it. This is the exact bug fixed in
     * HasChargeDAO::getHasChargeArray ($query = -> $query .=); asserting the
     * full string here would catch the same regression in any of these DAOs.
     */
    public function testListAppendsLimitWhenProvided(string $dao, string $table, string $pk, string $byId, string $list, string $count, string $remove): void
    {
        $this->db->seedObjectList([]);
        $dao::$list(0, 20);
        $this->assertSame("SELECT * FROM `$table` LIMIT 0,20", $this->db->lastQuery());
    }

    /** @dataProvider simpleDaoSpecs */
    public function testByIDThrowsWithoutId(string $dao, string $table, string $pk, string $byId, string $list, string $count, string $remove): void
    {
        $this->expectException(Exception::class);
        $dao::$byId(0);
    }

    /** @dataProvider simpleDaoSpecs */
    public function testRemoveByIDIssuesDelete(string $dao, string $table, string $pk, string $byId, string $list, string $count, string $remove): void
    {
        $dao::$remove(123);
        $this->assertStringContainsString("DELETE FROM `$table` WHERE `$pk`='123'", $this->db->lastQuery());
    }
}
