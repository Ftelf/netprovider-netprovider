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

/** ensure this file is being included by a parent file */
defined('VALID_MODULE') or die(_("Direct access into this section is not allowed"));

global $core;
require_once('handleevent.html.php');

$task = Utils::getParam($_REQUEST, 'task', null);
$heid = Utils::getParam($_REQUEST, 'HE_handleeventid', null);
$cid = Utils::getParam($_REQUEST, 'cid', array(0));
if (!is_array($cid)) {
    $cid = array (0);
}

switch ($task) {
    case 'new':
        editHandleEvent(null);
        break;

    case 'edit':
        editHandleEvent($heid);
        break;

    case 'editA':
        editHandleEvent(intval($cid[0]));
        break;

    case 'save':
    case 'apply':
        saveHandleEvent($task);
        break;

    case 'remove':
        removeHandleEvent($cid);
        break;

    case 'cancel':
        showHandleEvent();
        break;

    default:
        showHandleEvent();
        break;
}
/**
 *
 */
function showHandleEvent() {
    global $database, $mainframe, $acl, $core;
    require_once($core->getAppRoot() . 'modules/com_common/PageNav.php');

    $limit = Utils::getParam($_SESSION['UI_SETTINGS']['com_handleevent'], 'limit', 10);
    $limitstart = Utils::getParam($_SESSION['UI_SETTINGS']['com_handleevent'], 'limitstart', 0);

    $total = HandleEventDAO::getHandleEventCount();
    $handleevents = HandleEventDAO::getHandleEventArray($limitstart, $limit);

    $persons = PersonDAO::getPersonArray();

    $pageNav = new PageNav($total, $limitstart, $limit);
    HTML_handleevent::showHandleEvents($handleevents, $persons, $pageNav);
}
/**
 * @param $pid
 */
function editHandleEvent($pid=null) {
    global $core, $database, $my, $acl;

    if ($pid != null) {
        $handleEvent = HandleEventDAO::getHandleEventByID($pid);
    } else {
        $handleEvent = new HandleEvent();
    }

    $persons = PersonDAO::getPersonArray();

    $templateDirectory = opendir($core->getAppRoot()."templates/events/");

    $templates = array();

    while($entryName = readdir($templateDirectory)) {
        if (EndsWith($entryName, ".txt")) {
            $templates[] = $entryName;
        }
    }

    // close directory
    closedir($templateDirectory);


    HTML_handleevent::editHandleEvent($handleEvent, $persons, $templates);
}
/**
 * @param $task
 */
function saveHandleEvent($task) {
    global $database, $mainframe, $my, $acl, $appContext;

    $handleEvent = new HandleEvent();
    database::bind($_POST, $handleEvent);

    if ($handleEvent->HE_notifypersonid == 0) {
        $handleEvent->HE_notifypersonid = null;
    }

    $isNew 	= !$handleEvent->HE_handleeventid;

    if ($isNew) {
        $database->insertObject("handleevent", $handleEvent, "HE_handleeventid", false);
    } else {
        $database->updateObject("handleevent", $handleEvent, "HE_handleeventid", true, false);
    }

    switch ($task) {
        case 'apply':
            $msg = sprintf(_("Event handler '%s' updated"), $handleEvent->HE_name);
            $appContext->insertMessage($msg);
            $database->log($msg, Log::LEVEL_INFO);
            Core::redirect("index2.php?option=com_handleevent&task=edit&HE_handleeventid=$handleEvent->HE_handleeventid&hidemainmenu=1");
            break;
        case 'save':
            $msg = sprintf(_("Event handler '%s' saved"), $handleEvent->HE_name);
            $appContext->insertMessage($msg);
            $database->log($msg, Log::LEVEL_INFO);
        default:
            Core::redirect("index2.php?option=com_handleevent");
    }
}
/**
 * @param $cid
 */
function removeHandleEvent($cid) {
    global $database, $mainframe, $my, $acl, $appContext;
    if (count($cid) < 1) {
        Core::backWithAlert(_("Please select record to erase"));
    }

    if (count($cid)) {
        foreach ($cid as $id) {
            $handleEvent = HandleEventDAO::getHandleEventByID($id);

            HandleEventDAO::removeHandleEventByID($id);
            $msg = sprintf(_("Event handler '%s' deleted"), $handleEvent->HE_name);
            $appContext->insertMessage($msg);
            $database->log($msg, Log::LEVEL_INFO);
        }
        Core::redirect("index2.php?option=com_handleevent");
    }
}
function EndsWith($FullStr, $EndStr) {
    // Get the length of the end string
    $StrLen = strlen($EndStr);
    // Look at the end of FullStr for the substring the size of EndStr
    $FullStrEnd = substr($FullStr, strlen($FullStr) - $StrLen);
    // If it matches, it does end with EndStr
    return $FullStrEnd == $EndStr;
}
?>
