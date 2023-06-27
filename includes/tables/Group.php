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
 * Group
 */
class Group
{
    /**
     * @var int group id PK
     */
    public $GR_groupid;
    /**
     * @var string name of the group
     */
    public $GR_name;
    /**
     * @var int access list definition
     */
    public $GR_acl;
    /**
     * @var int group right level
     */
    public $GR_level;

    public const USER = 0;
    public const ADMINISTRATOR = 5;
    public const SUPER_ADMINISTRATOR = 9;

    public static array $LEVEL_ARRAY = [
        self::USER,
        self::ADMINISTRATOR,
        self::SUPER_ADMINISTRATOR
    ];

    public static function getLocalizedLevel($level): string
    {
        return match ($level) {
            self::USER => _("User"),
            self::ADMINISTRATOR => _("Administrator"),
            self::SUPER_ADMINISTRATOR => _("Super administrator"),
            default => "",
        };
    }
} // End of Group class
