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
require_once $core->getAppRoot() . "/includes/tables/NetworkDevice.php";
require_once $core->getAppRoot() . "/includes/tables/Network.php";
require_once $core->getAppRoot() . "/includes/tables/HasManagedNetwork.php";

/**
 *  HasManagedNetworkDAO
 */
class HasManagedNetworkDAO
{
    public static function getHasManagedNetworkAndNetworksArrayByNetworkDeviceID($id): array
    {
        global $database;
        $query = "SELECT * FROM `hasmanagednetwork` as h,`network` as n WHERE h.MN_networkdeviceid='$id' AND h.MN_networkid=n.NE_networkid";
        $database->setQuery($query);
        return $database->loadObjectList("MN_hasmanagednetworkid");
    }

    public static function getHasManagedNetworkByID($id): HasManagedNetwork
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $hasManagedNetwork = new HasManagedNetwork();
        $query = "SELECT * FROM `hasmanagednetwork` WHERE `MN_hasmanagednetworkid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($hasManagedNetwork);
        return $hasManagedNetwork;
    }

    public static function removeHasManagedNetworkByID($id): void
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "DELETE FROM `hasmanagednetwork` WHERE `MN_hasmanagednetworkid`='$id'";
        $database->setQuery($query);
        $database->query();
    }

    public static function removeHasManagedNetworksByManagedDeviceID($id): void
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "DELETE FROM `hasmanagednetwork` WHERE `MN_networkdeviceid`='$id'";
        $database->setQuery($query);
        $database->query();
    }
} // End of HasManagedNetworkDAO class
