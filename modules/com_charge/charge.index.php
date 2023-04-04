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
require_once $core->getAppRoot() . "includes/dao/ChargeDAO.php";
require_once $core->getAppRoot() . "includes/dao/HasChargeDAO.php";
require_once $core->getAppRoot() . "includes/dao/InternetDAO.php";
require_once "charge.html.php";

$task = Utils::getParam($_REQUEST, 'task', null);
$chid = Utils::getParam($_REQUEST, 'CH_chargeid', null);
$cid = Utils::getParam($_REQUEST, 'cid', []);
if (!is_array($cid)) {
    $cid = [];
}

switch ($task) {
case 'new':
    editCharge(null);
    break;

case 'edit':
    editCharge($chid);
    break;

case 'editA':
    editCharge(intval($cid[0]));
    break;

case 'save':
case 'apply':
    saveCharge($task);
    break;

case 'remove':
    removeCharge($cid);
    break;

case 'cancel':
    showCharge();
    break;

default:
    showCharge();
    break;
}
/**
 *
 */
function showCharge()
{
    global $database, $mainframe, $acl, $core;
    require_once $core->getAppRoot() . 'modules/com_common/PageNav.php';

    $limit = Utils::getParam($_SESSION['UI_SETTINGS']['com_charge'], 'limit', 10);
    $limitstart = Utils::getParam($_SESSION['UI_SETTINGS']['com_charge'], 'limitstart', 0);

    $total = ChargeDAO::getChargeCount();
    $charges = ChargeDAO::getChargeArray($limitstart, $limit);

    $internets = InternetDAO::getInternetArray();

    $pageNav = new PageNav($total, $limitstart, $limit);
    HTML_charge::showCharges($charges, $internets, $pageNav);
}

/**
 * @param integer $chid ChargeID
 */
function editCharge($chid = null)
{
    global $database, $my, $acl;

    if ($chid != null) {
        $charge = ChargeDAO::getChargeByID($chid);
    } else {
        $charge = new Charge();
        $charge->CH_tolerance = 7;
        $charge->CH_currency = "CZK";
    }

    $toleranceArray = getToleranceArray();

    $internets = InternetDAO::getInternetArray();

    HTML_charge::editCharge($charge, $toleranceArray, $internets);
}

/**
 * @param String $task task
 */
function saveCharge($task)
{
    global $core, $database, $mainframe, $my, $acl, $appContext;

    $charge = new Charge();
    database::bind($_POST, $charge);
    $isNew = !$charge->CH_chargeid;

    try {
        $charge->CH_amount = NumberFormat::parseMoney($charge->CH_amount);
    } catch (Exception $e) {
        Core::alert(_("Amount with VAT is in incorrect format"));
        $charge->CH_amount = null;

        $toleranceArray = getToleranceArray();
        $internets = InternetDAO::getInternetArray();

        HTML_charge::editCharge($charge, $toleranceArray, $internets);
        return;
    }

    if ($enableVatPayerSpecifics = $core->getProperty(Core::ENABLE_VAT_PAYER_SPECIFICS)) {
        try {
            $charge->CH_baseamount = NumberFormat::parseMoney($charge->CH_baseamount);
        } catch (Exception $e) {
            Core::alert(_("Base amount is in incorrect format"));
            $charge->CH_baseamount = null;

            $toleranceArray = getToleranceArray();
            $internets = InternetDAO::getInternetArray();

            HTML_charge::editCharge($charge, $toleranceArray, $internets);
            return;
        }

        try {
            $charge->CH_vat = NumberFormat::parseMoney($charge->CH_vat);
        } catch (Exception $e) {
            Core::alert(_("VAT is in incorrect format"));
            $charge->CH_vat = null;

            $toleranceArray = getToleranceArray();
            $internets = InternetDAO::getInternetArray();

            HTML_charge::editCharge($charge, $toleranceArray, $internets);
            return;
        }
    } else {
        $charge->CH_baseamount = $charge->CH_amount;
        $charge->CH_vat = 0;
    }

    if ($charge->CH_type != Charge::TYPE_INTERNET_PAYMENT) {
        $charge->CH_internetid = null;
    }

    if ($isNew) {
        $database->insertObject("charge", $charge, "CH_chargeid", false);
    } else {
        $database->updateObject("charge", $charge, "CH_chargeid", true, false);
    }

    switch ($task) {
    case 'apply':
        $msg = sprintf(_("Payment template '%s' updated"), $charge->CH_name);
        $appContext->insertMessage($msg);
        $database->log($msg, Log::LEVEL_INFO);
        Core::redirect("index2.php?option=com_charge&task=edit&CH_chargeid=$charge->CH_chargeid&hidemainmenu=1");
        break;
    case 'save':
        $msg = sprintf(_("Payment template '%s' saved"), $charge->CH_name);
        $appContext->insertMessage($msg);
        $database->log($msg, Log::LEVEL_INFO);
    default:
        Core::redirect("index2.php?option=com_charge");
    }
}

/**
 * @param array $cid ChargeID
 */
function removeCharge($cid)
{
    global $database, $mainframe, $my, $acl, $appContext;
    if (count($cid) < 1) {
        Core::backWithAlert(_("Please select record to erase"));
    }

    if (count($cid)) {
        foreach ($cid as $id) {
            $charge = ChargeDAO::getChargeByID($id);

            $hasCharges = ChargeDAO::getUsedChargeArray($id);

            if (count($hasCharges)) {
                $msg = sprintf(ngettext("Cannot delete payment template '%s', because it has binded %s payment", "Cannot delete payment template '%s', because it has binded %s payments", count($hasCharges)), $charge->CH_name, count($hasCharges));
                $database->log($msg, Log::LEVEL_WARNING);
                $limit = 10;
                foreach ($hasCharges as $hasCharge) {
                    $msg .= "\\n'" . $hasCharge->PE_firstname . " " . $hasCharge->PE_surname . "'";
                    if (!--$limit) {
                        break;
                    }
                }
                if (count($hasCharges) > $limit) {
                    $msg .= '\n...';
                }
                Core::backWithAlert($msg);
            } else {
                ChargeDAO::removeChargeByID($id);
                $msg = sprintf(_("Payment template '%s' deleted"), $charge->CH_name);
                $appContext->insertMessage($msg);
                $database->log($msg, Log::LEVEL_INFO);
            }
        }
        Core::redirect("index2.php?option=com_charge");
    }
}

/**
 * @return array tolerance days
 */
function getToleranceArray()
{
    $toleranceArray = [];

    for ($i = 0; $i <= 360; $i++) {
        $toleranceArray[] = sprintf(ngettext("%s day", "%s days", $i), $i);
    }

    return $toleranceArray;
}
