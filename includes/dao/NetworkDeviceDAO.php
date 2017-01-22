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