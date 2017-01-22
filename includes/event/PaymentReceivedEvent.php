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
 * PaymentReceivedEvent
 */
Class PaymentReceivedEvent extends Event {
	var $paymentDate = null;
	var $switchOffDate = null;
	
	function ChargePaymentDeadlineEvent($date, $person, $message, $paymentDate, $switchOffDate) {
		$this->setDate($date);
		$this->setPerson($person);
		$this->setMessage($message);
		$this->setPaymentDate($paymentDate);
		$this->setSwitchOffDate($switchOffDate);
	}
	
	public function setPaymentDate($paymentDate) {
		$this->paymentDate = $paymentDate;
	}
	
	public function getPaymentDate() {
		return $this->paymentDate;
	}
	
	public function setSwitchOffDate($switchOffDate) {
		$this->switchOffDate = $switchOffDate;
	}
	
	public function getSwitchOffDate() {
		return $this->switchOffDate;
	}
} // End of PaymentReceivedEvent class
?>