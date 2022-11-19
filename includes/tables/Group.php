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
class Group {
    /** @var int group id PK */
    var $GR_groupid = null;
    /** @var string name of the group */
    var $GR_name = null;
    /** @var int access list definition */
    var $GR_acl = null;
    /** @var int group right level */
    var $GR_level = null;

    const USER = 0;
    const ADMINISTRATOR = 5;
    const SUPER_ADMININSTRATOR = 9;

    public static $LEVEL_ARRAY = array(
        0, //User
        5, //Administrator
        9  //Super administrator
    );

    public static function getLocalizedLevel($level) {
        switch ($level) {
            case self::USER:
                return _("User");

            case self::ADMINISTRATOR:
                return _("Administrator");

            case self::SUPER_ADMININSTRATOR:
                return _("Super administrator");
        }
    }
} // End of Group class
?>
