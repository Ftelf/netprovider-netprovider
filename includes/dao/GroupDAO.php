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
require_once $core->getAppRoot() . "/includes/tables/Group.php";

/**
 *  GroupDAO
 */
class GroupDAO
{
    public static function getGroupCount()
    {
        global $database;
        $query = "SELECT count(*) FROM `group`";
        $database->setQuery($query);
        return $database->loadResult();
    }

    public static function getGroupArray($limitstart = null, $limit = null): array
    {
        global $database;
        $query = "SELECT * FROM `group`";
        if ($limitstart !== null && $limit !== null) {
            $query .= " LIMIT $limitstart,$limit";
        }
        $database->setQuery($query);
        return $database->loadObjectList("GR_groupid");
    }

    public static function getGroupByID($id): Group
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $group = new Group();
        $query = "SELECT * FROM `group` WHERE `GR_groupid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($group);
        return $group;
    }

    public static function removeGroupByID($id): void
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "DELETE FROM `group` WHERE `GR_groupid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }
} // End of GroupDAO class
