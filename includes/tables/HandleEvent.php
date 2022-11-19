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
 * HandleEvent
 */
class HandleEvent {
    /** @var int charge id PK */
    var $HE_handleeventid = null;
    /** @var int type */
    var $HE_type = null;
    /** @var boolean enabled */
    var $HE_status = null;
    /** @var varchar(255) name */
    var $HE_name = null;
    /** @var int charge id PK */
    var $HE_notifypersonid = null;
    /** @var int notify days before turnoff */
    var $HE_notifydaysbeforeturnoff = null;
    /** @var varchar(255) email subject */
    var $HE_emailsubject = null;
    /** @var varchar(255) template path */
    var $HE_templatepath = null;
    /** @var varchar(255) name */
    var $HE_description = null;

    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    public static $STATUS_ARRAY = array(
        0, //Disabled
        1  //Enabled
    );

    public static function getLocalizedStatus($enabled) {
        switch ($enabled) {
            case self::STATUS_DISABLED:
                return _("Disabled");

            case self::STATUS_ENABLED:
                return _("Enabled");
        }
    }

    const TYPE_CHARGE_PAYMENT_DEADLINE = 1;
//	const TYPE_PAYMENT_RECEIVED = 2;

    public static $TYPE_ARRAY = array(
        1 //Charge payment deadline
//		2 //Payment received
    );

    public static function getLocalizedType($type) {
        switch ($type) {
            case self::TYPE_CHARGE_PAYMENT_DEADLINE:
                return _("Charge payment deadline");

//			case self::TYPE_PAYMENT_RECEIVED:
//				return _("Payment received");
        }
    }
} // End of HandleEvent class
?>
