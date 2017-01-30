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