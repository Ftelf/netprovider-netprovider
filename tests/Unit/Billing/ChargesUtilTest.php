<?php
/**
 * ChargesUtil tests — billing engine.
 *
 * The class drives every read through global $database (DatabaseStub).
 * Tests pre-seed loadObjectList / loadObject / loadResult queues in the
 * exact order ChargesUtil consumes them, then assert the side effects:
 * inserts/updates recorded by DatabaseStub, balance arithmetic, and
 * generated messages.
 *
 * Constructor consumption order:
 *   1. ChargeDAO::getChargeArray()           → seedObjectList()
 *
 * proceedChargesForPerson() consumption order, per call:
 *   1. HasChargeDAO::getHasChargeArrayByPersonID()  → seedObjectList()
 *   2. PersonAccountDAO::getPersonAccountByID()      → seedObject()
 *   3. ChargeEntryDAO::getChargeEntryArrayByHasChargeID() → seedObjectList()
 */

class ChargesUtilTest extends TestCase
{
    /** Seeds an empty charge map so the constructor doesn't fail. */
    private function newChargesUtilWithChargeMap(array $charges = []): ChargesUtil
    {
        global $eventCrossBar;
        $eventCrossBar = null; // we won't fire events in basic tests

        $this->db->seedObjectList($charges); // ChargeDAO::getChargeArray
        return new ChargesUtil();
    }

    private function makeCharge(int $id, float $amount = 100.0, int $writeOff = 0, int $tolerance = 14): Charge
    {
        $c = new Charge();
        $c->CH_chargeid = $id;
        $c->CH_name     = "Charge $id";
        $c->CH_period   = Charge::PERIOD_MONTHLY;
        $c->CH_baseamount = $amount;
        $c->CH_vat        = 0;
        $c->CH_amount     = $amount;
        $c->CH_currency   = 'CZK';
        $c->CH_tolerance  = $tolerance;
        $c->CH_writeoffoffset = $writeOff;
        $c->CH_type       = Charge::TYPE_INTERNET_PAYMENT;
        return $c;
    }

    private function makeHasCharge(int $id, int $personId, int $chargeId, string $start, ?string $end = null, int $status = HasCharge::STATUS_ENABLED, int $actual = HasCharge::ACTUALSTATE_DISABLED): HasCharge
    {
        $h = new HasCharge();
        $h->HC_haschargeid = $id;
        $h->HC_chargeid    = $chargeId;
        $h->HC_personid    = $personId;
        $h->HC_datestart   = $start;
        $h->HC_dateend     = $end ?? DateUtil::DB_NULL_DATE;
        $h->HC_status      = $status;
        $h->HC_actualstate = $actual;
        return $h;
    }

    private function makePerson(int $id, int $accountId = 1, int $status = Person::STATUS_ACTIVE): Person
    {
        $p = new Person();
        $p->PE_personid = $id;
        $p->PE_personaccountid = $accountId;
        $p->PE_firstname = 'Test';
        $p->PE_surname   = 'User';
        $p->PE_status    = $status;
        return $p;
    }

    private function makeAccount(int $id, float $balance = 0.0): PersonAccount
    {
        $a = new PersonAccount();
        $a->PA_personaccountid = $id;
        $a->PA_balance = $balance;
        $a->PA_income  = 0;
        $a->PA_outcome = 0;
        return $a;
    }

    public function testConstructorLoadsChargeMap(): void
    {
        $util = $this->newChargesUtilWithChargeMap([$this->makeCharge(1)]);
        $this->assertInstanceOf(ChargesUtil::class, $util);
        $sql = $this->db->recordedQueries[0];
        $this->assertSame('SELECT * FROM `charge`', $sql);
    }

    public function testProceedChargesForPersonSkipsWhenChargeMissing(): void
    {
        $util = $this->newChargesUtilWithChargeMap([]); // no charges loaded
        $person = $this->makePerson(1);

        // proceedChargesForPerson seeds:
        // 1. HasCharge list
        // 2. PersonAccount
        $this->db->seedObjectList([$this->makeHasCharge(1, 1, 99, '2026-01-01')]);
        $this->db->seedObject($this->makeAccount(1, 0));

        $util->proceedChargesForPerson($person);

        // Should have logged "non-existent chargeID" because chargeMap is empty.
        $this->assertNotEmpty($this->db->logs);
        $this->assertStringContainsString('non-existent chargeID', $this->db->logs[0]['text']);
    }

    public function testProceedChargesForPersonDeductsBalanceWhenSufficientFunds(): void
    {
        // Charge: 50 CZK, write-off offset 0 days, tolerance 14
        $charge = $this->makeCharge(1, 50.0, 0, 14);
        $util   = $this->newChargesUtilWithChargeMap([$charge]);

        $person = $this->makePerson(1, 1);

        // Past write-off date so payment must be deducted now.
        $hasCharge = $this->makeHasCharge(1, 1, 1, '2020-01-01');

        $entry = new ChargeEntry();
        $entry->CE_chargeentryid = 100;
        $entry->CE_haschargeid   = 1;
        $entry->CE_amount        = 50.0;
        $entry->CE_period_date   = '2020-01-01';
        $entry->CE_writeoffoffset = 0;
        $entry->CE_status         = ChargeEntry::STATUS_PENDING;
        $entry->CE_realize_date   = DateUtil::DB_NULL_DATE;
        $entry->CE_overdue        = 0;

        $account = $this->makeAccount(1, 200.0); // plenty

        $this->db->seedObjectList([$hasCharge]);   // HasChargeDAO
        $this->db->seedObject($account);           // PersonAccountDAO
        $this->db->seedObjectList([$entry]);       // ChargeEntryDAO

        $util->proceedChargesForPerson($person);

        // After processing:
        //   - PersonAccount balance: 200 - 50 = 150
        //   - ChargeEntry status: FINISHED
        //   - Updates to personaccount and chargeentry happened
        $accountUpdates = array_values(array_filter(
            $this->db->updates,
            fn($u) => $u['table'] === 'personaccount'
        ));
        $this->assertNotEmpty($accountUpdates);
        $this->assertEqualsWithDelta(150.0, $accountUpdates[0]['object']->PA_balance, 0.001);

        $entryUpdates = array_values(array_filter(
            $this->db->updates,
            fn($u) => $u['table'] === 'chargeentry'
        ));
        $this->assertNotEmpty($entryUpdates);
        $this->assertSame(ChargeEntry::STATUS_FINISHED, $entryUpdates[0]['object']->CE_status);
    }

