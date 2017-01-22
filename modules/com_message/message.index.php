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
require_once($core->getAppRoot() . "includes/dao/MessageDAO.php");
require_once($core->getAppRoot() . "includes/dao/PersonDAO.php");
require_once($core->getAppRoot() . "includes/utils/DateUtil.php");
require_once($core->getAppRoot() . "includes/net/email/EmailUtil.php");
require_once("message.html.php");

$task = Utils::getParam($_REQUEST, 'task', null);
$mid = Utils::getParam($_REQUEST, 'ME_messageid', null);
$cid = Utils::getParam($_REQUEST, 'cid', array(0));
if (!is_array($cid)) {
	$cid = array (0);
}

switch ($task) {
	case 'remove':
		removeMessage($cid);
		break;
		
	case 'send':
		send();
		break;

	default:
		showMessage();
		break;
}
/**
 * 
 */
function showMessage() {
	global $database, $mainframe, $acl, $core;
	require_once($core->getAppRoot() . 'modules/com_common/PageNav.php');
	
	$limit = Utils::getParam($_SESSION['UI_SETTINGS']['com_message'], 'limit', 10);
	$limitstart = Utils::getParam($_SESSION['UI_SETTINGS']['com_message'], 'limitstart', 0);
	
	$filter['date_from'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_message']['filter'], 'date_from', "");
	$filter['date_to'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_message']['filter'], 'date_to', "");
	$filter['personid'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_message']['filter'], 'personid', "");
	
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
	
	if ($dateTo->getTime() != null) {
		$dateTo->add(DateUtil::DAY, 1);
	}
	
	$total = MessageDAO::getMessageCount($filter['personid'], $dateFrom->getFormattedDate(DateUtil::DB_DATETIME),$dateTo->getFormattedDate(DateUtil::DB_DATETIME));
	$messages = MessageDAO::getMessageArray($filter['personid'], $dateFrom->getFormattedDate(DateUtil::DB_DATETIME), $dateTo->getFormattedDate(DateUtil::DB_DATETIME), $limitstart, $limit);
	
	foreach ($messages as &$message) {
		$attachments = MessageAttachmentDAO::getMessageAttachmentNamesArrayForAttachmentForMessageID($message->ME_messageid);
		$attachmentArray = array();
		foreach ($attachments as &$attachment) {
			$attachmentArray[] = $attachment->MA_name . ' (' . floor($attachment->MA_attachment_length / 1000) . 'KB)<br/>';
		}
		$message->_attachmentText = implode("<br/>", $attachmentArray);
	}
	
	$persons = PersonDAO::getPersonArray();
	
	$pageNav = new PageNav($total, $limitstart, $limit);
	HTML_message::showMessage($messages, $persons, $pageNav, $filter);
}
/**
 * @param array $cid LogID
 */
function removeMessage($cid) {
	global $database, $mainframe, $my, $acl;
	if (count($cid) < 1) {
		Core::backWithAlert(_("Please select record to erase"));
	}
	if (count($cid)) {
		foreach ($cid as $id) {
			MessageDAO::removeMessageByID($id);
			MessageAttachmentDAO::removeAttachmentMessageByMessageID($id);
		}
		Core::redirect("index2.php?option=com_message");
	}
}
/**
 */
function send() {
	global $database, $mainframe, $my, $acl;
	
	$messagesUtil = new EmailUtil();
	$messagesUtil->sendMessages();
	
	Core::redirect("index2.php?option=com_message");
}
?>