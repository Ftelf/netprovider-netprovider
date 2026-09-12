<?php
/**
 * BankAccountEntryDAO tests — imported bank-statement rows.
 */

class BankAccountEntryDAOTest extends TestCase
{
    public function testGetArrayByBankAccountIDFiltersAndOrders(): void
    {
        $this->db->seedObjectList([]);
        BankAccountEntryDAO::getBankAccountEntryArrayByBankAccountID(5);
        $this->assertSame(
            "SELECT * FROM `bankaccountentry` WHERE `BE_bankaccountid`='5' ORDER BY `BE_datetime` ASC",
            $this->db->lastQuery()
        );
    }

    /**
     * Guards two bugs at once:
     *  - the LIMIT guard must be strict (!== null); a loose != null drops the
     *    clause when limitstart is 0 (the first page).
     *  - LIMIT must follow ORDER BY, otherwise MySQL rejects the query.
     */
    public function testGetArrayByBankAccountIDAppendsLimitAfterOrderBy(): void
    {
        $this->db->seedObjectList([]);
        BankAccountEntryDAO::getBankAccountEntryArrayByBankAccountID(5, 0, 20);
        $this->assertSame(
            "SELECT * FROM `bankaccountentry` WHERE `BE_bankaccountid`='5' ORDER BY `BE_datetime` ASC LIMIT 0,20",
            $this->db->lastQuery()
        );
    }

    public function testGetArrayByBankAccountIDPaginatesBeyondFirstPage(): void
    {
        $this->db->seedObjectList([]);
        BankAccountEntryDAO::getBankAccountEntryArrayByBankAccountID(5, 20, 20);
        $this->assertStringEndsWith('ORDER BY `BE_datetime` ASC LIMIT 20,20', $this->db->lastQuery());
    }
}
