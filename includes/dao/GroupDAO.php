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
require_once($core->getAppRoot() . "/includes/tables/Group.php");

/**
 *  GroupDAO
 */
class GroupDAO {
	static function getGroupCount() {
		global $database;
		$query = "SELECT count(*) FROM `group`";
		$database->setQuery($query);
		return $database->loadResult();
	}
	static function getGroupArray($limitstart=null, $limit=null) {
		global $database;
		$query = "SELECT * FROM `group`";
		if ($limitstart !== null && $limit !== null) {
			$query .= " LIMIT $limitstart,$limit";
		}
		$database->setQuery($query);
		return $database->loadObjectList("GR_groupid");
	}
	static function getGroupByID($id) {
		if (!$id) throw new Exception("no ID specified");
		global $database;
		$group = new Group();
		$query = "SELECT * FROM `group` WHERE `GR_groupid`='$id' LIMIT 1";
		$database->setQuery($query);
		$database->loadObject($group);
		return $group;
	}
	static function removeGroupByID($id) {
		if (!$id) throw new Exception("no ID specified");
		global $database;
		$query = "DELETE FROM `group` WHERE `GR_groupid`='$id' LIMIT 1";
		$database->setQuery($query);
		$database->query();
	}
} // End of GroupDAO class
?>