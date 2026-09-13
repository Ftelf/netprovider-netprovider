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
        $he->HE_notifydaysbeforeturnoff = 5;
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
        // Schema is `tinyint NOT NULL`; use a real threshold. dispatchEvent uses
        // the real "now", so build the switch-off relative to today (≈40 days out)
        // and keep the threshold (60) comfortably above it so the handler fires
        // deterministically regardless of when the suite runs.
        $he->HE_notifydaysbeforeturnoff = 60;
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
        $tolerance = new DateUtil();
        $tolerance->add(DateUtil::DAY, 40);
        $expectedSwitchOff = $tolerance->getFormattedDate(DateUtil::FORMAT_DATE);

        $event = new ChargePaymentDeadlineEvent($now, $person, 'msg', $charge, $period, $writeOff, $tolerance);

        $this->mockEmail->shouldReceive('queueMessage')->once()
            ->withArgs(function ($p, $subj, $body, $attach) use ($expectedSwitchOff) {
                $this->assertSame('Reminder', $subj);
                $this->assertStringContainsString('Hi Anna Novakova', $body);
                $this->assertStringContainsString('Internet 100/100', $body);
                $this->assertStringContainsString('amount=605', $body);
                $this->assertStringContainsString('currency=CZK', $body);
                $this->assertStringContainsString('date=01.05.2026', $body);
                $this->assertStringContainsString('writeOff=15.05.2026', $body);
                $this->assertStringContainsString('switchOff=' . $expectedSwitchOff, $body);
                $this->assertStringNotContainsString('|', $body, 'all placeholders interpolated');
                return true;
            });
        $this->mockEmail->shouldReceive('sendMessages')->once();

        $crossBar->dispatchEvent($event);
    }

    public function testDispatchSkipsWhenThresholdBelowWindow(): void
    {
        $he = new HandleEvent();
        $he->HE_handleeventid = 1;
        $he->HE_type   = HandleEvent::TYPE_CHARGE_PAYMENT_DEADLINE;
        $he->HE_status = HandleEvent::STATUS_ENABLED;
        $he->HE_emailsubject = 'Reminder';
        // Switch-off is ~40 days out; a 5-day threshold must NOT fire yet.
        $he->HE_notifydaysbeforeturnoff = 5;
        $he->HE_notifypersonid = null;

        $crossBar = $this->newCrossBarWithTemplate('hi |PERSON_NAME|', [$he]);

        $this->mockEmail->shouldNotReceive('queueMessage');

        $person = new Person();
        $person->PE_personid = 1;
        $person->PE_firstname = 'A';
        $person->PE_surname   = 'B';
        $charge = new Charge();
        $charge->CH_name = 'X';
        $charge->CH_baseamount = 0;
        $charge->CH_vat = 0;
        $charge->CH_amount = 0;
        $charge->CH_currency = 'CZK';
        $charge->CH_period = Charge::PERIOD_MONTHLY;

        $tolerance = new DateUtil();
        $tolerance->add(DateUtil::DAY, 40);

        $event = new ChargePaymentDeadlineEvent(
            new DateUtil('2026-05-01'),
            $person, 'm', $charge,
            new DateUtil('2026-05-01'),
            new DateUtil('2026-05-15'),
            $tolerance
        );

        $crossBar->dispatchEvent($event);
        $this->assertTrue(true);
    }

    public function testDispatchNotifiesOnInclusiveThresholdBoundary(): void
    {
        // Switch-off is exactly `threshold` days out, so threshold == daysBeforeTurnOff.
        // The comparison is inclusive (threshold >= daysBeforeTurnOff), so it must fire
        // on the boundary day. Pins that inclusive `>=`: flipping it to `>` reds this.
        // Determinism: the suite runs in UTC (no DST), and both `now` (zeroed inside
        // dispatchEvent) and the tolerance date below are anchored to midnight, so
        // daysBeforeTurnOff is exactly 14.0 regardless of the run's wall-clock time.
        $he = new HandleEvent();
        $he->HE_handleeventid = 1;
        $he->HE_type   = HandleEvent::TYPE_CHARGE_PAYMENT_DEADLINE;
        $he->HE_status = HandleEvent::STATUS_ENABLED;
        $he->HE_emailsubject = 'Reminder';
        $he->HE_notifydaysbeforeturnoff = 14;
        $he->HE_notifypersonid = null;

        $crossBar = $this->newCrossBarWithTemplate('hi |PERSON_NAME|', [$he]);

        $person = new Person();
        $person->PE_personid = 1;
        $person->PE_firstname = 'A';
        $person->PE_surname   = 'B';
        $charge = new Charge();
        $charge->CH_name = 'X';
        $charge->CH_baseamount = 0;
        $charge->CH_vat = 0;
        $charge->CH_amount = 0;
        $charge->CH_currency = 'CZK';
        $charge->CH_period = Charge::PERIOD_MONTHLY;

        // Switch-off (tolerance) = midnight today + exactly 14 days.
        $tolerance = new DateUtil();
        $tolerance->set(DateUtil::HOUR, 0);
        $tolerance->set(DateUtil::MINUTES, 0);
        $tolerance->set(DateUtil::SECONDS, 0);
        $tolerance->add(DateUtil::DAY, 14);

        $event = new ChargePaymentDeadlineEvent(
            new DateUtil('2026-05-01'),
            $person, 'm', $charge,
            new DateUtil('2026-05-01'),
            new DateUtil('2026-05-15'),
            $tolerance
        );

        $queued = false;
        $this->mockEmail->shouldReceive('queueMessage')->once()
            ->withArgs(function () use (&$queued) { $queued = true; return true; });
        $this->mockEmail->shouldReceive('sendMessages')->once();

        $crossBar->dispatchEvent($event);

        $this->assertTrue($queued, 'notification fires on the inclusive boundary day (threshold == daysBeforeTurnOff)');
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
