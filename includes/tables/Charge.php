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
 * Charge
 */
class Charge
{
    /**
     * @public int charge id PK
     */
    public $CH_chargeid;
    /**
     * @public varchar(255) name of the charge
     */
    public $CH_name;
    /**
     * @public varchar(255) description
     */
    public $CH_description;
    /**
     * @public int charge every time period
     */
    public $CH_period;
    /**
     * @public DECIMAL(10,2)
     */
    public $CH_vat;
    /**
     * @public DECIMAL(10,2)
     */
    public $CH_baseamount;
    /**
     * @public DECIMAL(10,2) charge amount
     */
    public $CH_amount;
    /**
     * @public varchar(10) currency of account
     */
    public $CH_currency;
    /**
     * @public int offset in days that specify deadline date for payment
     */
    public $CH_tolerance;
    /**
     * @public int write-off offset in days
     */
    public $CH_writeoffoffset;
    /**
     * @public int type of charge
     */
    public $CH_type;
    /**
     * @public int internet ID FK
     */
    public $CH_priority;
    /**
     * @public int charge priority ID FK
     */
    public $CH_internetid;

    public const PERIOD_MONTHLY = 3;

    public static array $PERIOD_ARRAY = [
        self::PERIOD_MONTHLY
    ];

    public static array $periodLocalization = [
        self::PERIOD_MONTHLY => "Monthly",
    ];

    public static function getLocalizedPeriod($period): string
    {
        return _(self::$periodLocalization[$period] ?? '');
    }

    public const TYPE_UNSPECIFIED = 1;
    public const TYPE_INTERNET_PAYMENT = 2;
    public const TYPE_ENTRY_FEE = 3;
    public const TYPE_PENALTY = 4;

    public static array $TYPE_ARRAY = [
        self::TYPE_UNSPECIFIED,
        self::TYPE_INTERNET_PAYMENT,
        self::TYPE_ENTRY_FEE,
        self::TYPE_PENALTY
    ];

    public static array $typeLocalization = [
        self::TYPE_UNSPECIFIED => "Unspecified",
        self::TYPE_INTERNET_PAYMENT => "Internet payment",
        self::TYPE_ENTRY_FEE => "Entry fee",
        self::TYPE_PENALTY => "Penalty"
    ];

    public static function getLocalizedType($type): string
    {
        return _(self::$typeLocalization[$type] ?? '');
    }
} // End of Charge class
