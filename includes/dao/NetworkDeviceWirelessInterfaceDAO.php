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
require_once $core->getAppRoot() . "/includes/tables/NetworkDeviceWirelessInterface.php";

/**
 *  NetworkDeviceWirelessInterfaceDAO
 */
class NetworkDeviceWirelessInterfaceDAO
{
    public static function getNetworkDeviceWirelessInterfaceCount()
    {
        global $database;
        $query = "SELECT count(*) FROM `networkdevicewirelessinterface`";
        $database->setQuery($query);
        return $database->loadResult();
    }

    public static function getNetworkDeviceWirelessInterfaceArray(): array
    {
        global $database;
        $query = "SELECT * FROM `networkdevicewirelessinterface`";
        $database->setQuery($query);
        return $database->loadObjectList("NW_networkdevicewirelessinterfaceid");
    }

    public static function getNetworkDeviceWirelessInterfaceArrayByNetworkDeviceID($id): array
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "SELECT * FROM `networkdevicewirelessinterface` WHERE `NW_networkdeviceid`='$id'";
        $database->setQuery($query);
        return $database->loadObjectList("NW_networkdevicewirelessinterfaceid");
    }

    public static function getNetworkDeviceWirelessInterfaceByID($id): NetworkDeviceWirelessInterface
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $networkDeviceWirelessInterface = new NetworkDeviceWirelessInterface();
        $query = "SELECT * FROM `networkdevicewirelessinterface` WHERE `NW_networkdevicewirelessinterfaceid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($networkDeviceWirelessInterface);
        return $networkDeviceWirelessInterface;
    }

    public static function removeNetworkDeviceWirelessInterfaceByID($id): void
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "DELETE FROM `networkdevicewirelessinterface` WHERE `NW_networkdevicewirelessinterfaceid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }

    public static function removeNetworkDeviceWirelessInterfaceByNetworkDeviceID($id): void
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "DELETE FROM `networkdevicewirelessinterface` WHERE `NW_networkdeviceid`='$id'";
        $database->setQuery($query);
        $database->query();
    }
} // End of NetworkDeviceWirelessInterfaceDAO class
