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
require_once 'Net/POP3.php';
require_once $core->getAppRoot() . "includes/utils/NumberFormat.php";
require_once $core->getAppRoot() . "includes/dao/BankAccountDAO.php";
require_once $core->getAppRoot() . "includes/dao/BankAccountEntryDAO.php";
require_once $core->getAppRoot() . "includes/dao/EmailListDAO.php";
require_once $core->getAppRoot() . "includes/net/email/MimeDecode.php";
require_once $core->getAppRoot() . "includes/billing/bankParser/BankParserFactory.php";

/**
 * EmailBankAccountList
 * Provides connection to pop3 server and downloads bank lists
 */
class EmailBankAccountList
{
    private $_bankAccount;
    private $_messages = [];
    private $_dbEmailListNamesMap;

    /**
     * Constructor
     */
    public function __construct($bankAccount)
    {
        global $database;
        $this->_bankAccount = $bankAccount;

        // Fill array of list already in database
        $dbEmailLists = EmailListDAO::getEmailListArrayByBankAccountID($this->_bankAccount->BA_bankaccountid);
        $dbEmailListNames = array_column($dbEmailLists, 'EL_name');

        $countedEmailListNames = array_count_values(array_map('strtolower', $dbEmailListNames));
        $onlyDuplicates = array_filter(
            $countedEmailListNames, function ($count) {
                return $count > 1;
            }, ARRAY_FILTER_USE_BOTH
        );

        $msg = '';
        foreach ($onlyDuplicates as $duplicateFilename => $count) {
            $msg .= "V databazi jsou duplikovane zaznamy bankovniho vypisu: '{$duplicateFilename}' v počtu: '{$count}'\n";
        }
        if ($msg) {
            throw new Exception($msg);
        }

        $this->_dbEmailListNamesMap = array_combine(array_map('strtolower', $dbEmailListNames), $dbEmailListNames);


    }

    function getMessages()
    {
        return $this->_messages;
    }

    /**
     * updateAccountListArray
     * will download EmailList from email server and insert them into database
     */
    function downloadNewAccountLists()
    {
        global $database, $core;

        $_pop3 = new Net_POP3();

        // Connect to localhost on usual port
        if (!$_pop3->connect($this->_bankAccount->BA_emailserver, 110)) {
            throw new Exception("Cannot connect to pop3 server " . $this->_bankAccount->BA_emailserver);
        }
        if (($err = $_pop3->login($this->_bankAccount->BA_emailusername, $this->_bankAccount->BA_emailpassword)) !== true) {
            throw new Exception("Cannot login to server: '{$this->_bankAccount->BA_emailserver}', error: {$err}");
        }

        // get email list on server
        $msgs = $_pop3->getListing();
        foreach ($msgs as $msgi) {
            $email = $_pop3->getMsg($msgi['msg_id']);
            $params = array(
                'input' => $email,
                'crlf' => "\r\n",
                'include_bodies' => true,
                'decode_headers' => true,
                'decode_bodies' => true,
                'output_encoding' => 'UTF-8//IGNORE'
            );
            $decode = new Mail_mimeDecode($email);
            $msgDecoded = $decode->decode($params);

            // EmailList is identified by Sender and Subject
            // if FROM and SUBJECT matches
            //
            if (stripos($msgDecoded->headers['from'], $this->_bankAccount->BA_emailsender) !== false
                && stripos($msgDecoded->headers['subject'], $this->_bankAccount->BA_emailsubject) !== false
            ) {

                foreach ($msgDecoded->parts as $part) {
                    if (isset($part->ctype_parameters) && isset($part->ctype_parameters['name'])) {
                        $filename = $part->ctype_parameters['name'];

                        $emailList = new EmailList();
                        if ($this->_bankAccount->BA_datasourcetype == BankAccount::DATASOURCE_TYPE_RB_ATTACHMENT_TXT) {
                            if ($this->processRbTxt($filename, $part->body, $emailList) === false) {
                                continue;
                            }
                        } else if ($this->_bankAccount->BA_datasourcetype == BankAccount::DATASOURCE_TYPE_RB_ATTACHMENT_PDF) {
                            if ($this->processRbPdf($filename, $part->body, $emailList) === false) {
                                continue;
                            }
                        } else if ($this->_bankAccount->BA_datasourcetype == BankAccount::DATASOURCE_TYPE_ISO_SEPA_XML) {
                            if ($this->processIsoSepaXml($filename, $part->body, $emailList) === false) {
                                continue;
                            }
                        } else {
                            continue;
                        }

                        $this->checkAndStoreEmailList($filename, $emailList, $isAlreadyPersisted);
                    }
                }
            }
        }

        // Validate email lists for continuity
        $listYears = EmailListDAO::getEmailListYears();

        $listYears2 = array_column($listYears, 'EL_year');
        foreach ($listYears2 as $year) {
            $listNames = EmailListDAO::getEmailListNamesByYear($year);

            $previousListName = null;
            foreach ($listNames as $listName) {
                if (!$previousListName) {
                    $previousListName = $listName;
                    continue;
                }

                if ($listName->EL_no - $previousListName->EL_no === 1) {
                    $previousListName = $listName;
                    continue;
                }

                $msg = "V databázi chybí výpis mezi: {$previousListName->EL_name} a {$listName->EL_name}";
                $this->_messages[] = $msg;
                $database->log($msg, Log::LEVEL_ERROR);

                if ($core->getProperty(Core::SEND_EMAIL_ON_CRITICAL_ERROR)) {
                    $emailUtil = new EmailUtil();
                    $emailUtil->sendEmailMessage($core->getProperty(Core::SUPERVISOR_EMAIL), 'Net provider error', $msg);
                }

                $previousListName = $listName;
            }
        }
    }

