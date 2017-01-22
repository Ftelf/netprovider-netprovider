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
require_once($core->getAppRoot() . "includes/dao/InternetDAO.php");
require_once("internet.html.php");

$task = Utils::getParam($_REQUEST, 'task', null);
$iid = Utils::getParam($_REQUEST, 'IN_internetid', null);
$cid = Utils::getParam($_REQUEST, 'cid', array(0));
if (!is_array($cid)) {
	$cid = array (0);
}

switch ($task) {
	case 'new':
		editInternet(null);
		break;

	case 'edit':
		editInternet($iid);
		break;

	case 'editA':
		editInternet(intval($cid[0]));
		break;
		
	case 'save':
	case 'apply':
 		saveInternet($task);
		break;

	case 'remove':
		removeInternet($cid);
		break;

	case 'cancel':
		showInternet();
		break;

	default:
		showInternet();
		break;
}
/**
 * 
 */
function showInternet() {
	global $database, $mainframe, $acl, $core;
	require_once($core->getAppRoot() . 'modules/com_common/PageNav.php');
	
	$limit = Utils::getParam($_SESSION['UI_SETTINGS']['com_internet'], 'limit', 10);
	$limitstart = Utils::getParam($_SESSION['UI_SETTINGS']['com_internet'], 'limitstart', 0);
	
	$total = InternetDAO::getInternetCount();
	$internets = InternetDAO::getInternetArray($limitstart, $limit);
	
	$pageNav = new PageNav($total, $limitstart, $limit);
	HTML_internet::showInternet($internets, $pageNav);
}
/**
 * @param $iid
 */
function editInternet($iid=null) {
	global $database, $my, $acl;
	
	if ($iid != null) {
		$internet = InternetDAO::getInternetByID($iid);
	} else {
		$internet = new Internet();
	}
	
	HTML_internet::editInternet($internet);
}
/**
 * @param $task
 */
function saveInternet($task) {
	global $database, $mainframe, $my, $acl, $appContext;

	$internet = new Internet();
	database::bind($_POST, $internet);
	$isNew 	= !$internet->IN_internetid;
	
	// get proper values
	//
	$errorArray = '';
	if (Utils::getParam($_REQUEST, 'IN_dnl_rate_cb', null) == "1") $internet->IN_dnl_rate = -1;
	if (Utils::getParam($_REQUEST, 'IN_upl_rate_cb', null) == "1") $internet->IN_upl_rate = -1;
	if (!is_numeric($internet->IN_dnl_rate)) $errorArray .= _("Guarant download is not in proper number format").'\n';
	if (!is_numeric($internet->IN_dnl_ceil)) $errorArray .= _("Maximum download is not in proper number format").'\n';
	if (!is_numeric($internet->IN_upl_rate)) $errorArray .= _("Guarant upload is not in proper number format").'\n';
	if (!is_numeric($internet->IN_upl_ceil)) $errorArray .= _("Maximum upload is not in proper number format").'\n';
	if (strlen($errorArray)) {
		Core::alert($errorArray);
		HTML_internet::editInternet($internet);
		return;
	}
	
	if ($isNew) {
		$database->insertObject("internet", $internet, "IN_internetid", false);
	} else {
		$database->updateObject("internet", $internet, "IN_internetid", false, false);
	}
	
	switch ($task) {
		case 'apply':
			$msg = sprintf(_("Internet template '%s' updated"), $internet->IN_name);
			$appContext->insertMessage($msg);
			$database->log($msg, LOG::LEVEL_INFO);
			Core::redirect("index2.php?option=com_internet&task=edit&IN_internetid=$internet->IN_internetid&hidemainmenu=1");
		case 'save':
			$msg = sprintf(_("Internet template '%s' saved"), $internet->IN_name);
			$appContext->insertMessage($msg);
			$database->log($msg, LOG::LEVEL_INFO);
		default:
			Core::redirect("index2.php?option=com_internet");
	}
}
/**
 * @param $cid
 */
function removeInternet($cid) {
	global $database, $mainframe, $my, $acl, $appContext;
	if (count($cid) < 1) {
		Core::backWithAlert(_("Please select record to erase"));
	}

	if (count($cid)) {
		foreach ($cid as $id) {
			$internet = InternetDAO::getInternetByID($id);
			$internetCharges = InternetDAO::getInternetChargesArrayByID($id);
			
			if (count($internetCharges)) {
				$msg = sprintf(ngettext("Cannot delete internet template '%s', because it has binded %s payment service", "Cannot delete internet template '%s', because it has binded %s payment services", count($internetCharges)), $internet->IN_name, count($internetCharges));
				$database->log($msg, LOG::LEVEL_WARNING);
				$limit = 10;
				foreach ($internetCharges as $internetCharge) {
					$msg .= "\\n'" . $internetCharge->CH_name . "'";
					if (!--$limit) break;
				}
				if (count($internetCharges) > $limit) $msg .= '\n...';
				Core::backWithAlert($msg);
			} else {
				InternetDAO::removeInternetByID($id);
				$msg = sprintf(_("Internet template '%s' deleted"), $internet->IN_name);
				$appContext->insertMessage($msg);
				$database->log($msg, LOG::LEVEL_INFO);
			}
		}
		Core::redirect("index2.php?option=com_internet");
	}
}
?>