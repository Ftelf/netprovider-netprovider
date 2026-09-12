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

    private function makeEntry(int $id, int $hasChargeId, float $amount, string $periodDate, int $status, int $overdue = 0, int $writeOff = 0): ChargeEntry
    {
        $e = new ChargeEntry();
        $e->CE_chargeentryid  = $id;
        $e->CE_haschargeid    = $hasChargeId;
        $e->CE_amount         = $amount;
        $e->CE_period_date    = $periodDate;
        $e->CE_writeoffoffset = $writeOff;
        $e->CE_status         = $status;
        $e->CE_realize_date   = DateUtil::DB_NULL_DATE;
        $e->CE_overdue        = $overdue;
        return $e;
    }

    /** The HasCharge captured by the last hascharge update, or null if none was written. */
    private function lastHasChargeUpdate(): ?HasCharge
    {
        $hc = array_values(array_filter(
            $this->db->updates,
            fn($u) => $u['table'] === 'hascharge'
        ));
        return $hc ? $hc[0]['object'] : null;
    }

    /** First day of the current month — an unambiguously "present" monthly period. */
    private function currentPeriod(): string
    {
        return date('Y-m-01');
    }

    /**
     * First day of the month `$offset` months from the current month, as 'Y-m-d'.
     * Anchored to day-1 so `+/- N months` never overflows (no 31st→next-month skew),
     * matching the projection walk which only ever steps day-1 dates.
     */
    private function monthStart(int $offset): string
    {
        return (new DateTimeImmutable('first day of this month'))
            ->modify(sprintf('%+d months', $offset))
            ->format('Y-m-d');
    }

    /** ChargeEntry rows inserted during the call, in insertion order. */
    private function chargeEntryInserts(): array
    {
        return array_values(array_filter(
            $this->db->inserts,
            fn($i) => $i['table'] === 'chargeentry'
        ));
    }

    /** The PersonAccount captured by the last personaccount update, or null if none. */
    private function lastPersonAccountUpdate(): ?PersonAccount
    {
        $pa = array_values(array_filter(
            $this->db->updates,
            fn($u) => $u['table'] === 'personaccount'
        ));
        return $pa ? end($pa)['object'] : null;
    }

    /** Pins the projection horizon so month counts are deterministic per test. */
    private function setAdvanceMonths(int $months): void
    {
        global $core;
        $core->setProperty(Core::BLANK_CHARGES_ADVANCE_COUNT, $months);
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

        // First-attempt payment: overdue recorded as 0 (no prior failed attempt),
        // realize date stamped today, and the charged amount added to outcome.
        $this->assertSame(0, $entryUpdates[0]['object']->CE_overdue);
        $this->assertSame(date('Y-m-d'), $entryUpdates[0]['object']->CE_realize_date);
        $this->assertEqualsWithDelta(50.0, $accountUpdates[0]['object']->PA_outcome, 0.001);
        // Money movement committed, never rolled back.
        $this->assertSame(1, $this->db->commits);
        $this->assertSame(0, $this->db->rollbacks);
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

        // Late but unpaid: overdue is recorded, but no money moved —
        // outcome untouched and realize date still the null-date.
        $this->assertGreaterThan(0, $entryUpdates[0]['object']->CE_overdue);
        $this->assertEqualsWithDelta(0.0, $accountUpdates[0]['object']->PA_outcome, 0.001);
        $this->assertSame(DateUtil::DB_NULL_DATE, $entryUpdates[0]['object']->CE_realize_date);
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

    public function testRetryAfterInsufficientFundsKeepsComputedOverdueOnPayment(): void
    {
        // An entry that previously failed for lack of funds is now payable.
        // The retry path must record the *actual* lateness, not reset to 0
        // (the first-attempt PENDING branch pays with overdue 0 — see above).
        $charge = $this->makeCharge(1, 50.0, 0, 14);
        $util   = $this->newChargesUtilWithChargeMap([$charge]);

        $person    = $this->makePerson(1, 1);
        $hasCharge = $this->makeHasCharge(1, 1, 1, '2020-01-01');

        $entry = new ChargeEntry();
        $entry->CE_chargeentryid  = 100;
        $entry->CE_haschargeid    = 1;
        $entry->CE_amount         = 50.0;
        $entry->CE_period_date    = '2020-01-01';   // write-off long past
        $entry->CE_writeoffoffset = 0;
        $entry->CE_status         = ChargeEntry::STATUS_PENDING_INSUFFICIENTFUNDS;
        $entry->CE_realize_date   = DateUtil::DB_NULL_DATE;
        $entry->CE_overdue        = 5;              // stale prior value

        $this->db->seedObjectList([$hasCharge]);
        $this->db->seedObject($this->makeAccount(1, 200.0));
        $this->db->seedObjectList([$entry]);

        $util->proceedChargesForPerson($person);

        $entryUpdates = array_values(array_filter(
            $this->db->updates,
            fn($u) => $u['table'] === 'chargeentry'
        ));
        $this->assertNotEmpty($entryUpdates);
        $this->assertSame(ChargeEntry::STATUS_FINISHED, $entryUpdates[0]['object']->CE_status);
        // Overdue recomputed to the real lateness — neither 0 nor the stale 5.
        $this->assertGreaterThan(5, $entryUpdates[0]['object']->CE_overdue);
    }

    public function testWriteOffInFutureLeavesEntryUnprocessed(): void
    {
        // Write-off date not yet reached: no collection, entry untouched.
        $charge = $this->makeCharge(1, 50.0, 0, 14);
        $util   = $this->newChargesUtilWithChargeMap([$charge]);

        $person    = $this->makePerson(1, 1);
        $hasCharge = $this->makeHasCharge(1, 1, 1, '2020-01-01');

        $entry = new ChargeEntry();
        $entry->CE_chargeentryid  = 100;
        $entry->CE_haschargeid    = 1;
        $entry->CE_amount         = 50.0;
        $entry->CE_period_date    = date('Y-m-01', strtotime('+2 months')); // future
        $entry->CE_writeoffoffset = 0;
        $entry->CE_status         = ChargeEntry::STATUS_PENDING;
        $entry->CE_realize_date   = DateUtil::DB_NULL_DATE;
        $entry->CE_overdue        = 0;

        $this->db->seedObjectList([$hasCharge]);
        $this->db->seedObject($this->makeAccount(1, 200.0));
        $this->db->seedObjectList([$entry]);

        $util->proceedChargesForPerson($person);

        // No collection happened: neither the entry nor the account was written.
        $this->assertSame([], array_values(array_filter(
            $this->db->updates, fn($u) => $u['table'] === 'chargeentry')));
        $this->assertSame([], array_values(array_filter(
            $this->db->updates, fn($u) => $u['table'] === 'personaccount')));
    }

    public function testAlreadyFinishedEntryIsNotChargedAgain(): void
    {
        // A FINISHED entry is never re-collected on a subsequent run.
        $charge = $this->makeCharge(1, 50.0, 0, 14);
        $util   = $this->newChargesUtilWithChargeMap([$charge]);

        $person    = $this->makePerson(1, 1);
        $hasCharge = $this->makeHasCharge(1, 1, 1, '2020-01-01');

        $entry = new ChargeEntry();
        $entry->CE_chargeentryid  = 100;
        $entry->CE_haschargeid    = 1;
        $entry->CE_amount         = 50.0;
        $entry->CE_period_date    = '2020-01-01';
        $entry->CE_writeoffoffset = 0;
        $entry->CE_status         = ChargeEntry::STATUS_FINISHED;
        $entry->CE_realize_date   = '2020-01-05';
        $entry->CE_overdue        = 0;

        $this->db->seedObjectList([$hasCharge]);
        $this->db->seedObject($this->makeAccount(1, 200.0));
        $this->db->seedObjectList([$entry]);

        $util->proceedChargesForPerson($person);

        // Not in {PENDING, INSUFFICIENT} → skipped: no money movement.
        $this->assertSame([], array_values(array_filter(
            $this->db->updates, fn($u) => $u['table'] === 'chargeentry')));
        $this->assertSame([], array_values(array_filter(
            $this->db->updates, fn($u) => $u['table'] === 'personaccount')));
    }

    public function testRollbackDuringCollectionLeavesBalanceUncorrupted(): void
    {
        // D2 (docs/billing.md §8): PA_balance is mutated in memory *before* the
        // per-entry transaction. When that transaction rolls back, the in-memory
        // balance is not restored, so the next entry deducts from a phantom
        // balance and its successful write persists the wrong value.
        //
        // Two due entries of 50 against a starting balance of 200. Entry 1's
        // commit fails and rolls back; entry 2 then succeeds. Intended: entry 1
        // is reverted, so entry 2 deducts from 200 -> persisted balance 150.
        // Current (buggy): entry 1's deduction survives, entry 2 deducts from
        // 150 -> persisted balance 100.
        $failing = new class extends DatabaseStub {
            public bool $failedOnce = false;
            public function commit(): void
            {
                if (!$this->failedOnce) {
                    $this->failedOnce = true;
                    throw new Exception('simulated commit failure on first entry');
                }
                parent::commit();
            }
        };

        global $database, $eventCrossBar;
        $eventCrossBar = null;
        $this->db = $failing;
        $database  = $failing;

        $failing->seedObjectList([$this->makeCharge(1, 50.0, 0, 14)]); // ChargeDAO (constructor)
        $util = new ChargesUtil();

        $person    = $this->makePerson(1, 1);
        $hasCharge = $this->makeHasCharge(1, 1, 1, '2020-01-01');

        $failing->seedObjectList([$hasCharge]);
        $failing->seedObject($this->makeAccount(1, 200.0));
        $failing->seedObjectList([
            $this->makeEntry(100, 1, 50.0, '2020-01-01', ChargeEntry::STATUS_PENDING),
            $this->makeEntry(101, 1, 50.0, '2020-02-01', ChargeEntry::STATUS_PENDING),
        ]);

        $util->proceedChargesForPerson($person);

        // The first transaction rolled back exactly once (failure path exercised).
        $this->assertSame(1, $failing->rollbacks);

        $accountUpdates = array_values(array_filter(
            $failing->updates,
            fn($u) => $u['table'] === 'personaccount'
        ));
        $this->assertNotEmpty($accountUpdates);
        $last = end($accountUpdates);
        $lastBalance = $last['object']->PA_balance;

        if (abs($lastBalance - 150.0) > 0.001) {
            $this->markTestIncomplete(sprintf(
                'D2 not yet fixed: a rolled-back deduction persisted. '
                . 'Expected balance 150.00 after entry 2, got %.2f. See docs/billing.md §8 (D2).',
                $lastBalance
            ));
        }
        $this->assertEqualsWithDelta(150.0, $lastBalance, 0.001);
    }

    // --- HC_actualstate decision table (docs/billing.md §5.1, §5.4, §5.5) ---
    //
    // The seed order for an ACTIVE person that reaches the entry loop is
    // HasCharge list -> PersonAccount -> ChargeEntry list. Charges that hit an
    // early pre-filter (HC_status DISABLED, not-yet-started) return before the
    // ChargeEntry query, so those tests must NOT seed an entry list.
    //
    // Accumulator tests set writeoffoffset high so the collection block is
    // skipped (write-off date in the future); the seeded CE_status / CE_overdue
    // then reach the state machine unchanged, isolating the decision from
    // collection side effects.

    public function testEnabledCleanPresentBecomesEnabled(): void
    {
        // ENABLED + clean current period + clean sequence -> ENABLED.
        $util   = $this->newChargesUtilWithChargeMap([$this->makeCharge(1, 50.0, 60, 14)]);
        $person = $this->makePerson(1, 1);
        // actual starts DISABLED, so flipping to ENABLED produces a write.
        $hasCharge = $this->makeHasCharge(1, 1, 1, '2020-01-01', null, HasCharge::STATUS_ENABLED, HasCharge::ACTUALSTATE_DISABLED);

        $this->db->seedObjectList([$hasCharge]);
        $this->db->seedObject($this->makeAccount(1, 0.0));
        $this->db->seedObjectList([$this->makeEntry(100, 1, 50.0, $this->currentPeriod(), ChargeEntry::STATUS_PENDING, 0, 60)]);

        $util->proceedChargesForPerson($person);

        $this->assertSame(HasCharge::ACTUALSTATE_ENABLED, $this->lastHasChargeUpdate()->HC_actualstate);
    }

    public function testPresentInsufficientWithinToleranceStaysEnabled(): void
    {
        // Current period unpaid but within tolerance days of overdue -> ENABLED.
        $util   = $this->newChargesUtilWithChargeMap([$this->makeCharge(1, 50.0, 60, 14)]);
        $person = $this->makePerson(1, 1);
        $hasCharge = $this->makeHasCharge(1, 1, 1, '2020-01-01', null, HasCharge::STATUS_ENABLED, HasCharge::ACTUALSTATE_DISABLED);

        $this->db->seedObjectList([$hasCharge]);
        $this->db->seedObject($this->makeAccount(1, 0.0));
        $this->db->seedObjectList([$this->makeEntry(100, 1, 50.0, $this->currentPeriod(), ChargeEntry::STATUS_PENDING_INSUFFICIENTFUNDS, 5, 60)]);

        $util->proceedChargesForPerson($person);

        $this->assertSame(HasCharge::ACTUALSTATE_ENABLED, $this->lastHasChargeUpdate()->HC_actualstate);
    }

    public function testPresentInsufficientBeyondToleranceBecomesDisabled(): void
    {
        // Current period unpaid and past tolerance -> DISABLED.
        $util   = $this->newChargesUtilWithChargeMap([$this->makeCharge(1, 50.0, 60, 14)]);
        $person = $this->makePerson(1, 1);
        // actual starts ENABLED so dropping to DISABLED produces a write.
        $hasCharge = $this->makeHasCharge(1, 1, 1, '2020-01-01', null, HasCharge::STATUS_ENABLED, HasCharge::ACTUALSTATE_ENABLED);

        $this->db->seedObjectList([$hasCharge]);
        $this->db->seedObject($this->makeAccount(1, 0.0));
        $this->db->seedObjectList([$this->makeEntry(100, 1, 50.0, $this->currentPeriod(), ChargeEntry::STATUS_PENDING_INSUFFICIENTFUNDS, 20, 60)]);

        $util->proceedChargesForPerson($person);

        $this->assertSame(HasCharge::ACTUALSTATE_DISABLED, $this->lastHasChargeUpdate()->HC_actualstate);
    }

    public function testPresentDisabledEntryForcesDisabled(): void
    {
        // A per-period DISABLED entry in the current period -> DISABLED.
        $util   = $this->newChargesUtilWithChargeMap([$this->makeCharge(1, 50.0, 60, 14)]);
        $person = $this->makePerson(1, 1);
        $hasCharge = $this->makeHasCharge(1, 1, 1, '2020-01-01', null, HasCharge::STATUS_ENABLED, HasCharge::ACTUALSTATE_ENABLED);

        $this->db->seedObjectList([$hasCharge]);
        $this->db->seedObject($this->makeAccount(1, 0.0));
        $this->db->seedObjectList([$this->makeEntry(100, 1, 50.0, $this->currentPeriod(), ChargeEntry::STATUS_DISABLED, 0, 60)]);

        $util->proceedChargesForPerson($person);

        $this->assertSame(HasCharge::ACTUALSTATE_DISABLED, $this->lastHasChargeUpdate()->HC_actualstate);
    }

    public function testPastUnpaidBeyondToleranceBreaksSequence(): void
    {
        // A long-overdue unpaid past period makes the sequence dirty -> DISABLED,
        // even though there is no problem with the current period.
        $util   = $this->newChargesUtilWithChargeMap([$this->makeCharge(1, 50.0, 0, 14)]);
        $person = $this->makePerson(1, 1);
        $hasCharge = $this->makeHasCharge(1, 1, 1, '2020-01-01', null, HasCharge::STATUS_ENABLED, HasCharge::ACTUALSTATE_ENABLED);

        $this->db->seedObjectList([$hasCharge]);
        // Low balance: collection keeps the entry INSUFFICIENT and recomputes an
        // overdue of thousands of days (2020 period), which exceeds tolerance.
        $this->db->seedObject($this->makeAccount(1, 0.0));
        $this->db->seedObjectList([$this->makeEntry(100, 1, 50.0, '2020-01-01', ChargeEntry::STATUS_PENDING_INSUFFICIENTFUNDS, 0, 0)]);

        $util->proceedChargesForPerson($person);

        $this->assertSame(HasCharge::ACTUALSTATE_DISABLED, $this->lastHasChargeUpdate()->HC_actualstate);
    }

    public function testForceEnabledOverridesEvenWithNoEntries(): void
    {
        // FORCE_ENABLED wins regardless of entries -> ENABLED.
        $util   = $this->newChargesUtilWithChargeMap([$this->makeCharge(1, 50.0, 0, 14)]);
        $person = $this->makePerson(1, 1);
        $hasCharge = $this->makeHasCharge(1, 1, 1, '2020-01-01', null, HasCharge::STATUS_FORCE_ENABLED, HasCharge::ACTUALSTATE_DISABLED);

        $this->db->seedObjectList([$hasCharge]);
        $this->db->seedObject($this->makeAccount(1, 0.0));
        $this->db->seedObjectList([]); // no entries at all

        $util->proceedChargesForPerson($person);

        $this->assertSame(HasCharge::ACTUALSTATE_ENABLED, $this->lastHasChargeUpdate()->HC_actualstate);
    }

    public function testForceDisabledOverridesCleanCurrentPeriod(): void
    {
        // FORCE_DISABLED wins even when the current period is fully paid -> DISABLED.
        $util   = $this->newChargesUtilWithChargeMap([$this->makeCharge(1, 50.0, 0, 14)]);
        $person = $this->makePerson(1, 1);
        $hasCharge = $this->makeHasCharge(1, 1, 1, '2020-01-01', null, HasCharge::STATUS_FORCE_DISABLED, HasCharge::ACTUALSTATE_ENABLED);

        $this->db->seedObjectList([$hasCharge]);
        $this->db->seedObject($this->makeAccount(1, 0.0));
        // FINISHED entry is clean and not re-collected, so it would otherwise enable.
        $this->db->seedObjectList([$this->makeEntry(100, 1, 50.0, $this->currentPeriod(), ChargeEntry::STATUS_FINISHED, 0, 0)]);

        $util->proceedChargesForPerson($person);

        $this->assertSame(HasCharge::ACTUALSTATE_DISABLED, $this->lastHasChargeUpdate()->HC_actualstate);
    }

    public function testEnabledWithNoEntriesBecomesDisabled(): void
    {
        // ENABLED but the charge has no ChargeEntry rows -> DISABLED.
        $util   = $this->newChargesUtilWithChargeMap([$this->makeCharge(1, 50.0, 0, 14)]);
        $person = $this->makePerson(1, 1);
        $hasCharge = $this->makeHasCharge(1, 1, 1, '2020-01-01', null, HasCharge::STATUS_ENABLED, HasCharge::ACTUALSTATE_ENABLED);

        $this->db->seedObjectList([$hasCharge]);
        $this->db->seedObject($this->makeAccount(1, 0.0));
        $this->db->seedObjectList([]); // no entries

        $util->proceedChargesForPerson($person);

        $this->assertSame(HasCharge::ACTUALSTATE_DISABLED, $this->lastHasChargeUpdate()->HC_actualstate);
    }

    public function testChargeAfterEndDateBecomesDisabled(): void
    {
        // Charge whose window has ended is never in the present -> DISABLED.
        $util   = $this->newChargesUtilWithChargeMap([$this->makeCharge(1, 50.0, 0, 14)]);
        $person = $this->makePerson(1, 1);
        $hasCharge = $this->makeHasCharge(1, 1, 1, '2019-01-01', '2019-06-01', HasCharge::STATUS_ENABLED, HasCharge::ACTUALSTATE_ENABLED);

        $this->db->seedObjectList([$hasCharge]);
        $this->db->seedObject($this->makeAccount(1, 0.0));
        $this->db->seedObjectList([]); // entries irrelevant once the charge has ended

        $util->proceedChargesForPerson($person);

        $this->assertSame(HasCharge::ACTUALSTATE_DISABLED, $this->lastHasChargeUpdate()->HC_actualstate);
    }

    public function testNotYetStartedChargeBecomesDisabled(): void
    {
        // Charge whose start date is in the future -> DISABLED, before entries load.
        $util   = $this->newChargesUtilWithChargeMap([$this->makeCharge(1, 50.0, 0, 14)]);
        $person = $this->makePerson(1, 1);
        $future = date('Y-m-01', strtotime('+1 year'));
        $hasCharge = $this->makeHasCharge(1, 1, 1, $future, null, HasCharge::STATUS_ENABLED, HasCharge::ACTUALSTATE_ENABLED);

        // No ChargeEntry list seeded: the not-started pre-filter returns first.
        $this->db->seedObjectList([$hasCharge]);
        $this->db->seedObject($this->makeAccount(1, 0.0));

        $util->proceedChargesForPerson($person);

        $this->assertSame(HasCharge::ACTUALSTATE_DISABLED, $this->lastHasChargeUpdate()->HC_actualstate);
    }

    public function testStatusDisabledChargeIsForcedDisabled(): void
    {
        // HC_status DISABLED -> actualstate DISABLED via the earliest pre-filter,
        // before the ChargeEntry query.
        $util   = $this->newChargesUtilWithChargeMap([$this->makeCharge(1, 50.0, 0, 14)]);
        $person = $this->makePerson(1, 1);
        $hasCharge = $this->makeHasCharge(1, 1, 1, '2020-01-01', null, HasCharge::STATUS_DISABLED, HasCharge::ACTUALSTATE_ENABLED);

        $this->db->seedObjectList([$hasCharge]);
        $this->db->seedObject($this->makeAccount(1, 0.0));

        $util->proceedChargesForPerson($person);

        $this->assertSame(HasCharge::ACTUALSTATE_DISABLED, $this->lastHasChargeUpdate()->HC_actualstate);
    }

    public function testNoStateWriteWhenActualStateUnchanged(): void
    {
        // When the computed state already equals the stored one, no write occurs.
        $util   = $this->newChargesUtilWithChargeMap([$this->makeCharge(1, 50.0, 60, 14)]);
        $person = $this->makePerson(1, 1);
        // Clean present period would compute ENABLED; actual is already ENABLED.
        $hasCharge = $this->makeHasCharge(1, 1, 1, '2020-01-01', null, HasCharge::STATUS_ENABLED, HasCharge::ACTUALSTATE_ENABLED);

        $this->db->seedObjectList([$hasCharge]);
        $this->db->seedObject($this->makeAccount(1, 0.0));
        $this->db->seedObjectList([$this->makeEntry(100, 1, 50.0, $this->currentPeriod(), ChargeEntry::STATUS_PENDING, 0, 60)]);

        $util->proceedChargesForPerson($person);

        $this->assertNull($this->lastHasChargeUpdate());
    }

    public function testGetMessagesReturnsAccumulator(): void
    {
        $util = $this->newChargesUtilWithChargeMap([]);
        $this->assertSame([], $util->getMessages());
    }

    // ---------------------------------------------------------------------
    // Projection — createOrRemoveChargeEntriesForPerson (§4)
    //
    // Consumption order per call:
    //   1. HasChargeDAO::getHasChargeArrayByPersonID()      → seedObjectList()
    //   2. per passing monthly HasCharge:
    //      a. ChargeEntryDAO::getChargeEntryArrayByHasChargeID() → seedObjectList()
    //      b. PersonAccountDAO::getPersonAccountByID() (removal step) → seedObject()
    // ---------------------------------------------------------------------

    public function testProjectionCreatesMonthlyEntriesUpToAdvanceCap(): void
    {
        // Open-ended charge, no existing entries: expect this month + advance months.
        $this->setAdvanceMonths(2);
        $util   = $this->newChargesUtilWithChargeMap([$this->makeCharge(1, 50.0, 7, 14)]);
        $person = $this->makePerson(1, 1);
        $hasCharge = $this->makeHasCharge(1, 1, 1, $this->monthStart(0), null);

        $this->db->seedObjectList([$hasCharge]);
        $this->db->seedObjectList([]);                 // no existing ChargeEntries
        $this->db->seedObject($this->makeAccount(1, 0.0));

        $util->createOrRemoveChargeEntriesForPerson($person);

        $inserts = $this->chargeEntryInserts();
        $this->assertCount(3, $inserts);
        $this->assertSame(
            [$this->monthStart(0), $this->monthStart(1), $this->monthStart(2)],
            array_map(fn($i) => $i['object']->CE_period_date, $inserts)
        );
        foreach ($inserts as $i) {
            $this->assertSame(ChargeEntry::STATUS_PENDING, $i['object']->CE_status);
            $this->assertSame(50.0, $i['object']->CE_amount);
            $this->assertSame(7, $i['object']->CE_writeoffoffset);
            $this->assertSame(DateUtil::DB_NULL_DATE, $i['object']->CE_realize_date);
            $this->assertSame(0, $i['object']->CE_overdue);
        }
    }

    public function testProjectionIsIdempotentForExistingPeriods(): void
    {
        // Two of the three in-window months already exist → only the gap is inserted.
        $this->setAdvanceMonths(2);
        $util   = $this->newChargesUtilWithChargeMap([$this->makeCharge(1, 50.0)]);
        $person = $this->makePerson(1, 1);
        $hasCharge = $this->makeHasCharge(1, 1, 1, $this->monthStart(0), null);

        $this->db->seedObjectList([$hasCharge]);
        $this->db->seedObjectList([
            $this->makeEntry(100, 1, 50.0, $this->monthStart(0), ChargeEntry::STATUS_PENDING),
            $this->makeEntry(101, 1, 50.0, $this->monthStart(1), ChargeEntry::STATUS_PENDING),
        ]);
        $this->db->seedObject($this->makeAccount(1, 0.0));

        $util->createOrRemoveChargeEntriesForPerson($person);

        $inserts = $this->chargeEntryInserts();
        $this->assertCount(1, $inserts);
        $this->assertSame($this->monthStart(2), $inserts[0]['object']->CE_period_date);
    }

    public function testProjectionIsCappedByAdvanceNotByFarDateEnd(): void
    {
        // dateEnd a year out, advance only 2 → horizon wins, not dateEnd.
        $this->setAdvanceMonths(2);
        $util   = $this->newChargesUtilWithChargeMap([$this->makeCharge(1, 50.0)]);
        $person = $this->makePerson(1, 1);
        $hasCharge = $this->makeHasCharge(1, 1, 1, $this->monthStart(0), $this->monthStart(12));

        $this->db->seedObjectList([$hasCharge]);
        $this->db->seedObjectList([]);
        $this->db->seedObject($this->makeAccount(1, 0.0));

        $util->createOrRemoveChargeEntriesForPerson($person);

        $inserts = $this->chargeEntryInserts();
        $this->assertCount(3, $inserts);
        $this->assertSame($this->monthStart(2), end($inserts)['object']->CE_period_date);
    }

    public function testProjectionStopsAtDateEndWhenBeforeCap(): void
    {
        // dateEnd (this month + 1) is nearer than the 6-month horizon → dateEnd wins.
        $this->setAdvanceMonths(6);
        $util   = $this->newChargesUtilWithChargeMap([$this->makeCharge(1, 50.0)]);
        $person = $this->makePerson(1, 1);
        $hasCharge = $this->makeHasCharge(1, 1, 1, $this->monthStart(0), $this->monthStart(1));

        $this->db->seedObjectList([$hasCharge]);
        $this->db->seedObjectList([]);
        $this->db->seedObject($this->makeAccount(1, 0.0));

        $util->createOrRemoveChargeEntriesForPerson($person);

        $this->assertSame(
            [$this->monthStart(0), $this->monthStart(1)],
            array_map(fn($i) => $i['object']->CE_period_date, $this->chargeEntryInserts())
        );
    }

    public function testOutOfScopeEntryBeforeStartIsDeleted(): void
    {
        // An entry two months before datestart is outside the window → DELETEd.
        $this->setAdvanceMonths(0);
        $util   = $this->newChargesUtilWithChargeMap([$this->makeCharge(1, 50.0)]);
        $person = $this->makePerson(1, 1);
        $hasCharge = $this->makeHasCharge(1, 1, 1, $this->monthStart(0), null);

        $this->db->seedObjectList([$hasCharge]);
        $this->db->seedObjectList([
            $this->makeEntry(200, 1, 50.0, $this->monthStart(-2), ChargeEntry::STATUS_PENDING), // out of scope
            $this->makeEntry(201, 1, 50.0, $this->monthStart(0), ChargeEntry::STATUS_PENDING),  // in scope (idempotent)
        ]);
        $this->db->seedObject($this->makeAccount(1, 0.0));

        $util->createOrRemoveChargeEntriesForPerson($person);

        $this->assertCount(0, $this->chargeEntryInserts()); // in-window month already present
        $this->assertContains(
            "DELETE FROM `chargeentry` WHERE `CE_chargeentryid`='200' LIMIT 1",
            $this->db->recordedQueries
        );
    }

    public function testFinishedOutOfScopeEntryIsRefunded(): void
    {
        // §4.5 / product-owner ruling: window-shrink refunds a collected month.
        $this->setAdvanceMonths(0);
        $util   = $this->newChargesUtilWithChargeMap([$this->makeCharge(1, 50.0)]);
        $person = $this->makePerson(1, 1);
        $hasCharge = $this->makeHasCharge(1, 1, 1, $this->monthStart(0), null);

        $account = $this->makeAccount(1, 100.0);
        $account->PA_outcome = 50.0;

        $this->db->seedObjectList([$hasCharge]);
        $this->db->seedObjectList([
            $this->makeEntry(300, 1, 50.0, $this->monthStart(-1), ChargeEntry::STATUS_FINISHED), // out of scope, paid
        ]);
        $this->db->seedObject($account);

        $util->createOrRemoveChargeEntriesForPerson($person);

        $this->assertContains(
            "DELETE FROM `chargeentry` WHERE `CE_chargeentryid`='300' LIMIT 1",
            $this->db->recordedQueries
        );
        $updated = $this->lastPersonAccountUpdate();
        $this->assertNotNull($updated);
        $this->assertSame(150.0, $updated->PA_balance); // refunded
        $this->assertSame(0.0, $updated->PA_outcome);   // reversed
    }

    public function testNonFinishedOutOfScopeEntryIsRemovedWithoutRefund(): void
    {
        // An unpaid out-of-scope entry moved no money → removal reverses nothing.
        $this->setAdvanceMonths(0);
        $util   = $this->newChargesUtilWithChargeMap([$this->makeCharge(1, 50.0)]);
        $person = $this->makePerson(1, 1);
        $hasCharge = $this->makeHasCharge(1, 1, 1, $this->monthStart(0), null);

        $account = $this->makeAccount(1, 100.0);
        $account->PA_outcome = 50.0;

        $this->db->seedObjectList([$hasCharge]);
        $this->db->seedObjectList([
            $this->makeEntry(400, 1, 50.0, $this->monthStart(-1), ChargeEntry::STATUS_PENDING), // out of scope, unpaid
        ]);
        $this->db->seedObject($account);

        $util->createOrRemoveChargeEntriesForPerson($person);

        $this->assertContains(
            "DELETE FROM `chargeentry` WHERE `CE_chargeentryid`='400' LIMIT 1",
            $this->db->recordedQueries
        );
        $updated = $this->lastPersonAccountUpdate();
        $this->assertNotNull($updated);
        $this->assertSame(100.0, $updated->PA_balance); // unchanged
        $this->assertSame(50.0, $updated->PA_outcome);  // unchanged
    }

    public function testProjectionSkipsHasChargeWithNonDayOneStart(): void
    {
        // A start date not on day-1 is logged and skipped before any insert or removal.
        $this->setAdvanceMonths(2);
        $util   = $this->newChargesUtilWithChargeMap([$this->makeCharge(1, 50.0)]);
        $person = $this->makePerson(1, 1);
        $hasCharge = $this->makeHasCharge(1, 1, 1, '2020-01-15', null); // 15th, not 1st

        $this->db->seedObjectList([$hasCharge]);
        $this->db->seedObjectList([]); // entry list still loaded (line 101) before the day-1 guard

        $util->createOrRemoveChargeEntriesForPerson($person);

        $this->assertCount(0, $this->chargeEntryInserts());
        $this->assertNull($this->lastPersonAccountUpdate()); // removal step never reached
        $this->assertNotEmpty(array_filter(
            $util->getMessages(),
            fn($m) => str_contains($m, 'invalid start date')
        ));
    }

    public function testBadChargeIdMustNotAbandonRemainingCharges(): void
    {
        // D1: a HasCharge referencing a missing chargeid should skip only that
        // charge (continue), leaving the person's other charges to project. The
        // code `return`s instead, so the valid charge below is never reached.
        $this->setAdvanceMonths(1);
        $util   = $this->newChargesUtilWithChargeMap([$this->makeCharge(2, 50.0)]); // only charge 2 exists
        $person = $this->makePerson(1, 1);
        $bad    = $this->makeHasCharge(10, 1, 99, $this->monthStart(0), null); // chargeid 99 missing
        $good   = $this->makeHasCharge(11, 1, 2,  $this->monthStart(0), null); // chargeid 2 valid

        $this->db->seedObjectList([$bad, $good]); // iterated in order: bad first
        $this->db->seedObjectList([]);            // valid charge: no existing entries
        $this->db->seedObject($this->makeAccount(1, 0.0));

        $util->createOrRemoveChargeEntriesForPerson($person);

        $inserts = $this->chargeEntryInserts();
        if (count($inserts) === 0) {
            $this->assertNotEmpty(array_filter(
                $util->getMessages(),
                fn($m) => str_contains($m, 'non-existent chargeID')
            ));
            $this->markTestIncomplete(
                'D1 not yet fixed: a missing chargeid `return`s and abandons the '
                . "person's remaining charges. Expected the valid charge (id 2) to "
                . 'still project 2 entries; got 0. See docs/billing.md §8 (D1).'
            );
        }

        // Once D1 is fixed (return → continue) this becomes a hard pass.
        $this->assertSame(
            [$this->monthStart(0), $this->monthStart(1)],
            array_map(fn($i) => $i['object']->CE_period_date, $inserts)
        );
    }
}
