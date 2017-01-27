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
require_once($core->getAppRoot() . "includes/dao/RoleDAO.php");
require_once($core->getAppRoot() . "includes/dao/RolememberDAO.php");
require_once("role.html.php");

$task = Utils::getParam($_REQUEST, 'task', null);
$rid = Utils::getParam($_REQUEST, 'RO_roleid', null);
$cid = Utils::getParam($_REQUEST, 'cid', array(0));
if (!is_array($cid)) {
	$cid = array (0);
}

switch ($task) {
	case 'new':
		editRole(null);
		break;

	case 'edit':
		editRole($rid);
		break;

	case 'editA':
		editRole(intval($cid[0]));
		break;
		
	case 'save':
	case 'apply':
 		saveRole($task);
		break;

	case 'remove':
		removeRole($cid);
		break;

	case 'cancel':
		showRole();
		break;

	default:
		showRole();
		break;
}
/**
 * 
 */
function showRole() {
	global $database, $mainframe, $acl, $core;
	require_once($core->getAppRoot() . 'modules/com_common/PageNav.php');
	
	$limit = Utils::getParam($_SESSION['UI_SETTINGS']['com_role'], 'limit', 10);
	$limitstart = Utils::getParam($_SESSION['UI_SETTINGS']['com_role'], 'limitstart', 0);
	
	$total = RoleDAO::getRoleCount();
	$roles = RoleDAO::getRoleArray($limitstart, $limit);
	
	$pageNav = new PageNav($total, $limitstart, $limit);
	HTML_role::showRoles($roles, $pageNav);
}
/**
 * @param $rid
 */
function editRole($rid=null) {
	global $database, $my, $acl;
	
	if ($rid != null) {
		$role = RoleDAO::getRoleByID($rid);
	} else {
		$role = new Role();
	}
		
	HTML_role::editRole($role);
}
/**
 * @param $task
 */
function saveRole($task) {
	global $database, $mainframe, $my, $acl, $appContext;

	$role = new Role();
	database::bind($_POST, $role);
	$isNew 	= !$role->RO_roleid;
	
	if ($isNew) {
		$database->insertObject("role", $role, "RO_roleid", false);
	} else {
		$database->updateObject("role", $role, "RO_roleid", false, false);
	}
	
	switch ($task) {
		case 'apply':
			$msg = sprintf(_("Role '%s' updated"), $role->RO_name);
			$appContext->insertMessage($msg);
			$database->log($msg, Log::LEVEL_INFO);
			Core::redirect("index2.php?option=com_role&task=edit&RO_roleid=$role->RO_roleid&hidemainmenu=1");
		case 'save':
			$msg = sprintf(_("Role '%s' saved"), $role->RO_name);
			$appContext->insertMessage($msg);
			$database->log($msg, Log::LEVEL_INFO);
		default:
			Core::redirect("index2.php?option=com_role");
	}
}
/**
 * @param $cid
 */
function removeRole($cid) {
	global $database, $mainframe, $my, $acl, $appContext;
	if (count($cid) < 1) {
		Core::backWithAlert(_("Please select record to erase"));
	}

	if (count($cid)) {
		foreach ($cid as $id) {
			if (($role = RoleDAO::getRoleByID($id)) == null) {
				Core::redirect("index2.php?option=com_role");
			}
			if (($rolemembers = RolememberDAO::getRolememberAndPersonsArrayByRoleID($id)) == null) $rolemembers = array();

			if (count($rolemembers)) {
				$msg = sprintf(ngettext("Cannot delete user role '%s', because it has binded %s user", "Cannot delete user role '%s', because it has binded %s users", count($rolemembers)), $role->RO_name, count($rolemembers));
				$database->log($msg, Log::LEVEL_WARNING);
				$limit = 10;
				foreach ($rolemembers as $rolemember) {
					$msg .= "\\n'" . $rolemember->PE_firstname . " " . $rolemember->PE_surname . "'";
					if (!--$limit) break;
				}
				if (count($rolemembers) > $limit) $msg .= '\n...';
				Core::backWithAlert($msg);
			} else {
				RoleDAO::removeRoleByID($id);
				$msg = sprintf(_("User role '%s' deleted"), $role->RO_name);
				$appContext->insertMessage($msg);
				$database->log($msg, Log::LEVEL_INFO);
			}
		}
		Core::redirect("index2.php?option=com_role");
	}
}
?>