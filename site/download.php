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

require_once(dirname(__FILE__) . "/../includes/Core.php");
$core = new Core();
require_once($core->getAppRoot() . "includes/Constants.php");
require_once($core->getAppRoot() . "includes/AppContext.php");
require_once($core->getAppRoot() . "includes/Database.php");
require_once($core->getAppRoot() . "includes/utils/Utils.php");
require_once($core->getAppRoot() . "includes/Mainframe.php");
require_once($core->getAppRoot() . "includes/utils/DateUtil.php");
require_once($core->getAppRoot() . "includes/dao/EmailListDAO.php");
require_once($core->getAppRoot() . "includes/dao/InvoiceDAO.php");
require_once($core->getAppRoot() . "includes/dao/SessionDAO.php");
require_once($core->getAppRoot() . "includes/invoice/InvoiceFactory.php");
require_once($core->getAppRoot() . "includes/tcpdf/tcpdf.php");

try {
	$database = new Database(
		$core->getProperty(Core::DATABASE_HOST),
		$core->getProperty(Core::DATABASE_USERNAME),
		$core->getProperty(Core::DATABASE_PASSWORD),
		$core->getProperty(Core::DATABASE_NAME)
	);
} catch (Exception $e) {
	$core->alert('_("Cannot connect to database")');
	exit();
}

$option = strtolower(Utils::getParam($_REQUEST, 'option', 'com_admin'));
$task = Utils::getParam($_REQUEST, 'task', null);

// must start the session before we create the mainframe object
session_name("NETPROVIDER");
session_start();

// mainframe is an API workhorse
$mainframe = new MainFrame($database, $option, '..', null);

$session = new Session();
$session->SE_sessionid = Utils::getParam($_SESSION, 'SE_sessionid', '');
$session->SE_personid = Utils::getParam($_SESSION, 'SE_personid', '');
$session->SE_username = Utils::getParam($_SESSION, 'SE_username', '');
$session->SE_acl = Utils::getParam($_SESSION, 'SE_acl', '');
$session->SE_time = Utils::getParam($_SESSION, 'SE_time', '');

// timeout old sessions
//
$sessions = SessionDAO::removeTimeoutedSession(1800);

// check against db record of session
if ($session->SE_sessionid != md5("$session->SE_username$session->SE_acl$session->SE_time") || !SessionDAO::checkSession($session)) {
	Core::redirect("index.php");
}

// update session timestamp
SessionDAO::updateSessionTimeout($session->SE_sessionid);

$my = $_SESSION['USER'];