    /**
     * uploadBankList
     * will upload EmailList from file and insert them into database
     */
    function uploadBankList($filename, $fileContent)
    {
        global $database;

        $emailList = new EmailList();
        if ($this->_bankAccount->BA_datasourcetype == BankAccount::DATASOURCE_TYPE_RB_ATTACHMENT_TXT) {
            if ($this->processRbTxt($filename, $fileContent, $emailList) === false) {
                return;
            }
        } else if ($this->_bankAccount->BA_datasourcetype == BankAccount::DATASOURCE_TYPE_RB_ATTACHMENT_PDF) {
            if ($this->processRbPdf($filename, $fileContent, $emailList) === false) {
                return;
            }
        } else if ($this->_bankAccount->BA_datasourcetype == BankAccount::DATASOURCE_TYPE_ISO_SEPA_XML) {
            if ($this->processIsoSepaXml($filename, $fileContent, $emailList) === false) {
                return;
            }
        } else {
            $msg = "List: neplatný název přílohy: '{$filename}'";
            $this->_messages[] = $msg;
            $database->log($msg, Log::LEVEL_ERROR);
        }

        $this->checkAndStoreEmailList($filename, $emailList, $isAlreadyPersisted);

        if ($isAlreadyPersisted) {
            $msg = "Výpis: '{$emailList->EL_name}' je již uložen v databázi";
            $this->_messages[] = $msg;
            $database->log($msg, Log::LEVEL_INFO);
        }
    }

