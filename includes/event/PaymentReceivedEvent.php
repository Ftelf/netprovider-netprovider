<?php
//
// +----------------------------------------------------------------------+
// | Ftelf ISP billing system                                               |
// +----------------------------------------------------------------------+
// | Copyright (c) 2006-2007 Ing. Lukas Dziadkowiec                       |
// +----------------------------------------------------------------------+
// | This source file is part of Ftelf ISP billing system,                  |
// | see LICENSE for licence details.                                     |
// +----------------------------------------------------------------------+
// | Authors: Lukas Dziadkowiec <i.ftelf@gmail.com>                  |
// +----------------------------------------------------------------------+

/**
 * @author  Lukas Dziadkowiec <i.ftelf@gmail.com>
 */

/**
 * PaymentReceivedEvent
 */
Class PaymentReceivedEvent extends Event {
    var $paymentDate = null;
    var $switchOffDate = null;

    public function __construct($date, $person, $message, $paymentDate, $switchOffDate) {
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
