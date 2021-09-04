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
 *  HasCharge
 */

class HasCharge {
    /** @var int hascharge id PK */
    var $HC_haschargeid = null;
    /** @var int chargeid FK */
    var $HC_chargeid = null;
    /** @var int personid FK */
    var $HC_personid = null;
    /** @var date datestart */
    var $HC_datestart = null;
    /** @var date dateend, null when charge is continuous */
    var $HC_dateend = null;
    /** @var int status */
    var $HC_status = null;
    /** @var int current status */
    var $HC_actualstate = null;

    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;
    const STATUS_FORCE_DISABLED = 2;
    const STATUS_FORCE_ENABLED = 3;

    public static $STATUS_ARRAY = array(
        0, //Deactivated
        1, //Activated
        2, //Service is always deactivated
        3  //Service is always activated
    );

    public static function getLocalizedStatus($status) {
        switch ($status) {
            case self::STATUS_DISABLED:
                return _("Deactivated");

            case self::STATUS_ENABLED:
                return _("Activated");

            case self::STATUS_FORCE_DISABLED:
                return _("Service is always deactivated");

            case self::STATUS_FORCE_ENABLED:
                return _("Service is always activated");
        }
    }

    const ACTUALSTATE_DISABLED = 0;
    const ACTUALSTATE_ENABLED = 1;

    public static $ACTUALSTATE_ARRAY = array(
        0, //Deactivated
        1  //Activated
    );

    public static function getLocalizedActualState($actualState) {
        switch ($actualState) {
            case self::ACTUALSTATE_DISABLED:
                return _("Deactivated");

            case self::ACTUALSTATE_ENABLED:
                return _("Activated");
        }
    }
} // End of HasCharge class
?>
