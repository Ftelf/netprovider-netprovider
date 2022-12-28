<?php
/**
 * Ftelf ISP billing system
 * This source file is part of Ftelf ISP billing system
 * see LICENSE for licence details.
 * php version 8.1.12
 *
 * @category Helper
 * @package  NetProvider
 * @author   Lukas Dziadkowiec <i.ftelf@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     https://www.ovjih.net
 */

global $core;
require_once $core->getAppRoot() . "includes/event/Event.php";
require_once $core->getAppRoot() . "includes/dao/HandleEventDAO.php";

/**
 * Core
 */
class EventCrossBar
{
    private $handleEventArray;
    private $templateArray;
    private $emailUtil;

    public function __construct()
    {
        global $core;

        $this->emailUtil = new EmailUtil();

        $this->handleEventArray = HandleEventDAO::getHandleEventArray();
        $this->templateArray = [];

        foreach ($this->handleEventArray as $handleEvent) {
            $path = $core->getAppRoot() . "templates/events/" . $handleEvent->HE_templatepath;
            if (!($template = file_get_contents($path))) {
                throw new Exception("Cannot open event template file: " . $path);
            }
            $this->templateArray[$handleEvent->HE_handleeventid] = $template;
        }
    }

    public function dispatchEvent($event): void
    {
        global $database;
        $now = new DateUtil();
        $now->set(DateUtil::HOUR, 0);
        $now->set(DateUtil::MINUTES, 0);
        $now->set(DateUtil::SECONDS, 0);

        if ($event instanceof ChargePaymentDeadlineEvent) {
            foreach ($this->handleEventArray as $handleEvent) {
                if ($handleEvent->HE_type == HandleEvent::TYPE_CHARGE_PAYMENT_DEADLINE
                    && $handleEvent->HE_status == HandleEvent::STATUS_ENABLED
                ) {

                    $daysBeforeTurnOff = ($event->getToleranceDate()->getTime() - $now->getTime()) / 3600 / 24;

                    if ($handleEvent->HE_notifydaysbeforeturnoff == null || $handleEvent->HE_notifydaysbeforeturnoff >= $daysBeforeTurnOff) {
                        $template = $this->templateArray[$handleEvent->HE_handleeventid];
                        $template = mb_ereg_replace("\|PERSON_NAME\|", $event->getPerson()->PE_firstname . " " . $event->getPerson()->PE_surname, $template);
                        $template = mb_ereg_replace("\|CHARGE_NAME\|", $event->getCharge()->CH_name, $template);
                        $template = mb_ereg_replace("\|CHARGE_BASEAMOUNT\|", $event->getCharge()->CH_baseamount, $template);
                        $template = mb_ereg_replace("\|CHARGE_VAT\|", $event->getCharge()->CH_vat, $template);
                        $template = mb_ereg_replace("\|CHARGE_AMOUNT\|", $event->getCharge()->CH_amount, $template);
                        $template = mb_ereg_replace("\|CHARGE_CURRENCY\|", $event->getCharge()->CH_currency, $template);
                        $template = mb_ereg_replace("\|CHARGE_PERIOD\|", Charge::getLocalizedPeriod($event->getCharge()->CH_period), $template);
                        $template = mb_ereg_replace("\|CHARGE_PERIOD_DATE\|", $event->getPeriodDate()->getFormattedDate(DateUtil::FORMAT_DATE), $template);
                        $template = mb_ereg_replace("\|CHARGE_WRITE_OFF\|", $event->getWriteOffDate()->getFormattedDate(DateUtil::FORMAT_DATE), $template);
                        $template = mb_ereg_replace("\|CHARGE_SWITCH_OFF\|", $event->getToleranceDate()->getFormattedDate(DateUtil::FORMAT_DATE), $template);

                        if ($handleEvent->HE_notifypersonid) {
                            $person = PersonDAO::getPersonByID($handleEvent->HE_notifypersonid);
                        } else {
                            $person = $event->getPerson();
                        }

                        $this->emailUtil->queueMessage($person, $handleEvent->HE_emailsubject, $template, null);

                        try {
                            $this->emailUtil->sendMessages();
                        } catch (Exception $e) {
                            $msg = "Error sending message: " . $e->getMessage();
                            $database->log($msg, Log::LEVEL_ERROR);
                        }
                    }
                }
            }
        }
    }
} // End of EventCrossBar class
