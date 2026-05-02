<?php
/**
 * AccountEntryUtil tests — matches incoming bank entries to persons.
 *
 * Constructor instantiates EmailUtil. Real EmailUtil reads config and
 * touches PEAR Mail. Tests build the instance via reflection,
 * substituting a Mockery double for the EmailUtil dependency.
 */

class AccountEntryUtilTest extends TestCase
{
    private function newWithMockedEmail(BankAccount $account, $emailMock): AccountEntryUtil
    {
        $r = new \ReflectionClass(AccountEntryUtil::class);
        $util = $r->newInstanceWithoutConstructor();

        $bp = $r->getProperty('_bankAccount');
        $bp->setAccessible(true);
        $bp->setValue($util, $account);

        $mp = $r->getProperty('_messages');
        $mp->setAccessible(true);
        $mp->setValue($util, []);

        $ep = $r->getProperty('emailUtil');
        $ep->setAccessible(true);
        $ep->setValue($util, $emailMock);

        return $util;
    }

    private function makeBankAccount(int $id = 1): BankAccount
    {
        $b = new BankAccount();
        $b->BA_bankaccountid = $id;
        return $b;
    }

    public function testSkipsNonPendingEntries(): void
    {
        $email = Mockery::mock();
        $util  = $this->newWithMockedEmail($this->makeBankAccount(), $email);

        $e = new BankAccountEntry();
        $e->BE_bankaccountentryid = 1;
        $e->BE_status = BankAccountEntry::STATUS_PROCESSED;
        $e->BE_typeoftransaction = BankAccountEntry::TYPE_INCOMEPAYMENT;
        $e->BE_variablesymbol = '12345';

        $this->db->seedObjectList([$e]);  // BankAccountEntryDAO
        $this->db->seedObjectList([]);    // ChargeDAO::getChargeArray

        $util->proceedAccountEntries();
        $this->assertEmpty($util->getMessages());
        $this->assertEmpty($this->db->updates);
    }

    public function testInternalTransactionEntriesAreMarkedAndSkipped(): void
    {
        $email = Mockery::mock();
        $util  = $this->newWithMockedEmail($this->makeBankAccount(), $email);

        $e = new BankAccountEntry();
        $e->BE_bankaccountentryid = 1;
        $e->BE_status            = BankAccountEntry::STATUS_PENDING;
        $e->BE_typeoftransaction = BankAccountEntry::TYPE_DIFFENTTRANSACTIONCHARGE;

        $this->db->seedObjectList([$e]);
        $this->db->seedObjectList([]);

        $util->proceedAccountEntries();

        $this->assertCount(1, $this->db->updates);
        $this->assertSame(BankAccountEntry::STATUS_PROCESSED, $this->db->updates[0]['object']->BE_status);
        $this->assertSame(BankAccountEntry::IDENTIFY_INTERNALTRANSACTION, $this->db->updates[0]['object']->BE_identifycode);
    }

    public function testEntriesWithoutVariableSymbolAreSkipped(): void
    {
        $email = Mockery::mock();
        $util  = $this->newWithMockedEmail($this->makeBankAccount(), $email);

        $e = new BankAccountEntry();
        $e->BE_bankaccountentryid = 1;
        $e->BE_status            = BankAccountEntry::STATUS_PENDING;
        $e->BE_typeoftransaction = BankAccountEntry::TYPE_INCOMEPAYMENT;
        $e->BE_variablesymbol    = '';

        $this->db->seedObjectList([$e]);
        $this->db->seedObjectList([]);

        $util->proceedAccountEntries();
        $this->assertEmpty($this->db->updates);
    }

    public function testDuplicateMatchesEmailsSupervisor(): void
    {
        $email = Mockery::mock();
        $email->shouldReceive('sendEmailMessage')->once()
            ->with('supervisor@example.com', Mockery::pattern('/duplicit/'), Mockery::any());

        $util = $this->newWithMockedEmail($this->makeBankAccount(), $email);

        $e = new BankAccountEntry();
        $e->BE_bankaccountentryid = 1;
        $e->BE_status            = BankAccountEntry::STATUS_PENDING;
        $e->BE_typeoftransaction = BankAccountEntry::TYPE_INCOMEPAYMENT;
        $e->BE_variablesymbol    = '1234';
        $e->BE_constantsymbol    = '0';
        $e->BE_specificsymbol    = '0';
        $e->BE_amount            = 100;
        $e->BE_datetime          = '2026-05-01 10:00:00';
        $e->BE_accountname       = 'TEST';
        $e->BE_accountnumber     = '12345';
        $e->BE_banknumber        = 5500;

        $p1 = new Person();
        $p1->PE_personid = 1;
        $p1->PE_firstname = 'Anna';
        $p1->PE_surname = 'Novakova';
        $p1->PA_personaccountid = 1;
        $p1->PE_status = Person::STATUS_ACTIVE;

        $p2 = clone $p1;
        $p2->PE_personid = 2;
        $p2->PE_firstname = 'Bob';

        $this->db->seedObjectList([$e]);   // bank entries
        $this->db->seedObjectList([]);     // chargeArray
        $this->db->seedObjectList([        // PersonDAO::getPersonWithAccountArrayForAccounting
            $p1, $p2,
        ]);

        $util->proceedAccountEntries();
        $messages = $util->getMessages();
        $this->assertNotEmpty($messages);
        $this->assertStringContainsString('duplicit', $messages[0]);
    }

    public function testUnmatchedAmountTriggersSupervisorEmail(): void
    {
        $email = Mockery::mock();
        $email->shouldReceive('sendEmailMessage')->once();

        $util = $this->newWithMockedEmail($this->makeBankAccount(), $email);

        $e = new BankAccountEntry();
        $e->BE_bankaccountentryid = 1;
        $e->BE_status            = BankAccountEntry::STATUS_PENDING;
        $e->BE_typeoftransaction = BankAccountEntry::TYPE_INCOMEPAYMENT;
        $e->BE_variablesymbol    = '1234';
        $e->BE_amount            = 250;
        $e->BE_datetime          = '2026-05-01 10:00:00';

        $charge = new Charge();
        $charge->CH_chargeid = 1;
        $charge->CH_amount   = 250;
        $charge->CH_name     = 'Internet';

        $this->db->seedObjectList([$e]);          // bank entries
        $this->db->seedObjectList([$charge]);     // charge map
        $this->db->seedObjectList([]);            // PersonDAO returns empty

        $util->proceedAccountEntries();

        $msgs = $util->getMessages();
        $this->assertNotEmpty($msgs);
        $this->assertStringContainsString('Internet', $msgs[0]);
    }
}
