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
require_once($core->getAppRoot() . "includes/dao/SessionDAO.php");
require_once($core->getAppRoot() . "includes/dao/PersonDAO.php");
require_once($core->getAppRoot() . "includes/dao/PersonAccountDAO.php");
require_once($core->getAppRoot() . "includes/dao/PersonAccountEntryDAO.php");
require_once($core->getAppRoot() . "includes/dao/GroupDAO.php");
require_once($core->getAppRoot() . "includes/dao/ChargeDAO.php");
require_once($core->getAppRoot() . "includes/dao/ChargeEntryDAO.php");
require_once($core->getAppRoot() . "includes/dao/HasChargeDAO.php");
require_once($core->getAppRoot() . "includes/dao/RoleDAO.php");
require_once($core->getAppRoot() . "includes/dao/RolememberDAO.php");
require_once($core->getAppRoot() . "includes/dao/IpDAO.php");
require_once($core->getAppRoot() . "includes/dao/IpAccountDAO.php");
require_once($core->getAppRoot() . "includes/dao/IpAccountAbsDAO.php");
require_once($core->getAppRoot() . "includes/dao/LogDAO.php");
require_once($core->getAppRoot() . "includes/dao/MessageDAO.php");
require_once($core->getAppRoot() . "includes/dao/NetworkDAO.php");
require_once($core->getAppRoot() . "includes/billing/ChargesUtil.php");
require_once('person.html.php');

$task = Utils::getParam($_REQUEST, 'task', null);
$pid = Utils::getParam($_REQUEST, 'PE_personid', null);
$cid = Utils::getParam($_REQUEST, 'cid', array());
$rmid = Utils::getParam($_REQUEST, 'RM_rolememberid', null);
$chid = Utils::getParam($_REQUEST, 'CH_chargeid', null);
$hcid = Utils::getParam($_REQUEST, 'HC_haschargeid', null);
$rid = Utils::getParam($_REQUEST, 'RO_roleid', null);

if (!is_array($cid)) {
    $cid = array (0);
}

switch ($task) {
    case 'new':
        editPerson(null);
        break;

    case 'cancelHasCharge':
    case 'edit':
        editPerson($pid);
        break;

    case 'editA':
        editPerson(intval($cid[0]));
        break;

    case 'save':
    case 'apply':
        savePerson($task);
        break;

    case 'remove':
        removePerson($cid);
        break;

    case 'cancel':
        showPerson();
        break;

    case 'addRole':
        addRole($pid, $rid);
        break;

    case 'removeRole':
        removeRole($pid, $rmid);
        break;

    case 'newHasCharge':
        editHasCharge(null, $chid, $pid);
        break;

    case 'editHasCharge':
        editHasCharge($hcid, null, $pid);
        break;

    case 'removeHasCharge':
        removeHasCharge($hcid);
        break;

    case 'saveHasCharge':
    case 'applyHasCharge':
        saveHasCharge($task);
        break;

    default:
        showPerson();
        break;
}

