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
 *  HasCharge
 */
class HasCharge
{
    /**
     * @var int hascharge id PK
     */
    public $HC_haschargeid;
    /**
     * @var int chargeid FK
     */
    public $HC_chargeid;
    /**
     * @var int personid FK
     */
    public $HC_personid;
    /**
     * @var date datestart
     */
    public $HC_datestart;
    /**
     * @var date dateend, null when charge is continuous
     */
    public $HC_dateend;
    /**
     * @var int status
     */
    public $HC_status;
    /**
     * @var int current status
     */
    public $HC_actualstate;

    public const STATUS_DISABLED = 0;
    public const STATUS_ENABLED = 1;
    public const STATUS_FORCE_DISABLED = 2;
    public const STATUS_FORCE_ENABLED = 3;

    public static array $STATUS_ARRAY = [
        self::STATUS_DISABLED,
        self::STATUS_ENABLED,
        self::STATUS_FORCE_DISABLED,
        self::STATUS_FORCE_ENABLED
    ];

    public static function getLocalizedStatus($status): string
    {
        return match ($status) {
            self::STATUS_DISABLED => "Deactivated",
            self::STATUS_ENABLED => "Activated",
            self::STATUS_FORCE_DISABLED => "Service is always deactivated",
            self::STATUS_FORCE_ENABLED => "Service is always activated",
            default => "",
        };
    }

    public const ACTUALSTATE_DISABLED = 0;
    public const ACTUALSTATE_ENABLED = 1;

    public static array $ACTUALSTATE_ARRAY = [
        self::ACTUALSTATE_DISABLED,
        self::ACTUALSTATE_ENABLED
    ];

    public static function getLocalizedActualState($actualState): string
    {
        return match ($actualState) {
            self::ACTUALSTATE_DISABLED => "Deactivated",
            self::ACTUALSTATE_ENABLED => "Activated",
            default => "",
        };
    }
} // End of HasCharge class
