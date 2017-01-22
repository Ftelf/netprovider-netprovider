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
require_once('Net/POP3.php');
require_once($core->getAppRoot() . "includes/dao/BankAccountDAO.php");
require_once($core->getAppRoot() . "includes/dao/BankAccountEntryDAO.php");
require_once($core->getAppRoot() . "includes/dao/EmailListDAO.php");
require_once($core->getAppRoot() . "includes/net/email/MimeDecode.php");
require_once($core->getAppRoot() . "includes/billing/bankParser/BankParserFactory.php");

/**
 * EmailBankAccountList
 * Provides connection to pop3 server and downloads bank lists
 */
class EmailBankAccountList {
	private $_bankAccount;
	private $_messages = array();
	
	/**
	 * Constructor
	 */
    function EmailBankAccountList($bankAccount) {
        $this->_bankAccount = $bankAccount;
    }
    function getMessages() {
    	return $this->_messages;
    }
    /**
     * updateAccountListArray
     * will download EmailList from email server and insert them into database
     */
    function downloadNewAccountLists() {
    	global $database;
    	
    	$_pop3 =& new Net_POP3();
    	$matches = null;

		// Connect to localhost on usual port
		//
		if (!$_pop3->connect($this->_bankAccount->BA_emailserver, 110)) {
			throw new Exception("Cannot connect to pop3 server " . $this->_bankAccount->BA_emailserver);
		}
		if (($err = $_pop3->login($this->_bankAccount->BA_emailusername, $this->_bankAccount->BA_emailpassword)) !== true) {
			throw new Exception("Cannot login to server " . $this->_bankAccount->BA_emailserver);
		}
		
    	$dbEmailLists = EmailListDAO::getEmailListArrayByBankAccountID($this->_bankAccount->BA_bankaccountid);
    	// Fill array of list already in database
    	//
    	$dbNames = array();
    	foreach ($dbEmailLists as $dbEmailList) {
    		$dbNames[$dbEmailList->EL_name] = true;
    	}
    	// get email list on server
    	//
    	$msgs = $_pop3->getListing();
    	foreach ($msgs as $msgi) {
			$email = $_pop3->getMsg($msgi['msg_id']);
			$params = array(
							'input'           => $email,
							'crlf'            => "\r\n",
							'include_bodies'  => true,
							'decode_headers'  => true,
							'decode_bodies'   => true,
							'output_encoding' => 'UTF-8//IGNORE'
							);
			$decode = new Mail_mimeDecode($email);
			$msgDecoded = $decode->decode($params);
			
			// EmailList is identified by Sender and Subject
			// if FROM and SUBJECT matches
			//
			if (stripos($msgDecoded->headers['from'], $this->_bankAccount->BA_emailsender) !== false &&
				stripos($msgDecoded->headers['subject'], $this->_bankAccount->BA_emailsubject) !== false) {
				
				foreach ($msgDecoded->parts as $part) {
					if (isset($part->ctype_parameters) && isset($part->ctype_parameters['name']) ) {
						$filename = $part->ctype_parameters['name'];
						
						$emailList = new EmailList();
						
						if ($this->_bankAccount->BA_datasourcetype == BankAccount::DATASOURCE_TYPE_ABO) {
							if (mb_ereg('^([[:digit:]]{5})_([[:digit:]]{6,20})_([[:alpha:]]*)\.TXT$', $filename, $matches)) {
								$idNo = $matches[1];
								$accountNumber = $matches[2];
								$currency = $matches[3];
								
								if ($this->_bankAccount->BA_accountnumber != $accountNumber) {
									$msg = sprintf("List: Číslo bankovního konta nesouhlasí %s != %s", $this->_bankAccount->BA_accountnumber, $accountNumber);
									$this->_messages[] = $msg;
									$database->log($msg, LOG::LEVEL_WARNING);
									continue;
								}
								
								$emailList->EL_year = mb_substr($idNo, 0, 2) + 2000;
								$emailList->EL_no = mb_substr($idNo, 2, 3); 
							} else {
								$msg = sprintf("List: neplatný název přílohy: ", $filename);
								$this->_messages[] = $msg;
								$database->log($msg, LOG::LEVEL_WARNING);
								continue;
							}
							
							$emailList->EL_list = $part->body;
						} else if ($this->_bankAccount->BA_datasourcetype == BankAccount::DATASOURCE_TYPE_CSOB_XML) {
							if (mb_ereg('^([[:digit:]]{6,20})_([[:digit:]]{8})-([[:digit:]]{1,4})_DCZB\.xml$', $filename, $matches)) {
								$accountNumber = $matches[1];
								$dateString = $matches[2];
								$idNo = $matches[3];
								
								if ($this->_bankAccount->BA_accountnumber != $accountNumber) {
									$msg = sprintf("List: Číslo bankovního konta nesouhlasí %s != %s", $this->_bankAccount->BA_accountnumber, $accountNumber);
									$this->_messages[] = $msg;
									$database->log($msg, LOG::LEVEL_WARNING);
									continue;
								}
									
								$emailList->EL_year = mb_substr($dateString, 0, 4);
								$emailList->EL_no = $idNo;
							} else {
								$msg = sprintf("List: neplatný název přílohy: ", $filename);
								$this->_messages[] = $msg;
								$database->log($msg, LOG::LEVEL_WARNING);
								continue;
							}
							
							$emailList->EL_list = iconv("windows-1250", "UTF-8", str_replace("windows-1250", "UTF-8", $part->body));
						} else if ($this->_bankAccount->BA_datasourcetype == BankAccount::DATASOURCE_TYPE_KB_ABO) {
							if (mb_ereg('^([[:digit:]]{6,20})_([[:digit:]]{8})-([[:digit:]]{1,4})_DCZB\.xml$', $filename, $matches)) {
								$accountNumber = $matches[1];
								$dateString = $matches[2];
								$idNo = $matches[3];
								
								if ($this->_bankAccount->BA_accountnumber != $accountNumber) {
									$msg = sprintf("List: Číslo bankovního konta nesouhlasí %s != %s", $this->_bankAccount->BA_accountnumber, $accountNumber);
									$this->_messages[] = $msg;
									$database->log($msg, LOG::LEVEL_WARNING);
									continue;
								}
									
								$emailList->EL_year = mb_substr($dateString, 0, 4);
								$emailList->EL_no = $idNo;
							} else {
								$msg = sprintf("List: neplatný název přílohy: ", $filename);
								$this->_messages[] = $msg;
								$database->log($msg, LOG::LEVEL_WARNING);
								continue;
							}
							
							$emailList->EL_list = iconv("windows-1250", "UTF-8", str_replace("windows-1250", "UTF-8", $part->body));
						} else {
							continue;
						}
						
						$emailList->EL_bankaccountid = $this->_bankAccount->BA_bankaccountid;
						$emailList->EL_name = $filename;
						
						$bankParserFactory = new BankParserFactory($this->_bankAccount->BA_datasourcetype, $part->body);
						try {
							$bankParserFactory->parse();
							
							$parsedList = $bankParserFactory->getDocument();
							if ($this->_bankAccount->BA_accountnumber != $parsedList['ACCOUNT_NUMBER']) {
								throw new Exception("Čísla bankovního konta nesouhlasí");
							}
							if ($this->_bankAccount->BA_banknumber != $parsedList['BANK_NUMBER']) {
								throw new Exception("Čísla bank nesouhlasí");
							}
							if ($this->_bankAccount->BA_currency != $parsedList['CURRENCY']) {
								throw new Exception("Měna nesouhlasí $this->_bankAccount->BA_currency != $parsedList[CURRENCY]");
							}
							if ($emailList->EL_no != $parsedList['LIST_NO']) {
								throw new Exception("Číslo výpisu nesouhlasí $emailList->EL_no != $parsedList[LIST_NO]");
							}
							$emailList->EL_currency = $parsedList['CURRENCY'];
							$emailList->EL_datefrom = $parsedList['LIST_DATE_FROM'];
							$emailList->EL_dateto = $parsedList['LIST_DATE_TO'];
							$emailList->EL_entrycount = count($parsedList['LIST']);
							$emailList->EL_status = EmailList::STATUS_PENDING;
						} catch (Exception $e) {
							$emailList->EL_currency = "N/A";
							$emailList->EL_datefrom = DateUtil::DB_NULL_DATETIME;
							$emailList->EL_dateto = DateUtil::DB_NULL_DATETIME;
							$emailList->EL_no = "N/A";
							$emailList->EL_entrycount = "N/A";
							$emailList->EL_status = EmailList::STATUS_ERROR;
							
							$msg = "Výpis: $emailList->EL_name nemohl být zpracován, obsahuje chyby. ERROR: " . $e->getMessage();
							$this->_messages[] = $msg;
							$database->log($msg, LOG::LEVEL_ERROR);
							continue;
						}
						if (!isset($dbNames[$emailList->EL_name])) {
							try {
								$database->insertObject("emaillist", $emailList, "EL_emaillistid", false);
								$msg = "Výpis $emailList->EL_name uložen do databáze";
								$this->_messages[] = $msg;
								$database->log($msg, LOG::LEVEL_INFO);
							} catch (Exception $e) {
								$msg = "Výpis $emailList->EL_name nebyl uložen do databáze. ERROR: " . $e->getMessage();
								$this->_messages[] = $msg;
								$database->log($msg, LOG::LEVEL_ERROR);
							}
						}
					}
				}
			}
		}
    }
    
