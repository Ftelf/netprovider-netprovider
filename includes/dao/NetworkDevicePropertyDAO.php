<?php
//
// +----------------------------------------------------------------------+
// | Ftelf ISP billing system                                               |
// +----------------------------------------------------------------------+
// | Copyright (c) 2006-2007 Ing. Lukas Dziadkowiec                       |
// +----------------------------------------------------------------------+
// | This source file is part of Ftelf ISP billing system,                  |
// | see LICENSE for licence details.                                     |
// +----------------------------------------------------------------------+
// | Authors: Lukas Dziadkowiec <i.ftelf@gmail.com>                  |
// +----------------------------------------------------------------------+

/**
 * @author  Lukas Dziadkowiec <i.ftelf@gmail.com>
 */

global $core;
require_once($core->getAppRoot() . "/includes/tables/NetworkDeviceProperty.php");

/**
 *  NetworkDevicePropertyDAO
 */
class NetworkDevicePropertyDAO {
    static function getNetworkDevicePropertyCount() {
        global $database;
        $query = "SELECT count(*) FROM `networkdeviceproperty`";
        $database->setQuery($query);
        return $database->loadResult();
    }
    static function getNetworkDevicePropertyArray() {
        global $database;
        $query = "SELECT * FROM `networkdeviceproperty`";
        $database->setQuery($query);
        return $database->loadObjectList("NP_networkdevicepropertyid");
    }
    static function getNetworkDevicePropertyArrayByNetworkDeviceID($id) {
        if ($id == null) throw new Exception("no ID specified");
        global $database;
        $query = "SELECT * FROM `networkdeviceproperty` WHERE `NP_networkdeviceid`='$id'";
        $database->setQuery($query);
        return $database->loadObjectList("NP_networkdevicepropertyid");
    }
    static function getNetworkDevicePropertyByID($id) {
        if ($id == null) throw new Exception("no ID specified");
        global $database;
        $networkDeviceProperty = new NetworkDeviceProperty();
        $query = "SELECT * FROM `networkdeviceproperty` WHERE `NP_networkdevicepropertyid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($networkDeviceProperty);
        return $networkDeviceProperty;
    }
    static function removeNetworkDevicePropertyByID($id) {
        if ($id == null) throw new Exception("no ID specified");
        global $database;
        $query = "DELETE FROM `networkdeviceproperty` WHERE `NP_networkdevicepropertyid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }
    static function removeNetworkDevicePropertyByNetworkDeviceID($id) {
        if ($id == null) throw new Exception("no ID specified");
        global $database;
        $query = "DELETE FROM `networkdeviceproperty` WHERE `NP_networkdeviceid`='$id'";
        $database->setQuery($query);
        $database->query();
    }
} // End of NetworkDevicePropertyDAO class
?>
