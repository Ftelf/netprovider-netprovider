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
require_once($core->getAppRoot() . "includes/dao/PersonDAO.php");
require_once($core->getAppRoot() . "includes/dao/IpDAO.php");
require_once($core->getAppRoot() . "includes/dao/IpAccountDAO.php");
require_once($core->getAppRoot() . "includes/dao/IpAccountAbsDAO.php");

require_once('iptrafficreport.html.php');

$task = Utils::getParam($_REQUEST, 'task', null);

switch ($task) {
    case 'trafficReport':
        showBankList();
        break;

    default:
        showTrafficReport();
        break;
}

function showTrafficReport() {
    global $database, $mainframe, $acl, $core;
    require_once($core->getAppRoot() . 'modules/com_common/PageNav.php');

    $filter = array();

    // get filters
    $search = $filter['search'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_iptrafficreport']['filter'], 'search', "");
    $period = $filter['period'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_iptrafficreport']['filter'], 'period', null);
    $filter['date_from'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_iptrafficreport']['filter'], 'date_from', null);
    $filter['date_to'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_iptrafficreport']['filter'], 'date_to', null);
    $filter['sort_key'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_iptrafficreport']['filter'], 'sort_key', null);
    $filter['sort_direction'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_iptrafficreport']['filter'], 'sort_direction', null);
    $showRate = ("checked" == ($filter['show_rate'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_iptrafficreport']['filter'], 'show_rate', null)));

    // get limits
    $limit = Utils::getParam($_SESSION['UI_SETTINGS']['com_iptrafficreport'], 'limit', 10);
    $limitstart = Utils::getParam($_SESSION['UI_SETTINGS']['com_iptrafficreport'], 'limitstart', 0);

    if ($filter['sort_key'] == IpDAO::data) {
        $ips = IpDAO::getIpWithPersonArray(IpDAO::data, $filter['search'], null, null);

        $pageNav = new PageNav(count($ips), $limitstart, $limit);
    } else {
        $ips = IpDAO::getIpWithPersonArray($filter['sort_key'], $filter['search'], $limitstart, $limit);

        $count = IpDAO::getIpWithPersonArrayCount($filter['search']);

        $pageNav = new PageNav($count, $limitstart, $limit);
    }

    $report = array();
    $report['intervals'] = array();
    $report['options'] = array();
    $report['options']['HOURS'] = _("Hours");
    $report['options']['DAYS'] = _("Days");
    $report['options']['MONTHS'] = _("Months");

    $dateFrom = new DateUtil();
    try {
        $dateFrom->parseDate($filter['date_from'], DateUtil::FORMAT_DATE);
    } catch (Exception $e) {
        $dateFrom = new DateUtil();
        $dateFrom->set(DateUtil::SECONDS, 0);
        $dateFrom->set(DateUtil::MINUTES, 0);
        $dateFrom->set(DateUtil::HOUR, 0);
    }

    $dateTo = new DateUtil();
    try {
        $dateTo->parseDate($filter['date_to'], DateUtil::FORMAT_DATE);
    } catch (Exception $e) {
        $dateTo = new DateUtil();
        $dateTo->set(DateUtil::SECONDS, 0);
        $dateTo->set(DateUtil::MINUTES, 0);
        $dateTo->set(DateUtil::HOUR, 0);
    }

    if ($dateFrom->after($dateTo)) {
        $dateTemp = $dateTo;
        $dateTo = $dateFrom;
        $dateFrom = $dateTemp;
    }

    $filter['date_from'] = $dateFrom->getFormattedDate(DateUtil::FORMAT_DATE);
    $filter['date_to'] = $dateTo->getFormattedDate(DateUtil::FORMAT_DATE);

    if ($period != "HOURS" && $period != "DAYS" && $period != "MONTHS") {
        $period = "MONTHS";
    }

    $filter['period'] = $period;

    if ($period == "HOURS") {
        $iDate = new DateUtil($dateFrom);
        $dateTo = new DateUtil($dateFrom);
        $dateTo->add(DateUtil::DAY, 1);
        while ($iDate < $dateTo) {
            $report['intervals'][] = intval($iDate->get(DateUtil::HOUR)) . "-" . ($iDate->get(DateUtil::HOUR) + 1);
            $iDate->add(DateUtil::HOUR, 1);
        }

        foreach ($ips as &$ip) {
            $ip->data = IpAccountDAO::getIpAccountHourSumByIpID($ip->IP_ipid, $dateFrom, $dateTo, ($showRate) ? 3600 : null);
        }
    } else if ($period == "DAYS") {
        $iDate = new DateUtil($dateFrom);
        $dateTo = new DateUtil(date("Y-m-d 23:59:59", $dateTo->getTime()));
        while ($iDate <= $dateTo) {
            $report['intervals'][] = $iDate->getFormattedDate(DateUtil::FORMAT_DATE);
            $iDate->add(DateUtil::DAY, 1);
        }

        foreach ($ips as &$ip) {
            $ip->data = IpAccountDAO::getIpAccountDateSumByIpID($ip->IP_ipid, $dateFrom, $dateTo, ($showRate) ? 86400 : null);
        }
    } else if ($period == "MONTHS") {
        $dateFrom->set(DateUtil::DAY, 1);
        $iDate = new DateUtil($dateFrom);
        $dateTo = new DateUtil(date("Y-m-t 23:59:59", $dateTo->getTime()));
        while ($iDate <= $dateTo) {
            $report['intervals'][] = $iDate->getFormattedDate(DateUtil::FORMAT_MONTHLY);
            $iDate->add(DateUtil::MONTH, 1);
        }

        foreach ($ips as &$ip) {
            $ip->data = IpAccountDAO::getIpAccountMonthSumByIpID($ip->IP_ipid, $dateFrom, $dateTo, ($showRate) ? 2592000 : null);
        }
    }

    if ($filter['sort_key'] == IpDAO::data) {
        function cmp($a, $b) {
            $va = 0;
            foreach ($a->data as &$ipDateReport) {
                $va += $ipDateReport->bytes_sum;
            }

            $vb = 0;
            foreach ($b->data as &$ipDateReport) {
                $vb += $ipDateReport->bytes_sum;
            }

            if ($va == $vb) return 0;
            return ($va > $vb) ? -1 : 1;
        }

        usort ($ips, "cmp");

        $ips = array_slice($ips, $limitstart, $limit);
    }

    HTML_IpTrafficReport::showTraffic($ips, $report, $filter, $pageNav);
}
?>