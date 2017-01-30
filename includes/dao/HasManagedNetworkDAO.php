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

global $core;
require_once($core->getAppRoot() . "/includes/tables/NetworkDevice.php");
require_once($core->getAppRoot() . "/includes/tables/Network.php");
require_once($core->getAppRoot() . "/includes/tables/HasManagedNetwork.php");

/**
 *  RolememberDAO
 */
class HasManagedNetworkDAO {
    static function getHasManagedNetworkAndNetworksArrayByNetworkDeviceID($id) {
        global $database;
        $query = "SELECT * FROM `hasmanagednetwork` as h,`network` as n WHERE h.MN_networkdeviceid='$id' AND h.MN_networkid=n.NE_networkid";
        $database->setQuery($query);
        return $database->loadObjectList("MN_hasmanagednetworkid");
    }
    static function getHasManagedNetworkByID($id) {
        if ($id == null) throw new Exception("no ID specified");
        global $database;
        $hasManagedNetwork = new HasManagedNetwork();
        $query = "SELECT * FROM `hasmanagednetwork` WHERE `MN_hasmanagednetworkid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($hasManagedNetwork);
        return $hasManagedNetwork;
    }
    static function removeHasManagedNetworkByID($id) {
        if ($id == null) throw new Exception("no ID specified");
        global $database;
        $query = "DELETE FROM `hasmanagednetwork` WHERE `MN_hasmanagednetworkid`='$id'";
        $database->setQuery($query);
        $database->query();
    }
    static function removeHasManagedNetworksByManagedDeviceID($id) {
        if ($id == null) throw new Exception("no ID specified");
        global $database;
        $query = "DELETE FROM `hasmanagednetwork` WHERE `MN_networkdeviceid`='$id'";
        $database->setQuery($query);
        $database->query();
    }
} // End of RolememberDAO class
?>