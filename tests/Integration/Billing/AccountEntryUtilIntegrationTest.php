<?php
/**
 * Integration coverage for the bank-statement import path against a real MySQL.
 *
 * Exercises `AccountEntryUtil::proceedAccountEntries()` through the real DAOs and
 * the real `Database` wrapper, then asserts the persisted effect: a matched
 * incoming payment credits the person's account, inserts a PersonAccountEntry,
 * and flips the BankAccountEntry to PROCESSED/IDENTIFY_PERSONACCOUNT.
 *
 * Matching is by variable symbol via PersonDAO::getPersonWithAccountArrayForAccounting,
 * so the seeded personaccount's PA_variablesymbol equals the entry's BE_variablesymbol.
 */

class AccountEntryUtilIntegrationTest extends IntegrationTestCase
{
    protected array $workingTables = [
        'personaccountentry', 'bankaccountentry', 'bankaccount', 'person', 'personaccount',
    ];

    private const VARIABLE_SYMBOL = 555666;

    public function testIncomingPaymentIsMatchedCreditedAndPersisted(): void
    {
        $bankAccountId = $this->seedBankAccount();
        $accountId     = $this->seedPersonAccount(100.00, self::VARIABLE_SYMBOL);
        $this->seedPerson($accountId, Person::STATUS_ACTIVE);
        $entryId = $this->seedBankAccountEntry(
            $bankAccountId,
            (string) self::VARIABLE_SYMBOL,
            300.00,
            BankAccountEntry::TYPE_INCOMEPAYMENT
        );

        (new AccountEntryUtil($this->loadBankAccount($bankAccountId)))->proceedAccountEntries();

        $personEntries = PersonAccountEntryDAO::getPersonAccountEntryArrayByBankAccountEntryID($entryId);
        $this->assertCount(1, $personEntries, 'a person account entry is created for the matched payment');
        $personEntry = $personEntries[array_key_first($personEntries)];
        $this->assertEquals(300.00, (float) $personEntry->PN_amount, 'entry carries the payment amount');
        $this->assertEquals($accountId, (int) $personEntry->PN_personaccountid, 'entry is booked on the matched account');
        $this->assertEquals(PersonAccountEntry::SOURCE_BANKACCOUNT, (int) $personEntry->PN_source, 'entry source is the bank account');

        $account = PersonAccountDAO::getPersonAccountByID($accountId);
        $this->assertEquals(400.00, (float) $account->PA_balance, 'balance is credited by the payment');
        $this->assertEquals(300.00, (float) $account->PA_income, 'income is credited by the payment');

        $entry = BankAccountEntryDAO::getBankAccountEntryByID($entryId);
        $this->assertEquals(BankAccountEntry::STATUS_PROCESSED, (int) $entry->BE_status, 'bank entry is marked processed');
        $this->assertEquals(BankAccountEntry::IDENTIFY_PERSONACCOUNT, (int) $entry->BE_identifycode, 'bank entry is identified to a person account');
        $this->assertEquals((int) $personEntry->PN_personaccountentryid, (int) $entry->BE_personaccountentryid, 'bank entry links back to the created person account entry');
    }

    public function testInternalTransferIsProcessedWithoutCreditingAnyone(): void
    {
        $bankAccountId = $this->seedBankAccount();
        $accountId     = $this->seedPersonAccount(100.00, self::VARIABLE_SYMBOL);
        $this->seedPerson($accountId, Person::STATUS_ACTIVE);
        $entryId = $this->seedBankAccountEntry(
            $bankAccountId,
            (string) self::VARIABLE_SYMBOL,
            300.00,
            BankAccountEntry::TYPE_POSITIVEINCREASE // an internal-transaction type
        );

        (new AccountEntryUtil($this->loadBankAccount($bankAccountId)))->proceedAccountEntries();

        $this->assertCount(
            0,
            PersonAccountEntryDAO::getPersonAccountEntryArrayByBankAccountEntryID($entryId),
            'an internal transfer credits no person account'
        );

        $account = PersonAccountDAO::getPersonAccountByID($accountId);
        $this->assertEquals(100.00, (float) $account->PA_balance, 'balance is untouched by an internal transfer');

        $entry = BankAccountEntryDAO::getBankAccountEntryByID($entryId);
        $this->assertEquals(BankAccountEntry::STATUS_PROCESSED, (int) $entry->BE_status, 'internal transfer is still marked processed');
        $this->assertEquals(BankAccountEntry::IDENTIFY_INTERNALTRANSACTION, (int) $entry->BE_identifycode, 'internal transfer is identified as such');
    }

    // --- fixtures -----------------------------------------------------------

    private function loadBankAccount(int $bankAccountId): BankAccount
    {
        return BankAccountDAO::getBankAccountByID($bankAccountId);
    }

    private function seedBankAccount(): int
    {
        $bankAccount = new BankAccount();
        $bankAccount->BA_bankname      = 'Test Bank';
        $bankAccount->BA_accountname   = 'ISP Ltd';
        $bankAccount->BA_accountnumber = 1234567890;
        $bankAccount->BA_banknumber    = 800;
        $bankAccount->BA_iban          = 'CZ0000000000001234567890';
        $bankAccount->BA_currency      = 'CZK';
        $bankAccount->BA_startbalance  = 0.00;
        $this->db->insertObject('bankaccount', $bankAccount, 'BA_bankaccountid', false);

        return (int) $bankAccount->BA_bankaccountid;
    }

    private function seedPersonAccount(float $balance, int $variableSymbol): int
    {
        $account = new PersonAccount();
        $account->PA_currency       = 'CZK';
        $account->PA_startbalance   = 0.00;
        $account->PA_balance        = $balance;
        $account->PA_income         = 0.00;
        $account->PA_outcome        = 0.00;
        $account->PA_variablesymbol = $variableSymbol;
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
        $person->PE_firstname       = 'Petr';
        $person->PE_surname         = 'Svoboda';
        $person->PE_status          = $status;
        $person->PE_registerdate    = '2020-01-01 00:00:00';
        $this->db->insertObject('person', $person, 'PE_personid', false);

        return (int) $person->PE_personid;
    }

    private function seedBankAccountEntry(int $bankAccountId, string $variableSymbol, float $amount, int $typeOfTransaction): int
    {
        $entry = new BankAccountEntry();
        $entry->BE_bankaccountid     = $bankAccountId;
        $entry->BE_datetime          = '2024-01-15 10:00:00';
        $entry->BE_note              = '';
        $entry->BE_accountname       = 'Payer Name';
        $entry->BE_accountnumber     = '9876543210';
        $entry->BE_banknumber        = 300;
        $entry->BE_writeoff_date     = '2024-01-15';
        $entry->BE_typeoftransaction = $typeOfTransaction;
        $entry->BE_variablesymbol    = $variableSymbol;
        $entry->BE_constantsymbol    = '';
        $entry->BE_specificsymbol    = '';
        $entry->BE_amount            = $amount;
        $entry->BE_charge            = 0.00;
        $entry->BE_message           = 'monthly payment';
        $entry->BE_status            = BankAccountEntry::STATUS_PENDING;
        $entry->BE_identifycode      = BankAccountEntry::IDENTIFY_UNIDENTIFIED;
        $this->db->insertObject('bankaccountentry', $entry, 'BE_bankaccountentryid', false);

        return (int) $entry->BE_bankaccountentryid;
    }
}
