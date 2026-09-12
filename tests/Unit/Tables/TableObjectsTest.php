<?php
/**
 * Tests for the table value-objects that ship a getLocalizedX() helper.
 *
 * These methods are widely used by templates; a regression here breaks
 * every status badge in the UI.
 */

class TableObjectsTest extends TestCase
{
    public function testPersonStatusLabels(): void
    {
        $this->assertSame('Passive',   Person::getLocalizedStatus(Person::STATUS_PASSIVE));
        $this->assertSame('Active',    Person::getLocalizedStatus(Person::STATUS_ACTIVE));
        $this->assertSame('Discarted', Person::getLocalizedStatus(Person::STATUS_DISCARTED));
        $this->assertSame('',          Person::getLocalizedStatus(999));
    }

    public function testHasChargeStatusAndActualState(): void
    {
        $this->assertSame('Deactivated', HasCharge::getLocalizedStatus(HasCharge::STATUS_DISABLED));
        $this->assertSame('Activated',   HasCharge::getLocalizedStatus(HasCharge::STATUS_ENABLED));
        $this->assertSame('Service is always deactivated', HasCharge::getLocalizedStatus(HasCharge::STATUS_FORCE_DISABLED));
        $this->assertSame('Service is always activated',   HasCharge::getLocalizedStatus(HasCharge::STATUS_FORCE_ENABLED));
        $this->assertSame('',            HasCharge::getLocalizedStatus(999));

        $this->assertSame('Deactivated', HasCharge::getLocalizedActualState(HasCharge::ACTUALSTATE_DISABLED));
        $this->assertSame('Activated',   HasCharge::getLocalizedActualState(HasCharge::ACTUALSTATE_ENABLED));
    }

    public function testChargeTypeAndPeriod(): void
    {
        $this->assertSame('Monthly',         Charge::getLocalizedPeriod(Charge::PERIOD_MONTHLY));
        $this->assertSame('Internet payment', Charge::getLocalizedType(Charge::TYPE_INTERNET_PAYMENT));
        $this->assertSame('Entry fee',       Charge::getLocalizedType(Charge::TYPE_ENTRY_FEE));
        $this->assertSame('Penalty',         Charge::getLocalizedType(Charge::TYPE_PENALTY));
        $this->assertSame('Unspecified',     Charge::getLocalizedType(Charge::TYPE_UNSPECIFIED));
        $this->assertSame('',                Charge::getLocalizedType(99));
    }

    public function testChargeEntryStatusLabels(): void
    {
        $this->assertSame('Finished',                  ChargeEntry::getLocalizedStatus(ChargeEntry::STATUS_FINISHED));
        $this->assertSame('Pending',                   ChargeEntry::getLocalizedStatus(ChargeEntry::STATUS_PENDING));
        $this->assertSame('Pending, insufficient funds', ChargeEntry::getLocalizedStatus(ChargeEntry::STATUS_PENDING_INSUFFICIENTFUNDS));
        $this->assertSame('Testing, free of charge',  ChargeEntry::getLocalizedStatus(ChargeEntry::STATUS_TESTINGFREEOFCHARGE));
        $this->assertSame('Disabled for this period', ChargeEntry::getLocalizedStatus(ChargeEntry::STATUS_DISABLED));
        $this->assertSame('Error',                     ChargeEntry::getLocalizedStatus(ChargeEntry::STATUS_ERROR));
    }

    public function testGroupLevels(): void
    {
        $this->assertSame('User',                 Group::getLocalizedLevel(Group::USER));
        $this->assertSame('Administrator',        Group::getLocalizedLevel(Group::ADMINISTRATOR));
        $this->assertSame('Super administrator',  Group::getLocalizedLevel(Group::SUPER_ADMINISTRATOR));
    }

    public function testHandleEventStatusAndType(): void
    {
        $this->assertSame('Disabled', HandleEvent::getLocalizedStatus(HandleEvent::STATUS_DISABLED));
        $this->assertSame('Enabled',  HandleEvent::getLocalizedStatus(HandleEvent::STATUS_ENABLED));
        $this->assertSame('Charge payment deadline', HandleEvent::getLocalizedType(HandleEvent::TYPE_CHARGE_PAYMENT_DEADLINE));
    }

    public function testBankAccountEntryStatusAndIdentify(): void
    {
        $this->assertSame('Pending',   BankAccountEntry::getLocalizedStatus(BankAccountEntry::STATUS_PENDING));
        $this->assertSame('Processed', BankAccountEntry::getLocalizedStatus(BankAccountEntry::STATUS_PROCESSED));
    }

    public function testBankAccountDatasourceAndType(): void
    {
        $this->assertSame('Manual',          BankAccount::getLocalizedDatasource(BankAccount::DATASOURCE_MANUAL));
        $this->assertSame('EMail content',   BankAccount::getLocalizedDatasource(BankAccount::DATASOURCE_EMAIL_CONTENT));
        $this->assertSame('RB TXT attachment', BankAccount::getLocalizedDatasourceType(BankAccount::DATASOURCE_TYPE_RB_ATTACHMENT_TXT));
        $this->assertSame('RB PDF attachment', BankAccount::getLocalizedDatasourceType(BankAccount::DATASOURCE_TYPE_RB_ATTACHMENT_PDF));
        $this->assertSame('ISO SEPA XML',      BankAccount::getLocalizedDatasourceType(BankAccount::DATASOURCE_TYPE_ISO_SEPA_XML));
    }
}
