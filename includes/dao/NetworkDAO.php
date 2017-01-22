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
require_once($core->getAppRoot() . "/includes/tables/Network.php");

/**
 *  NetworkDAO
 */
class NetworkDAO {
	static function getNetworkCount() {
		global $database;
		$query = "SELECT count(*) FROM `network`";
		$database->setQuery($query);
		return $database->loadResult();
	}
	static function getNetworkArray() {
		global $database;
		$query = "SELECT * FROM `network`";
		$database->setQuery($query);
		return $database->loadObjectList("NE_networkid");
	}
	static function getNetworkArrayByParentNetworkID($id) {
		if (!$id) throw new Exception("no ID specified");
		global $database;
		$query = "SELECT * FROM `network` WHERE `NE_parent_networkid`='$id'";
		$database->setQuery($query);
		return $database->loadObjectList("NE_networkid");
	}
	static function getNetworkArrayByPersonID($id) {
		if (!$id) throw new Exception("no ID specified");
		global $database;
		$query = "SELECT * FROM `network` WHERE `NE_personid`='$id'";
		$database->setQuery($query);
		return $database->loadObjectList("NE_networkid");
	}
	static function getNetworkByID($id) {
		if (!$id) throw new Exception("no ID specified");
		global $database;
		$network = new Network();
		$query = "SELECT * FROM `network` WHERE `NE_networkid`='$id' LIMIT 1";
		$database->setQuery($query);
		$database->loadObject($network);
		return $network;
	}
	static function getFirstNetworkByParentNetworkID($id) {
		if ($id === null) throw new Exception("no ID specified");
		global $database;
		$network = new Network();
		$query = "SELECT * FROM `network` WHERE `NE_parent_networkid`='$id' LIMIT 1";
		$database->setQuery($query);
		$database->loadObject($network);
		return $network;
	}
	static function removeNetworkByID($id) {
		if (!$id) throw new Exception("no ID specified");
		global $database;
		$query = "DELETE FROM `network` WHERE `NE_networkid`='$id' LIMIT 1";
		$database->setQuery($query);
		return $database->query();
	}
	static function isLeafNetwork($id) {
		if (!$id) throw new Exception("no ID specified");
		global $database;
		$network = new Network();
		$query = "SELECT * FROM `network` WHERE `NE_parent_networkid`='$id' LIMIT 1";
		$database->setQuery($query);
		try {
			$database->loadObject($network);
			return false;
		} catch (Exception $e) {
			return true;
		}
	}
} // End of NetworkDAO class
?>