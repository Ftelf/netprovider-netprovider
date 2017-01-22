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
require_once($core->getAppRoot() . "/includes/tables/NetworkDeviceInterface.php");

/**
 *  NetworkDeviceInterfaceDAO
 */
class NetworkDeviceInterfaceDAO {
	static function getNetworkDeviceInterfaceCount() {
		global $database;
		$query = "SELECT count(*) FROM `networkdeviceinterface`";
		$database->setQuery($query);
		return $database->loadResult();
	}
	static function getNetworkDeviceInterfaceArray() {
		global $database;
		$query = "SELECT * FROM `networkdeviceinterface`";
		$database->setQuery($query);
		return $database->loadObjectList("NI_networkdeviceinterfaceid");
	}
	static function getNetworkDeviceInterfaceArrayByNetworkDeviceID($id) {
		if ($id == null) throw new Exception("no ID specified");
		global $database;
		$query = "SELECT * FROM `networkdeviceinterface` WHERE `NI_networkdeviceid`='$id'";
		$database->setQuery($query);
		return $database->loadObjectList("NI_networkdeviceinterfaceid");
	}
	static function getNetworkDeviceInterfaceByID($id) {
		if ($id == null) throw new Exception("no ID specified");
		global $database;
		$networkDeviceInterface = new NetworkDeviceInterface();
		$query = "SELECT * FROM `networkdeviceinterface` WHERE `NI_networkdeviceinterfaceid`='$id' LIMIT 1";
		$database->setQuery($query);
		$database->loadObject($networkDeviceInterface);
		return $networkDeviceInterface;
	}
	static function removeNetworkDeviceInterfaceByID($id) {
		if ($id == null) throw new Exception("no ID specified");
		global $database;
		$query = "DELETE FROM `networkdeviceinterface` WHERE `NI_networkdeviceinterfaceid`='$id' LIMIT 1";
		$database->setQuery($query);
		$database->query();
	}
	static function removeNetworkDeviceInterfaceByNetworkDeviceID($id) {
		if ($id == null) throw new Exception("no ID specified");
		global $database;
		$query = "DELETE FROM `networkdeviceinterface` WHERE `NI_networkdeviceid`='$id'";
		$database->setQuery($query);
		$database->query();
	}
} // End of NetworkDeviceInterfaceDAO class
?>