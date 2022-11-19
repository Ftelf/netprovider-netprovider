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
require_once($core->getAppRoot() . "/includes/tables/NetworkDevice.php");

/**
 *  NetworkDeviceDAO
 */
class NetworkDeviceDAO {
    static function getNetworkDeviceCount() {
        global $database;
        $query = "SELECT count(*) FROM `networkdevice`";
        $database->setQuery($query);
        return $database->loadResult();
    }
    static function getNetworkDeviceCountWherePlatform($platform) {
        if (!$platform) throw new Exception("no platform specified");
        global $database;
        $query = "SELECT count(*) FROM `networkdevice` WHERE `ND_platform`='$platform'";
        $database->setQuery($query);
        return $database->loadResult();
    }
    static function getNetworkDeviceArray($limitstart=null, $limit=null) {
        global $database;
        $query = "SELECT * FROM `networkdevice`";
        if ($limitstart !== null && $limit !== null) {
            $query .= " LIMIT $limitstart,$limit";
        }
        $database->setQuery($query);
        return $database->loadObjectList("ND_networkdeviceid");
    }
    static function getNetworkDeviceArrayWherePlatform($platform, $limitstart=null, $limit=null) {
        if (!$platform) throw new Exception("no platform specified");
        global $database;
        $query = "SELECT * FROM `networkdevice` WHERE `ND_platform`='$platform'";
        if ($limitstart && $limit) {
            $query .= " LIMIT $limitstart,$limit";
        }
        $database->setQuery($query);
        return $database->loadObjectList("ND_networkdeviceid");
    }
    static function getNetworkDeviceByID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $networkDevice = new NetworkDevice();
        $query = "SELECT * FROM `networkdevice` WHERE `ND_networkdeviceid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($networkDevice);
        return $networkDevice;
    }
    static function removeNetworkDeviceByID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $query = "DELETE FROM `networkdevice` WHERE `ND_networkdeviceid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }
} // End of NetworkDeviceDAO class
?>
