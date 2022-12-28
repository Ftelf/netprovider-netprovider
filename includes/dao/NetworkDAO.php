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
require_once $core->getAppRoot() . "/includes/tables/Network.php";

/**
 *  NetworkDAO
 */
class NetworkDAO
{
    public static function getNetworkCount()
    {
        global $database;
        $query = "SELECT count(*) FROM `network`";
        $database->setQuery($query);
        return $database->loadResult();
    }

    public static function getNetworkArray(): array
    {
        global $database;
        $query = "SELECT * FROM `network`";
        $database->setQuery($query);
        return $database->loadObjectList("NE_networkid");
    }

    public static function getNetworkArrayByParentNetworkID($id): array
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "SELECT * FROM `network` WHERE `NE_parent_networkid`='$id'";
        $database->setQuery($query);
        return $database->loadObjectList("NE_networkid");
    }

    public static function getNetworkArrayByPersonID($id): array
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "SELECT * FROM `network` WHERE `NE_personid`='$id'";
        $database->setQuery($query);
        return $database->loadObjectList("NE_networkid");
    }

    public static function getNetworkByID($id): Network
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $network = new Network();
        $query = "SELECT * FROM `network` WHERE `NE_networkid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($network);
        return $network;
    }

    public static function getFirstNetworkByParentNetworkID($id): Network
    {
        if ($id === null) {
            throw new Exception("no ID specified");
        }
        global $database;
        $network = new Network();
        $query = "SELECT * FROM `network` WHERE `NE_parent_networkid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($network);
        return $network;
    }

    public static function removeNetworkByID($id): void
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "DELETE FROM `network` WHERE `NE_networkid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }

    public static function isLeafNetwork($id): bool
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "SELECT count(*) FROM `network` WHERE `NE_parent_networkid`='$id'";
        $database->setQuery($query);
        return $database->loadResult() === "0";
    }
} // End of NetworkDAO class
