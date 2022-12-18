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
require_once $core->getAppRoot() . "/includes/tables/NetworkDeviceInterface.php";

/**
 *  NetworkDeviceInterfaceDAO
 */
class NetworkDeviceInterfaceDAO
{
    public static function getNetworkDeviceInterfaceCount()
    {
        global $database;
        $query = "SELECT count(*) FROM `networkdeviceinterface`";
        $database->setQuery($query);
        return $database->loadResult();
    }

    public static function getNetworkDeviceInterfaceArray(): array
    {
        global $database;
        $query = "SELECT * FROM `networkdeviceinterface`";
        $database->setQuery($query);
        return $database->loadObjectList("NI_networkdeviceinterfaceid");
    }

    public static function getNetworkDeviceInterfaceArrayByNetworkDeviceID($id): array
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "SELECT * FROM `networkdeviceinterface` WHERE `NI_networkdeviceid`='$id'";
        $database->setQuery($query);
        return $database->loadObjectList("NI_networkdeviceinterfaceid");
    }

    public static function getNetworkDeviceInterfaceByID($id): NetworkDeviceInterface
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $networkDeviceInterface = new NetworkDeviceInterface();
        $query = "SELECT * FROM `networkdeviceinterface` WHERE `NI_networkdeviceinterfaceid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($networkDeviceInterface);
        return $networkDeviceInterface;
    }

    public static function removeNetworkDeviceInterfaceByID($id): void
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "DELETE FROM `networkdeviceinterface` WHERE `NI_networkdeviceinterfaceid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }

    public static function removeNetworkDeviceInterfaceByNetworkDeviceID($id): void
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "DELETE FROM `networkdeviceinterface` WHERE `NI_networkdeviceid`='$id'";
        $database->setQuery($query);
        $database->query();
    }
} // End of NetworkDeviceInterfaceDAO class