    /**
     * Imports BankAccountEntries
     */
    function importBankAccountEntries()
    {
        global $database;

        $emailLists = EmailListDAO::getEmailListArrayByBankAccountID($this->_bankAccount->BA_bankaccountid);

        foreach ($emailLists as &$emailList) {
            // list is pending, no error during parsing
            //
            if ($emailList->EL_status == EmailList::STATUS_PENDING || $emailList->EL_status == EmailList::STATUS_ERROR) {
                // Parse list
                //
                $bankParserFactory = new BankParserFactory($this->_bankAccount->BA_datasourcetype, $emailList->EL_list);
                try {
                    $bankParserFactory->parse();
                } catch (Exception $e) {
                    $emailList->EL_status = EmailList::STATUS_ERROR;
                    $database->updateObject("emaillist", $emailList, "EL_emaillistid", false, false);
                    $msg = "Výpis $emailList->EL_name nemohl být zpársován";
                    $this->_messages[] = $msg;
                    $database->log($msg, Log::LEVEL_ERROR);
                    continue;
                }
                $parsedList = $bankParserFactory->getDocument();
                // insert BankAccountEntries into database
                try {
                    $database->startTransaction();
                    foreach ($parsedList['LIST'] as &$bankAccountEntry) {
                        $bankAccountEntry->BE_bankaccountid = $this->_bankAccount->BA_bankaccountid;
                        $database->insertObject("bankaccountentry", $bankAccountEntry, "BE_bankaccountentryid", false);
                    }
                    unset($bankAccountEntry);
                    $emailList->EL_status = EmailList::STATUS_COMPLETED;
                    $emailList->EL_entrycount = count($parsedList['LIST']);
                    $database->updateObject("emaillist", $emailList, "EL_emaillistid", false, false);
                    $database->commit();

                    $msg = "Položky výpisu $emailList->EL_name úspěšně importovány";
                    $this->_messages[] = $msg;
                    $database->log($msg, Log::LEVEL_INFO);
                } catch (Exception $e) {
                    $database->rollback();
                    $emailList->EL_status = EmailList::STATUS_ERROR;
                    $database->updateObject("emaillist", $emailList, "EL_emaillistid", false, false);

                    $msg = "ERROR: Položky výpisu $emailList->EL_name nebyly importovány: " . $e->getMessage();
                    $this->_messages[] = $msg;
                    $database->log($msg, Log::LEVEL_ERROR);
                }
            }
        }
        unset($emailList);
    }

    function processRbTxt($filename, $fileContent, $emailList)
    {
        global $database;
        $matches = null;
        if (mb_eregi('^([[:digit:]]{5})_([[:digit:]]{6,20})_([[:alpha:]]+)\.TXT$', $filename, $matches)) {
            $idNo = $matches[1];
            $accountNumber = $matches[2];
            $currency = $matches[3];

            $emailList->EL_year = NumberFormat::parseInteger(mb_substr($idNo, 0, 2)) + 2000;
            $emailList->EL_no = mb_substr($idNo, 2, 3);
        } else {
            $msg = "List: neplatný název přílohy: '{$filename}'";
            $this->_messages[] = $msg;
            $database->log($msg, Log::LEVEL_WARNING);
            return false;
        }

        $emailList->EL_list = $fileContent;
        $emailList->EL_listtype = EmailList::LISTTYPE_TXT;
        $emailList->EL_name = $filename;

        return $this->validateListValues($database, $emailList, $filename, $accountNumber, $currency);
    }

    function processRbPdf($filename, $fileContent, $emailList)
    {
        global $database;
        $matches = null;
        if (mb_eregi('^Vypis_([[:digit:]]{6,20})_([[:alpha:]]+)_([[:digit:]]{4})_([[:digit:]]{1,3})\.PDF$', $filename, $matches)) {
            $accountNumber = $matches[1];
            $currency = $matches[2];
            $year = $matches[3];
            $no = $matches[4];

            $emailList->EL_year = NumberFormat::parseInteger($year);
            $emailList->EL_no = $no;

        } else {
            $msg = "List: neplatný název přílohy: '{$filename}'";
            $this->_messages[] = $msg;
            $database->log($msg, Log::LEVEL_ERROR);
            return false;
        }

        $emailList->EL_list = $fileContent;
        $emailList->EL_listtype = EmailList::LISTTYPE_PDF;
        $emailList->EL_name = $filename;

        return $this->validateListValues($database, $emailList, $filename, $accountNumber, $currency);
    }

