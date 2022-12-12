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
 * Log
 */
class Log {
    /** @var int log id PK */
    var $LO_logid;
    /** @var int id of the person */
    var $LO_personid;
    /** @var datetime timestamp */
    var $LO_datetime;
    /** @var String log string */
    var $LO_log;
    /** @var int log level */
    var $LO_level;

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