if ($option == 'com_bankaccount') {
	if ($task == 'download') {
		$lid = Utils::getParam($_REQUEST, 'EL_emaillistid', null);
		
		try {
			$emailList = EmailListDAO::getEmailListByID($lid);
		} catch (Exception $e) {
			exit();
		}
		header("Content-Description: File Transfer");
		header("Content-Type: application/force-download");
		header("Content-Disposition: attachment; filename=\"$emailList->EL_name\"");
		echo $emailList->EL_list;
	}
} else if ($option == 'com_myprofile') {
	if ($task == 'invoice') {
		$iid = Utils::getParam($_REQUEST, 'IN_invoiceid', null);
		
		$invoice = InvoiceDAO::getInvoiceByID($iid);
		$person = PersonDAO::getPersonByID($invoice->IN_personid);
		
		if ($my->GR_level == Group::SUPER_ADMININSTRATOR || $my->PE_personid == $person->PE_personid) {
			$invoice = new InvoiceFactory($iid);
		
			$invoice->output();
		}
	}
	
} else if ($option == 'com_personaccount') {
	if ($task == 'invoice') {
		$cid = Utils::getParam($_REQUEST, 'CE_chargeentryid', null);
		
		$invoice = InvoiceDAO::getInvoiceByChargeEntryID($cid);
		$person = PersonDAO::getPersonByID($invoice->IN_personid);
		
		if ($my->GR_level == Group::SUPER_ADMININSTRATOR || $my->PE_personid == $person->PE_personid) {
			$invoice = new InvoiceFactory($invoice->IN_invoiceid);
		
			$invoice->output();
		}
	}
} else if ($option == 'com_invoice') {
	if ($task == 'download') {
		$iid = Utils::getParam($_REQUEST, 'IN_invoiceid', null);
		
		$invoice = InvoiceDAO::getInvoiceByID($iid);
		$person = PersonDAO::getPersonByID($invoice->IN_personid);
		
		if ($my->GR_level == Group::SUPER_ADMININSTRATOR || $my->PE_personid == $person->PE_personid) {
			$invoice = new InvoiceFactory($iid);
		
			$invoice->output();
		}
	} else if ($task == 'createList') {
		if ($my->GR_level == Group::SUPER_ADMININSTRATOR) {
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
			
			$invoices = InvoiceDAO::getInvoiceDetailArray($filter['search'], $filter['group'], $filter['person_status'], $filter['chargeentry_status'], $dateFrom->getFormattedDate(DateUtil::DB_DATE), $dateTo->getFormattedDate(DateUtil::DB_DATE), null, null);
			
			$groups = GroupDAO::getGroupArray();
			
			
			header('Expires: 0');
			header('Cache-control: private');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Content-Description: File Transfer');
			header('Content-Type: application/vnd.ms-excel');
			header('Content-disposition: attachment; filename="invoice_list.csv"');
			
			echo '"PE_groupid","PE_status","PE_firstname","PE_surname","PE_degree_prefix","PE_degree_suffix","PE_email","PE_tel","PE_icq","PE_jabber","PE_address","PE_city","PE_zip","PE_ic","PE_dic","PE_companyname","IN_invoicenumber","IN_constantsymbol","IN_variablesymbol","IN_specificsymbol","IN_invoicedate","IN_baseamount","IN_amount","IN_currency","CE_period_date","CE_realize_date","CE_overdue","CE_status"';
			
			foreach($invoices as $invoice) {
				$invoiceDate = new DateUtil($invoice->IN_invoicedate);
				$periodDate = new DateUtil($invoice->CE_period_date);
				$realizeDate = new DateUtil($invoice->CE_realize_date);
				
				echo "\r\n";
				echo '"' . $groups[$invoice->PE_groupid]->GR_name . '"';
				echo ',"' . Person::getLocalizedStatus($invoice->PE_status) . '"';
				echo ',"' . $invoice->PE_firstname . '"';
				echo ',"' . $invoice->PE_surname . '"';
				echo ',"' . $invoice->PE_degree_prefix . '"';
				
				echo ',"' . $invoice->PE_degree_suffix . '"';
				echo ',"' . $invoice->PE_email . '"';
				echo ',"' . $invoice->PE_tel . '"';
				echo ',"' . $invoice->PE_icq . '"';
				echo ',"' . $invoice->PE_jabber . '"';
				
				echo ',"' . $invoice->PE_address . '"';
				echo ',"' . $invoice->PE_city . '"';
				echo ',"' . $invoice->PE_zip . '"';
				echo ',"' . $invoice->PE_ic . '"';
				echo ',"' . $invoice->PE_dic . '"';
				
				echo ',"' . $invoice->PE_companyname . '"';
				echo ',"' . $invoice->IN_invoicenumber . '"';
				echo ',"' . $invoice->IN_constantsymbol . '"';
				echo ',"' . $invoice->IN_variablesymbol . '"';
				echo ',"' . $invoice->IN_specificsymbol . '"';
				
				echo ',"' . $invoiceDate->getFormattedDate(DateUtil::FORMAT_DATE) . '"';
				echo ',"' . $invoice->IN_baseamount . '"';
				echo ',"' . $invoice->IN_amount . '"';
				echo ',"' . $invoice->IN_currency . '"';
				
				echo ',"' . $periodDate->getFormattedDate(DateUtil::FORMAT_DATE) . '"';
				echo ',"' . $realizeDate->getFormattedDate(DateUtil::FORMAT_DATE) . '"';
				echo ',"' . $invoice->CE_overdue . '"';
				echo ',"' . ChargeEntry::getLocalizedStatus($invoice->CE_status) . '"';
			}
		}
	}
}
?>