    function processIsoSepaXml($filename, $fileContent, $emailList)
    {
        global $database;
        $matches = null;
        if (mb_eregi('^Vypis_([[:digit:]]{6,20})_([[:alpha:]]+)_([[:digit:]]{4})_([[:digit:]]{1,3})\.XML.ZIP$', $filename, $matches)) {
            if (!$this->unzipFile($fileContent, $filename, $emailList->EL_list, $emailList->EL_name)) {
                $msg = "List: Cannot unzip file: '{$filename}'";
                $this->_messages[] = $msg;
                $database->log($msg, Log::LEVEL_ERROR);
                return false;
            }
            $accountNumber = $matches[1];
            $currency = $matches[2];
            $emailList->EL_year = NumberFormat::parseInteger($matches[3]);
            $emailList->EL_no = $matches[4];
        } else if (mb_eregi('^Vypis_([[:digit:]]{6,20})_([[:alpha:]]+)_([[:digit:]]{4})_([[:digit:]]{1,3})\.XML$', $filename, $matches)) {
            $emailList->EL_name = $filename;
            $emailList->EL_list = $fileContent;
            $accountNumber = $matches[1];
            $currency = $matches[2];
            $emailList->EL_year = NumberFormat::parseInteger($matches[3]);
            $emailList->EL_no = $matches[4];
        } else {
            $msg = "List: neplatný název přílohy: '{$filename}'";
            $this->_messages[] = $msg;
            $database->log($msg, Log::LEVEL_ERROR);
            return false;
        }

        $emailList->EL_listtype = EmailList::LISTTYPE_SEPA_XML;

        return $this->validateListValues($emailList, $filename, $accountNumber, $currency);
    }

    function validateListValues($emailList, $filename, $accountNumber, $currency)
    {
        global $database;
        $msg = '';
        if ($emailList->EL_list === null) {
            $msg .= "List: bankovní výpis je prázdný: '{$filename}'\n";
        }
        if ($this->_bankAccount->BA_accountnumber != $accountNumber) {
            $msg .= "List: Číslo bankovního konta výpisu: '${filename}' nesouhlasí, '{$this->_bankAccount->BA_accountnumber}' != '{$accountNumber}'\n";
        }
        if ($this->_bankAccount->BA_currency != $currency) {
            $msg .= "List: Měna výpisu: '${filename}' nesouhlasí,  '{$this->_bankAccount->BA_currency}' != '{$currency}'";
        }

        if ($msg) {
            $this->_messages[] = $msg;
            $database->log($msg, Log::LEVEL_ERROR);
            return false;
        }

        return true;
    }

