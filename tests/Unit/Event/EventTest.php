<?php
/**
 * Plain Event / ChargePaymentDeadlineEvent / PaymentReceivedEvent value
 * objects: getter / setter round-trips and __toString.
 */

class EventTest extends TestCase
{
    public function testEventBaseGettersSetters(): void
    {
        $person = new Person();
        $person->PE_personid = 1;
        $now = new DateUtil('2026-05-01');
        $e = new Event($now, $person, 'hello');
        $this->assertSame($now,    $e->getDate());
        $this->assertSame($person, $e->getPerson());
        $this->assertSame('hello', $e->getMessage());

        $newPerson = new Person();
        $e->setPerson($newPerson);
        $this->assertSame($newPerson, $e->getPerson());

        $newDate = new DateUtil('2027-01-01');
        $e->setDate($newDate);
        $this->assertSame($newDate, $e->getDate());

        $e->setMessage('updated');
        $this->assertSame('updated', $e->getMessage());
    }

    public function testChargePaymentDeadlineEventStoresAllFields(): void
    {
        $person = new Person();
        $person->PE_personid = 1;
        $person->PE_firstname = 'A';
        $person->PE_surname = 'B';
        $charge = new Charge();
        $charge->CH_name = 'X';
        $period   = new DateUtil('2026-05-01');
        $writeOff = new DateUtil('2026-05-15');
        $tol      = new DateUtil('2026-05-30');
        $now      = new DateUtil('2026-05-01');

        $e = new ChargePaymentDeadlineEvent($now, $person, 'msg', $charge, $period, $writeOff, $tol);
        $this->assertSame($charge,   $e->getCharge());
        $this->assertSame($period,   $e->getPeriodDate());
        $this->assertSame($writeOff, $e->getWriteOffDate());
        $this->assertSame($tol,      $e->getToleranceDate());

        $str = (string) $e;
        $this->assertStringContainsString('ChargePaymentDeadlineEvent', $str);
        $this->assertStringContainsString('chargeName: X', $str);
    }

    public function testPaymentReceivedEvent(): void
    {
        $person = new Person();
        $person->PE_personid = 1;
        $now      = new DateUtil('2026-05-01');
        $payment  = new DateUtil('2026-04-30');
        $switchOff = new DateUtil('2026-05-15');

        $e = new PaymentReceivedEvent($now, $person, 'msg', $payment, $switchOff);
        $this->assertSame($payment,   $e->getPaymentDate());
        $this->assertSame($switchOff, $e->getSwitchOffDate());
    }
}
