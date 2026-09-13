<?php
/**
 * Integration coverage for the billing engine end to end against a real MySQL.
 *
 * Exercises `ChargesUtil::proceedCharges()` -> `proceedChargesForPerson()` through
 * the real DAOs and the real `Database` wrapper (insert/update/transaction), then
 * asserts the *persisted* rows — not in-memory objects. This is the read/write
 * path the unit tier deliberately cannot cover (it stubs the DB).
 *
 * Each test owns its rows and the base TRUNCATEs the working tables first, so the
 * single seeded person is the only one `PersonDAO::getPersonArray()` returns.
 */

class ChargesUtilIntegrationTest extends IntegrationTestCase
{
    protected array $workingTables = ['chargeentry', 'hascharge', 'charge', 'person', 'personaccount'];

    public function testProceedChargesCollectsPaymentAndEnablesWhenFundsSufficient(): void
    {
        $chargeId   = $this->seedCharge(121.00, 100.00, 21.00, 7, 0);
        $accountId  = $this->seedPersonAccount(1000.00);
        $personId   = $this->seedPerson($accountId, Person::STATUS_ACTIVE);
        $hasChargeId = $this->seedHasCharge($chargeId, $personId, HasCharge::STATUS_ENABLED, HasCharge::ACTUALSTATE_DISABLED);
        $entryId    = $this->seedChargeEntry($hasChargeId, '2020-02-01', 121.00, ChargeEntry::STATUS_PENDING);

        (new ChargesUtil())->proceedCharges();

        $entry = ChargeEntryDAO::getChargeEntryByID($entryId);
        $this->assertEquals(ChargeEntry::STATUS_FINISHED, $entry->CE_status, 'due entry is collected');
        $this->assertNotEquals(DateUtil::DB_NULL_DATE, $entry->CE_realize_date, 'realize date is stamped on collection');

        $account = PersonAccountDAO::getPersonAccountByID($accountId);
        $this->assertEquals(879.00, (float) $account->PA_balance, 'balance debited by the charge amount');
        $this->assertEquals(121.00, (float) $account->PA_outcome, 'outcome credited by the charge amount');

        $hasCharge = HasChargeDAO::getHasChargeByID($hasChargeId);
        $this->assertEquals(HasCharge::ACTUALSTATE_ENABLED, $hasCharge->HC_actualstate, 'a fully-paid sequence enables the service');
    }

    public function testProceedChargesMarksInsufficientFundsAndLeavesBalanceUntouched(): void
    {
        $chargeId   = $this->seedCharge(121.00, 100.00, 21.00, 7, 0);
        $accountId  = $this->seedPersonAccount(50.00);
        $personId   = $this->seedPerson($accountId, Person::STATUS_ACTIVE);
        $hasChargeId = $this->seedHasCharge($chargeId, $personId, HasCharge::STATUS_ENABLED, HasCharge::ACTUALSTATE_ENABLED);
        $entryId    = $this->seedChargeEntry($hasChargeId, '2020-02-01', 121.00, ChargeEntry::STATUS_PENDING);

        (new ChargesUtil())->proceedCharges();

        $entry = ChargeEntryDAO::getChargeEntryByID($entryId);
        $this->assertEquals(ChargeEntry::STATUS_PENDING_INSUFFICIENTFUNDS, $entry->CE_status, 'unpayable entry flips to insufficient-funds');

        $account = PersonAccountDAO::getPersonAccountByID($accountId);
        $this->assertEquals(50.00, (float) $account->PA_balance, 'balance is not touched when funds are insufficient');
        $this->assertEquals(0.00, (float) $account->PA_outcome, 'outcome is not touched when funds are insufficient');

        $hasCharge = HasChargeDAO::getHasChargeByID($hasChargeId);
        $this->assertEquals(HasCharge::ACTUALSTATE_DISABLED, $hasCharge->HC_actualstate, 'a long-overdue unpaid entry disables the service');
    }

    // --- fixtures -----------------------------------------------------------

    private function seedCharge(float $amount, float $baseAmount, float $vat, int $tolerance, int $writeOffOffset): int
    {
        $charge = new Charge();
        $charge->CH_name          = 'Internet 100/100';
        $charge->CH_description    = 'integration fixture';
        $charge->CH_period         = Charge::PERIOD_MONTHLY;
        $charge->CH_vat            = $vat;
        $charge->CH_baseamount     = $baseAmount;
        $charge->CH_amount         = $amount;
        $charge->CH_currency       = 'CZK';
        $charge->CH_tolerance      = $tolerance;
        $charge->CH_writeoffoffset = $writeOffOffset;
        $charge->CH_type           = Charge::TYPE_INTERNET_PAYMENT;
        $charge->CH_priority       = 0;
        $this->db->insertObject('charge', $charge, 'CH_chargeid', false);

        return (int) $charge->CH_chargeid;
    }

    private function seedPersonAccount(float $balance): int
    {
        $account = new PersonAccount();
        $account->PA_currency       = 'CZK';
        $account->PA_startbalance   = 0.00;
        $account->PA_balance        = $balance;
        $account->PA_income         = 0.00;
        $account->PA_outcome        = 0.00;
        $account->PA_variablesymbol = 100200300;
        $account->PA_constantsymbol = 0;
        $account->PA_specificsymbol = 0;
        $this->db->insertObject('personaccount', $account, 'PA_personaccountid', false);

        return (int) $account->PA_personaccountid;
    }

    private function seedPerson(int $accountId, int $status): int
    {
        $person = new Person();
        $person->PE_groupid         = 0;
        $person->PE_personaccountid = $accountId;
        $person->PE_firstname       = 'Anna';
        $person->PE_surname         = 'Novakova';
        $person->PE_status          = $status;
        $person->PE_registerdate    = '2020-01-01 00:00:00';
        $this->db->insertObject('person', $person, 'PE_personid', false);

        return (int) $person->PE_personid;
    }

    private function seedHasCharge(int $chargeId, int $personId, int $status, int $actualState): int
    {
        $hasCharge = new HasCharge();
        $hasCharge->HC_chargeid    = $chargeId;
        $hasCharge->HC_personid    = $personId;
        $hasCharge->HC_datestart   = '2020-01-01';
        $hasCharge->HC_dateend     = DateUtil::DB_NULL_DATE; // open-ended -> "in present"
        $hasCharge->HC_status      = $status;
        $hasCharge->HC_actualstate = $actualState;
        $this->db->insertObject('hascharge', $hasCharge, 'HC_haschargeid', false);

        return (int) $hasCharge->HC_haschargeid;
    }

    private function seedChargeEntry(int $hasChargeId, string $periodDate, float $amount, int $status): int
    {
        $entry = new ChargeEntry();
        $entry->CE_haschargeid   = $hasChargeId;
        $entry->CE_period_date   = $periodDate;
        $entry->CE_writeoffoffset = 0;
        $entry->CE_realize_date  = DateUtil::DB_NULL_DATE;
        $entry->CE_overdue       = 0;
        $entry->CE_vat           = 21.00;
        $entry->CE_baseamount    = 100.00;
        $entry->CE_amount        = $amount;
        $entry->CE_currency      = 'CZK';
        $entry->CE_status        = $status;
        $this->db->insertObject('chargeentry', $entry, 'CE_chargeentryid', false);

        return (int) $entry->CE_chargeentryid;
    }
}
