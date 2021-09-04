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
 * Message
 */
class Message {
    /** @var int charge id PK */
    var $ME_messageid = null;
    /** @var int person id FK */
    var $ME_personid = null;
    /** @var datetime datetime */
    var $ME_datetime = null;
    /** @var varchar(255) subject of the message */
    var $ME_subject = null;
    /** @var text body of the message */
    var $ME_body = null;
    /** @var int status of the message */
    var $ME_status = null;

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
