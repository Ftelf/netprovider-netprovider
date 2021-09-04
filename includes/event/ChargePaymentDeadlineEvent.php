<?php
//
// +----------------------------------------------------------------------+
// | Stealth ISP QOS system                                               |
// +----------------------------------------------------------------------+
// | Copyright (c) 2006-2007 Ing. Lukas Dziadkowiec                       |
// +----------------------------------------------------------------------+
// | This source file is part of Stealth ISP QOS system,                  |
// | see LICENSE for licence details.                                     |
// +----------------------------------------------------------------------+
// | Authors: Lukas Dziadkowiec <i.ftelf@gmail.com>                  |
// +----------------------------------------------------------------------+

/**
 * @author  Lukas Dziadkowiec <i.ftelf@gmail.com>
 */

/**
 * ChargePaymentDeadlineEvent
 */
Class ChargePaymentDeadlineEvent extends Event {
    private $charge = null; //Charge that is delayed
    private $periodDate = null; //Period date of payment: CE_period_date
    private $writeOffDate = null; //Date of payment writeOff: CE_period_date + CE_writeoffoffset
    private $toleranceDate = null; //Date till payment delay will be tolerated CE_period_date + CH_tolerance

    public function __construct($date, $person, $message, $charge, $periodDate, $writeOffDate, $toleranceDate) {
        $this->setDate($date);
        $this->setPerson($person);
        $this->setMessage($message);
        $this->setCharge($charge);
        $this->setPeriodDate($periodDate);
        $this->setWriteOffDate($writeOffDate);
        $this->setToleranceDate($toleranceDate);
    }

    public function setCharge($charge) {
        $this->charge = $charge;
    }

    public function getCharge() {
        return $this->charge;
    }

    public function setPeriodDate($periodDate) {
        $this->periodDate = $periodDate;
    }

    public function getPeriodDate() {
        return $this->periodDate;
    }

    public function setWriteOffDate($writeOffDate) {
        $this->writeOffDate = $writeOffDate;
    }

    public function getWriteOffDate() {
        return $this->writeOffDate;
    }

    public function setToleranceDate($toleranceDate) {
        $this->toleranceDate = $toleranceDate;
    }

    public function getToleranceDate() {
        return $this->toleranceDate;
    }

    function __toString() {
        return	"\n---------------------------------\n".
                "ChargePaymentDeadlineEvent\n".
                "date: ".$this->date."\n".
                "person: ".$this->person->PE_personid." ".$this->person->PE_firstname." ".$this->person->PE_surname."\n".
                "message: ".$this->message."\n".
                "chargeName: ".$this->charge->CH_name."\n".
                "chargePeriod: ".$this->chargePeriod."\n".
                "periodDate: ".$this->periodDate."\n".
                "writeOffDate: ".$this->writeOffDate."\n".
                "toleranceDate: ".$this->toleranceDate."\n".
                "\n---------------------------------\n";
    }
} // End of PaymentEvent class
?>