    public function testProceedChargesForPersonWithExactBalance(): void
    {
        // Charge: 50 CZK, write-off offset 0 days, tolerance 14
        $charge = $this->makeCharge(1, 50.0, 0, 14);
        $util   = $this->newChargesUtilWithChargeMap([$charge]);

        $person = $this->makePerson(1, 1);

        // Past write-off date so payment must be deducted now.
        $hasCharge = $this->makeHasCharge(1, 1, 1, '2020-01-01');

        $entry = new ChargeEntry();
        $entry->CE_chargeentryid = 100;
        $entry->CE_haschargeid   = 1;
        $entry->CE_amount        = 50.0;
        $entry->CE_period_date   = '2020-01-01';
        $entry->CE_writeoffoffset = 0;
        $entry->CE_status         = ChargeEntry::STATUS_PENDING;
        $entry->CE_realize_date   = DateUtil::DB_NULL_DATE;
        $entry->CE_overdue        = 0;

        $account = $this->makeAccount(1, 50.0); // exactly the charge amount

        $this->db->seedObjectList([$hasCharge]);   // HasChargeDAO
        $this->db->seedObject($account);           // PersonAccountDAO
        $this->db->seedObjectList([$entry]);       // ChargeEntryDAO

        $util->proceedChargesForPerson($person);

        // Boundary: balance == amount takes the else branch:
        //   - ChargeEntry status: FINISHED
        //   - PersonAccount balance: 50 - 50 = 0
        $entryUpdates = array_values(array_filter(
            $this->db->updates,
            fn($u) => $u['table'] === 'chargeentry'
        ));
        $this->assertNotEmpty($entryUpdates);
        $this->assertSame(ChargeEntry::STATUS_FINISHED, $entryUpdates[0]['object']->CE_status);

        $accountUpdates = array_values(array_filter(
            $this->db->updates,
            fn($u) => $u['table'] === 'personaccount'
        ));
        $this->assertNotEmpty($accountUpdates);
        $this->assertEqualsWithDelta(0.0, $accountUpdates[0]['object']->PA_balance, 0.001);
    }

    public function testProceedChargesForPersonMarksInsufficientFundsWhenBalanceLow(): void
    {
        $charge = $this->makeCharge(1, 100.0, 0, 14);
        $util   = $this->newChargesUtilWithChargeMap([$charge]);

        $person   = $this->makePerson(1, 1);
        $hasCharge = $this->makeHasCharge(1, 1, 1, '2020-01-01');

        $entry = new ChargeEntry();
        $entry->CE_chargeentryid = 100;
        $entry->CE_haschargeid   = 1;
        $entry->CE_amount        = 100.0;
        $entry->CE_period_date   = '2020-01-01';
        $entry->CE_writeoffoffset = 0;
        $entry->CE_status         = ChargeEntry::STATUS_PENDING;
        $entry->CE_realize_date   = DateUtil::DB_NULL_DATE;
        $entry->CE_overdue        = 0;

        $account = $this->makeAccount(1, 10.0); // not enough

        $this->db->seedObjectList([$hasCharge]);
        $this->db->seedObject($account);
        $this->db->seedObjectList([$entry]);

        $util->proceedChargesForPerson($person);

        $entryUpdates = array_values(array_filter(
            $this->db->updates,
            fn($u) => $u['table'] === 'chargeentry'
        ));
        $this->assertNotEmpty($entryUpdates);
        $this->assertSame(ChargeEntry::STATUS_PENDING_INSUFFICIENTFUNDS, $entryUpdates[0]['object']->CE_status);

        // Balance should not change.
        $accountUpdates = array_values(array_filter(
            $this->db->updates,
            fn($u) => $u['table'] === 'personaccount'
        ));
        $this->assertNotEmpty($accountUpdates);
        $this->assertEqualsWithDelta(10.0, $accountUpdates[0]['object']->PA_balance, 0.001);
    }

    public function testProceedChargesForPersonDisablesPassivePersonsCharges(): void
    {
        $util = $this->newChargesUtilWithChargeMap([$this->makeCharge(1)]);
        $person = $this->makePerson(1, 1, Person::STATUS_PASSIVE);

        $hasCharge = $this->makeHasCharge(1, 1, 1, '2020-01-01', null, HasCharge::STATUS_ENABLED, HasCharge::ACTUALSTATE_ENABLED);
        $this->db->seedObjectList([$hasCharge]);

        $util->proceedChargesForPerson($person);

        $updates = array_values(array_filter(
            $this->db->updates,
            fn($u) => $u['table'] === 'hascharge'
        ));
        $this->assertNotEmpty($updates);
        $this->assertSame(HasCharge::ACTUALSTATE_DISABLED, $updates[0]['object']->HC_actualstate);
    }

    public function testGetMessagesReturnsAccumulator(): void
    {
        $util = $this->newChargesUtilWithChargeMap([]);
        $this->assertSame([], $util->getMessages());
    }
}
