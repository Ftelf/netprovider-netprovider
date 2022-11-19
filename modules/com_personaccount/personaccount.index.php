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
require_once($core->getAppRoot() . "includes/dao/PersonAccountDAO.php");
require_once($core->getAppRoot() . "includes/tables/PersonAccount.php");
require_once($core->getAppRoot() . "includes/dao/BankAccountEntryDAO.php");
require_once($core->getAppRoot() . "includes/dao/PersonAccountEntryDAO.php");
require_once($core->getAppRoot() . "includes/dao/ChargeEntryDAO.php");
require_once($core->getAppRoot() . "includes/billing/ChargesUtil.php");
require_once($core->getAppRoot() . "includes/utils/DateUtil.php");
require_once('personaccount.html.php');

$task = Utils::getParam($_REQUEST, 'task', null);
$pid = Utils::getParam($_REQUEST, 'PE_personid', null);
$pnid = Utils::getParam($_REQUEST, 'PN_personaccountentryid', null);
$ceid = Utils::getParam($_REQUEST, 'CE_chargeentryid', null);
$cid = Utils::getParam($_REQUEST, 'cid', array(0));
if (!is_array($cid)) {
    $cid = array (0);
}

switch ($task) {
    case 'createBlankCharges': {
        createBlankCharges();
        break;
    }

    case 'proceedCharges': {
        proceedCharges();
        break;
    }

    case 'returnPayment': {
        returnPayment($pid, $pnid);
        break;
    }

    case 'freeCharge': {
        freeCharge($ceid);
        break;
    }

    case 'ignoreCharge': {
        ignoreCharge($ceid);
        break;
    }

    case 'removeCharge': {
        removeCharge($ceid);
        break;
    }

    case 'cancelPAE':
    case 'showDetail':
        showPersonAccountDetail($pid);
        break;

    case 'showDetailA':
        showPersonAccountDetail(intval($cid[0]));
        break;

    case 'edit':
        editPersonAccount($pid);
        break;

    case 'cancel':
        showPersonAccount();
        break;

    case 'cancelEdit':
        showPersonAccountDetail($pid);
        break;

    case 'apply':
    case 'save':
        savePersonAccount($pid, $task);
        break;

    case 'newPAE':
        editPersonAccountEntry($pid);
        break;

    case 'savePAE':
        savePersonAccountEntry($pid, $task);
        break;

    default:
        showPersonAccount();
        break;
}

/**
 *
 */
