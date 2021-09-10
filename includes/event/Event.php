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

global $core;
require_once($core->getAppRoot() . "includes/event/ChargePaymentDeadlineEvent.php");

/**
 * Event
 */
Class Event {
    var $date = null;
    var $message = null;
    var $person = null;

    public function __construct($date, $person, $message) {
        $this->setDate($date);
        $this->setPerson($person);
        $this->setMessage($message);
    }

    public function setDate($date) {
        $this->date = $date;
    }

    public function getDate() {
        return $this->date;
    }

    public function setMessage($message) {
        $this->message = $message;
    }

    public function getMessage() {
        return $this->message;
    }

    public function setPerson($person) {
        $this->person = $person;
    }

    public function getPerson() {
        return $this->person;
    }
} // End of Event class
?>
