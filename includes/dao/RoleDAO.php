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
require_once($core->getAppRoot() . "/includes/tables/Role.php");

/**
 *  RoleDAO
 */
class RoleDAO {
	static function getRoleCount() {
		global $database;
		$query = "SELECT count(*) FROM `role`";
		$database->setQuery($query);
		return $database->loadResult();
	}
	static function getRoleArray($limitstart=null, $limit=null) {
		global $database;
		$query = "SELECT * FROM `role`";
		if ($limitstart != null && $limit != null) {
			$query .= " LIMIT $limitstart,$limit";
		}
		$database->setQuery($query);
		return $database->loadObjectList("RO_roleid");
	}
	static function getRoleByID($id) {
		if ($id == null) throw new Exception("no ID specified");
		global $database;
		$role = new Role();
		$query = "SELECT * FROM `role` WHERE `RO_roleid`='$id' LIMIT 1";
		$database->setQuery($query);
		$database->loadObject($role);
		return $role;
	}
	static function removeRoleByID($id) {
		if ($id == null) throw new Exception("no ID specified");
		global $database;
		$query = "DELETE FROM `role` WHERE `RO_roleid`='$id' LIMIT 1";
		$database->setQuery($query);
		$database->query();
	}
} // End of RoleDAO class
?>