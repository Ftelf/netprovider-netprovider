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
require_once($core->getAppRoot() . "includes/tables/BankAccountEntry.php");
require_once($core->getAppRoot() . "includes/tables/Group.php");
require_once($core->getAppRoot() . "includes/dao/BankAccountDAO.php");
require_once($core->getAppRoot() . "includes/dao/BankAccountEntryDAO.php");
require_once($core->getAppRoot() . "includes/dao/EmailListDAO.php");
require_once($core->getAppRoot() . "includes/EmailBankAccountList.php");
require_once($core->getAppRoot() . "includes/billing/AccountEntryUtil.php");
require_once('bankaccount.html.php');

$task = Utils::getParam($_REQUEST, 'task', null);
$bid = Utils::getParam($_REQUEST, 'BA_bankaccountid', null);
$eid = Utils::getParam($_REQUEST, 'BE_bankaccountentryid', null);
$lid = Utils::getParam($_REQUEST, 'EL_emaillistid', null);
$cid = Utils::getParam($_REQUEST, 'cid', array(0));
if (!is_array($cid)) {
    $cid = array (0);
}

switch ($task) {
//    case 'newBA':
//        editBankAccount(null);
//        break;

    case 'editBA':
        editBankAccount($bid);
        break;

    case 'saveBA':
    case 'applyBA':
        saveBankAccount($task);
        break;

    case 'cancelUploadBankList':
    case 'showBankList':
        showBankList($bid);
        break;

    case 'uploadBankLists':
        uploadBankLists($bid);
        break;

    case 'downloadBankLists':
        downloadBankLists($bid);
        break;

    case 'processBankLists':
        processBankLists($bid);
        break;

    case 'processEntries':
        proceedAccountEntries($bid);
        break;

    case 'doUploadBankLists':
        doUploadBankLists($bid);
        break;

//    case 'removeB':
//        removeGroup($cid);
//        break;

    case 'editBAE':
        editBankAccountEntry($eid);
        break;

    case 'editBAEA':
        editBankAccountEntries($cid);
        break;

    case 'saveBAE':
        saveBankAccountEntry($task);
        break;

    case 'saveBAEA':
        saveBankAccountEntries($cid, $task);
        break;

    case 'cancel':
        showBankAccount($bid);
        break;

    default:
        showBankAccount($bid);
        break;
}

