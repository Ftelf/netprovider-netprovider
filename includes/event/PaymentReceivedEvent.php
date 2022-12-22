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
 * PaymentReceivedEvent
 */
class PaymentReceivedEvent extends Event
{
    private $paymentDate;
    private $switchOffDate;

    public function __construct($date, $person, $message, $paymentDate, $switchOffDate)
    {
        parent::__construct($date, $person, $message);
        $this->setPaymentDate($paymentDate);
        $this->setSwitchOffDate($switchOffDate);
    }

    public function setPaymentDate($paymentDate): void
    {
        $this->paymentDate = $paymentDate;
    }

    public function getPaymentDate()
    {
        return $this->paymentDate;
    }

    public function setSwitchOffDate($switchOffDate): void
    {
        $this->switchOffDate = $switchOffDate;
    }

    public function getSwitchOffDate()
    {
        return $this->switchOffDate;
    }
} // End of PaymentReceivedEvent class
