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
require_once $core->getAppRoot() . "includes/dao/ChargeDAO.php";
require_once $core->getAppRoot() . "includes/dao/HasChargeDAO.php";
require_once $core->getAppRoot() . "includes/dao/ChargeEntryDAO.php";
require_once $core->getAppRoot() . "includes/html/css/PaymentReportStyles.php";
require_once 'paymentreport.html.php';

$task = Utils::getParam($_REQUEST, 'task', null);

switch ($task) {
    default:
        showPaymentReport();
        break;
}

function showPaymentReport()
{
    global $database, $mainframe, $acl, $core, $appContext;
    include_once $core->getAppRoot() . 'modules/com_common/PageNav.php';

    $filter = array();

    // get filters
    $filter['search'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_paymentreport']['filter'], 'search', "");
    $filter['CH_chargeid'] = $filter_CH_chargeid = Utils::getParam($_SESSION['UI_SETTINGS']['com_paymentreport']['filter'], 'CH_chargeid', array());
    $filter['PE_status'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_paymentreport']['filter'], 'PE_status', -1);
    $filter['HC_status'] = $filter_HC_status = Utils::getParam($_SESSION['UI_SETTINGS']['com_paymentreport']['filter'], 'HC_status', -1);
    $filter['HC_actualstate'] = $filter_HC_actualstate = Utils::getParam($_SESSION['UI_SETTINGS']['com_paymentreport']['filter'], 'HC_actualstate', -1);
    $filter['date_from'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_paymentreport']['filter'], 'date_from', null);
    $filter['date_to'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_paymentreport']['filter'], 'date_to', null);

    // get limits
    $limit = Utils::getParam($_SESSION['UI_SETTINGS']['com_paymentreport'], 'limit', 10);
    $limitstart = Utils::getParam($_SESSION['UI_SETTINGS']['com_paymentreport'], 'limitstart', 0);

    $persons = PersonDAO::getPersonWithAccountArray($filter['search'], 0, $filter['PE_status'], null, null);
    $allCharges = ChargeDAO::getChargeArray();

    $charges = array_filter(
        $allCharges, function ($v, $k) {
        return $v->CH_period == Charge::PERIOD_MONTHLY;
    }, ARRAY_FILTER_USE_BOTH
    );

    if (!is_array($filter_CH_chargeid)) {
        $filter['CH_chargeid'] = $filter_CH_chargeid = array();
    }

    $selectedCharges = array_filter(
        $charges, function ($v, $k) use ($filter_CH_chargeid) {
        return in_array($v->CH_chargeid, $filter_CH_chargeid);
    }, ARRAY_FILTER_USE_BOTH
    );


    $paymentReport = array();

    $report = array();
    $report['dates'] = array();

    $dateFrom = new DateUtil();
    $dateTo = new DateUtil();

    try {
        $dateFrom->parseDate($filter['date_from'], DateUtil::FORMAT_MONTHLY);
    } catch (Exception $e) {
        $dateFrom = new DateUtil();
        $dateFrom->set(DateUtil::HOUR, 0);
        $dateFrom->set(DateUtil::MINUTES, 0);
        $dateFrom->set(DateUtil::SECONDS, 0);
        $dateFrom->set(DateUtil::DAY, 1);
        $dateFrom->add(DateUtil::MONTH, -3);
    }

    try {
        $dateTo->parseDate($filter['date_to'], DateUtil::FORMAT_MONTHLY);
    } catch (Exception $e) {
        $dateTo = new DateUtil();
        $dateTo->set(DateUtil::HOUR, 0);
        $dateTo->set(DateUtil::MINUTES, 0);
        $dateTo->set(DateUtil::SECONDS, 0);
        $dateTo->set(DateUtil::DAY, 1);
        $dateTo->add(DateUtil::MONTH, 2);
    }

    if ($dateFrom->after($dateTo)) {
        $tmpDate = $dateTo;
        $dateTo = $dateFrom;
        $dateFrom = $tmpDate;
    }

    $filter['date_from'] = $dateFrom->getFormattedDate(DateUtil::FORMAT_MONTHLY);
    $filter['date_to'] = $dateTo->getFormattedDate(DateUtil::FORMAT_MONTHLY);

    foreach ($persons as &$person) {
        if ($filter_CH_chargeid) {
            $hasCharges = HasChargeDAO::getHasChargeReportArray($person->PE_personid, $filter_CH_chargeid, $dateFrom, $dateTo);

            if (count($hasCharges)) {
                foreach ($hasCharges as $hasCharge) {
                    $hasCharge->_chargeEntries = ChargeEntryDAO::getChargeEntryArrayByHasChargeID($hasCharge->HC_haschargeid, $dateFrom, $dateTo, 'CE_period_date');
                }

                $person->_hasCharge = $hasCharges;
                $paymentReport[] = $person;
            }
        }
    }

    $messages = array();

    //    if ($charge->CH_period == Charge::PERIOD_MONTHLY) {
    $iDate = $dateFrom;
    while (!$iDate->after($dateTo)) {
        $report['dates'][$iDate->getTime()] = array();
        $report['dates'][$iDate->getTime()]['DateUtil'] = clone $iDate;
        $report['dates'][$iDate->getTime()]['DATE_STRING'] = $iDate->getFormattedDate(DateUtil::FORMAT_MONTHLY);

        $report['dates'][$iDate->getTime()]['summary'] = array();
        $report['dates'][$iDate->getTime()]['summary']['payed'] = 0;
        $report['dates'][$iDate->getTime()]['summary']['payedWithDelay'] = 0;
        $report['dates'][$iDate->getTime()]['summary']['delayed'] = 0;
        $report['dates'][$iDate->getTime()]['summary']['pending'] = 0;
        $report['dates'][$iDate->getTime()]['summary']['free'] = 0;

        $iDate->add(DateUtil::MONTH, 1);
    }

    reset($paymentReport);
    foreach ($paymentReport as $key => &$personReport) {
        reset($personReport);
        foreach ($personReport->_hasCharge as $haschargeid => &$hasCharge) {
            if ($filter_HC_status != -1) {
                if ($hasCharge->HC_status != $filter_HC_status) {
                    unset($personReport->_hasCharge[$haschargeid]);
                    continue;
                }
            }

            if ($filter_HC_actualstate != -1) {
                if ($hasCharge->HC_actualstate != $filter_HC_actualstate) {
                    unset($personReport->_hasCharge[$haschargeid]);
                    continue;
                }
            }

            $hasCharge->_dates = array();
            $foundAnyEntry = false;

            reset($report['dates']);
            foreach ($report['dates'] as &$date) {
                $dbDate = $date['DateUtil']->getFormattedDate(DateUtil::DB_DATE);

                $info = array();
                $info['date'] = $dbDate;
                $info['colspan'] = 1;

                // test if HasCharge belongs to current dateEntry
                $dateStart = new DateUtil($hasCharge->HC_datestart);
                $dateEnd = new DateUtil($hasCharge->HC_dateend);
                if (!$dateStart->after($date['DateUtil']) && ($dateEnd->getTime() == null || !$date['DateUtil']->after($dateEnd))) {
                    if (isset($hasCharge->_chargeEntries[$dbDate])) {
                        $chargeEntry = $hasCharge->_chargeEntries[$dbDate];

                        chargeEntryToStyle($hasCharge, $chargeEntry, $info, $date);

                        $foundAnyEntry = true;
                    } else {
                        $info['text'] = '';
                        $info['style'] = PaymentReportStyles::STATUS_PENDING_PAYMENT_NOT_CREATED;
                    }
                } else {
                    $info['text'] = '';
                    $info['style'] = PaymentReportStyles::STATUS_HAS_NO_CHARGE;
                }
                $hasCharge->_dates[$date['DateUtil']->getTime()] = $info;
            }
            if (!$foundAnyEntry) {
                $messages[] = '<a href="/index2.php?option=com_person&task=edit&hidemainmenu=1&PE_personid=' . $personReport->PE_personid . '">' . $personReport->PE_firstname . ' ' . $personReport->PE_surname . '</a>: ' . _('Has unterminated payment');

                unset($paymentReport[$key]);
            }
        }

        if (count($personReport->_hasCharge) == 0) {
            unset($paymentReport[$key]);
        }
    }
    //    }

    $pageNav = new PageNav(count($paymentReport), $limitstart, $limit);
    $paymentReport = array_slice($paymentReport, $limitstart, $limit);

    HTML_PaymentReport::showPayments($messages, $charges, $paymentReport, $report, $filter, $pageNav);
}

function chargeEntryToStyle(&$hasCharge, &$chargeEntry, &$info, &$date)
{
    if ($chargeEntry->CE_status == ChargeEntry::STATUS_ERROR) {
        // never used so far
    } else if ($chargeEntry->CE_status == ChargeEntry::STATUS_FINISHED && $chargeEntry->CE_overdue == 0) {
        $info['text'] = '';
        $info['style'] = PaymentReportStyles::STATUS_FINISHED_IN_TIME;
        $date['summary']['payed'] += $chargeEntry->CE_amount;
    } else if ($chargeEntry->CE_status == ChargeEntry::STATUS_FINISHED && $chargeEntry->CE_overdue > 0) {
        $info['text'] = sprintf(ngettext("+%s day", "+%s days", $chargeEntry->CE_overdue), $chargeEntry->CE_overdue);
        $info['style'] = PaymentReportStyles::STATUS_FINISHED_OVERDUE;
        $date['summary']['payedWithDelay'] += $chargeEntry->CE_amount;
    } else if ($chargeEntry->CE_status == ChargeEntry::STATUS_PENDING) {
        $info['text'] = '';
        $info['style'] = PaymentReportStyles::STATUS_PENDING;
        $date['summary']['pending'] += $chargeEntry->CE_amount;
    } else if ($chargeEntry->CE_status == ChargeEntry::STATUS_PENDING_INSUFFICIENTFUNDS && $chargeEntry->CE_overdue <= $hasCharge->CH_tolerance) {
        $info['text'] = sprintf(ngettext("+%s day", "+%s days", $chargeEntry->CE_overdue), $chargeEntry->CE_overdue);
        $info['style'] = PaymentReportStyles::STATUS_PENDING_INSUFFICIENT_FUNDS;
        $date['summary']['delayed'] += $chargeEntry->CE_amount;
    } else if ($chargeEntry->CE_status == ChargeEntry::STATUS_PENDING_INSUFFICIENTFUNDS && $chargeEntry->CE_overdue > 0) {
        $info['text'] = sprintf(ngettext("+%s day", "+%s days", $chargeEntry->CE_overdue), $chargeEntry->CE_overdue);
        $info['style'] = PaymentReportStyles::STATUS_PENDING_INSUFFICIENT_FUNDS_OVERDUE;
        $date['summary']['delayed'] += $chargeEntry->CE_amount;
    } else if ($chargeEntry->CE_status == ChargeEntry::STATUS_TESTINGFREEOFCHARGE) {
        $info['text'] = '';
        $info['style'] = PaymentReportStyles::STATUS_FREE_OF_CHARGE;
        $date['summary']['free']++;
    } else {
        $info['text'] = '';
        $info['style'] = PaymentReportStyles::STATUS_OTHER;
    }
}
