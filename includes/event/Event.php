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
require_once $core->getAppRoot() . "includes/event/ChargePaymentDeadlineEvent.php";

/**
 * Event
 */
class Event
{
    private $date;
    private $message;
    private $person;

    public function __construct($date, $person, $message)
    {
        $this->setDate($date);
        $this->setPerson($person);
        $this->setMessage($message);
    }

    public function setDate($date): void
    {
        $this->date = $date;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setMessage($message): void
    {
        $this->message = $message;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setPerson($person): void
    {
        $this->person = $person;
    }

    public function getPerson()
    {
        return $this->person;
    }
} // End of Event class
