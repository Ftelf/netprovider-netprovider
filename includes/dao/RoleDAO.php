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
require_once $core->getAppRoot() . "/includes/tables/Role.php";

/**
 *  RoleDAO
 */
class RoleDAO
{
    public static function getRoleCount()
    {
        global $database;
        $query = "SELECT count(*) FROM `role`";
        $database->setQuery($query);
        return $database->loadResult();
    }

    public static function getRoleArray($limitstart = null, $limit = null): array
    {
        global $database;
        $query = "SELECT * FROM `role`";
        if ($limitstart != null && $limit != null) {
            $query .= " LIMIT $limitstart,$limit";
        }
        $database->setQuery($query);
        return $database->loadObjectList("RO_roleid");
    }

    public static function getRoleByID($id): Role
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $role = new Role();
        $query = "SELECT * FROM `role` WHERE `RO_roleid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($role);
        return $role;
    }

    public static function removeRoleByID($id): void
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "DELETE FROM `role` WHERE `RO_roleid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }
} // End of RoleDAO class
