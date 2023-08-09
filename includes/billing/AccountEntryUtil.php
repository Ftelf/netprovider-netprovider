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

global $core;
require_once $core->getAppRoot() . "includes/dao/BankAccountEntryDAO.php";
require_once $core->getAppRoot() . "includes/dao/PersonAccountDAO.php";
require_once $core->getAppRoot() . "includes/dao/PersonAccountEntryDAO.php";
require_once $core->getAppRoot() . "includes/dao/PersonDAO.php";
require_once $core->getAppRoot() . "includes/dao/ChargeDAO.php";
require_once $core->getAppRoot() . "includes/net/email/EmailUtil.php";

/**
 * AccountEntryUtil
 */
class AccountEntryUtil
{
    private $_bankAccount;
    private array $_messages;

    private $emailUtil;

    public function __construct($bankAccount)
    {
        $this->_bankAccount = $bankAccount;
        $this->_messages = [];
        $this->emailUtil = new EmailUtil();
    }

    public function getMessages(): array
    {
        return $this->_messages;
    }

    public function proceedAccountEntries(): void
    {
        global $database, $core;

        $bankAccountEntries = BankAccountEntryDAO::getBankAccountEntryArrayByBankAccountID($this->_bankAccount->BA_bankaccountid);
        $chargeEntries = ChargeDAO::getChargeArray();

        foreach ($bankAccountEntries as $bankAccountEntry) {
            // Proceed only pending BankAccountEntry
            if ($bankAccountEntry->BE_status != BankAccountEntry::STATUS_PENDING) {
                continue;
            }
            if ($bankAccountEntry->BE_typeoftransaction == BankAccountEntry::TYPE_DIFFENTTRANSACTIONCHARGE || $bankAccountEntry->BE_typeoftransaction == BankAccountEntry::TYPE_POSITIVEINCREASE) {
                $bankAccountEntry->BE_identifycode = BankAccountEntry::IDENTIFY_INTERNALTRANSACTION;
                $bankAccountEntry->BE_status = BankAccountEntry::STATUS_PROCESSED;
                $database->updateObject("bankaccountentry", $bankAccountEntry, "BE_bankaccountentryid", false);

                continue;
            }
            if (empty($bankAccountEntry->BE_variablesymbol)) {
                continue;
            }

            $dateString = (new DateUtil($bankAccountEntry->BE_datetime))->getFormattedDate(DateUtil::FORMAT_DATE);

            // try find person by variable symbol
            $persons = PersonDAO::getPersonWithAccountArrayForAccounting($bankAccountEntry->BE_variablesymbol, $bankAccountEntry->BE_constantsymbol, $bankAccountEntry->BE_specificsymbol);

            if (count($persons) > 1) {
                $concatenatedArray = array_map(function($person) {
                    return "$person->PE_firstname $person->PE_surname";
                }, $persons);
                $userNames = implode(', ', $concatenatedArray);

                $message = "Platba příchozí: $dateString, z účtu: $bankAccountEntry->BE_accountnumber/$bankAccountEntry->BE_banknumber, jméno účtu: $bankAccountEntry->BE_accountname, částka: $bankAccountEntry->BE_amount, variabilní symbol: $bankAccountEntry->BE_variablesymbol identifikována duplicitním uživatelům: $userNames";
                $this->_messages[] = $message;
                $this->emailUtil->sendEmailMessage($core->getProperty(Core::SUPERVISOR_EMAIL), "Příchozí platba duplicitním uživatelům", $message);

                continue;
            }
            if (empty($persons)) {
                foreach ($chargeEntries as $charge) {
                    if ($bankAccountEntry->BE_amount == $charge->CH_amount) {
                        $message = "Platba příchozí: $dateString, z účtu: $bankAccountEntry->BE_accountnumber/$bankAccountEntry->BE_banknumber, jméno účtu: $bankAccountEntry->BE_accountname, částka: $bankAccountEntry->BE_amount, variabilní symbol: $bankAccountEntry->BE_variablesymbol nebyla nikomu přiřazena. Pravděpodobně platba za: $charge->CH_name";
                        $this->_messages[] = $message;
                        $this->emailUtil->sendEmailMessage($core->getProperty(Core::SUPERVISOR_EMAIL), "Neznámá příchozí platba", $message);

                        break;
                    }
                }

                continue;
            }

            $person = $persons[array_key_first($persons)];

            if ($person->PE_status != Person::STATUS_ACTIVE) {
                $message = "Platba příchozí: $dateString, z účtu: $bankAccountEntry->BE_accountnumber/$bankAccountEntry->BE_banknumber, jméno účtu: $bankAccountEntry->BE_accountname, částka: $bankAccountEntry->BE_amount, variabilní symbol: $bankAccountEntry->BE_variablesymbol identifikována od neaktivního uživatele: $person->PE_firstname $person->PE_surname";
                $this->_messages[] = $message;
                $this->emailUtil->sendEmailMessage($core->getProperty(Core::SUPERVISOR_EMAIL), "Příchozí platba neaktivnímu uživateli", $message);

                continue;
            }

            // this payment is for this person and variable and-or constant and-or specific symbol matches
            $bankAccountEntry->BE_identifycode = BankAccountEntry::IDENTIFY_PERSONACCOUNT;
            $bankAccountEntry->BE_status = BankAccountEntry::STATUS_PROCESSED;

            // new incoming payment for PersonAccount;
            $personAccountEntry = new PersonAccountEntry();
            $personAccountEntry->PN_bankaccountentryid = $bankAccountEntry->BE_bankaccountentryid;
            $personAccountEntry->PN_personaccountid = $person->PA_personaccountid;
            $personAccountEntry->PN_date = $bankAccountEntry->BE_datetime;
            $personAccountEntry->PN_amount = $bankAccountEntry->BE_amount;
            $personAccountEntry->PN_source = PersonAccountEntry::SOURCE_BANKACCOUNT;
            $personAccountEntry->PN_comment = $bankAccountEntry->BE_message;

            // Get PersonAccount from database and update balance
            $personAccount = PersonAccountDAO::getPersonAccountByID($person->PA_personaccountid);
            $personAccount->PA_balance += $personAccountEntry->PN_amount;
            $personAccount->PA_income += $personAccountEntry->PN_amount;

            try {
                $database->startTransaction();
                $database->updateObject("personaccount", $personAccount, "PA_personaccountid", false);

                // Insert PersonAccountEntry into database
                $database->insertObject("personaccountentry", $personAccountEntry, "PN_personaccountentryid");

                // Update BankAccountEntry
                $bankAccountEntry->BE_personaccountentryid = $personAccountEntry->PN_personaccountentryid;
                $database->updateObject("bankaccountentry", $bankAccountEntry, "BE_bankaccountentryid", false);
                $database->commit();
                $this->_messages[] = "Platba příchozí: $dateString, z účtu: $bankAccountEntry->BE_accountnumber/$bankAccountEntry->BE_banknumber, částka: $bankAccountEntry->BE_amount identifikována od uživatele: $person->PE_firstname $person->PE_surname";
            } catch (Exception $e) {
                $database->rollback();
                $msg = "Platba příchozí: $dateString, z účtu: $bankAccountEntry->BE_accountnumber/$bankAccountEntry->BE_banknumber, částka: $bankAccountEntry->BE_amount identifikována od uživatele: $person->PE_firstname $person->PE_surname nemohla být uložena: " . $e->getMessage();
                $this->_messages[] = $msg;
                $database->log($msg, Log::LEVEL_ERROR);
            }
        }
    }
} // End of AccountEntryUtil class
