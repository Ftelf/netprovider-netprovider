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
require_once $core->getAppRoot() . "includes/dao/LogDAO.php";
require_once $core->getAppRoot() . "includes/dao/PersonDAO.php";
require_once "log.html.php";

$task = Utils::getParam($_REQUEST, 'task', null);
$lid = Utils::getParam($_REQUEST, 'LO_logid', null);
$cid = Utils::getParam($_REQUEST, 'cid', array(0));
if (!is_array($cid)) {
    $cid = array (0);
}

switch ($task) {
case 'remove':
    removeLog($cid);
    break;

default:
    showLog();
    break;
}
/**
 *
 */
function showLog()
{
    global $database, $mainframe, $acl, $core;
    require_once $core->getAppRoot() . 'modules/com_common/PageNav.php';

    $limit = Utils::getParam($_SESSION['UI_SETTINGS']['com_log'], 'limit', 10);
    $limitstart = Utils::getParam($_SESSION['UI_SETTINGS']['com_log'], 'limitstart', 0);

    $logLevel = $filter['log_level'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_log']['filter'], 'log_level', 0);
    $filter['date_from'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_log']['filter'], 'date_from', "");
    $filter['date_to'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_log']['filter'], 'date_to', "");
    $filter['personid'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_log']['filter'], 'personid', "");

    $dateFrom = new DateUtil();
    $dateTo = new DateUtil();

    try {
        $dateFrom->parseDate($filter['date_from'], DateUtil::FORMAT_DATE);
    } catch (Exception $e) {
    }

    try {
        $dateTo->parseDate($filter['date_to'], DateUtil::FORMAT_DATE);
    } catch (Exception $e) {
    }

    try {
        if ($dateFrom->after($dateTo)) {
            $dateTemp = $dateTo;
            $dateTo = $dateFrom;
            $dateFrom = $dateTemp;
        }
    } catch (Exception $e) {
    }

    $filter['date_from'] = $dateFrom->getFormattedDate(DateUtil::FORMAT_DATE);
    $filter['date_to'] = $dateTo->getFormattedDate(DateUtil::FORMAT_DATE);

    if ($dateTo->getTime() != null) {
        $dateTo->add(DateUtil::DAY, 1);
    }

    $total = LogDAO::getLogCount($logLevel, $filter['personid'], $dateFrom->getFormattedDate(DateUtil::DB_DATETIME), $dateTo->getFormattedDate(DateUtil::DB_DATETIME));
    $logs = LogDAO::getLogArray($logLevel, $filter['personid'], $dateFrom->getFormattedDate(DateUtil::DB_DATETIME), $dateTo->getFormattedDate(DateUtil::DB_DATETIME), $limitstart, $limit);

    $persons = LogDAO::getPersonArrayWhenInLog();

    $pageNav = new PageNav($total, $limitstart, $limit);
    HTML_log::showLog($logs, $persons, $pageNav, $filter);
}
/**
 * @param array $cid LogID
 */
function removeLog($cid)
{
    global $database, $mainframe, $my, $acl;
    if (count($cid) < 1) {
        Core::backWithAlert(_("Please select record to erase"));
    }
    if (count($cid)) {
        foreach ($cid as $id) {
            LogDAO::removeLogByID($id);
        }
        Core::redirect("index2.php?option=com_log");
    }
}
?>
