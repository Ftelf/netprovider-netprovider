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
 * Message
 */
class Message {
    /** @var int charge id PK */
    var $ME_messageid;
    /** @var int person id FK */
    var $ME_personid;
    /** @var datetime datetime */
    var $ME_datetime;
    /** @var varchar(255) subject of the message */
    var $ME_subject;
    /** @var text body of the message */
    var $ME_body;
    /** @var int status of the message */
    var $ME_status;

    const STATUS_PENDING = 1;
    const STATUS_SENDED = 2;
    const STATUS_CANNOT_BE_SEND = 3;

    public static $STATUS_ARRAY = array(
        1, //Pending
        2, //Sent
        3  //Cannot be sent
    );

    public static function getLocalizedStatus($status) {
        switch ($status) {
            case self::STATUS_PENDING :
                return _("Pending");

            case self::STATUS_SENDED :
                return _("Sent");

            case self::STATUS_CANNOT_BE_SEND :
                return _("Cannot be sent");
        }
    }
} // End of Message class
?>