function showPerson() {
    global $database, $mainframe, $acl, $core;

    require_once($core->getAppRoot() . 'modules/com_common/PageNav.php');

    $filter = array();
    // default settings if no setting in session
    //
    $filter['search'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_person']['filter'], 'search', "");
    $filter['group'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_person']['filter'], 'group', 0);
    $filter['status'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_person']['filter'], 'status', -1);

    $limit = Utils::getParam($_SESSION['UI_SETTINGS']['com_person'], 'limit', 10);
    $limitstart = Utils::getParam($_SESSION['UI_SETTINGS']['com_person'], 'limitstart', 0);

    $total = PersonDAO::getPersonCount($filter['search'], $filter['group'], $filter['status']);
    $persons = PersonDAO::getPersonArray($filter['search'], $filter['group'], $filter['status'], $limitstart, $limit);
    $groups = GroupDAO::getGroupArray();

    $pageNav = new PageNav($total, $limitstart, $limit);
    HTML_person::showPersons($persons, $groups, $pageNav, $filter);
}

function editPerson($pid=null) {
    global $database, $my, $acl;

    if ($pid != null) {
        $person = PersonDAO::getPersonByID($pid);

        if ($person->PE_password != null) {
            $person->PE_password = "******";
        }
        // get roles for this person
        //
        $hasRoles = RolememberDAO::getRolememberAndRoleArrayByPersonID($pid);
        // get HasCharges for this person
        //
        $hasCharges = HasChargeDAO::getHasChargeWithChargeWithPersonArrayByPersonID($pid);
        // get Ips for this person
        //
        $hasIps = IpDAO::getIpArrayByPersonID($person->PE_personid);
    } else {
        $person = new Person();
        $person->PE_status = Person::STATUS_PASSIVE;
        $person->PE_birthdate = DateUtil::DB_NULL_DATE;
        $hasRoles = array();
        $hasCharges = array();
        $hasIps = array();
    }
    // get available groups
    //
    $groups = GroupDAO::getGroupArray();
    // get all available roles in database
    //
    $roles = RoleDAO::getRoleArray();
    // if user has role, unset it from available role array
    //
    foreach ($hasRoles as $hasRole) {
        unset($roles[$hasRole->RO_roleid]);
    }
    $charges = ChargeDAO::getChargeArray();

    HTML_Person::editPerson($person, $groups, $hasRoles, $hasIps, $roles, $charges, $hasCharges);
}

function savePerson($task) {
    global $core, $database, $mainframe, $my, $acl, $appContext;

    $person = new Person();
    database::bind($_POST, $person);
    $isNew 	= !$person->PE_personid;

    if (!$isNew) {
        $storedPerson = PersonDAO::getPersonByID($person->PE_personid);
    }

    $PE_password1 = trim(Utils::getParam($_POST, 'PE_password1', ""));
    $PE_password2 = trim(Utils::getParam($_POST, 'PE_password2', ""));

    $showAgain = false;

    if ($person->PE_username) {
        try {
        $findSameUsernamePerson = PersonDAO::getPersonWithGroupByUsername($person->PE_username);
            if ($findSameUsernamePerson->PE_personid != $person->PE_personid) {
                $core->alert(_("Username already exists"));

                $showAgain = true;
            }
        } catch (Exception $e) {}
    }

    if (($PE_password1 == "******" && $PE_password2 == "******") || ($PE_password1 == "" && $PE_password2 == "")) {
        $person->PE_password = null;
    } else if ($PE_password1 == $PE_password2) {
        $person->PE_password = md5($PE_password1);
    } else {
        $core->alert(_("User passwords are not same"));

        $showAgain = true;
    }

    if ($showAgain) {
        // show again the same form
        //
        if (!$isNew) {
            // get roles for this person
            //
            $hasRoles = RolememberDAO::getRolememberAndRoleArrayByPersonID($person->PE_personid);
            // get Ips for this person
            //
            $hasIps = IpDAO::getIpArrayByPersonID($person->PE_personid);
            // get HasCharges for this person
            //
            $hasCharges = HasChargeDAO::getHasChargeWithChargeWithPersonArrayByPersonID($person->PE_personid);

            if ($storedPerson->PE_password != null) {
                $person->PE_password = "******";
            } else {
                $person->PE_password = null;
            }
        } else {
            $hasRoles = array();
            $hasIps = array();
            $hasCharges = array();
        }
        // get available groups
        //
        $groups = GroupDAO::getGroupArray();
        // get all available roles in database
        //
        $roles = RoleDAO::getRoleArray();
        // 	if user has role, unset it from available role array
        //
        foreach ($hasRoles as $hasRole) {
            unset($roles[$hasRole->RO_roleid]);
        }

        $charges = ChargeDAO::getChargeArray();

        HTML_Person::editPerson($person, $groups, $hasRoles, $hasIps, $roles, $charges, $hasCharges);
        return;
    }

    try {
        $birthdate = new DateUtil();
        $birthdate->parseDate($person->PE_birthdate, DateUtil::FORMAT_DATE);
        $person->PE_birthdate = $birthdate->getFormattedDate(DateUtil::DB_DATE);
    } catch (Exception $e) {
        $person->PE_birthdate = DateUtil::DB_NULL_DATE;
    }

    if ($isNew) {
        $now = new DateUtil();
        $person->PE_registerdate = $now->getFormattedDate(DateUtil::DB_DATETIME);
        // create PersonAccount for Person

        $personAccount = new PersonAccount();
        $personAccount->PA_currency = "CZK";
        $personAccount->PA_startbalance = 0;
        $personAccount->PA_balance = 0;
        $personAccount->PA_income = 0;
        $personAccount->PA_outcome = 0;
        $personAccount->PA_variablesymbol = 0;
        $personAccount->PA_constantsymbol = 0;
        $personAccount->PA_specificsymbol = 0;

        try {
            $database->startTransaction();
            $database->insertObject("personaccount", $personAccount, "PA_personaccountid", false);
            $person->PE_personaccountid = $personAccount->PA_personaccountid;
            $database->insertObject("person", $person, "PE_personid", false);
            $database->commit();
        } catch (Exception $e) {
            $database->rollback();
            throw $e;
        }
    } else {
        $database->updateObject("person", $person, "PE_personid", false, false);
    }

    switch ($task) {
        case 'apply':
            $msg = sprintf(_("User '%s' updated"), $person->PE_firstname." ".$person->PE_surname);
            $appContext->insertMessage($msg);
            $database->log($msg, LOG::LEVEL_INFO);
            Core::redirect("index2.php?option=com_person&task=edit&PE_personid=$person->PE_personid&hidemainmenu=1");
        case 'save':
            $msg = sprintf(_("User '%s' saved"), $person->PE_firstname." ".$person->PE_surname);
            $appContext->insertMessage($msg);
            $database->log($msg, LOG::LEVEL_INFO);
        default:
            Core::redirect("index2.php?option=com_person");
    }
}

function removePerson($cid) {
    global $database, $mainframe, $my, $acl, $appContext;
    if (count($cid) < 1) {
        Core::backWithAlert(_("Please select record to erase"));
    }
    if (count($cid) ) {
        $deleted = array();
        foreach ($cid as $id) {
            // query person to be deleted
            //
            $person = PersonDAO::getPersonByID($id);
            $personAccount = PersonAccountDAO::getPersonAccountByID($person->PE_personaccountid);
            // query for person's network and alert when some
            //
            $networks = NetworkDAO::getNetworkArrayByPersonID($id);

            if (count($networks)) {
                $msg = sprintf(ngettext("Cannot delete user '%s' beacause it has associated %s network", "Cannot delete user '%s' beacause it has associated %s networks", count($networks)), $person->PE_firstname." ".$person->PE_surname, count($networks));
                $database->log($msg, LOG::LEVEL_WARNING);
                $limit = 10;
                foreach ($networks as $network) {
                    $msg .= "\\n'" . $network->NE_net . "'";
                    if (!--$limit) break;
                }
                if (count($networks) > $limit) $msg .= '\n...';
                Core::backWithAlert($msg);
            }

            $personAccountEntries = PersonAccountEntryDAO::getPersonAccountEntryArrayByPersonAccountID($person->PE_personaccountid);

            if (count($personAccountEntries)) {
                $msg = sprintf(ngettext("Cannot delete user '%s' beacause it has associated %s account entry", "Cannot delete user '%s' beacause it has associated %s account entries", count($personAccountEntries)), $person->PE_firstname." ".$person->PE_surname, count($personAccountEntries));
                $database->log($msg, LOG::LEVEL_WARNING);
                $limit = 10;
                foreach ($personAccountEntries as $personAccountEntry) {
                    $msg .= "\\n'" . $personAccountEntry->PN_date . " částka " . $personAccountEntry->PN_amount . "'";
                    if (!--$limit) break;
                }
                if (count($personAccountEntries) > $limit) $msg .= '\n...';
                Core::backWithAlert($msg);
            }

            try {
                $database->startTransaction();
                // remove all associated sessions
                //
                SessionDAO::removeSessionByPersonID($id);
                // remove all associated roles
                //
                RolememberDAO::removeRolemembersByPersonID($id);
                // remove all associated messages
                //
                MessageDAO::removeMessageByPersonID($id);
                // remove all associated logs
                //
                LogDAO::removeLogByPersonID($id);
                // remove IPs and ip accounts
                //
                $ips = IpDAO::getIpArrayByPersonID($id);
                foreach ($ips as $ip) {
                    IpDAO::removeIpByID($ip->IP_ipid);
                    IpAccountDAO::removeIpAccountByIPID($ip->IP_ipid);
                    IpAccountAbsDAO::removeIpAccountAbsByIPID($ip->IP_ipid);
                }
                // remove PersonAccount
                //
                PersonAccountDAO::removePersonAccountByID($person->PE_personaccountid);
                // delete person specified by its id
                //
                PersonDAO::removePersonByID($id);
                $database->commit();
            } catch (Exception $e) {
                $database->rollback();
                throw $e;
            }
            $msg = sprintf(_("User '%s' deleted"), $person->PE_firstname." ".$person->PE_surname);
            $appContext->insertMessage($msg);
            $database->log($msg, LOG::LEVEL_INFO);
        }
    }
    Core::redirect("index2.php?option=com_person");
}

function addRole($pid, $rid) {
    global $database, $mainframe, $my, $acl, $appContext;

    if ($pid == null) {
        savePerson('apply');
    }

    if ($rid != null) {
        $role = new Role();
        // query for person
        //
        $person = PersonDAO::getPersonByID($pid);
        // query for role
        //
        $role = RoleDAO::getRoleByID($rid);
        // create new membership to role
        //
        $rolemember = new Rolemember();
        $rolemember->RM_personid = $person->PE_personid;
        $rolemember->RM_roleid = $role->RO_roleid;
        // add role membership
        //
        $database->insertObject("rolemember", $rolemember, "RM_rolememberid", false);
        $msg = sprintf(_("Role '%s' has been added to user '%s'"), $role->RO_name, $person->PE_surname." ".$person->PE_firstname);
        $appContext->insertMessage($msg);
        $database->log($msg, LOG::LEVEL_INFO);
    }

    Core::redirect("index2.php?option=com_person&task=edit&PE_personid=$pid&hidemainmenu=1");
}

function removeRole($pid, $rmid) {
    global $database, $mainframe, $my, $acl, $appContext;

    if ($rmid != null) {
        $role = new Role();
        // get rolemember by ID
        //
        $rolemember = RolememberDAO::getRolememberByID($rmid);
        // get person
        //
        $person = PersonDAO::getPersonByID($pid);
        // get role
        //
        $role = RoleDAO::getRoleByID($rolemember->RM_roleid);

        RolememberDAO::removeRolemembersByID($rmid);
        $msg = sprintf(_("Role '%s' has been removed from user '%s'"), $role->RO_name, $person->PE_surname." ".$person->PE_firstname);
        $appContext->insertMessage($msg);
        $database->log($msg, LOG::LEVEL_INFO);
    }

    Core::redirect("index2.php?option=com_person&task=edit&PE_personid=$pid&hidemainmenu=1");
}
/**
 * function editHasCharge
 * @param $hcid Id of edited HasCharge entry
 * @param $chid Id of New Charge entry
 * @param $pid Id of person
 */
function editHasCharge($hcid=null, $chid=null, $pid=null) {
    global $database, $my, $acl;

    if ($pid == null) {
        savePerson('apply');
    }

    $person = PersonDAO::getPersonByID($pid);

    $status = array();
    $status['HC_datestart'] = false;
    $status['HC_dateend'] = false;

    if ($chid != null && $hcid == null) {
        // new HasCharge
        //
        $charge = ChargeDAO::getChargeByID($chid);
        $hasCharge = new HasCharge();
        $hasCharge->HC_chargeid = $charge->CH_chargeid;
        $hasCharge->HC_personid = $pid;
        $hasCharge->HC_status = HasCharge::STATUS_ENABLED;
        $status['HC_datestart'] = true;
        $status['HC_dateend'] = true;
    } else if ($chid == null && $hcid != null) {
        $hasCharge = HasChargeDAO::getHasChargeByID($hcid);
        $charge = ChargeDAO::getChargeByID($hasCharge->HC_chargeid);
        if ($pid != $hasCharge->HC_personid) {
            Core::redirect("index2.php?option=com_person");
        }
    } else {
        Core::redirect("index2.php?option=com_person");
    }
    HTML_Person::editHasCharge($person, $hasCharge, $charge, $status);
}
/**
 * function removeHasCharge
 * @param $hcid Id of edited HasCharge entry
 * @param $chid Id of New Charge entry
 * @param $pid Id of person
 */
function removeHasCharge($hcid=null) {
    global $database, $my, $acl, $appContext;

    $hasCharge = HasChargeDAO::getHasChargeByID($hcid);

    $person = PersonDAO::getPersonByID($hasCharge->HC_personid);
    $personAccount = PersonAccountDAO::getPersonAccountByID($person->PE_personaccountid);

    $charge = ChargeDAO::getChargeByID($hasCharge->HC_chargeid);

    $chargeEntries = ChargeEntryDAO::getChargeEntryArrayByHasChargeID($hasCharge->HC_haschargeid);

    try {
        $database->startTransaction();

        foreach ($chargeEntries as $chargeEntry) {
            if ($chargeEntry->CE_status == ChargeEntry::STATUS_FINISHED) {
                $refundedAmount = $chargeEntry->CE_amount;
                $personAccount->PA_balance += $refundedAmount;
                $personAccount->PA_outcome -= $refundedAmount;
            }

            ChargeEntryDAO::removeChargeEntryByID($chargeEntry->CE_chargeentryid);
        }

        HasChargeDAO::removeHasChargeByID($hasCharge->HC_haschargeid);

        $database->updateObject("personaccount", $personAccount, "PA_personaccountid", false, false);

        $database->commit();
    } catch (Exception $e) {
        $database->rollback();
        throw $e;
    }

    $msg = sprintf(_("Charge '%s' has been removed from user '%s'"), $charge->CH_name, $person->PE_surname." ".$person->PE_firstname);
    $appContext->insertMessage($msg);
    $database->log($msg, LOG::LEVEL_INFO);

    Core::redirect(sprintf("index2.php?option=com_person&task=edit&PE_personid=%s&hidemainmenu=1", $hasCharge->HC_personid));
}
/**
 * function editHasCharge
 * @param $hcid Id of edited HasCharge entry
 * @param $chid Id of New Charge entry
 * @param $pid Id of person
 */
function saveHasCharge($task) {
    global $database, $mainframe, $my, $acl, $appContext;

    $hasCharge = new HasCharge();
    database::bind($_POST, $hasCharge);
    $isNew 	= !$hasCharge->HC_haschargeid;

    $status = array();
    $status['HC_datestart'] = false;
    $status['HC_dateend'] = false;

    $person = PersonDAO::getPersonByID($hasCharge->HC_personid);
    $storedCharge = ChargeDAO::getChargeByID($hasCharge->HC_chargeid);
    if (!$isNew) {
        $storedHasCharge = HasChargeDAO::getHasChargeByID($hasCharge->HC_haschargeid);

        if ($storedCharge->CH_period == Charge::PERIOD_MONTHLY) {
            $dateEnd = new DateUtil();
            try {
                if ($hasCharge->HC_dateend != '') {
                    $dateEnd->parseDate($hasCharge->HC_dateend, DateUtil::FORMAT_MONTHLY);
                } else {
                    $dateEnd->setTime(null);
                }
            } catch (Exception $e) {
                $appContext->insertMessage(_("Date is in incorrect format"));
                HTML_Person::editHasCharge($person, $hasCharge, $storedCharge, $status);
                return;
            }
            $hasCharge->HC_datestart = null;
            $hasCharge->HC_dateend = $dateEnd->getFormattedDate(DateUtil::DB_DATE);
        }
    } else {
        $status['HC_datestart'] = true;
        $status['HC_dateend'] = true;
        if ($storedCharge->CH_period == Charge::PERIOD_MONTHLY) {
            $dateStart = new DateUtil();
            $dateEnd = new DateUtil();
            try {
                $dateStart->parseDate($hasCharge->HC_datestart, DateUtil::FORMAT_MONTHLY);
            } catch (Exception $e) {
                $appContext->insertMessage(_("Date is in incorrect format"));
                HTML_Person::editHasCharge($person, $hasCharge, $storedCharge, $status);
                return;
            }
            $hasCharge->HC_datestart = $dateStart->getFormattedDate(DateUtil::DB_DATE);
            try {
                if ($hasCharge->HC_dateend != '') {
                    $dateEnd->parseDate($hasCharge->HC_dateend, DateUtil::FORMAT_MONTHLY);
                } else {
                    $dateEnd->setTime(null);
                }
            } catch (Exception $e) {
                $appContext->insertMessage(_("Date is in incorrect format"));
                HTML_Person::editHasCharge($person, $hasCharge, $storedCharge, $status);
                return;
            }
            $hasCharge->HC_dateend = $dateEnd->getFormattedDate(DateUtil::DB_DATE);
        }
    }

    if ($isNew) {
        $database->insertObject("hascharge", $hasCharge, "HC_haschargeid", false);
    } else {
        $database->updateObject("hascharge", $hasCharge, "HC_haschargeid", false, false);
    }

    $chargesUtil = new ChargesUtil();
    $chargesUtil->createOrRemoveChargeEntriesForPerson($person, true, true);

    switch ($task) {
        case 'applyHasCharge':
            $msg = sprintf(_("Payment '%s' has been actualized for user '%s'"), $storedCharge->CH_name, "$person->PE_surname $person->PE_firstname");
            $appContext->insertMessage($msg);
            $appContext->insertMessages($chargesUtil->getMessages());
            $database->log($msg, LOG::LEVEL_INFO);
            Core::redirect("index2.php?option=com_person&hidemainmenu=1&task=editHasCharge&PE_personid=$hasCharge->HC_personid&HC_haschargeid=$hasCharge->HC_haschargeid");
        case 'saveHasCharge':
            $msg = sprintf(_("Payment '%s' has been saved for user '%s'"), $storedCharge->CH_name, "$person->PE_surname $person->PE_firstname");
            $appContext->insertMessage($msg);
            $appContext->insertMessages($chargesUtil->getMessages());
            $database->log($msg, LOG::LEVEL_INFO);
            Core::redirect("index2.php?option=com_person&hidemainmenu=1&task=edit&PE_personid=$hasCharge->HC_personid");
        default:
            Core::redirect("index2.php?option=com_person");
    }
}
?>