	/**
     * uploadBankList
     * will upload EmailList from file and insert them into database
     */
	function uploadBankList($filename, $fileContent) {
		global $database;
    	
		$dbEmailLists = EmailListDAO::getEmailListArrayByBankAccountID($this->_bankAccount->BA_bankaccountid);
    	// Fill array of list already in database
    	//
    	$dbNames = array();
    	foreach ($dbEmailLists as $dbEmailList) {
    		$dbNames[$dbEmailList->EL_name] = true;
    	}
    	
    	$emailList = new EmailList();
    	
		if ($this->_bankAccount->BA_datasourcetype == BankAccount::DATASOURCE_TYPE_ABO) {
			if (mb_ereg('^([[:digit:]]{5})_([[:digit:]]{6,20})_([[:alpha:]]*)\.TXT$', $filename, $matches)) {
				$idNo = $matches[1];
				$accountNumber = $matches[2];
				$currency = $matches[3];
				
				if ($this->_bankAccount->BA_accountnumber != $accountNumber) {
					$msg = sprintf("List: Číslo bankovního konta nesouhlasí %s != %s", $this->_bankAccount->BA_accountnumber, $accountNumber);
					$this->_messages[] = $msg;
					$database->log($msg, LOG::LEVEL_WARNING);
					return;
				}
				
				$emailList->EL_year = mb_substr($idNo, 0, 2) + 2000;
				$emailList->EL_no = mb_substr($idNo, 2, 3); 
			} else {
				$msg = sprintf("List: neplatný název přílohy: ", $filename);
				$this->_messages[] = $msg;
				$database->log($msg, LOG::LEVEL_WARNING);
				return;
			}
		} else if ($this->_bankAccount->BA_datasourcetype == BankAccount::DATASOURCE_TYPE_CSOB_XML) {
			if (mb_ereg('^([[:digit:]]{6,20})_([[:digit:]]{8})-([[:digit:]]{1,4})_DCZB\.xml$', $filename, $matches)) {
				$accountNumber = $matches[1];
				$dateString = $matches[2];
				$idNo = $matches[3];
				
				if ($this->_bankAccount->BA_accountnumber != $accountNumber) {
					$msg = sprintf("List: Číslo bankovního konta nesouhlasí %s != %s", $this->_bankAccount->BA_accountnumber, $accountNumber);
					$this->_messages[] = $msg;
					$database->log($msg, LOG::LEVEL_WARNING);
					return;
				}
					
				$emailList->EL_year = mb_substr($dateString, 0, 4);
				$emailList->EL_no = $idNo;
				
				$emailList->EL_list = iconv("windows-1250", "UTF-8", str_replace("windows-1250", "UTF-8", $part->body));
			} else {
				$msg = sprintf("List: neplatný název přílohy: ", $filename);
				$this->_messages[] = $msg;
				$database->log($msg, LOG::LEVEL_WARNING);
				return;
			}
		} else if ($this->_bankAccount->BA_datasourcetype == BankAccount::DATASOURCE_TYPE_KB_ABO) {
			//434885660257_20090803_20090809.txt
			if (mb_ereg('^([[:digit:]]{6,20})_([[:digit:]]{8})_([[:digit:]]{8})\.txt$', $filename, $matches)) {
				$accountNumber = $matches[1];
				$dateFromString = $matches[2];
				$dateToString = $matches[3];
				
				if ($this->_bankAccount->BA_accountnumber != $accountNumber) {
					$msg = sprintf("List: Číslo bankovního konta nesouhlasí %s != %s", $this->_bankAccount->BA_accountnumber, $accountNumber);
					$this->_messages[] = $msg;
					$database->log($msg, LOG::LEVEL_WARNING);
					return;
				}
					
				$emailList->EL_year = mb_substr($dateFromString, 0, 4);
				$emailList->EL_no = $idNo;
				
				$emailList->EL_list = iconv("windows-1250", "UTF-8", str_replace("windows-1250", "UTF-8", $part->body));
			} else {
				$msg = sprintf("List: neplatný název přílohy: ", $filename);
				$this->_messages[] = $msg;
				$database->log($msg, LOG::LEVEL_WARNING);
				return;
			}
		} else {
			return;
		}
						
    	$emailList->EL_bankaccountid = $this->_bankAccount->BA_bankaccountid;
    	$emailList->EL_name = $filename;
    	
		$emailList->EL_list = $fileContent;

		$bankParserFactory = new BankParserFactory($this->_bankAccount->BA_datasourcetype, $emailList->EL_list);
		try {
			$bankParserFactory->parse();
			
			$parsedList = $bankParserFactory->getDocument();
			if ($this->_bankAccount->BA_accountnumber != $parsedList['ACCOUNT_NUMBER']) {
				throw new Exception(sprintf("Čísla bankovního konta nesouhlasí: %s != %s", $this->_bankAccount->BA_accountnumber, $parsedList['ACCOUNT_NUMBER']));
			}
			if ($this->_bankAccount->BA_banknumber != $parsedList['BANK_NUMBER']) {
				throw new Exception("Čísla bank nesouhlasí");
			}
			if ($this->_bankAccount->BA_currency != $parsedList['CURRENCY']) {
				throw new Exception("Měna nesouhlasí $this->_bankAccount->BA_currency != $parsedList[CURRENCY]");
			}
			$emailList->EL_no = $parsedList['LIST_NO'];
			$emailList->EL_currency = $parsedList['CURRENCY'];
			$emailList->EL_datefrom = $parsedList['LIST_DATE_FROM'];
			$emailList->EL_dateto = $parsedList['LIST_DATE_TO'];
			$emailList->EL_entrycount = count($parsedList['LIST']);
			$emailList->EL_status = EmailList::STATUS_PENDING;
		} catch (Exception $e) {
			$emailList->EL_currency = "N/A";
			$emailList->EL_datefrom = DateUtil::DB_NULL_DATETIME;
			$emailList->EL_dateto = DateUtil::DB_NULL_DATETIME;
			$emailList->EL_no = "N/A";
			$emailList->EL_entrycount = "N/A";
			$emailList->EL_status = EmailList::STATUS_ERROR;
			
			$msg = "Výpis: $emailList->EL_name nemohl být zpracován, obsahuje chyby. ERROR: " . $e->getMessage();
			$this->_messages[] = $msg;
			$database->log($msg, LOG::LEVEL_ERROR);
			return;
		}
		
		if (isset($dbNames[$emailList->EL_name])) {
			$msg = "Výpis $emailList->EL_name je již uložen v databázi";
			$this->_messages[] = $msg;
			$database->log($msg, LOG::LEVEL_INFO);
		} else {
			try {
				$database->insertObject("emaillist", $emailList, "EL_emaillistid", false);
				$msg = "Výpis $emailList->EL_name uložen do databáze";
				$this->_messages[] = $msg;
				$database->log($msg, LOG::LEVEL_INFO);
			} catch (Exception $e) {
				$msg = "Výpis $emailList->EL_name nebyl uložen do databáze. ERROR: " . $e->getMessage();
				$this->_messages[] = $msg;
				$database->log($msg, LOG::LEVEL_ERROR);
			}
		}
    }
    /**
     * Imports BankAccountEntries
     */
	function importBankAccountEntries() {
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
					$database->log($msg, LOG::LEVEL_ERROR);
					continue;
    			}
				$parsedList = $bankParserFactory->getDocument();
    			// insert BankAccountEntries into database
    			//
    			try {
    				$database->startTransaction();
    				foreach ($parsedList['LIST'] as &$bankAccountEntry) {
    					$bankAccountEntry->BE_bankaccountid = $this->_bankAccount->BA_bankaccountid;
    					$database->insertObject("bankaccountentry", $bankAccountEntry, "BE_bankaccountentryid", false);
    				}
    				$emailList->EL_status = EmailList::STATUS_COMPLETED;
    				$emailList->EL_entrycount = count($parsedList['LIST']);
    				$database->updateObject("emaillist", $emailList, "EL_emaillistid", false, false);
    				$database->commit();
    				
    				$msg = "Položky výpisu $emailList->EL_name úspěšně importovány";
					$this->_messages[] = $msg;
					$database->log($msg, LOG::LEVEL_INFO);
    			} catch (Exception $e) {
    				$database->rollback();
    				$emailList->EL_status = EmailList::STATUS_ERROR;
    				$database->updateObject("emaillist", $emailList, "EL_emaillistid", false, false);
    				
    				$msg = "ERROR: Položky výpisu $emailList->EL_name nebyly importovány: " . $e->getMessage();
    				$this->_messages[] = $msg;
					$database->log($msg, LOG::LEVEL_ERROR);
    			}
    		}
    	}
    }
} // End of EmailBankAccountList class
?>