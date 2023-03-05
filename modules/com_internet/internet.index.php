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
require_once $core->getAppRoot() . "includes/dao/InternetDAO.php";
require_once "internet.html.php";

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
default:
    showInternet();
    break;
}
/**
 */
function showInternet()
{
    global $core;
    require_once $core->getAppRoot() . 'modules/com_common/PageNav.php';

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
function editInternet($iid=null)
{
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
function saveInternet($task)
{
    global $database, $appContext;

    $internet = new Internet();
    database::bind($_POST, $internet);
    $isNew = !$internet->IN_internetid;

    // get proper values
    $errorArray = [];
    if (Utils::getParam($_REQUEST, 'IN_dnl_rate_cb', null) == "1") { $internet->IN_dnl_rate = -1;
    }
    if (Utils::getParam($_REQUEST, 'IN_upl_rate_cb', null) == "1") { $internet->IN_upl_rate = -1;
    }
    if (!is_numeric($internet->IN_dnl_rate)) { $errorArray[] = _("Guaranteed download is not in proper number format").'\n';
    }
    if (!is_numeric($internet->IN_dnl_ceil)) { $errorArray[] = _("Maximum download is not in proper number format").'\n';
    }
    if (!is_numeric($internet->IN_upl_rate)) { $errorArray[] = _("Guaranteed upload is not in proper number format").'\n';
    }
    if (!is_numeric($internet->IN_upl_ceil)) { $errorArray[] = _("Maximum upload is not in proper number format").'\n';
    }
    if (count($errorArray)) {
        Core::alert(implode(", ", $errorArray));
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
        $database->log($msg, Log::LEVEL_INFO);
        Core::redirect("index2.php?option=com_internet&task=edit&IN_internetid=$internet->IN_internetid&hidemainmenu=1");
        break;
    case 'save':
        $msg = sprintf(_("Internet template '%s' saved"), $internet->IN_name);
        $appContext->insertMessage($msg);
        $database->log($msg, Log::LEVEL_INFO);
    default:
        Core::redirect("index2.php?option=com_internet");
    }
}
/**
 * @param $cid
 */
function removeInternet($cid)
{
    global $database, $appContext;

    if (count($cid) < 1) {
        Core::backWithAlert(_("Please select record to erase"));
    }

    if (count($cid)) {
        foreach ($cid as $id) {
            $internet = InternetDAO::getInternetByID($id);
            $internetCharges = InternetDAO::getInternetChargesArrayByID($id);

            if (count($internetCharges)) {
                $msg = sprintf(ngettext("Cannot delete internet template '%s', because it has bound %s payment service: ", "Cannot delete internet template '%s', because it has bound %s payment services: ", count($internetCharges)), $internet->IN_name, count($internetCharges));
                $database->log($msg, Log::LEVEL_WARNING);
                $limit = 3;
                $names = array_column(array_slice($internetCharges, 0, $limit), 'CH_name');
                if (count($internetCharges) > $limit) {
                    $names[] = "...";
                }
                $msg .= implode(', ', $names);

                Core::alert($msg);
            } else {
                InternetDAO::removeInternetByID($id);
                $msg = sprintf(_("Internet template '%s' deleted"), $internet->IN_name);
                $appContext->insertMessage($msg);
                $database->log($msg, Log::LEVEL_INFO);
            }
        }
        Core::redirect("index2.php?option=com_internet");
    }
}
?>
