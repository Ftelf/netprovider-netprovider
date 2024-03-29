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
class Log
{
    /**
     * @var int log id PK
     */
    public $LO_logid;
    /**
     * @var int id of the person
     */
    public $LO_personid;
    /**
     * @var datetime timestamp
     */
    public $LO_datetime;
    /**
     * @var String log string
     */
    public $LO_log;
    /**
     * @var int log level
     */
    public $LO_level;

    public const LEVEL_UNSPECIFIED = 0;
    public const LEVEL_LOG = 1;
    public const LEVEL_DEBUG = 2;
    public const LEVEL_INFO = 3;
    public const LEVEL_WARNING = 4;
    public const LEVEL_ERROR = 5;
    public const LEVEL_CRITICAL = 6;
    public const LEVEL_SECURITY = 7;

    public static $LEVEL_ARRAY = [
        self::LEVEL_LOG,
        self::LEVEL_DEBUG,
        self::LEVEL_INFO,
        self::LEVEL_WARNING,
        self::LEVEL_ERROR,
        self::LEVEL_CRITICAL,
        self::LEVEL_SECURITY
    ];

    public static function getLocalizedLevel($level): string
    {
        return match ((int)$level) {
            self::LEVEL_UNSPECIFIED => _("Unspecified"),
            self::LEVEL_LOG => _("Log"),
            self::LEVEL_DEBUG => _("Debug"),
            self::LEVEL_INFO => _("Info"),
            self::LEVEL_WARNING => _("Warning"),
            self::LEVEL_ERROR => _("Error"),
            self::LEVEL_CRITICAL => _("Critical"),
            self::LEVEL_SECURITY => _("Security"),
            default => "",
        };
    }
} // End of Log class
