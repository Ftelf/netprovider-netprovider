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

/** ensure this file is being included by a parent file */
defined('VALID_MODULE') or die(_("Direct access into this section is not allowed"));

global $core;
require_once($core->getAppRoot() . "includes/dao/SessionDAO.php");
require_once($core->getAppRoot() . "includes/dao/PersonDAO.php");
require_once($core->getAppRoot() . "includes/dao/PersonAccountDAO.php");
require_once($core->getAppRoot() . "includes/dao/PersonAccountEntryDAO.php");
require_once($core->getAppRoot() . "includes/dao/GroupDAO.php");
require_once($core->getAppRoot() . "includes/dao/InvoiceDAO.php");
require_once($core->getAppRoot() . "includes/dao/ChargeDAO.php");
require_once($core->getAppRoot() . "includes/dao/ChargeDAO.php");
require_once($core->getAppRoot() . "includes/dao/HasChargeDAO.php");
require_once($core->getAppRoot() . "includes/dao/RoleDAO.php");
require_once($core->getAppRoot() . "includes/dao/RolememberDAO.php");
require_once($core->getAppRoot() . "includes/dao/IpDAO.php");
require_once($core->getAppRoot() . "includes/dao/LogDAO.php");
require_once($core->getAppRoot() . "includes/dao/MessageDAO.php");
require_once($core->getAppRoot() . "includes/dao/NetworkDAO.php");
require_once($core->getAppRoot() . "includes/dao/BankAccountEntryDAO.php");
require_once($core->getAppRoot() . "includes/dao/InternetDAO.php");
require_once($core->getAppRoot() . "includes/dao/ChargeEntryDAO.php");
require_once($core->getAppRoot() . "includes/dao/IpAccountDAO.php");
require_once($core->getAppRoot() . "includes/dao/IpAccountAbsDAO.php");
require_once('myprofile.html.php');

$task = Utils::getParam($_REQUEST, 'task', null);

switch ($task) {
	case 'edit':
		editPerson();
		break;

		
	case 'save':
 		savePerson($task);
		break;

	case 'cancel':
		showPerson();
		break;
	
	default:
		showPerson();
		break;
}

function showPerson() {
	global $database, $mainframe, $acl, $core;
	
	$pid = $_SESSION['SE_personid'];
	
	$person = PersonDAO::getPersonByID($pid);
	$personAccount = PersonAccountDAO::getPersonAccountByID($person->PE_personaccountid);
	
	$bankAccountEntries = BankAccountEntryDAO::getBankAccountEntryArray();
	$personAccountEntries = PersonAccountEntryDAO::getPersonAccountEntryArrayByPersonAccountID($personAccount->PA_personaccountid);
	
	$charges = ChargeDAO::getChargeArray();
	$internets = InternetDAO::getInternetArray();
	$hasCharges = HasChargeDAO::getHasChargeWithChargeWithPersonArrayByPersonID($person->PE_personid);
	
	foreach ($hasCharges as &$hasCharge) {
		$hasCharge->_chargeEntries = ChargeEntryDAO::getChargeEntryArrayByHasChargeID($hasCharge->HC_haschargeid);
		
		foreach ($hasCharge->_chargeEntries as &$chargeEntry) {
			try {
				$chargeEntry->_invoice = InvoiceDAO::getInvoiceByChargeEntryID($chargeEntry->CE_chargeentryid);
			} catch (Exception $e) {
				$chargeEntry->_invoice = null;
			}
		}
	}
	
	$group = GroupDAO::getGroupByID($person->PE_groupid);
	
	$roles = RolememberDAO::getRolememberAndRoleArrayByPersonID($pid);
	$ips = IpDAO::getIpArrayByPersonID($person->PE_personid);
	$networks = NetworkDAO::getNetworkArrayByPersonID($pid);
	$messages = MessageDAO::getMessageArray($pid, null);
	
	$traffic = array();
	
	$dateMonthTemp = Utils::getParam($_SESSION['UI_SETTINGS']['com_myprofile']['filter'], 'date_month', null);
	$dateDayTemp = Utils::getParam($_SESSION['UI_SETTINGS']['com_myprofile']['filter'], 'date_day', null);
	
	$dateMonth = new DateUtil();
	$traffic['TRAFFIC_MONTH'] = array();
	
	try {
		$dateMonth->parseDate($dateMonthTemp, DateUtil::FORMAT_MONTHLY);
	} catch (Exception $e) {
		$dateMonth = new DateUtil();
		$dateMonth->set(DateUtil::SECONDS, 0);
		$dateMonth->set(DateUtil::MINUTES, 0);
		$dateMonth->set(DateUtil::HOUR, 0);
		$dateMonth->set(DateUtil::DAY, 1);
	}
	
	$traffic['DATE_MONTH'] = $dateMonth;
	
	$dateDay = new DateUtil();
	try {
		$dateDay->parseDate($dateDayTemp, DateUtil::FORMAT_DATE);
	} catch (Exception $e) {
		$dateDay = new DateUtil();
		$dateDay->set(DateUtil::SECONDS, 0);
		$dateDay->set(DateUtil::MINUTES, 0);
		$dateDay->set(DateUtil::HOUR, 0);
	}
	
	$IA_bytes_inSum = 0;
	$IA_bytes_outSum = 0;
	$IA_packets_inSum = 0;
	$IA_packets_outSum = 0;
	
	foreach ($ips as &$ip) {
		$sum = IpAccountDAO::getIpAccountMonthSumByIpID($ip->IP_ipid, $dateMonth->get(DateUtil::YEAR), $dateMonth->get(DateUtil::MONTH));
		
		$traffic['TRAFFIC_MONTH'][] = array(
			'IP' => $ip->IP_address,
			'DATA_IN' => $sum->IA_bytes_in,
			'DATA_OUT' => $sum->IA_bytes_out,
			'PACKET_IN' => $sum->IA_packets_in,
			'PACKET_OUT' => $sum->IA_packets_out
		);
		
		$IA_bytes_inSum += $sum->IA_bytes_in;
		$IA_bytes_outSum += $sum->IA_bytes_out;
		$IA_packets_inSum += $sum->IA_packets_in;
		$IA_packets_outSum += $sum->IA_packets_out;
	}
	
	$traffic['SUMMARY'] = array(
		'DATA_IN' => $IA_bytes_inSum,
		'DATA_OUT' => $IA_bytes_outSum,
		'PACKET_IN' => $IA_packets_inSum,
		'PACKET_OUT' => $IA_packets_outSum
	);
	
	HTML_myprofile::showMyProfile($person, $personAccount, $bankAccountEntries, $personAccountEntries, $charges, $internets, $hasCharges, $group, $roles, $ips, $networks, $messages, $traffic);
}

