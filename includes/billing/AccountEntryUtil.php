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

global $core;
require_once($core->getAppRoot() . "includes/dao/BankAccountEntryDAO.php");
require_once($core->getAppRoot() . "includes/dao/PersonAccountDAO.php");
require_once($core->getAppRoot() . "includes/dao/PersonAccountEntryDAO.php");
require_once($core->getAppRoot() . "includes/dao/PersonDAO.php");

/**
 * AccountEntryUtil
 */
class AccountEntryUtil {
    private $_bankAccount;
    private $_messages;

    public function __construct($bankAccount) {
        $this->bankAccount = $bankAccount;
        $this->_messages = array();
    }
    function getMessages() {
        return $this->_messages;
    }
    public function proceedAccountEntries() {
        global $database;

        $bankAccountEntries = BankAccountEntryDAO::getBankAccountEntryArrayByBankAccountID($this->bankAccount->BA_bankaccountid);
        $persons = PersonDAO::getPersonWithAccountArray();

        foreach ($bankAccountEntries as $bankAccountEntry) {
            // Proceed only pending BankAccountEntry
            //
            if ($bankAccountEntry->BE_status != BankAccountEntry::STATUS_PENDING) {
                continue;
            }
            if ($bankAccountEntry->BE_typeoftransaction == BankAccountEntry::TYPE_DIFFENTTRANSACTIONCHARGE ||
                $bankAccountEntry->BE_typeoftransaction == BankAccountEntry::TYPE_POSITIVEINCREASE /*||
                $bankAccountEntry->BE_typeoftransaction == BankAccountEntry::TYPE_CASHDISPENCERDRAFT ||
                $bankAccountEntry->BE_typeoftransaction == BankAccountEntry::TYPE_BANKCARDPAYMENT*/) {

                $bankAccountEntry->BE_identifycode = BankAccountEntry::IDENTIFY_INTERNALTRANSACTION;
                $bankAccountEntry->BE_status = BankAccountEntry::STATUS_PROCESSED;
                $database->updateObject("bankaccountentry", $bankAccountEntry, "BE_bankaccountentryid", false, false);
                continue;
            }
            if ($bankAccountEntry->BE_variablesymbol) {
                // try find person by variable symbol
                //
                foreach ($persons as &$person) {
                    if (    $person->PE_status == Person::STATUS_ACTIVE &&
                            $person->PA_variablesymbol && $person->PA_variablesymbol == $bankAccountEntry->BE_variablesymbol &&
                            (!$person->PA_constantsymbol || $person->PA_constantsymbol == $bankAccountEntry->BE_constantsymbol) &&
                            (!$person->PA_specificsymbol || $person->PA_specificsymbol == $bankAccountEntry->BE_specificsymbol) ) {

                        // this payment is for this person and variable and-or constant and-or specific symbol matches
                        //
                        $bankAccountEntry->BE_identifycode = BankAccountEntry::IDENTIFY_PERSONACCOUNT;
                        $bankAccountEntry->BE_status = BankAccountEntry::STATUS_PROCESSED;
                        // new incomming payment for PersonAccount;
                        //
                        $personAccountEntry = new PersonAccountEntry();
                        $personAccountEntry->PN_bankaccountentryid =$bankAccountEntry->BE_bankaccountentryid;
                        $personAccountEntry->PN_personaccountid = $person->PA_personaccountid;
                        $personAccountEntry->PN_date = $bankAccountEntry->BE_datetime;
                        $personAccountEntry->PN_amount = $bankAccountEntry->BE_amount;
                        $personAccountEntry->PN_source = PersonAccountEntry::SOURCE_BANKACCOUNT;
                        $personAccountEntry->PN_comment = $bankAccountEntry->BE_message;

                        // Get PersonAccount from database and update balance
                        //
                        $personAccount = PersonAccountDAO::getPersonAccountByID($person->PA_personaccountid);
                        $personAccount->PA_balance += $personAccountEntry->PN_amount;
                        $personAccount->PA_income += $personAccountEntry->PN_amount;

                        try {
                            $database->startTransaction();
                            $database->updateObject("personaccount", $personAccount, "PA_personaccountid", false, false);
                            // Insert PersonAccountEntry into database
                            //
                            $database->insertObject("personaccountentry", $personAccountEntry, "PN_personaccountentryid", false);
                            // Update BankAccountEntry
                            //
                            $bankAccountEntry->BE_personaccountentryid = $personAccountEntry->PN_personaccountentryid;
                            $database->updateObject("bankaccountentry", $bankAccountEntry, "BE_bankaccountentryid", false, false);
                            $database->commit();
                            $this->_messages[] = "Platba z účtu $bankAccountEntry->BE_accountnumber/$bankAccountEntry->BE_banknumber, částka $bankAccountEntry->BE_amount identifikována od uživatele $person->PE_firstname $person->PE_surname";
                        } catch (Exception $e) {
                            $database->rollback();
                            $msg = "Platba z účtu $bankAccountEntry->BE_accountnumber/$bankAccountEntry->BE_banknumber, částka $bankAccountEntry->BE_amount identifikována od uživatele $person->PE_firstname $person->PE_surname nemohla být uložena: " . $e->getMessage();
                            $this->_messages[] = $msg;
                            $database->log($msg, LOG::LEVEL_ERROR);
                        }
                    }
                }
            }
        }
    }
} // End of AccountEntryUtil class
?>