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
require_once($core->getAppRoot() . "/includes/tables/HandleEvent.php");

/**
 *  HandleEventDAO
 */
class HandleEventDAO {
	static function getHandleEventCount() {
		global $database;
		$query = "SELECT count(*) FROM `handleevent`";
		$database->setQuery($query);
		return $database->loadResult();
	}
	static function getHandleEventArray($limitstart=null, $limit=null) {
		global $database;
		$query = "SELECT * FROM `handleevent`";
		if ($limitstart !== null && $limit !== null) {
			$query .= " LIMIT $limitstart,$limit";
		}
		$database->setQuery($query);
		return $database->loadObjectList("HE_handleeventid");
	}
	static function getHandleEventByID($id) {
		if (!$id) throw new Exception("no ID specified");
		global $database;
		$handleEvent = new HandleEvent();
		$query = "SELECT * FROM `handleevent` WHERE `HE_handleeventid`='$id' LIMIT 1";
		$database->setQuery($query);
		$database->loadObject($handleEvent);
		return $handleEvent;
	}
	static function removeHandleEventByID($id) {
		if (!$id) throw new Exception("no ID specified");
		global $database;
		$query = "DELETE FROM `handleevent` WHERE `HE_handleeventid`='$id' LIMIT 1";
		$database->setQuery($query);
		$database->query();
	}
} // End of HandleEventDAO class
?>