function showPersonAccount() {
    global $database, $mainframe, $acl, $core;
    require_once($core->getAppRoot() . 'modules/com_common/PageNav.php');

    $filter = array();
    // default settings if no setting in session
    //
    $filter['search'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_personaccount']['filter'], 'search', "");
    $filter['status'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_personaccount']['filter'], 'status', -1);
    $filter['bilance'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_personaccount']['filter'], 'bilance', -1);

    $limit = Utils::getParam($_SESSION['UI_SETTINGS']['com_personaccount'], 'limit', 10);
    $limitstart = Utils::getParam($_SESSION['UI_SETTINGS']['com_personaccount'], 'limitstart', 0);

    $persons = PersonDAO::getPersonArray($filter['search'], 0, $filter['status']);
    $personAccounts = PersonAccountDAO::getPersonAccountArray();
    $vss = array();
    $msgs = array();

    foreach ($persons as $k => $person) {
        if (!isset($personAccounts[$person->PE_personaccountid])) {
            $msgs[] = sprintf(_("%s has no account"), $person->PE_firstname . " " . $person->PE_surname);
        }
        $personAccount = $personAccounts[$person->PE_personaccountid];
        if ($personAccount->PA_variablesymbol != null && $personAccount->PA_variablesymbol != 0) {
            if (isset($vss[$personAccount->PA_variablesymbol])) {
                $personTemp = $vss[$personAccount->PA_variablesymbol];
                $msgs[] = sprintf(_("%s %s VS: %s"), $personTemp->PE_firstname, $personTemp->PE_surname, $personAccounts[$personTemp->PE_personaccountid]->PA_variablesymbol);
                $msgs[] = sprintf(_("%s %s VS: %s"), $person->PE_firstname, $person->PE_surname, $personAccount->PA_variablesymbol);
            }
            $vss[$personAccount->PA_variablesymbol] = $person;
        }
        if ($filter['bilance'] != -1) {
            if ($filter['bilance'] == 1 && $personAccount->PA_balance >= 0) {
                unset($persons[$k]);
                continue;
            } else if ($filter['bilance'] == 2 && $personAccount->PA_balance != 0) {
                unset($persons[$k]);
                continue;
            } else if ($filter['bilance'] == 3 && $personAccount->PA_balance <= 0) {
                unset($persons[$k]);
                continue;
            }
        }
    }
    $pageNav = new PageNav(count($persons), $limitstart, $limit);
    $personsView = array_slice($persons, $limitstart, $limit);
    HTML_PersonAccount::showEntries($personsView, $personAccounts, $pageNav, $filter, $msgs);
}
/**
 * @param integer $pid PersonID
 */
function showPersonAccountDetail($pid=null) {
    global $database, $my, $acl;
    $flags = array();
    $person = PersonDAO::getPersonByID($pid);
    $personAccount = PersonAccountDAO::getPersonAccountByID($person->PE_personaccountid);

    $bankAccountEntries = BankAccountEntryDAO::getBankAccountEntryArray();
    $personAccountEntries = PersonAccountEntryDAO::getPersonAccountEntryArrayByPersonAccountID($personAccount->PA_personaccountid);

    $charges = ChargeDAO::getChargeArray();
    $hasCharges = HasChargeDAO::getHasChargeWithChargeWithPersonArrayByPersonID($person->PE_personid);

    $chargeEntries = array();
    foreach ($hasCharges as $hasCharge) {
        $chargeEntriesTmp = ChargeEntryDAO::getChargeEntryArrayByHasChargeID($hasCharge->HC_haschargeid);
        $hasCharge->_chargeEntries = $chargeEntriesTmp;
        foreach ($chargeEntriesTmp as $chargeEntryTmp) {
            $chargeEntries[$chargeEntryTmp->CE_chargeentryid] = $chargeEntryTmp;
        }
    }
    HTML_PersonAccount::showPersonAccountDetail($person, $personAccount, $bankAccountEntries, $personAccountEntries, $chargeEntries, $hasCharges, $charges);
}
/**
 * @param integer $pid PersonID
 */
function editPersonAccount($pid=null) {
    global $database, $my, $acl;
    $flags = array();
    $person = PersonDAO::getPersonByID($pid);
    $personAccount = PersonAccountDAO::getPersonAccountByID($person->PE_personaccountid);

    HTML_PersonAccount::editPersonAccountDetail($person, $personAccount);
}
/**
 * @param integer $pid PersonID
 * @param String $task task
 */
function savePersonAccount($pid, $task) {
    global $database, $mainframe, $my, $acl, $appContext;

    $personAccount = new PersonAccount();
    database::bind($_POST, $personAccount);

    $personAccount->PA_startbalance = null;
    $personAccount->PA_balance = null;
    $personAccount->PA_income = null;
    $personAccount->PA_outcome = null;

    $person = PersonDAO::getPersonByID($pid);

    $personAccount->PA_personaccountid = $person->PE_personaccountid;

    if (!Utils::getParam($_POST, '_CB_PA_variablesymbol', 0)) $personAccount->PA_variablesymbol = 0;
    if (!Utils::getParam($_POST, '_CB_PA_constantsymbol', 0)) $personAccount->PA_constantsymbol = 0;
    if (!Utils::getParam($_POST, '_CB_PA_specificsymbol', 0)) $personAccount->PA_specificsymbol = 0;

    if (!is_numeric($personAccount->PA_variablesymbol) ||
        !is_numeric($personAccount->PA_constantsymbol)  ||
        !is_numeric($personAccount->PA_specificsymbol)) {
        Core::redirect("index2.php?option=com_personaccount&task=edit&PE_personid=$person->PE_personid&hidemainmenu=1");
    }

    $database->updateObject("personaccount", $personAccount, "PA_personaccountid", false, false);

    switch ($task) {
        case 'apply':
            $msg = sprintf(_("User account from user '%s' updated"), $person->PE_firstname." ".$person->PE_surname);
            $appContext->insertMessage($msg);
            $database->log($msg, LOG::LEVEL_INFO);
            Core::redirect("index2.php?option=com_personaccount&task=edit&PE_personid=$person->PE_personid&hidemainmenu=1");
            break;
        case 'save':
            $msg = sprintf(_("User account from user '%s' saved"), $person->PE_firstname." ".$person->PE_surname);
            $appContext->insertMessage($msg);
            $database->log($msg, LOG::LEVEL_INFO);
        default:
            Core::redirect("index2.php?option=com_personaccount&task=showDetail&PE_personid=$person->PE_personid&hidemainmenu=1");
            break;
    }
}
/**
 *
 */
function createBlankCharges() {
    global $database, $appContext, $my, $acl;

    if ($my->GR_level == Group::SUPER_ADMININSTRATOR) {
        $chargesUtil = new ChargesUtil();
        $chargesUtil->createBlankChargeEntries();

        $appContext->insertMessage(_('Blank charge creation finished.'));
    }

    Core::redirect("index2.php?option=com_personaccount");
}
/**
 *
 */
function proceedCharges() {
    global $database, $appContext, $my, $acl;

    if ($my->GR_level == Group::SUPER_ADMININSTRATOR) {
        $chargesUtil = new ChargesUtil();
        $chargesUtil->proceedCharges(false);

        $appContext->insertMessage(_('Charges process finished. Messaging clients has been suppressed.'));
    }

    Core::redirect("index2.php?option=com_personaccount");
}
/**
 * @param integer $ceif PersonAccountEntryID
 */
function returnPayment($pid, $pnid) {
    global $database, $my, $acl, $appContext;

    $person = PersonDAO::getPersonByID($pid);

    $personAccountEntry = PersonAccountEntryDAO::getPersonAccountEntryByID($pnid);

    try {
        $database->startTransaction();

        if ($personAccountEntry->PN_source == PersonAccountEntry::SOURCE_BANKACCOUNT) {
            $bankAccountEntry = BankAccountEntryDAO::getBankAccountEntryByID($personAccountEntry->PN_bankaccountentryid);
            $bankAccountEntry->BE_personaccountentryid = null;
            $bankAccountEntry->BE_status = BankAccountEntry::STATUS_PENDING;
            $bankAccountEntry->BE_identifycode = BankAccountEntry::IDENTIFY_UNIDENTIFIED;
            $database->updateObject("bankaccountentry", $bankAccountEntry, "BE_bankaccountentryid", true, false);

            $personAccountEntries = PersonAccountEntryDAO::getPersonAccountEntryArrayByBankAccountEntryID($personAccountEntry->PN_bankaccountentryid);

            foreach ($personAccountEntries as $personAccountEntry) {
                $personAccount = PersonAccountDAO::getPersonAccountByID($personAccountEntry->PN_personaccountid);
                $person = PersonDAO::getPersonByPersonAccountID($personAccount->PA_personaccountid);
                $personAccount->PA_balance -= $personAccountEntry->PN_amount;
                $personAccount->PA_income -= $personAccountEntry->PN_amount;

                $database->updateObject("personaccount", $personAccount, "PA_personaccountid", false, false);
                PersonAccountEntryDAO::removePersonAccountEntryByID($personAccountEntry->PN_personaccountentryid);

                $date = new DateUtil($personAccountEntry->PN_date);

                $msg = sprintf(_("User's '%s' payment with amount %s and date %s has been removed"), $person->PE_firstname." ".$person->PE_surname, $personAccountEntry->PN_amount, $date->getFormattedDate(DateUtil::FORMAT_DATE));
                $appContext->insertMessage($msg);
                $database->log($msg, LOG::LEVEL_INFO);
            }

            $dateTime = new DateUtil($bankAccountEntry->BE_datetime);

            $msg = sprintf(_("Bank entry %s %s %s %s amount %s returned to process"), $dateTime->getFormattedDate(DateUtil::FORMAT_DATE), $bankAccountEntry->BE_accountnumber."/".$bankAccountEntry->BE_banknumber, $bankAccountEntry->BE_accountname, $bankAccountEntry->BE_message, $bankAccountEntry->BE_amount);
            $appContext->insertMessage($msg);
            $database->log($msg, LOG::LEVEL_INFO);
        } else if ($personAccountEntry->PN_source == PersonAccountEntry::SOURCE_CASH) {
            $personAccount = PersonAccountDAO::getPersonAccountByID($personAccountEntry->PN_personaccountid);

            $personAccount->PA_balance -= $personAccountEntry->PN_amount;
            $personAccount->PA_income -= $personAccountEntry->PN_amount;

            $database->updateObject("personaccount", $personAccount, "PA_personaccountid", false, false);
            PersonAccountEntryDAO::removePersonAccountEntryByID($personAccountEntry->PN_personaccountentryid);

            $date = new DateUtil($personAccountEntry->PN_date);

            $msg = sprintf(_("User's '%s' payment with amount %s and date %s has been removed"), $person->PE_firstname." ".$person->PE_surname, $personAccountEntry->PN_amount, $date->getFormattedDate(DateUtil::FORMAT_DATE));
            $appContext->insertMessage($msg);
            $database->log($msg, LOG::LEVEL_INFO);
        } else if ($personAccountEntry->PN_source == PersonAccountEntry::SOURCE_DISCOUNT) {
            $personAccount = PersonAccountDAO::getPersonAccountByID($personAccountEntry->PN_personaccountid);

            $personAccount->PA_balance -= $personAccountEntry->PN_amount;

            $database->updateObject("personaccount", $personAccount, "PA_personaccountid", false, false);
            PersonAccountEntryDAO::removePersonAccountEntryByID($personAccountEntry->PN_personaccountentryid);

            $date = new DateUtil($personAccountEntry->PN_date);

            $msg = sprintf(_("User's '%s' payment with amount %s and date %s has been removed"), $person->PE_firstname." ".$person->PE_surname, $personAccountEntry->PN_amount, $date->getFormattedDate(DateUtil::FORMAT_DATE));
            $appContext->insertMessage($msg);
            $database->log($msg, LOG::LEVEL_INFO);
        }
        $database->commit();
    } catch (Exception $e) {
        $database->rollback();
        throw $e;
    }

    Core::redirect("index2.php?option=com_personaccount&task=showDetail&PE_personid=$pid&hidemainmenu=1");
}
/**
 * @param integer $ceif ChargeEntryID
 */
function freeCharge($ceid) {
    global $database, $my, $acl, $appContext;

    $chargeEntry = ChargeEntryDAO::getChargeEntryByID($ceid);
    $hasCharge = HasChargeDAO::getHasChargeByID($chargeEntry->CE_haschargeid);
    $person = PersonDAO::getPersonByID($hasCharge->HC_personid);
    $charge = ChargeDAO::getChargeByID($hasCharge->HC_chargeid);

    if ($chargeEntry->CE_status != ChargeEntry::STATUS_PENDING &&
        $chargeEntry->CE_status != ChargeEntry::STATUS_PENDING_INSUFFICIENTFUNDS) {
        Core::redirect("index2.php?option=com_personaccount");
    }
    $chargeEntry->CE_baseamount = 0;
    $chargeEntry->CE_vat = 0;
    $chargeEntry->CE_amount = 0;
    $chargeEntry->CE_currency = $charge->CH_currency;
    $chargeEntry->CE_overdue = 0;
    $chargeEntry->CE_status = ChargeEntry::STATUS_TESTINGFREEOFCHARGE;

    $database->updateObject("chargeentry", $chargeEntry, "CE_chargeentryid", false, false);

    switch ($charge->CH_period) {
        case Charge::PERIOD_MONTHLY:
            $format = DateUtil::FORMAT_MONTHLY;
            break;
        default:
            $format = DateUtil::FORMAT_FULL;
    }
    $period = new DateUtil($chargeEntry->CE_period_date);

    $msg = sprintf(_("User's '%s' payment '%s' for period %s was excused"), $person->PE_firstname." ".$person->PE_surname, $charge->CH_name, $period->getFormattedDate($format));
    $appContext->insertMessage($msg);
    $database->log($msg, LOG::LEVEL_INFO);

    Core::redirect("index2.php?option=com_personaccount&task=showDetail&PE_personid=$hasCharge->HC_personid&hidemainmenu=1");
}
/**
 * @param integer $ceif ChargeEntryID
 */
function ignoreCharge($ceid) {
    global $database, $my, $acl, $appContext;

    $chargeEntry = ChargeEntryDAO::getChargeEntryByID($ceid);
    $hasCharge = HasChargeDAO::getHasChargeByID($chargeEntry->CE_haschargeid);
    $person = PersonDAO::getPersonByID($hasCharge->HC_personid);
    $charge = ChargeDAO::getChargeByID($hasCharge->HC_chargeid);

    if ($chargeEntry->CE_status != ChargeEntry::STATUS_PENDING &&
        $chargeEntry->CE_status != ChargeEntry::STATUS_PENDING_INSUFFICIENTFUNDS) {
        Core::redirect("index2.php?option=com_personaccount");
    }
    $chargeEntry->CE_baseamount = 0;
    $chargeEntry->CE_vat = 0;
    $chargeEntry->CE_amount = 0;
    $chargeEntry->CE_currency = $charge->CH_currency;
    $chargeEntry->CE_overdue = 0;
    $chargeEntry->CE_status = ChargeEntry::STATUS_DISABLED;

    $database->updateObject("chargeentry", $chargeEntry, "CE_chargeentryid", false, false);

    switch ($charge->CH_period) {
        case Charge::PERIOD_MONTHLY:
            $format = DateUtil::FORMAT_MONTHLY;
            break;
        default:
            $format = DateUtil::FORMAT_FULL;
    }
    $period = new DateUtil($chargeEntry->CE_period_date);

    $msg = sprintf(_("User's '%s' payment '%s' for period %s is ignored"), $person->PE_firstname." ".$person->PE_surname, $charge->CH_name, $period->getFormattedDate($format));
    $appContext->insertMessage($msg);
    $database->log($msg, LOG::LEVEL_INFO);

    Core::redirect("index2.php?option=com_personaccount&task=showDetail&PE_personid=$hasCharge->HC_personid&hidemainmenu=1");
}
/**
 * @param integer $ceif ChargeEntryID
 */
function removeCharge($ceid) {
    global $database, $my, $acl, $appContext;

    $chargeEntry = ChargeEntryDAO::getChargeEntryByID($ceid);
    $hasCharge = HasChargeDAO::getHasChargeByID($chargeEntry->CE_haschargeid);
    $person = PersonDAO::getPersonByID($hasCharge->HC_personid);
    $charge = ChargeDAO::getChargeByID($hasCharge->HC_chargeid);

    $personAccount = null;

    if ($chargeEntry->CE_status == ChargeEntry::STATUS_FINISHED) {
        $personAccount = PersonAccountDAO::getPersonAccountByID($person->PE_personaccountid);

        $refundedAmount = $chargeEntry->CE_amount;
        $personAccount->PA_balance += $refundedAmount;
        $personAccount->PA_outcome -= $refundedAmount;
    }

    try {
        $database->startTransaction();
        if ($personAccount != null) {
            $database->updateObject("personaccount", $personAccount, "PA_personaccountid", false, false);
        }
        ChargeEntryDAO::removeChargeEntryByID($chargeEntry->CE_chargeentryid);
        $database->commit();
    } catch (Exception $e) {
        $database->rollback();
        throw $e;
    }

    switch ($charge->CH_period) {
        case Charge::PERIOD_MONTHLY:
            $format = DateUtil::FORMAT_MONTHLY;
            break;
        default:
            $format = DateUtil::FORMAT_FULL;
    }
    $period = new DateUtil($chargeEntry->CE_period_date);

    $msg = sprintf(_("User's '%s' payment '%s' for period %s was removed"), $person->PE_firstname." ".$person->PE_surname, $charge->CH_name, $period->getFormattedDate($format));
    $appContext->insertMessage($msg);
    $database->log($msg, LOG::LEVEL_INFO);

    Core::redirect("index2.php?option=com_personaccount&task=showDetail&PE_personid=$hasCharge->HC_personid&hidemainmenu=1");
}
/**
 * @param integer $pid PersonID
 */
function editPersonAccountEntry($pid=null) {
    global $database, $my, $acl;
    $flags = array();
    $person = PersonDAO::getPersonByID($pid);
    $personAccount = PersonAccountDAO::getPersonAccountByID($person->PE_personaccountid);

    $personAccountEntry = new PersonAccountEntry();
    HTML_PersonAccount::editPersonAccountEntry($person, $personAccount, $personAccountEntry);
}
/**
 * @param integer $pid PersonID
 * @param String $task task
 */
function savePersonAccountEntry($pid, $task) {
    global $database, $mainframe, $my, $acl, $appContext;

    $person = PersonDAO::getPersonByID($pid);
    $personAccount = PersonAccountDAO::getPersonAccountByID($person->PE_personaccountid);

    $personAccountEntry = new PersonAccountEntry();
    database::bind($_POST, $personAccountEntry);

    try {
        $personAccountEntry->PN_amount = NumberFormat::parseMoney($personAccountEntry->PN_amount);
    } catch (Exception $e) {
        Core::alert(_("Money amount is in incorrect format"));
        $personAccountEntry->PN_amount = null;
        HTML_PersonAccount::editPersonAccountEntry($person, $personAccount, $personAccountEntry);
        return;
    }

    if ($personAccountEntry->PN_source == PersonAccountEntry::SOURCE_CASH) {
        $personAccount->PA_balance += $personAccountEntry->PN_amount;
        $personAccount->PA_income += $personAccountEntry->PN_amount;
    } else if ($personAccountEntry->PN_source == PersonAccountEntry::SOURCE_DISCOUNT) {
        $personAccount->PA_balance += $personAccountEntry->PN_amount;
    } else {
        throw new Exception("ERROR: unknown BankAccountEntry source");
    }

    try {
        $PN_date = new DateUtil();
        $PN_date->parseDate($personAccountEntry->PN_date, DateUtil::FORMAT_DATE);
    } catch (Exception $e) {
        Core::alert(_("Date is in incorrect format"));
        HTML_PersonAccount::editPersonAccountEntry($person, $personAccount, $personAccountEntry);
        return;
    }

    $personAccountEntry->PN_date = $PN_date->getFormattedDate(DateUtil::DB_DATE);
    $personAccountEntry->PN_personaccountid = $personAccount->PA_personaccountid;

    try {
        $database->startTransaction();
        $database->insertObject("personaccountentry", $personAccountEntry, "PN_personaccountentryid", false);
        $database->updateObject("personaccount", $personAccount, "PA_personaccountid", false, false);
        $database->commit();
    } catch (Exception $e) {
        $database->rollback();
        throw $e;
    }

    switch ($task) {
        default:
            $msg = sprintf(_("User's '%s' account was benefited with money amount '%s'"), $person->PE_firstname." ".$person->PE_surname, $personAccountEntry->PN_amount);
            $appContext->insertMessage($msg);
            $database->log($msg, LOG::LEVEL_INFO);

            Core::redirect("index2.php?option=com_personaccount&task=showDetail&PE_personid=$person->PE_personid&hidemainmenu=1");
            break;
    }
}
?>
