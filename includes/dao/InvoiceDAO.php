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
require_once($core->getAppRoot() . "/includes/tables/Invoice.php");
require_once($core->getAppRoot() . "/includes/dao/PersonDAO.php");
require_once($core->getAppRoot() . "/includes/dao/GroupDAO.php");
require_once($core->getAppRoot() . "/includes/dao/ChargeEntryDAO.php");

/**
 *  InvoiceDAO
 */
class InvoiceDAO {
	static function getInvoiceCount() {
		global $database;
		$query = "SELECT count(*) FROM `invoice`";
		$database->setQuery($query);
		return $database->loadResult();
	}
	static function getInvoiceArray($limitstart=null, $limit=null) {
		global $database;
		$query = "SELECT * FROM `invoice`";
		if ($limitstart !== null && $limit !== null) {
			$query .= " LIMIT $limitstart,$limit";
		}
		$database->setQuery($query);
		return $database->loadObjectList("IN_invoiceid");
	}
	static function getInvoiceDetailCount($search="", $group=0, $personStatus=-1, $chargeEntryStatus=-1, $dateFrom=null, $dateTo=null) {
		global $database;
		
		$query = "SELECT count(*) FROM `person`, `group`, `invoice`, `chargeentry` WHERE PE_personid=IN_personid AND PE_groupid=GR_groupid AND IN_chargeentryid=CE_chargeentryid";
		
		if ($search != "") {
			$query .= " AND (`PE_firstname` LIKE '$search%' OR `PE_surname` LIKE '$search%' OR `PE_nick` LIKE '$search%' OR `PE_username` LIKE '$search%' OR `PE_email` LIKE '$search%' OR `PE_tel` LIKE '$search%' OR `PE_icq` LIKE '$search%' OR `PE_jabber` LIKE '$search%' OR `PE_address` LIKE '$search%' OR `PE_city` LIKE '$search%' OR `PE_zip` LIKE '$search%' OR `PE_ic` LIKE '$search%' OR `PE_dic` LIKE '$search%' OR `PE_shortcompanyname` LIKE '$search%' OR `PE_companyname` LIKE '$search%')";
		}
		if ($group != 0) {
			$query .= " AND `PE_groupid`='$group'";
		}
		if ($personStatus != -1) {
			$query .= " AND `PE_status`='$personStatus'";
		}
		if ($chargeEntryStatus != -1) {
			$query .= " AND `PE_status`='$chargeEntryStatus'";
		}
		if ($dateFrom != DateUtil::DB_NULL_DATE) {
			$query .= " AND `IN_invoicedate`>='$dateFrom'";
		}
		if ($dateTo != DateUtil::DB_NULL_DATE) {
			$query .= " AND `IN_invoicedate`<='$dateTo'";
		}
		
		$database->setQuery($query);
		return $database->loadResult();
	}
	static function getInvoiceDetailArray($search="", $group=0, $personStatus=-1, $chargeEntryStatus=-1, $dateFrom=null, $dateTo=null, $limitstart=null, $limit=null) {
		global $database;
		
		$query = "SELECT * FROM `person`, `group`, `invoice`, `chargeentry` WHERE PE_personid=IN_personid AND PE_groupid=GR_groupid AND IN_chargeentryid=CE_chargeentryid";
		
		if ($search != "") {
			$query .= " AND (`PE_firstname` LIKE '$search%' OR `PE_surname` LIKE '$search%' OR `PE_nick` LIKE '$search%' OR `PE_username` LIKE '$search%' OR `PE_email` LIKE '$search%' OR `PE_tel` LIKE '$search%' OR `PE_icq` LIKE '$search%' OR `PE_jabber` LIKE '$search%' OR `PE_address` LIKE '$search%' OR `PE_city` LIKE '$search%' OR `PE_zip` LIKE '$search%' OR `PE_ic` LIKE '$search%' OR `PE_dic` LIKE '$search%' OR `PE_shortcompanyname` LIKE '$search%' OR `PE_companyname` LIKE '$search%')";
		}
		if ($group != 0) {
			$query .= " AND `PE_groupid`='$group'";
		}
		if ($personStatus != -1) {
			$query .= " AND `PE_status`='$personStatus'";
		}
		if ($chargeEntryStatus != -1) {
			$query .= " AND `CE_status`='$chargeEntryStatus'";
		}
		if ($dateFrom != DateUtil::DB_NULL_DATE) {
			$query .= " AND `IN_invoicedate`>='$dateFrom'";
		}
		if ($dateTo != DateUtil::DB_NULL_DATE) {
			$query .= " AND `IN_invoicedate`<='$dateTo'";
		}
		
		$query .= " ORDER BY `IN_invoicedate`, `PE_surname`, `PE_firstname`";
		
		if ($limitstart !== null && $limit !== null) {
			$query .= " LIMIT $limitstart, $limit";
		}
		
		$database->setQuery($query);
		return $database->loadObjectList('IN_invoiceid');
	}
	static function getInvoiceByID($id) {
		if (!$id) throw new Exception("no ID specified");
		global $database;
		$invoice = new Invoice();
		$query = "SELECT * FROM `invoice` WHERE `IN_invoiceid`='$id' LIMIT 1";
		$database->setQuery($query);
		$database->loadObject($invoice);
		return $invoice;
	}
	static function getInvoiceByChargeEntryID($id) {
		if (!$id) throw new Exception("no ID specified");
		global $database;
		$invoice = new Invoice();
		$query = "SELECT * FROM `invoice` WHERE `IN_chargeentryid`='$id' LIMIT 1";
		$database->setQuery($query);
		$database->loadObject($invoice);
		return $invoice;
	}
	static function removeInvoiceByID($id) {
		if (!$id) throw new Exception("no ID specified");
		global $database;
		$query = "DELETE FROM `invoice` WHERE `IN_invoiceid`='$id' LIMIT 1";
		$database->setQuery($query);
		$database->query();
	}
	static function removeInvoiceByChargeEntryID($id) {
		if (!$id) throw new Exception("no ID specified");
		global $database;
		$query = "DELETE FROM `invoice` WHERE `IN_chargeentryid`='$id' LIMIT 1";
		$database->setQuery($query);
		$database->query();
	}
} // End of InvoiceDAO class
?>