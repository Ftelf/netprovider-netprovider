<?php
/**
 * EventCrossBar tests — token interpolation + email dispatch.
 *
 * Real EventCrossBar:
 *  - loads HandleEvent rows from DB
 *  - reads template files from templates/events/
 *  - on dispatch, interpolates tokens then queues + sends mail via EmailUtil.
 *
 * Tests:
 *  1. construct: verify template loading + raise on missing template.
 *  2. dispatchEvent: verify token replacement in stored template using
 *     reflection (no real email sending — emailUtil is replaced with a
 *     Mockery double).
 */

class EventCrossBarTest extends TestCase
{
    private function newCrossBarWithTemplate(string $template, array $handleEvents): EventCrossBar
    {
        $r = new \ReflectionClass(EventCrossBar::class);
        $instance = $r->newInstanceWithoutConstructor();

        $hp = $r->getProperty('handleEventArray');
        $hp->setAccessible(true);
        $hp->setValue($instance, $handleEvents);

        $tp = $r->getProperty('templateArray');
        $tp->setAccessible(true);
        $byId = [];
        foreach ($handleEvents as $he) {
            $byId[$he->HE_handleeventid] = $template;
        }
        $tp->setValue($instance, $byId);

        $ep = $r->getProperty('emailUtil');
        $ep->setAccessible(true);
        $ep->setValue($instance, $this->mockEmail);

        return $instance;
    }

    private $mockEmail;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockEmail = Mockery::mock();
    }

    public function testConstructorThrowsWhenTemplateMissing(): void
    {
        $he = new HandleEvent();
        $he->HE_handleeventid = 1;
        $he->HE_type = HandleEvent::TYPE_CHARGE_PAYMENT_DEADLINE;
        $he->HE_status = HandleEvent::STATUS_ENABLED;
        $he->HE_templatepath = 'this_template_does_not_exist.txt';
        $he->HE_emailsubject = 'Test';
        $he->HE_notifydaysbeforeturnoff = null;
        $he->HE_notifypersonid = null;

        $this->db->seedObjectList([$he]);   // HandleEventDAO::getHandleEventArray
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Cannot open event template/');
        new EventCrossBar();
    }

    public function testDispatchInterpolatesTokensAndQueuesMessage(): void
    {
        $template = "Hi |PERSON_NAME|, |CHARGE_NAME| amount=|CHARGE_AMOUNT| currency=|CHARGE_CURRENCY| period=|CHARGE_PERIOD| date=|CHARGE_PERIOD_DATE| writeOff=|CHARGE_WRITE_OFF| switchOff=|CHARGE_SWITCH_OFF|";

        $he = new HandleEvent();
        $he->HE_handleeventid = 1;
        $he->HE_type   = HandleEvent::TYPE_CHARGE_PAYMENT_DEADLINE;
        $he->HE_status = HandleEvent::STATUS_ENABLED;
        $he->HE_emailsubject = 'Reminder';
        $he->HE_notifydaysbeforeturnoff = null;
        $he->HE_notifypersonid = null;

        $crossBar = $this->newCrossBarWithTemplate($template, [$he]);

        $person = new Person();
        $person->PE_personid = 1;
        $person->PE_firstname = 'Anna';
        $person->PE_surname   = 'Novakova';

        $charge = new Charge();
        $charge->CH_name        = 'Internet 100/100';
        $charge->CH_baseamount  = 500;
        $charge->CH_vat         = 21;
        $charge->CH_amount      = 605;
        $charge->CH_currency    = 'CZK';
        $charge->CH_period      = Charge::PERIOD_MONTHLY;

        $now = new DateUtil('2026-05-01');
        $period = new DateUtil('2026-05-01');
        $writeOff = new DateUtil('2026-05-15');
        $tolerance = new DateUtil('2026-05-30');

        $event = new ChargePaymentDeadlineEvent($now, $person, 'msg', $charge, $period, $writeOff, $tolerance);

        $this->mockEmail->shouldReceive('queueMessage')->once()
            ->withArgs(function ($p, $subj, $body, $attach) {
                $this->assertSame('Reminder', $subj);
                $this->assertStringContainsString('Hi Anna Novakova', $body);
                $this->assertStringContainsString('Internet 100/100', $body);
                $this->assertStringContainsString('amount=605', $body);
                $this->assertStringContainsString('currency=CZK', $body);
                $this->assertStringContainsString('date=01.05.2026', $body);
                $this->assertStringContainsString('writeOff=15.05.2026', $body);
                $this->assertStringContainsString('switchOff=30.05.2026', $body);
                $this->assertStringNotContainsString('|', $body, 'all placeholders interpolated');
                return true;
            });
        $this->mockEmail->shouldReceive('sendMessages')->once();

        $crossBar->dispatchEvent($event);
    }

    public function testDispatchSkipsDisabledHandlers(): void
    {
        $he = new HandleEvent();
        $he->HE_handleeventid = 1;
        $he->HE_type   = HandleEvent::TYPE_CHARGE_PAYMENT_DEADLINE;
        $he->HE_status = HandleEvent::STATUS_DISABLED ?? 0;
        $he->HE_emailsubject = 'X';

        $crossBar = $this->newCrossBarWithTemplate('hi', [$he]);

        $this->mockEmail->shouldNotReceive('queueMessage');

        $person = new Person();
        $person->PE_personid = 1;
        $person->PE_firstname = 'A';
        $person->PE_surname = 'B';
        $charge = new Charge();
        $charge->CH_name = 'X';
        $charge->CH_baseamount = 0;
        $charge->CH_vat = 0;
        $charge->CH_amount = 0;
        $charge->CH_currency = 'CZK';
        $charge->CH_period = Charge::PERIOD_MONTHLY;

        $event = new ChargePaymentDeadlineEvent(
            new DateUtil(),
            $person, 'm', $charge,
            new DateUtil('2026-05-01'),
            new DateUtil('2026-05-15'),
            new DateUtil('2026-05-30')
        );

        $crossBar->dispatchEvent($event);
        $this->assertTrue(true);
    }
}
