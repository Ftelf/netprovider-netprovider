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
class HandleEvent
{
    /**
     * @var int charge id PK
     */
    public $HE_handleeventid;
    /**
     * @var int type
     */
    public $HE_type;
    /**
     * @var boolean enabled
     */
    public $HE_status;
    /**
     * @var varchar(255) name
     */
    public $HE_name;
    /**
     * @var int charge id PK
     */
    public $HE_notifypersonid;
    /**
     * @var int notify days before turnoff
     */
    public $HE_notifydaysbeforeturnoff;
    /**
     * @var varchar(255) email subject
     */
    public $HE_emailsubject;
    /**
     * @var varchar(255) template path
     */
    public $HE_templatepath;
    /**
     * @var varchar(255) name
     */
    public $HE_description;

    public const STATUS_DISABLED = 0;
    public const STATUS_ENABLED = 1;

    public static array $STATUS_ARRAY = [
        self::STATUS_DISABLED,
        self::STATUS_ENABLED
    ];

    public static function getLocalizedStatus($status): string
    {
        return match ($status) {
            self::STATUS_DISABLED => _("Disabled"),
            self::STATUS_ENABLED => _("Enabled"),
            default => "",
        };
    }

    public const TYPE_CHARGE_PAYMENT_DEADLINE = 1;
    //  const TYPE_PAYMENT_RECEIVED = 2;

    public static array $TYPE_ARRAY = [
        self::TYPE_CHARGE_PAYMENT_DEADLINE
//        self::TYPE_PAYMENT_RECEIVED
    ];

    public static function getLocalizedType($type): string
    {
        return match ($type) {
            self::TYPE_CHARGE_PAYMENT_DEADLINE => _("Charge payment deadline"),
//            self::TYPE_PAYMENT_RECEIVED => _("Payment received")
            default => "",
        };
    }
} // End of HandleEvent class
