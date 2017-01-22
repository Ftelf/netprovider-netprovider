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
require_once($core->getAppRoot() . "/includes/tables/Person.php");
require_once($core->getAppRoot() . "/includes/tables/PersonAccount.php");
require_once($core->getAppRoot() . "/includes/tables/Group.php");

/**
 *  PersonDAO
 */
class PersonDAO {
	static function getPersonCount($search="", $group=0, $status=-1) {
		global $database;
		
		$query = "SELECT count(*) FROM `person` WHERE 1";
		
		if ($search != "") {
			$query .= " AND (`PE_firstname` LIKE '$search%' OR `PE_surname` LIKE '$search%' OR `PE_nick` LIKE '$search%' OR `PE_username` LIKE '$search%' OR `PE_email` LIKE '$search%' OR `PE_tel` LIKE '$search%' OR `PE_icq` LIKE '$search%' OR `PE_jabber` LIKE '$search%' OR `PE_address` LIKE '$search%' OR `PE_city` LIKE '$search%' OR `PE_zip` LIKE '$search%' OR `PE_ic` LIKE '$search%' OR `PE_dic` LIKE '$search%' OR `PE_shortcompanyname` LIKE '$search%' OR `PE_companyname` LIKE '$search%')";
		}
		if ($group != 0) {
			$query .= " AND `PE_groupid`='$group'";
		}
		if ($status != -1) {
			$query .= " AND `PE_status`='$status'";
		}
		
		$database->setQuery($query);
		return $database->loadResult();
	}
	static function getPersonArray($search="", $group=0, $status=-1, $limitstart=null, $limit=null) {
		global $database;
		
		$query = "SELECT * FROM `person` WHERE 1";
		
		if ($search != "") {
			$query .= " AND (`PE_firstname` LIKE '$search%' OR `PE_surname` LIKE '$search%' OR `PE_nick` LIKE '$search%' OR `PE_username` LIKE '$search%' OR `PE_email` LIKE '$search%' OR `PE_tel` LIKE '$search%' OR `PE_icq` LIKE '$search%' OR `PE_jabber` LIKE '$search%' OR `PE_address` LIKE '$search%' OR `PE_city` LIKE '$search%' OR `PE_zip` LIKE '$search%' OR `PE_ic` LIKE '$search%' OR `PE_dic` LIKE '$search%' OR `PE_shortcompanyname` LIKE '$search%' OR `PE_companyname` LIKE '$search%')";
		}
		if ($group != 0) {
			$query .= " AND `PE_groupid`='$group'";
		}
		if ($status != -1) {
			$query .= " AND `PE_status`='$status'";
		}
		
		$query .= " ORDER BY `PE_surname`, `PE_firstname`";
		
		if ($limitstart !== null && $limit !== null) {
			$query .= " LIMIT $limitstart, $limit";
		}
		
		$database->setQuery($query);
		return $database->loadObjectList('PE_personid');
	}
	static function getPersonArrayByGroupID($id) {
		if ($id == null) throw new Exception("no ID specified");
		global $database;
		$query = "SELECT * FROM `person` WHERE `PE_groupid`='$id'";
		$database->setQuery($query);
		return $database->loadObjectList('PE_personid');
	}
	static function getPersonByID($id) {
		if ($id == null) throw new Exception("no ID specified");
		global $database;
		$person = new Person();
		$query = "SELECT * FROM `person` WHERE `PE_personid`='$id' LIMIT 1";
		$database->setQuery($query);
		$database->loadObject($person);
		return $person;
	}
	static function getPersonByPersonAccountID($id) {
		if ($id == null) throw new Exception("no ID specified");
		global $database;
		$person = new Person();
		$query = "SELECT * FROM `person` WHERE `PE_personaccountid`='$id' LIMIT 1";
		$database->setQuery($query);
		$database->loadObject($person);
		return $person;
	}
	static function getPersonByIP($ip) {
		if ($ip == null) throw new Exception("no IP specified");
		global $database;
		$person = new Person();
		$query = "SELECT * FROM `person`, `ip` WHERE `PE_personid`=`IP_personid` AND `IP_ip`='$ip' LIMIT 1";
		$database->setQuery($query);
		$database->loadObject($person);
		return $person;
	}
	static function removePersonByID($id) {
		if ($id == null) throw new Exception("no ID specified");
		global $database;
		$query = "DELETE FROM `person` WHERE `PE_personid`='$id' LIMIT 1";
		$database->setQuery($query);
		$database->query();
	}
	static function getPersonWithAccountArray() {
		global $database;
		$query = "SELECT * FROM `person` as pe, `personaccount` as pa WHERE pe.PE_personaccountid=pa.PA_personaccountid ORDER BY pe.PE_surname, pe.PE_firstname";
		$database->setQuery($query);
		return $database->loadObjectList('PE_personid');
	}
	static function getPersonWithGroupByUsername($username) {
		if ($username == null) throw new Exception("username ID specified");
		global $database;
		$person = null;
		$query = "SELECT * FROM `person`,`group` WHERE `PE_username`='$username' AND `PE_groupid`=`GR_groupid` LIMIT 1";
		$database->setQuery($query);
		$database->loadObject($person);
		return $person;
	}
	static function getPersonArrayForQOS() {
		global $database;
		
		$query = "SELECT * FROM `person` WHERE `PE_status`='".Person::STATUS_ACTIVE."' ORDER BY `PE_surname`, `PE_firstname`";
		
		$database->setQuery($query);
		return $database->loadObjectList('PE_personid');
	}
	static function getSuperAdministratorsPersonArray() {
		global $database;
		
		$query = "SELECT * FROM `person` as pe, `group` as gr WHERE pe.PE_status='".Person::STATUS_ACTIVE."' AND pe.PE_groupid = gr.GR_groupid AND gr.GR_level='".Group::SUPER_ADMININSTRATOR."' ORDER BY pe.PE_surname, pe.PE_firstname";
		
		$database->setQuery($query);
		return $database->loadObjectList('PE_personid');
	}
} // End of PersonDAO class
?>