function editPerson() {
	global $database, $my, $acl;
	
	$pid = $_SESSION['SE_personid'];
	
	$person = PersonDAO::getPersonByID($pid);
	
	if ($person->PE_password != null) {
		$person->PE_password = "******";
	}
	
	HTML_myprofile::editMyProfile($person);
}

function savePerson() {
	global $core, $database, $mainframe, $my, $acl, $appContext;

	$pid = $_SESSION['SE_personid'];
	
	$storedPerson = PersonDAO::getPersonByID($pid);

	$person = new Person();
	database::bind($_POST, $person);
	
	$person->PE_username = null;
	
	$PE_password1 = trim(Utils::getParam($_POST, 'PE_password1', ""));
	$PE_password2 = trim(Utils::getParam($_POST, 'PE_password2', ""));
	
	$showAgain = false;
	
	if (($PE_password1 == "******" && $PE_password2 == "******") || ($PE_password1 == "" && $PE_password2 == "")) {
		$person->PE_password = null;
	} else if ($PE_password1 == $PE_password2) {
		$person->PE_password = md5($PE_password1);
	} else {
		$core->alert(_("User passwords are not same"));
		
		if ($storedPerson->PE_password != null) {
			$person->PE_password = "******";
		} else {
			$person->PE_password = null;
		}
		
		HTML_myprofile::editMyProfile($person);
		return;
	}
	
	try {
		$birthdate = new DateUtil();
		$birthdate->parseDate($person->PE_birthdate, DateUtil::FORMAT_DATE);
		$person->PE_birthdate = $birthdate->getFormattedDate(DateUtil::DB_DATE);
	} catch (Exception $e) {
		$person->PE_birthdate = DateUtil::DB_NULL_DATE;
	}
	
	$database->updateObject("person", $person, "PE_personid", false, false);
	
	$msg = sprintf(_("User '%s' actualized his profile"), $person->PE_firstname." ".$person->PE_surname);//$msg = "Uživatel: '$person->PE_firstname $person->PE_surname' aktualizoval svůj profil";
	$appContext->insertMessage($msg);
	$database->log($msg, LOG::LEVEL_INFO);
	
	Core::redirect("index2.php?option=com_myprofile");
}
?>