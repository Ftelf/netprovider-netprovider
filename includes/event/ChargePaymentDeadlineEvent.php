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

/**
 * ChargePaymentDeadlineEvent
 */
class ChargePaymentDeadlineEvent extends Event
{
    private $charge; //Charge that is delayed
    private $periodDate; //Period date of payment: CE_period_date
    private $writeOffDate; //Date of payment writeOff: CE_period_date + CE_writeoffoffset
    private $toleranceDate; //Date till payment delay will be tolerated CE_period_date + CH_tolerance

    public function __construct($date, $person, $message, $charge, $periodDate, $writeOffDate, $toleranceDate)
    {
        parent::__construct($date, $person, $message);
        $this->setCharge($charge);
        $this->setPeriodDate($periodDate);
        $this->setWriteOffDate($writeOffDate);
        $this->setToleranceDate($toleranceDate);
    }

    public function setCharge($charge): void
    {
        $this->charge = $charge;
    }

    public function getCharge()
    {
        return $this->charge;
    }

    public function setPeriodDate($periodDate): void
    {
        $this->periodDate = $periodDate;
    }

    public function getPeriodDate()
    {
        return $this->periodDate;
    }

    public function setWriteOffDate($writeOffDate): void
    {
        $this->writeOffDate = $writeOffDate;
    }

    public function getWriteOffDate()
    {
        return $this->writeOffDate;
    }

    public function setToleranceDate($toleranceDate): void
    {
        $this->toleranceDate = $toleranceDate;
    }

    public function getToleranceDate()
    {
        return $this->toleranceDate;
    }

    public function __toString()
    {
        return "\n---------------------------------\n" .
            "ChargePaymentDeadlineEvent\n" .
            "date: " . $this->getDate() . "\n" .
            "person: " . $this->getPerson()->PE_personid . " " . $this->getPerson()->PE_firstname . " " . $this->getPerson()->PE_surname . "\n" .
            "message: " . $this->getMessage() . "\n" .
            "chargeName: " . $this->charge->CH_name . "\n" .
            "periodDate: " . $this->periodDate . "\n" .
            "writeOffDate: " . $this->writeOffDate . "\n" .
            "toleranceDate: " . $this->toleranceDate . "\n" .
            "\n---------------------------------\n";
    }
} // End of PaymentEvent class