    function checkAndStoreEmailList($filename, $emailList, &$isAlreadyPersisted)
    {
        global $database, $core;
        $emailList->EL_bankaccountid = $this->_bankAccount->BA_bankaccountid;

        $bankParserFactory = new BankParserFactory($this->_bankAccount->BA_datasourcetype, $emailList->EL_list);
        try {
            $bankParserFactory->parse();

            $parsedList = $bankParserFactory->getDocument();
            if ($this->_bankAccount->BA_accountnumber != $parsedList['ACCOUNT_NUMBER']) {
                throw new Exception("Čísla bankovního konta nesouhlasí: {$this->_bankAccount->BA_accountnumber} != {$parsedList['ACCOUNT_NUMBER']}, výpis: '{$filename}'");
            }
            if ($this->_bankAccount->BA_banknumber != $parsedList['BANK_NUMBER']) {
                throw new Exception("Čísla bank nesouhlasí: {$this->_bankAccount->BA_banknumber} != {$parsedList['BANK_NUMBER']}, výpis: '{$filename}'");
            }
            if ($this->_bankAccount->BA_currency != $parsedList['CURRENCY']) {
                throw new Exception("Měna nesouhlasí {$this->_bankAccount->BA_currency} != {$parsedList['CURRENCY']}, výpis: '{$filename}'");
            }
            if ($emailList->EL_no != $parsedList['LIST_NO']) {
                throw new Exception("Číslo výpisu nesouhlasí {$emailList->EL_no} != {$parsedList['LIST_NO']}, výpis: '{$filename}'");
            }
            $emailList->EL_currency = $parsedList['CURRENCY'];
            $emailList->EL_datefrom = $parsedList['LIST_DATE_FROM'];
            $emailList->EL_dateto = $parsedList['LIST_DATE_TO'];
            $emailList->EL_entrycount = count($parsedList['LIST']);
            $emailList->EL_status = EmailList::STATUS_PENDING;
        } catch (Exception $e) {
            $msg = "Výpis: {$emailList->EL_name} nemohl být zpracován, obsahuje chyby. ERROR: {$e->getMessage()}";
            $this->_messages[] = $msg;
            $database->log($msg, Log::LEVEL_ERROR);

            if ($core->getProperty(Core::SEND_EMAIL_ON_CRITICAL_ERROR)) {
                $emailUtil = new EmailUtil();
                $emailUtil->sendEmailMessage($core->getProperty(Core::SUPERVISOR_EMAIL), "Net provider error", $msg);
            }
        }

        if (isset($this->_dbEmailListNamesMap[strtolower($emailList->EL_name)])) {
            $isAlreadyPersisted = true;
        } else {
            try {
                $database->insertObject("emaillist", $emailList, "EL_emaillistid", false);
                $msg = "Výpis: {$emailList->EL_name} uložen do databáze";
                $this->_messages[] = $msg;
                $database->log($msg, Log::LEVEL_INFO);

                $dbNames[] = $emailList->EL_name;
            } catch (Exception $e) {
                $msg = "Výpis: {$emailList->EL_name} nebyl uložen do databáze. ERROR: {$e->getMessage()}";
                $this->_messages[] = $msg;
                $database->log($msg, Log::LEVEL_ERROR);
            }
        }
    }

    /**
     * unzipFileIfNeeded
     */
    function unzipFile($content, $filename, &$unzippedContent, &$unzippedFilename)
    {
        global $database;
        if (endsWithCaseInsensitive($filename, '.zip') === false) {
            $msg = "Soubor nemá příponu .zip: '%{$filename}'";
            $this->_messages[] = $msg;
            $database->log($msg, Log::LEVEL_ERROR);
            return false;
        }

        // Create a temporary file which creates file with unique file name
        $tmp = tempnam(sys_get_temp_dir(), md5(uniqid(microtime(true), true)));

        // Write the zipped content inside
        file_put_contents($tmp, $content);

        // Uncompress and read the ZIP archive
        $zip = new ZipArchive;
        if (true === $zip->open($tmp)) {
            $unzippedFilename = mb_substr($filename, 0, -4);
            if (($index = $zip->locateName($unzippedFilename)) !== false) {
                $unzippedContent = $zip->getFromIndex($index);
                $zip->close();
                unlink($tmp);

                return true;
            } else {
                $zip->close();
                unlink($tmp);

                $msg = "V ZIP souboru přílohy: '{$filename}' není xml výpis: '{$unzippedFilename}'";
                $this->_messages[] = $msg;
                $database->log($msg, Log::LEVEL_ERROR);
                return false;
            }
        } else {
            unlink($tmp);

            $msg = "Nelze otevřít ZIP soubor přílohy:'{$filename}'";
            $this->_messages[] = $msg;
            $database->log($msg, Log::LEVEL_ERROR);
            return false;
        }
    }
} // End of EmailBankAccountList class
function endsWithCaseInsensitive($FullStr, $EndStr)
{
    // Get the length of the end string
    $StrLen = strlen($EndStr);
    // Look at the end of FullStr for the substring the size of EndStr
    $FullStrEnd = substr($FullStr, strlen($FullStr) - $StrLen);
    // If it matches, it does end with EndStr
    return strcasecmp($FullStrEnd, $EndStr) === 0;
}

?>
