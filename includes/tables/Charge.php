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
// | Authors: Lukas Dziadkowiec <stealth.home@seznam.cz>                  |
// +----------------------------------------------------------------------+

/**
 * @author  Lukas Dziadkowiec <stealth.home@seznam.cz>
 */

/**
 * Charge
 */
class Charge {
    /** @var int charge id PK */
    var $CH_chargeid = null;
    /** @var varchar(255) name of the charge */
    var $CH_name = null;
    /** @var varchar(255) description */
    var $CH_description = null;
    /** @var int charge every time period */
    var $CH_period = null;
    /** @var DECIMAL(10,2) */
    var $CH_vat = null;
    /** @var DECIMAL(10,2) */
    var $CH_baseamount = null;
    /** @var DECIMAL(10,2) charge amount */
    var $CH_amount = null;
    /** @var varchar(10) currency of account */
    var $CH_currency = null;
    /** @var int offset in days that specify deadline date for payment */
    var $CH_tolerance = null;
    /** @var int write-off offset in days */
    var $CH_writeoffoffset = null;
    /** @var int type of charge */
    var $CH_type = null;
    /** @var int internet ID FK */
    var $CH_priority = null;
    /** @var int charge priority ID FK */
    var $CH_internetid = null;

    const PERIOD_ONCE = 1;
    //reserve weekly = 2
    const PERIOD_MONTHLY = 3;

    public static $PERIOD_ARRAY = array(
        1, //Once
        3 //Monthly
    );

    public static function getLocalizedPeriod($period) {
        switch ($period) {
            case self::PERIOD_ONCE:
                return _("Once");

            case self::PERIOD_MONTHLY:
                return _("Monthly");
        }
    }

    const TYPE_UNSPECIFIED = 1;
    const TYPE_INTERNET_PAYMENT = 2;
    const TYPE_ENTRY_FEE = 3;
    const TYPE_PENALTY = 4;

    public static $TYPE_ARRAY = array(
        1, //Unspecified
        2, //Internet payment
        3, //Entry fee
        4, //Penalty
    );

    public static function getLocalizedType($type) {
        switch ($type) {
            case self::TYPE_UNSPECIFIED:
                return _("Unspecified");

            case self::TYPE_INTERNET_PAYMENT:
                return _("Internet payment");

            case self::TYPE_ENTRY_FEE:
                return _("Entry fee");

            case self::TYPE_PENALTY:
                return _("Penalty");
        }
    }
} // End of Charge class
?>
