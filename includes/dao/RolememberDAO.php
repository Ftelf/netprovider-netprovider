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

global $core;
require_once $core->getAppRoot() . "/includes/tables/Person.php";
require_once $core->getAppRoot() . "/includes/tables/Role.php";
require_once $core->getAppRoot() . "/includes/tables/Rolemember.php";

/**
 *  RolememberDAO
 */
class RolememberDAO
{
    public static function getRolememberArray(): array
    {
        global $database;
        $query = "SELECT * FROM `rolemember`";
        $database->setQuery($query);
        return $database->loadObjectList("RM_rolememberid");
    }

    public static function getRolememberAndPersonsArrayByRoleID($id): array
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "SELECT * FROM `person` as p,`rolemember` as r WHERE r.RM_roleid='$id' AND p.PE_personid=r.RM_personid";
        $database->setQuery($query);
        return $database->loadObjectList("RM_rolememberid");
    }

    public static function getRolememberAndRoleArrayByPersonID($id): array
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "SELECT * FROM `rolemember`, `role` WHERE `RM_personid`='$id' AND `RM_roleid`=`RO_roleid`";
        $database->setQuery($query);
        return $database->loadObjectList("RM_rolememberid");
    }

    public static function getRolememberByID($id): Rolemember
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $rolemember = new Rolemember();
        $query = "SELECT * FROM `rolemember` WHERE `RM_rolememberid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($rolemember);
        return $rolemember;
    }

    public static function removeRolemembersByID($id): void
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "DELETE FROM `rolemember` WHERE `RM_rolememberid`='$id'";
        $database->setQuery($query);
        $database->query();
    }

    public static function removeRolemembersByPersonID($id): void
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "DELETE FROM `rolemember` WHERE `RM_personid`='$id'";
        $database->setQuery($query);
        $database->query();
    }
} // End of RolememberDAO class
