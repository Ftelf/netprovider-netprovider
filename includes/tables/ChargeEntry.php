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
 * ChargeEntry
 */
class ChargeEntry
{
    /**
     * @public int chargeEntry id PK
     */
    public $CE_chargeentryid;
    /**
     * @public int haschargeid FK
     */
    public $CE_haschargeid;
    /**
     * @public date date period charge is intend for
     */
    public $CE_period_date;
    /**
     * @public int write-off offset in days
     */
    public $CE_writeoffoffset;
    /**
     * @public date date charge was realized
     */
    public $CE_realize_date;
    /**
     * @public int overdue days
     */
    public $CE_overdue;
    /**
     * @public DECIMAL(10,2)
     */
    public $CE_vat;
    /**
     * @public DECIMAL(10,2)
     */
    public $CE_baseamount;
    /**
     * @public DECIMAL(10,2) charge amount
     */
    public $CE_amount;
    /**
     * @public varchar(10) currency of account
     */
    public $CE_currency;
    /**
     * @public int status of chargeEntry
     */
    public $CE_status;

    public const STATUS_FINISHED = 1;
    public const STATUS_PENDING = 2;
    public const STATUS_PENDING_INSUFFICIENTFUNDS = 3;
    public const STATUS_TESTINGFREEOFCHARGE = 4;
    public const STATUS_DISABLED = 5;
    public const STATUS_ERROR = 6;

    public static array $STATUS_ARRAY = [
        self::STATUS_FINISHED,
        self::STATUS_PENDING,
        self::STATUS_PENDING_INSUFFICIENTFUNDS,
        self::STATUS_TESTINGFREEOFCHARGE,
        self::STATUS_DISABLED,
        self::STATUS_ERROR
    ];

    public static function getLocalizedStatus($status): string
    {
        return match ((int)$status) {
            self::STATUS_FINISHED => _("Finished"),
            self::STATUS_PENDING => _("Pending"),
            self::STATUS_PENDING_INSUFFICIENTFUNDS => _("Pending, insufficient funds"),
            self::STATUS_TESTINGFREEOFCHARGE => _("Testing, free of charge"),
            self::STATUS_DISABLED => _("Disabled for this period"),
            self::STATUS_ERROR => _("Error"),
            default => "",
        };
    }
} // End of ChargeEntry class
