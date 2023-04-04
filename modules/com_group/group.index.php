<?php
/**
 * Ftelf ISP billing system
 * This source file is part of Ftelf ISP billing system
 * see LICENSE for licence details.
 * php version 8.1.12
 *
 * @category Helper
 * @package  NetProvider
 * @author   Lukas Dziadkowiec <i.ftelf@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     https://www.ovjih.net
 */

/**
 * ensure this file is being included by a parent file
 */
defined('VALID_MODULE') or die(_("Direct access into this section is not allowed"));

global $core;
require_once $core->getAppRoot() . "includes/dao/PersonDAO.php";
require_once $core->getAppRoot() . "includes/dao/GroupDAO.php";
require_once 'group.html.php';

$task = Utils::getParam($_REQUEST, 'task', null);
$gid = Utils::getParam($_REQUEST, 'GR_groupid', null);
$cid = Utils::getParam($_REQUEST, 'cid', array(0));
if (!is_array($cid)) {
    $cid = array(0);
}

switch ($task) {
case 'new':
    editGroup(null);
    break;

case 'edit':
    editGroup($gid);
    break;

case 'editA':
    editGroup(intval($cid[0]));
    break;

case 'save':
case 'apply':
    saveGroup($task);
    break;

case 'remove':
    removeGroup($cid);
    break;

case 'cancel':
    showGroup();
    break;

default:
    showGroup();
    break;
}
/**
 *
 */
function showGroup()
{
    global $database, $mainframe, $acl, $core;
    require_once $core->getAppRoot() . 'modules/com_common/PageNav.php';

    $limit = Utils::getParam($_SESSION['UI_SETTINGS']['com_group'], 'limit', 10);
    $limitstart = Utils::getParam($_SESSION['UI_SETTINGS']['com_group'], 'limitstart', 0);

    $total = GroupDAO::getGroupCount();
    $groups = GroupDAO::getGroupArray($limitstart, $limit);

    $pageNav = new PageNav($total, $limitstart, $limit);
    HTML_group::showGroups($groups, $pageNav);
}

/**
 * @param $pid
 */
function editGroup($pid = null)
{
    global $database, $my, $acl;

    if ($pid != null) {
        $group = GroupDAO::getGroupByID($pid);
    } else {
        $group = new Group();
    }

    HTML_group::editGroup($group);
}

/**
 * @param $task
 */
function saveGroup($task)
{
    global $database, $mainframe, $my, $acl, $appContext;

    $group = new Group();
    database::bind($_POST, $group);

    $isNew = !$group->GR_groupid;

    //
    //Acl not yet implemented
    //  if (!is_numeric($group->GR_acl)) $group->GR_acl = 0;
    $group->GR_acl = 0;

    if ($isNew) {
        $database->insertObject("group", $group, "GR_groupid", false);
    } else {
        $database->updateObject("group", $group, "GR_groupid", false, false);
    }

    switch ($task) {
    case 'apply':
        $msg = sprintf(_("Group '%s' updated"), $group->GR_name);
        $appContext->insertMessage($msg);
        $database->log($msg, Log::LEVEL_INFO);
        Core::redirect("index2.php?option=com_group&task=edit&GR_groupid=$group->GR_groupid&hidemainmenu=1");
        break;
    case 'save':
        $msg = sprintf(_("Group '%s' saved"), $group->GR_name);
        $appContext->insertMessage($msg);
        $database->log($msg, Log::LEVEL_INFO);
    default:
        Core::redirect("index2.php?option=com_group");
    }
}

/**
 * @param $cid
 */
function removeGroup($cid)
{
    global $database, $mainframe, $my, $acl, $appContext;
    if (count($cid) < 1) {
        Core::backWithAlert(_("Please select record to erase"));
    }

    if (count($cid)) {
        foreach ($cid as $id) {
            $group = GroupDAO::getGroupByID($id);

            $persons = PersonDAO::getPersonArrayByGroupID($id);

            if (count($persons)) {
                $msg = sprintf(ngettext("Cannot delete user group '%s', because it has binded %s user", "Cannot delete user group '%s', because it has binded %s users", count($persons)), $group->GR_name, count($persons));
                $database->log($msg, Log::LEVEL_WARNING);
                $limit = 10;
                foreach ($persons as $person) {
                    $msg .= "\\n'" . $person->PE_firstname . " " . $person->PE_surname . "'";
                    if (!--$limit) {
                        break;
                    }
                }
                if (count($persons) > $limit) {
                    $msg .= '\n...';
                }
                Core::backWithAlert($msg);
            } else {
                GroupDAO::removeGroupByID($id);
                $msg = sprintf(_("User group '%s' deleted"), $group->GR_name);
                $appContext->insertMessage($msg);
                $database->log($msg, Log::LEVEL_INFO);
            }
        }
        Core::redirect("index2.php?option=com_group");
    }
}
