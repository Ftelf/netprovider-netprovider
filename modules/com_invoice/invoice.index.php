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

if (!$core->getProperty(Core::ENABLE_INVOICE_MODULE))
	die(_("Direct access into this section is not allowed"));

global $core;
require_once("invoice.html.php");
require_once($core->getAppRoot() . "includes/dao/InvoiceDAO.php");

$task = Utils::getParam($_REQUEST, 'task', null);
//$iid = Utils::getParam($_REQUEST, 'IN_invoiceid', null);
$cid = Utils::getParam($_REQUEST, 'cid', array(0));
if (!is_array($cid)) {
	$cid = array (0);
}

switch ($task) {
	default:
		showInvoice();
		break;
}
/**
 * 
 */
function showInvoice() {
	global $database, $mainframe, $acl, $core;
	
	require_once($core->getAppRoot() . 'modules/com_common/PageNav.php');
	
	$filter = array();
	// default settings if no setting in session
	//
	$filter['search'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_invoice']['filter'], 'search', "");
	$filter['group'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_invoice']['filter'], 'group', 0);
	$filter['person_status'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_invoice']['filter'], 'person_status', -1);
	$filter['chargeentry_status'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_invoice']['filter'], 'chargeentry_status', -1);
	$filter['date_from'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_invoice']['filter'], 'date_from', null);
	$filter['date_to'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_invoice']['filter'], 'date_to', null);
	
	$dateFrom = new DateUtil();
	$dateTo = new DateUtil();
	try {
		$dateFrom->parseDate($filter['date_from'], DateUtil::FORMAT_DATE);
	} catch (Exception $e) {}
	try {
		$dateTo->parseDate($filter['date_to'], DateUtil::FORMAT_DATE);
	} catch (Exception $e) {}
	
	try {
		if ($dateFrom->after($dateTo)) {
			$dateTemp = $dateTo;
			$dateTo = $dateFrom;
			$dateFrom = $dateTemp;
		}
	} catch (Exception $e) {}
	
	$filter['date_from'] = $dateFrom->getFormattedDate(DateUtil::FORMAT_DATE);
	$filter['date_to'] = $dateTo->getFormattedDate(DateUtil::FORMAT_DATE);
	
	$limit = Utils::getParam($_SESSION['UI_SETTINGS']['com_invoice'], 'limit', 10);
	$limitstart = Utils::getParam($_SESSION['UI_SETTINGS']['com_invoice'], 'limitstart', 0);
	
	$total = InvoiceDAO::getInvoiceDetailCount($filter['search'], $filter['group'], $filter['person_status'], $filter['chargeentry_status'], $dateFrom->getFormattedDate(DateUtil::DB_DATE), $dateTo->getFormattedDate(DateUtil::DB_DATE));
	$invoices = InvoiceDAO::getInvoiceDetailArray($filter['search'], $filter['group'], $filter['person_status'], $filter['chargeentry_status'], $dateFrom->getFormattedDate(DateUtil::DB_DATE), $dateTo->getFormattedDate(DateUtil::DB_DATE), $limitstart, $limit);
	
	$groups = GroupDAO::getGroupArray();
	
	$pageNav = new PageNav($total, $limitstart, $limit);
	HTML_invoice::showInvoices($invoices, $groups, $pageNav, $filter);
}
?>