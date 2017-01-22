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
require_once($core->getAppRoot() . "/includes/tables/NetworkDeviceWirelessInterface.php");

/**
 *  NetworkDeviceWirelessInterfaceDAO
 */
class NetworkDeviceWirelessInterfaceDAO {
	static function getNetworkDeviceWirelessInterfaceCount() {
		global $database;
		$query = "SELECT count(*) FROM `networkdevicewirelessinterface`";
		$database->setQuery($query);
		return $database->loadResult();
	}
	static function getNetworkDeviceWirelessInterfaceArray() {
		global $database;
		$query = "SELECT * FROM `networkdevicewirelessinterface`";
		$database->setQuery($query);
		return $database->loadObjectList("NW_networkdevicewirelessinterfaceid");
	}
	static function getNetworkDeviceWirelessInterfaceArrayByNetworkDeviceID($id) {
		if ($id == null) throw new Exception("no ID specified");
		global $database;
		$query = "SELECT * FROM `networkdevicewirelessinterface` WHERE `NW_networkdeviceid`='$id'";
		$database->setQuery($query);
		return $database->loadObjectList("NW_networkdevicewirelessinterfaceid");
	}
	static function getNetworkDeviceWirelessInterfaceByID($id) {
		if ($id == null) throw new Exception("no ID specified");
		global $database;
		$networkDeviceWirelessInterface = new NetworkDeviceWirelessInterface();
		$query = "SELECT * FROM `networkdevicewirelessinterface` WHERE `NW_networkdevicewirelessinterfaceid`='$id' LIMIT 1";
		$database->setQuery($query);
		$database->loadObject($networkDeviceWirelessInterface);
		return $networkDeviceWirelessInterface;
	}
	static function removeNetworkDeviceWirelessInterfaceByID($id) {
		if ($id == null) throw new Exception("no ID specified");
		global $database;
		$query = "DELETE FROM `networkdevicewirelessinterface` WHERE `NW_networkdevicewirelessinterfaceid`='$id' LIMIT 1";
		$database->setQuery($query);
		return $database->query();
	}
	static function removeNetworkDeviceWirelessInterfaceByNetworkDeviceID($id) {
		if ($id == null) throw new Exception("no ID specified");
		global $database;
		$query = "DELETE FROM `networkdevicewirelessinterface` WHERE `NW_networkdeviceid`='$id'";
		$database->setQuery($query);
		$database->query();
	}
} // End of NetworkDeviceWirelessInterfaceDAO class
?>