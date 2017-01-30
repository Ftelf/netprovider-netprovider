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
 * Log
 */
class Log {
    /** @var int log id PK */
    var $LO_logid = null;
    /** @var int id of the person */
    var $LO_personid = null;
    /** @var datetime timestamp */
    var $LO_datetime = null;
    /** @var String log string */
    var $LO_log = null;
    /** @var int log level */
    var $LO_level = null;

    const LEVEL_UNSPECIFIED = 0;
    const LEVEL_LOG = 1;
    const LEVEL_DEBUG = 2;
    const LEVEL_INFO = 3;
    const LEVEL_WARNING = 4;
    const LEVEL_ERROR = 5;
    const LEVEL_CRITICAL = 6;
    const LEVEL_SECURITY = 7;

    public static $LEVEL_ARRAY = array(
        1, //Log
        2, //Debug
        3, //Info
        4, //Warning
        5, //Error
        6, //Critical
        7  //Security
    );

    public static function getLocalizedLevel($level) {
        switch ($level) {
            case self::LEVEL_UNSPECIFIED:
                return _("Unspecified");

            case self::LEVEL_LOG:
                return _("Log");

            case self::LEVEL_DEBUG:
                return _("Debug");

            case self::LEVEL_INFO:
                return _("Info");

            case self::LEVEL_WARNING:
                return _("Warning");

            case self::LEVEL_ERROR:
                return _("Error");

            case self::LEVEL_CRITICAL:
                return _("Critical");

            case self::LEVEL_SECURITY:
                return _("Security");
        }
    }
} // End of Log class
?>