function showBankAccount($bid = null) {
    global $database, $mainframe, $acl, $core;
    require_once($core->getAppRoot() . 'modules/com_common/PageNav.php');

    $filter = array();
    // get filters
    //
    if ($bid) {
        $_SESSION['UI_SETTINGS']['com_bankaccount']['filter']['BA_bankaccountid'] = $bid;
    } else {
        $bid = Utils::getParam($_SESSION['UI_SETTINGS']['com_bankaccount']['filter'], 'BA_bankaccountid', 0);
    }

    $filter['entryTypeOfTransaction'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_bankaccount']['filter'], 'entryTypeOfTransaction', -1);
    $filter['entryStatusOfTransaction'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_bankaccount']['filter'], 'entryStatusOfTransaction', -1);
    $filter['entryIdentifyCodeOfTransaction'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_bankaccount']['filter'], 'entryIdentifyCodeOfTransaction', -1);
    $filter['date_from'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_bankaccount']['filter'], 'date_from', null);
    $filter['date_to'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_bankaccount']['filter'], 'date_to', null);
    // get limits
    //
    $limit = Utils::getParam($_SESSION['UI_SETTINGS']['com_bankaccount'], 'limit', 10);
    $limitstart = Utils::getParam($_SESSION['UI_SETTINGS']['com_bankaccount'], 'limitstart', 0);
    // total count of bankAccounts
    //
    $bankAccounts = BankAccountDAO::getBankAccountArray();

    // get accountentries for this account
    //
    if ($bid == 0) {
        foreach ($bankAccounts as $k => $bankAccount) {
            $bid = $k;
            break;
        }
    }
    // compute bank account report
    //
    $report = array();
    $report['GLOBAL']['START'] = $bankAccounts[$bid]->BA_startbalance;
    $report['LIST']['START'] = "-";

    $report['GLOBAL']['INCOME'] = 0;
    $report['LIST']['INCOME'] = 0;

    $report['GLOBAL']['EXPENSE'] = 0;
    $report['LIST']['EXPENSE'] = 0;

    $report['GLOBAL']['CHARGE'] = 0;
    $report['LIST']['CHARGE'] = 0;

    $report['GLOBAL']['BALANCE'] = $bankAccounts[$bid]->BA_startbalance;
    $report['LIST']['BALANCE'] = "-";

    if (isset($bankAccounts[$bid])) {
        $allBankAccountEntries = BankAccountEntryDAO::getBankAccountEntryArrayByBankAccountID($bankAccounts[$bid]->BA_bankaccountid);
    }
    $dateFrom = new DateUtil();
    $dateTo = new DateUtil();
    try {
        $dateFrom->parseDate($filter['date_from'], DateUtil::FORMAT_DATE);
    } catch (Exception $e) {}
    try {
        $dateTo->parseDate($filter['date_to'], DateUtil::FORMAT_DATE);
        $dateTo->add(DateUtil::DAY, 1);
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

    $bankAccountEntries = array();
    foreach ($allBankAccountEntries as $k => $allBankAccountEntry) {
        if ($allBankAccountEntry->BE_amount > 0) $report['GLOBAL']['INCOME'] += $allBankAccountEntry->BE_amount;
        if ($allBankAccountEntry->BE_amount < 0) $report['GLOBAL']['EXPENSE'] += $allBankAccountEntry->BE_amount;
        $report['GLOBAL']['BALANCE'] += $allBankAccountEntry->BE_amount + $allBankAccountEntry->BE_charge;
        $report['GLOBAL']['CHARGE'] += $allBankAccountEntry->BE_charge;

        if ($filter['entryTypeOfTransaction'] != -1) {
            if ($allBankAccountEntry->BE_typeoftransaction != $filter['entryTypeOfTransaction']) continue;
        }
        if ($filter['entryStatusOfTransaction'] != -1) {
            if ($allBankAccountEntry->BE_status != $filter['entryStatusOfTransaction']) continue;
        }
        if ($filter['entryIdentifyCodeOfTransaction'] != -1) {
            if ($allBankAccountEntry->BE_identifycode != $filter['entryIdentifyCodeOfTransaction']) continue;
        }
        $dateTime = new DateUtil($allBankAccountEntry->BE_datetime);
        try {
            if ($dateFrom->getTime() != null && $dateTime->before($dateFrom)) continue;
            if ($dateTo->getTime() != null && $dateTime->after($dateTo)) continue;
        } catch (Exception $e) {}

        if ($allBankAccountEntry->BE_amount > 0) $report['LIST']['INCOME'] += $allBankAccountEntry->BE_amount;
        if ($allBankAccountEntry->BE_amount < 0) $report['LIST']['EXPENSE'] += $allBankAccountEntry->BE_amount;
        $report['LIST']['CHARGE'] += $allBankAccountEntry->BE_charge;
        $bankAccountEntries[$k] = $allBankAccountEntry;
    }

    $pageNav = new PageNav(count($bankAccountEntries), $limitstart, $limit);
    $bankAccountEntries = array_slice($bankAccountEntries, $limitstart, $limit);

    foreach ($bankAccountEntries as $k => &$bankAccountEntry) {
        if ($bankAccountEntry->BE_identifycode == BankAccountEntry::IDENTIFY_PERSONACCOUNT) {
            $identifyPersons = PersonAccountEntryDAO::getPersonNameArrayByBankAccountEntryID($bankAccountEntry->BE_bankaccountentryid);

            $bankAccountEntry->userAccountName = "";
            foreach ($identifyPersons as &$identifyPerson) {
                $bankAccountEntry->userAccountName .= $identifyPerson->PE_firstname . " " . $identifyPerson->PE_surname;
            }
        } else {
            $bankAccountEntry->userAccountName = "";
        }
    }

    HTML_BankAccount::showEntries($bankAccounts, $bid, $bankAccountEntries, $report, $filter, $pageNav);
}

function editBankAccount($bid=null) {
    global $database, $my, $acl;
    // flags to disable certain fields
    //
    $flags = array();
    if ($bid != null) {
        // edit, some fields will be disabled
        //
        $bankAccount = BankAccountDAO::getBankAccountByID($bid);
        $flags['BA_bankname'] = false;
        $flags['BA_banknumber'] = false;
        $flags['BA_accountname'] = false;
        $flags['BA_accountnumber'] = false;
        $flags['BA_iban'] = false;
        $flags['BA_currency'] = false;
        $flags['BA_startballance'] = false;
    } else {
        // new account
        //
        $bankAccount = new BankAccount();
        $bankAccount->BA_currency = "CZK";
        $bankAccount->BA_startballance = "0,00";
        $flags['BA_bankname'] = true;
        $flags['BA_banknumber'] = true;
        $flags['BA_accountname'] = true;
        $flags['BA_accountnumber'] = true;
        $flags['BA_iban'] = true;
        $flags['BA_currency'] = true;
        $flags['BA_startballance'] = true;
    }
    HTML_BankAccount::editBankAccount($bankAccount, $flags);
}
/**
 *
 */
function saveBankAccount($task) {
    global $database, $mainframe, $my, $acl, $appContext;

    $bankAccount = new BankAccount();
    database::bind($_POST, $bankAccount);

    $isNew = !$bankAccount->BA_bankaccountid;

    if ($isNew) {
        try {
            $bankAccount->BA_startbalance = NumberFormat::parseMoney($bankAccount->BA_startbalance);
        } catch (Exception $e) {
            Core::alert('Nesprávný fomát počátečního zůstatku');
            $flags = array();
            if ($isNew) {
                $flags['BA_bankname'] = true;
                $flags['BA_banknumber'] = true;
                $flags['BA_accountname'] = true;
                $flags['BA_accountnumber'] = true;
                $flags['BA_iban'] = true;
                $flags['BA_currency'] = true;
                $flags['BA_startbalance'] = true;
            } else {
                $flags['BA_bankname'] = false;
                $flags['BA_banknumber'] = false;
                $flags['BA_accountname'] = false;
                $flags['BA_accountnumber'] = false;
                $flags['BA_iban'] = false;
                $flags['BA_currency'] = false;
                $flags['BA_startbalance'] = false;
            }
            HTML_BankAccount::editBankAccount($bankAccount, $flags);
            return;
        }
        $database->insertObject("bankaccount", $bankAccount, "BA_bankaccountid", false);

        $accountName = $bankAccount->BA_bankname.": ".$bankAccount->BA_accountname;
        $accountNumber = $bankAccount->BA_accountnumber."/".$bankAccount->BA_banknumber;
    } else {
        $bankAccount->BA_bankname = null;
        $bankAccount->BA_banknumber = null;
        $bankAccount->BA_accountname = null;
        $bankAccount->BA_accountnumber = null;
        $bankAccount->BA_iban = null;
        $bankAccount->BA_currency = null;
        $bankAccount->BA_startbalance = null;
        $database->updateObject("bankaccount", $bankAccount, "BA_bankaccountid", false, false);

        $storedBankAccount = BankAccountDAO::getBankAccountByID($bankAccount->BA_bankaccountid);

        $accountName = $storedBankAccount->BA_bankname.": ".$storedBankAccount->BA_accountname;
        $accountNumber = $storedBankAccount->BA_accountnumber."/".$storedBankAccount->BA_banknumber;
    }

    switch ($task) {
        case 'applyBA':
            $msg = sprintf(_("Bank account '%s' '%s' updated"), $accountName, $accountNumber);
            $appContext->insertMessage($msg);
            $database->log($msg, Log::LEVEL_INFO);
            Core::redirect("index2.php?option=com_bankaccount&task=editBA&BA_bankaccountid=$bankAccount->BA_bankaccountid&hidemainmenu=1");
            break;
        case 'saveBA':
            $msg = sprintf(_("Bank account '%s' '%s' saved"), $accountName, $accountNumber);
            $appContext->insertMessage($msg);
            $database->log($msg, Log::LEVEL_INFO);
        default:
            Core::redirect("index2.php?option=com_bankaccount&task=show&BA_bankaccountid=$bankAccount->BA_bankaccountid");
            break;
    }
}
/**
 *
 */
function showBankList($bid) {
    global $database, $my, $acl, $core;
    require_once($core->getAppRoot() . 'modules/com_common/PageNav.php');

    // get limits
    //
    $limit = Utils::getParam($_SESSION['UI_SETTINGS']['com_bankaccount'], 'limit2', 10);
    $limitstart = Utils::getParam($_SESSION['UI_SETTINGS']['com_bankaccount'], 'limitstart2', 0);

    $bankAccount = BankAccountDAO::getBankAccountByID($bid);
    $emailLists = EmailListDAO::getEmailListArrayByBankAccountID($bid);

    if (count($emailLists) < $limitstart) {
        $limitstart = 0;
        $_SESSION['UI_SETTINGS']['com_bankaccount']['limitstart2'] = $limitstart;
    }

    $pageNav = new PageNav(count($emailLists), $limitstart, $limit, '2');

    $slicedEmailLists = array_slice($emailLists, $limitstart, $limit);

    HTML_BankAccount::showBankList($bankAccount, $slicedEmailLists, $pageNav);
}
/**
 *
 */
function uploadBankLists($bid) {
    global $database, $my, $acl, $appContext;

    if ($my->GR_level != Group::SUPER_ADMINISTRATOR) {
        $appContext->insertMessage(_("Insuficient rights"));
        Core::redirect("index2.php?option=com_bankaccount&task=showBankList&BA_bankaccountid=$bid&hidemainmenu=1");
    }

    $bankAccount = BankAccountDAO::getBankAccountByID($bid);

    HTML_BankAccount::uploadBankLists($bankAccount);
}
/**
 *
 */
function downloadBankLists($bid) {
    global $database, $my, $acl, $appContext;

    if ($my->GR_level != Group::SUPER_ADMINISTRATOR) {
        $appContext->insertMessage(_("Insuficient rights"));
        Core::redirect("index2.php?option=com_bankaccount&task=showBankList&BA_bankaccountid=$bid&hidemainmenu=1");
    }

    $bankAccount = BankAccountDAO::getBankAccountByID($bid);
    // Download new BankAccountLists
    //
    $emailBankAccountList = new EmailBankAccountList($bankAccount);

    try {
        $emailBankAccountList->downloadNewAccountLists();
        $appContext->insertMessages($emailBankAccountList->getMessages());

        $appContext->insertMessage(_('Downloading new printouts finished without errors'));
    } catch (Exception $e) {
        $msg = "Error proceeding bank account lists: " . $e->getMessage();
        $appContext->insertMessage($msg);
        $database->log($msg, Log::LEVEL_ERROR);
    }
    Core::redirect("index2.php?option=com_bankaccount&task=showBankList&BA_bankaccountid=$bid&hidemainmenu=1");
}
/**
 *
 */
function processBankLists($bid) {
    global $database, $my, $acl, $appContext;

    if ($my->GR_level != Group::SUPER_ADMINISTRATOR) {
        $appContext->insertMessage("Insuficient rights");
        Core::redirect("index2.php?option=com_bankaccount&task=showBankList&BA_bankaccountid=$bid&hidemainmenu=1");
    }

    $bankAccount = BankAccountDAO::getBankAccountByID($bid);

    // Import data from email listing
    try {
        $emailBankAccountList = new EmailBankAccountList($bankAccount);
        $emailBankAccountList->importBankAccountEntries();
        $appContext->insertMessages($emailBankAccountList->getMessages());

        $appContext->insertMessage(_('Import finished without errors'));
    } catch (Exception $e) {
        $msg = "Error importing bank account entries: " . $e->getMessage();
        $appContext->insertMessage($msg);
        $database->log($msg, Log::LEVEL_ERROR);
    }

    Core::redirect("index2.php?option=com_bankaccount&task=showBankList&BA_bankaccountid=$bid&hidemainmenu=1");
}
/**
 *
 */
function proceedAccountEntries($bid) {
    global $database, $my, $acl, $appContext;

    if ($my->GR_level != Group::SUPER_ADMINISTRATOR) {
        Core::redirect("index2.php?option=com_bankaccount&task=showBankList&BA_bankaccountid=$bid&hidemainmenu=1");
    }

    $bankAccount = BankAccountDAO::getBankAccountByID($bid);

    $accountEntryUtil = new AccountEntryUtil($bankAccount);

    try {
        $accountEntryUtil->proceedAccountEntries();
        $appContext->insertMessages($accountEntryUtil->getMessages());

        $appContext->insertMessage(_('Entries processed without errors'));
    } catch (Exception $e) {
        $msg = "Error proceeding bank account entries: " . $e->getMessage();
        $appContext->insertMessage($msg);
        $database->log($msg, Log::LEVEL_ERROR);
    }

    Core::redirect("index2.php?option=com_bankaccount&task=showBankList&BA_bankaccountid=$bid&hidemainmenu=1");
}
/**
 *
 */
function doUploadBankLists($bid) {
    global $database, $my, $acl, $appContext;

    if ($my->GR_level != Group::SUPER_ADMINISTRATOR) {
        $appContext->insertMessage(_("Insuficient rights"));
        Core::redirect("index2.php?option=com_bankaccount&task=showBankList&BA_bankaccountid=$bid&hidemainmenu=1");
    }

    $bankAccount = BankAccountDAO::getBankAccountByID($bid);
    // upload new BankAccountLists
    if ($bankAccount->BA_datasourcetype == BankAccount::DATASOURCE_TYPE_RB_ATTACHMENT_TXT) {
        $fileType = "text/plain";
    } else if ($bankAccount->BA_datasourcetype == BankAccount::DATASOURCE_TYPE_RB_ATTACHMENT_PDF) {
        $fileType = "application/pdf";
    } else if ($bankAccount->BA_datasourcetype == BankAccount::DATASOURCE_TYPE_ISO_SEPA_XML) {
        $fileType = "text/xml";
    } else {
        Core::redirect("index2.php?option=com_bankaccount&task=uploadBankLists&BA_bankaccountid=$bid&hidemainmenu=1");
    }

    if ($_FILES['banklistFile']['type'] != $fileType) {
        $msg = "Bank list file must be in '{$fileType}' format, found: '{$_FILES['banklistFile']['type']}'";
        $appContext->insertMessage($msg);
        $database->log($msg, Log::LEVEL_ERROR);
        Core::redirect("index2.php?option=com_bankaccount&task=uploadBankLists&BA_bankaccountid=$bid&hidemainmenu=1");
    }

    try {
        $fileContent = file_get_contents($_FILES['banklistFile']['tmp_name']);

        $emailBankAccountList = new EmailBankAccountList($bankAccount);
        $emailBankAccountList->uploadBankList($_FILES['banklistFile']['name'], $fileContent);
        $appContext->insertMessages($emailBankAccountList->getMessages());
    } catch (Exception $e) {
        $msg = "Error proceeding bank account lists: {$e->getMessage()}";
        $appContext->insertMessage($msg);
        $database->log($msg, Log::LEVEL_ERROR);
    }

    Core::redirect("index2.php?option=com_bankaccount&task=uploadBankLists&BA_bankaccountid=$bid&hidemainmenu=1");
}
/**
 *
 */
function editBankAccountEntry($eid=null) {
    global $database, $my, $acl;

    $bankAccountEntry = BankAccountEntryDAO::getBankAccountEntryByID($eid);
    // Do not allow edit proceeded entries
    //
    if ($bankAccountEntry->BE_status == BankAccountEntry::STATUS_PROCESSED) {
        Core::redirect("index2.php?option=com_bankaccount");
    }
    $persons = PersonDAO::getPersonWithAccountArray();

    HTML_BankAccount::editBankAccountEntry($bankAccountEntry, $persons);
}
/**
 *
 */
function editBankAccountEntries($cid=null) {
    global $database, $my, $acl;

    if (count($cid) === 1) {
        editBankAccountEntry(intval($cid[0]));
        return;
    }

    $bankAccountEntries = array();
    foreach ($cid as $id) {
        $eid = intval($id);
        $bankAccountEntry = BankAccountEntryDAO::getBankAccountEntryByID($eid);
        // Do not allow edit proceeded entries
        //
        if ($bankAccountEntry->BE_status == BankAccountEntry::STATUS_PROCESSED) {
            continue;
        }
        $bankAccountEntries[$eid] =  $bankAccountEntry;
    }

    if (!count($bankAccountEntries)) {
        Core::redirect("index2.php?option=com_bankaccount");
    }

    HTML_BankAccount::editBankAccountEntries($bankAccountEntries);
}
/**
 *
 */
function saveBankAccountEntry($task) {
    global $database, $mainframe, $my, $acl, $appContext;

    $bankAccountEntry = new BankAccountEntry();
    database::bind($_POST, $bankAccountEntry);

    $storedBankAccountEntry = BankAccountEntryDAO::getBankAccountEntryByID($bankAccountEntry->BE_bankaccountentryid);

    if ($bankAccountEntry->BE_status == BankAccountEntry::STATUS_PROCESSED) {
        Core::redirect("index2.php?option=com_bankaccount&task=show&BA_bankaccountid=$storedBankAccountEntry->BE_bankaccountid");
    }

    $bankAccountEntry->BE_status = BankAccountEntry::STATUS_PROCESSED;

    $dateTime = new DateUtil($storedBankAccountEntry->BE_datetime);

    if ($bankAccountEntry->BE_identifycode == BankAccountEntry::IDENTIFY_PERSONACCOUNT) {

        $personaccountids = Utils::getParam($_POST, 'PN_personaccountid', null);

        $sum = 0;
        foreach ($personaccountids as $k => $amount) {
            try {
                NumberFormat::parseMoney($amount);
            } catch (Exception $e) {
                $appContext->insertMessage(_("Amount must be in number format"));
                Core::redirect("index2.php?option=com_bankaccount&task=editBAE&BA_bankaccountid=$storedBankAccountEntry->BE_bankaccountid&BE_bankaccountentryid=$bankAccountEntry->BE_bankaccountentryid&hidemainmenu=1");
            }

            if ($amount < 0) {
                $appContext->insertMessage(_("Amount must be positive value"));
                Core::redirect("index2.php?option=com_bankaccount&task=editBAE&BA_bankaccountid=$storedBankAccountEntry->BE_bankaccountid&BE_bankaccountentryid=$bankAccountEntry->BE_bankaccountentryid&hidemainmenu=1");
            }

            $sum += $amount;
        }

        if ($storedBankAccountEntry->BE_amount != $sum) {
            $appContext->insertMessage(_("Amount sum doesn't match"));
            Core::redirect("index2.php?option=com_bankaccount&task=editBAE&BA_bankaccountid=$storedBankAccountEntry->BE_bankaccountid&BE_bankaccountentryid=$bankAccountEntry->BE_bankaccountentryid&hidemainmenu=1");
        }

        try {
            $database->startTransaction();
            foreach ($personaccountids as $personaccountid => $amount) {
                $personAccountEntry = new PersonAccountEntry();
                $personAccountEntry->PN_bankaccountentryid = $storedBankAccountEntry->BE_bankaccountentryid;
                $personAccountEntry->PN_personaccountid = $personaccountid;
                $personAccountEntry->PN_date = $storedBankAccountEntry->BE_datetime;
                $personAccountEntry->PN_amount = $amount;
                $personAccountEntry->PN_source = PersonAccountEntry::SOURCE_BANKACCOUNT;
                $personAccountEntry->PN_comment = $storedBankAccountEntry->BE_message;
                // Get PersonAccount from database and update balance
                //
                $personAccount = PersonAccountDAO::getPersonAccountByID($personaccountid);
                $personAccount->PA_balance += $personAccountEntry->PN_amount;
                $personAccount->PA_income += $personAccountEntry->PN_amount;

                $database->startTransaction();
                $database->updateObject("personaccount", $personAccount, "PA_personaccountid", false, false);
                // Insert PersonAccountEntry into database
                //
                $database->insertObject("personaccountentry", $personAccountEntry, "PN_personaccountentryid", false);
                // Update BankAccountEntry
                //
                $bankAccountEntry->BE_personaccountentryid = $personAccountEntry->PN_personaccountentryid;
                $database->updateObject("bankaccountentry", $bankAccountEntry, "BE_bankaccountentryid", false, false);

                $person = PersonDAO::getPersonByPersonAccountID($personAccount->PA_personaccountid);
                $msg = sprintf(_("Bank entry %s %s %s %s amount %s credited person account '%s' with %s"), $dateTime->getFormattedDate(DateUtil::FORMAT_DATE), $storedBankAccountEntry->BE_accountnumber."/".$storedBankAccountEntry->BE_banknumber, $storedBankAccountEntry->BE_accountname, $storedBankAccountEntry->BE_message, $storedBankAccountEntry->BE_amount, $person->PE_firstname." ".$person->PE_surname, $personAccountEntry->PN_amount);
                $appContext->insertMessage($msg);
                $database->log($msg, Log::LEVEL_INFO);
            }
            $database->commit();
        } catch (Exception $e) {
            $database->rollback();
            throw $e;
        }
    } else {
        $database->updateObject("bankaccountentry", $bankAccountEntry, "BE_bankaccountentryid", false, false);
    }

    switch ($task) {
        default:
            $msg = sprintf(_("Bank entry %s %s %s %s amount %s proceed"), $dateTime->getFormattedDate(DateUtil::FORMAT_DATE), $storedBankAccountEntry->BE_accountnumber."/".$storedBankAccountEntry->BE_banknumber, $storedBankAccountEntry->BE_accountname, $storedBankAccountEntry->BE_message, $storedBankAccountEntry->BE_amount);
            $appContext->insertMessage($msg);
            $database->log($msg, Log::LEVEL_INFO);
            Core::redirect("index2.php?option=com_bankaccount&task=show&BA_bankaccountid=$storedBankAccountEntry->BE_bankaccountid");
            break;
    }
}
/**
 *
 */
function saveBankAccountEntries($cid, $task) {
    global $database, $mainframe, $my, $acl, $appContext;

    $ic = Utils::getParam($_POST, 'BE_identifycode', null);


    foreach ($cid as $id) {
        $eid = intval($id);
        $bankAccountEntry = BankAccountEntryDAO::getBankAccountEntryByID($eid);
        // Do not allow edit proceeded entries
        //
        if ($bankAccountEntry->BE_status == BankAccountEntry::STATUS_PROCESSED) {
            continue;
        }
        $bankAccountEntry->BE_status = BankAccountEntry::STATUS_PROCESSED;
        $bankAccountEntry->BE_identifycode = $ic;

        $database->updateObject("bankaccountentry", $bankAccountEntry, "BE_bankaccountentryid", false, false);

        $dateTime = new DateUtil($bankAccountEntry->BE_datetime);
        $msg = sprintf(_("Bank entry %s %s %s %s amount %s proceed"), $dateTime->getFormattedDate(DateUtil::FORMAT_DATE), $bankAccountEntry->BE_accountnumber."/".$bankAccountEntry->BE_banknumber, $bankAccountEntry->BE_accountname, $bankAccountEntry->BE_message, $bankAccountEntry->BE_amount);
        $appContext->insertMessage($msg);
        $database->log($msg, Log::LEVEL_INFO);

        $BA_bankaccountid = $bankAccountEntry->BE_bankaccountid;
    }

    switch ($task) {
        default:
            Core::redirect("index2.php?option=com_bankaccount&task=show&BA_bankaccountid=$BA_bankaccountid");
            break;
    }
}
?>
