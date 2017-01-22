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
// | Authors: Lukas Dziadkowiec <stealth.home@seznam.cz>                  |
// +----------------------------------------------------------------------+

/**
 * @author  Lukas Dziadkowiec <stealth.home@seznam.cz>
 */

/**
 * InvoiceCreated
 */
Class InvoiceCreated extends Event {
	private $invoiceid = null; //Charge that is delayed
	
	function InvoiceCreated($date, $person, $message, $invoiceid) {
		$this->setDate($date);
		$this->setPerson($person);
		$this->setMessage($message);
		$this->setInvoiceId($invoiceid);
	}
	
	public function setInvoiceId($invoiceid) {
		$this->invoiceid = $invoiceid;
	}
	
	public function getInvoiceId() {
		return $this->invoiceid;
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
} // End of InvoiceCreated class
?>