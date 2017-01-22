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
require_once($core->getAppRoot() . "/includes/tables/Charge.php");
require_once("BankAccountDAO.php");

/**
 *  ChargeDAO
 */
class ChargeDAO {
	static function getChargeCount() {
		global $database;
		$query = "SELECT count(*) FROM `charge`";
		$database->setQuery($query);
		return $database->loadResult();
	}
	static function getChargeArray($limitstart=null, $limit=null) {
		global $database;
		$query = "SELECT * FROM `charge`";
		if ($limitstart !== null && $limit !== null) {
			$query .= " LIMIT $limitstart,$limit";
		}
		$database->setQuery($query);
		return $database->loadObjectList("CH_chargeid");
	}
	static function getChargeArrayByPeriod($period) {
		global $database;
		$query = "SELECT * FROM `charge` WHERE `CH_period`=$period";
		$database->setQuery($query);
		return $database->loadObjectList("CH_chargeid");
	}
	static function getUsedChargeArray($id) {
		if (!$id) throw new Exception("no ID specified");
		global $database;
		$query = "SELECT * FROM `person` as p,`hascharge` as hc,`charge` as ch WHERE ch.CH_chargeid=$id AND ch.CH_chargeid=hc.HC_chargeid AND p.PE_personid=hc.HC_personid";
		$database->setQuery($query);
		return $database->loadObjectList("PE_personid");
	}
	static function getChargeByID($id) {
		if (!$id) throw new Exception("no ID specified");
		global $database;
		$charge = new Charge();
		$query = "SELECT * FROM `charge` WHERE `CH_chargeid`='$id' LIMIT 1";
		$database->setQuery($query);
		$database->loadObject($charge);
		return $charge;
	}
	static function removeChargeByID($id) {
		if (!$id) throw new Exception("no ID specified");
		global $database;
		$query = "DELETE FROM `charge` WHERE `CH_chargeid`='$id' LIMIT 1";
		$database->setQuery($query);
		$database->query();
	}
} // End of ChargeDAO class
?>