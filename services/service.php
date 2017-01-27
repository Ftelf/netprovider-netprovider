#!/usr/bin/php
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
 
	require_once(dirname(__FILE__) . "/../includes/Core.php");
	$core = new Core();
	require_once($core->getAppRoot() . "includes/EmailBankAccountList.php");
	require_once($core->getAppRoot() . "includes/billing/AccountEntryUtil.php");
	require_once($core->getAppRoot() . "includes/Database.php");
	require_once($core->getAppRoot() . "includes/billing/ChargesUtil.php");
	require_once($core->getAppRoot() . "includes/net/CommanderCrossbar.php");
	require_once($core->getAppRoot() . "includes/net/email/EmailUtil.php");
	require_once($core->getAppRoot() . "includes/event/EventCrossBar.php");
	require_once($core->getAppRoot() . "includes/dao/IpAccountDAO.php");
	
	try {
		$database = new Database(
			$core->getProperty(Core::DATABASE_HOST),
			$core->getProperty(Core::DATABASE_USERNAME),
			$core->getProperty(Core::DATABASE_PASSWORD),
			$core->getProperty(Core::DATABASE_NAME)
		);
	} catch (Exception $e) {
		openlog("NetProvider", LOG_PERROR, LOG_DAEMON);
		syslog(LOG_INFO, "Service: Cannot connect do database");
		closelog();
		exit();
	}
	
	// Init Event crossbar
	$eventCrossBar = new EventCrossBar();

	$scheduller = new Service($argv);
	$scheduller->exec();
	

/**
 * Service
 */
class Service {
	public $_runPayments = null;
	
	public $_runQosDown = null;
	public $_runQosUp = null;
	
	public $_proceedNetworking = null;
	
	public $_ipFilterDown = null;
	public $_ipFilterUp = null;
	
	public $_ipAccount = null;
	
	public $_cleanUp = null;
	
	private $_database = null;
	private $_ini = null;
	private $_argv = null;
	private $_dryRun = false;

	public function __construct($argv) {
		
		$this->_dryRun = (in_array('--dry-run', $argv));
		
		$this->_runPayments = (in_array('--proceed-payments', $argv));
		
		$this->_proceedNetworking = (in_array('--proceed-networking', $argv));
		
		$this->_ipFilterDown = (in_array('--ip-filter-down', $argv));
		$this->_ipFilterUp = (in_array('--ip-filter-up', $argv));
		
		$this->_runQosDown = (in_array('--qos-down', $argv));
		$this->_runQosUp = (in_array('--qos-up', $argv));
		
		$this->_ipAccount = (in_array('--ip-account', $argv));
		
		$this->_cleanUp = (in_array('--clean-up', $argv));
	}
	
	public function exec() {
		global $core, $database;
		
		openlog("NetProvider", LOG_PERROR, LOG_DAEMON);
		
		try {
			if ($this->_runPayments && !$this->_dryRun) {
				$this->payments();
				$msg = "Payments service completed successfully";
				syslog(LOG_INFO, $msg);
				$database->log($msg, Log::LEVEL_INFO);
			}
			
			if ($this->_proceedNetworking) {
				$this->proceedNetworking();
				
				$msg = "networking service completed successfully";
				syslog(LOG_INFO, $msg);
				$database->log($msg, Log::LEVEL_INFO);
			}
			
			if (!$this->_proceedNetworking && $this->_ipFilterDown) {
				$this->ipFilterDown();
				
				$msg = "ip filter down service completed successfully";
				syslog(LOG_INFO, $msg);
				$database->log($msg, Log::LEVEL_INFO);
			}
			
			if (!$this->_proceedNetworking && $this->_ipFilterUp) {
				$this->ipFilterUp();
				
				$msg = "ip filter up service completed successfully";
				syslog(LOG_INFO, $msg);
				$database->log($msg, Log::LEVEL_INFO);
			}
			
			if (!$this->_proceedNetworking && $this->_runQosDown) {
				$this->qosDown();
				$msg = "QOS down service completed successfully";
				syslog(LOG_INFO, $msg);
				$database->log($msg, Log::LEVEL_INFO);
			}
			
			if (!$this->_proceedNetworking && $this->_runQosUp) {
				$this->qosUp();
				$msg = "QOS up service completed successfully";
				syslog(LOG_INFO, $msg);
				$database->log($msg, Log::LEVEL_INFO);
			}
			
			if (!$this->_proceedNetworking && $this->_ipAccount && !$this->_dryRun) {
				$this->ipAccount();
				$msg = "ip accounting service completed successfully";
				syslog(LOG_INFO, $msg);
				$database->log($msg, Log::LEVEL_INFO);
			}
			
			if ($this->_cleanUp && !$this->_dryRun) {
				$this->cleanUp2();
				$msg = "database cleanup completed successfully";
				syslog(LOG_INFO, $msg);
				$database->log($msg, Log::LEVEL_INFO);
			}
		} catch (Exception $e) {
			$msg = "service fail: ".$e->getMessage();
			
			echo $msg;
			syslog(LOG_ERR, $msg);
			$database->log($msg, Log::LEVEL_ERROR);
			
			if ($core->getProperty(Core::SEND_EMAIL_ON_CRITICAL_ERROR)) {
				$emailUtil = new EmailUtil();
				$emailUtil->sendEmailMessage($core->getProperty(Core::SUPERVISOR_EMAIL), "Net provider error", $e);
			}
		}
		
		closelog();
	}
	
	public function payments() {
		// Download new BankAccountLists
		//
		$bankAccounts = BankAccountDAO::getBankAccountArray();
		
		try {
			foreach ($bankAccounts as $bankAccount) {
			
				$emailBankAccountList = new EmailBankAccountList($bankAccount);
				$emailBankAccountList->downloadNewAccountLists();
				$emailBankAccountList->importBankAccountEntries();
				
				$accountEntryUtil = new AccountEntryUtil($bankAccount);
				$accountEntryUtil->proceedAccountEntries();
			}
			
			$chargesUtil = new ChargesUtil();
			$chargesUtil->createBlankChargeEntries();
			$chargesUtil->proceedCharges();
		} catch (Exception $e) {
			throw $e;
		}
	}
	
	public function proceedNetworking() {
		$commanderCrossbar = new CommanderCrossbar();
		$commanderCrossbar->setDryRun($this->_dryRun);
		$commanderCrossbar->inicialize();
		
		$this->showResult($commanderCrossbar->ipFilterDown());
		//$this->showResult($commanderCrossbar->qosDown());
		$this->showResult($commanderCrossbar->ipFilterUp());
		//$this->showResult($commanderCrossbar->qosUp());
	}
	
	public function ipFilterDown() {
		$commanderCrossbar = new CommanderCrossbar();
		$commanderCrossbar->setDryRun($this->_dryRun);
		$commanderCrossbar->inicialize();
		
		$this->showResult($commanderCrossbar->ipFilterDown());
	}
	
	public function ipFilterUp() {
		$commanderCrossbar = new CommanderCrossbar();
		$commanderCrossbar->setDryRun($this->_dryRun);
		$commanderCrossbar->inicialize();
		
		$this->showResult($commanderCrossbar->ipFilterUp());
	}
	
	public function qosDown() {
		$commanderCrossbar = new CommanderCrossbar();
		$commanderCrossbar->setDryRun($this->_dryRun);
		$commanderCrossbar->inicialize();
		
		$this->showResult($commanderCrossbar->qosDown());
	}	
	public function qosUp() {
		$commanderCrossbar = new CommanderCrossbar();
		$commanderCrossbar->setDryRun($this->_dryRun);
		$commanderCrossbar->inicialize();
		
		$this->showResult($commanderCrossbar->qosUp());
	}
	
	public function ipAccount() {
		$commanderCrossbar = new CommanderCrossbar();
		$commanderCrossbar->setDryRun($this->_dryRun);
		$commanderCrossbar->inicialize();
		
		$commanderCrossbar->accountIP();
	}
	
	public function cleanUp() {
		global $database;
		
		$now = new DateUtil();
		$currentMonth = $now->get(DateUtil::MONTH);
		$currentYear = $now->get(DateUtil::YEAR);
		$currentThreshold = 12 * $currentYear + $currentMonth;
		
		$ips = IpDAO::getIpArray();
		
		foreach ($ips as $ip) {
			$ipAccountEntries = IpAccountDAO::getIpAccountArrayByIpID($ip->IP_ipid);
			
			if (count($ipAccountEntries)) {
				$newGroup = true;
				
				$database->startTransaction();
				
				foreach ($ipAccountEntries as $ipAccountEntry) {
					$date = new DateUtil($ipAccountEntry->IA_datetime);
					
					$day = $date->get(DateUtil::DAY);
					$month = $date->get(DateUtil::MONTH);
					$year = $date->get(DateUtil::YEAR);
					$threshold = 12 * $year + $month;
					
					if ($newGroup) {
						$newGroup = false;
						$IA_bytes_in = 0;
						$IA_packets_in = 0;
						$IA_bytes_out = 0;
						$IA_packets_out = 0;
						
						$actualDay = $day;
						$actualMonth = $month;
						$actualYear = $year;
						$actualThreshold = $threshold;
						
						if ($threshold + 1 < $currentThreshold) {	//Older than 2 months
							$actualType = 1;
						} else if ($threshold < $currentThreshold) {	//Older than 1 month
							$actualType = 2;
						} else {	//Actual month
							$actualType = 3;
							
							$accountIdStack = array();
							break;
						}
						
						$accountIdStack = array();
					}
					
					if ($actualThreshold == $threshold && ($actualType == 1 || ($actualType == 2 && $actualDay == $day))) {
						$IA_bytes_in += $ipAccountEntry->IA_bytes_in;
						$IA_packets_in += $ipAccountEntry->IA_packets_in;
						$IA_bytes_out += $ipAccountEntry->IA_bytes_out;
						$IA_packets_out += $ipAccountEntry->IA_packets_out;
						
						$accountIdStack[] = $ipAccountEntry->IA_ipaccountid;
					} else {
						$newDate = new DateUtil();
						$newDate->set(DateUtil::YEAR, $actualYear);
						$newDate->set(DateUtil::MONTH, $actualMonth);
						$newDate->set(DateUtil::HOUR, 0);
						$newDate->set(DateUtil::MINUTES, 0);
						$newDate->set(DateUtil::SECONDS, 0);
						
						if ($actualType == 1) {
							$newDate->set(DateUtil::DAY, 1);
						} else if ($actualType == 2) {
							$newDate->set(DateUtil::DAY, $actualDay);
						}
						
						foreach($accountIdStack as $id) {
							IpAccountDAO::removeIpAccountByID($id);
						}
						
						$ipAccount = new IpAccount();
						$ipAccount->IA_ipid = $ip->IP_ipid;
						$ipAccount->IA_datetime = $newDate->getFormattedDate(DateUtil::DB_DATETIME);
						$ipAccount->IA_bytes_in = $IA_bytes_in;
						$ipAccount->IA_bytes_out = $IA_bytes_out;
						$ipAccount->IA_packets_in  = $IA_packets_in;
						$ipAccount->IA_packets_out = $IA_packets_out;
						
						$database->insertObject("ipaccount", $ipAccount, "IA_ipaccountid", false);
						
						$IA_bytes_in = 0;
						$IA_packets_in = 0;
						$IA_bytes_out = 0;
						$IA_packets_out = 0;
						
						$actualDay = $day;
						$actualMonth = $month;
						$actualYear = $year;
						$actualThreshold = $threshold;
						
						if ($threshold + 1 < $currentThreshold) {	//Older than 2 months
							$actualType = 1;
						} else if ($threshold < $currentThreshold) {	//Older than 1 month
							$actualType = 2;
						} else {	//Actual month
							$actualType = 3;
							
							$accountIdStack = array();
							break;
						}
						
						$accountIdStack = array();
						
						$IA_bytes_in += $ipAccountEntry->IA_bytes_in;
						$IA_packets_in += $ipAccountEntry->IA_packets_in;
						$IA_bytes_out += $ipAccountEntry->IA_bytes_out;
						$IA_packets_out += $ipAccountEntry->IA_packets_out;
						
						$accountIdStack[] = $ipAccountEntry->IA_ipaccountid;
					}
				}
				if (count($accountIdStack)) {
					$newDate = new DateUtil();
					$newDate->set(DateUtil::YEAR, $actualYear);
					$newDate->set(DateUtil::MONTH, $actualMonth);
					$newDate->set(DateUtil::HOUR, 0);
					$newDate->set(DateUtil::MINUTES, 0);
					$newDate->set(DateUtil::SECONDS, 0);
					
					if ($actualType == 1) {
						$newDate->set(DateUtil::DAY, 1);
					} else if ($actualType == 2) {
						$newDate->set(DateUtil::DAY, $actualDay);
					}
					
					foreach($accountIdStack as $id) {
						IpAccountDAO::removeIpAccountByID($id);
					}
						
					$ipAccount = new IpAccount();
					$ipAccount->IA_ipid = $ip->IP_ipid;
					$ipAccount->IA_datetime = $newDate->getFormattedDate(DateUtil::DB_DATETIME);
					$ipAccount->IA_bytes_in = $IA_bytes_in;
					$ipAccount->IA_bytes_out = $IA_bytes_out;
					$ipAccount->IA_packets_in  = $IA_packets_in;
					$ipAccount->IA_packets_out = $IA_packets_out;
					
					$database->insertObject("ipaccount", $ipAccount, "IA_ipaccountid", false);
				}
				
				$database->commit();
			}
		}
	}
	
	public function cleanUp2() {
		global $database;
		
		$now = new DateUtil();
		$currentMonth = $now->get(DateUtil::MONTH);
		$currentYear = $now->get(DateUtil::YEAR);
		$currentThreshold = 12 * $currentYear + $currentMonth;
		
		$ips = IpDAO::getIpArray();
		
		foreach ($ips as $ip) {
			$ipAccountEntries = IpAccountDAO::getIpAccountMonthSumArrayByIpID($ip->IP_ipid, $currentThreshold - 2);
			if (count($ipAccountEntries)) {
				foreach ($ipAccountEntries as $ipAccountEntry) {
					if ($ipAccountEntry->count > 1) {
						$database->startTransaction();
						
						IpAccountDAO::removeIpAccountByYearMonth($ip->IP_ipid, $ipAccountEntry->year, $ipAccountEntry->month);
						
						$newDate = new DateUtil();
						$newDate->set(DateUtil::YEAR, $ipAccountEntry->year);
						$newDate->set(DateUtil::MONTH, $ipAccountEntry->month);
						$newDate->set(DateUtil::DAY, 1);
						$newDate->set(DateUtil::HOUR, 0);
						$newDate->set(DateUtil::MINUTES, 0);
						$newDate->set(DateUtil::SECONDS, 0);
						
						$ipAccount = new IpAccount();
						$ipAccount->IA_ipid = $ip->IP_ipid;
						$ipAccount->IA_datetime = $newDate->getFormattedDate(DateUtil::DB_DATETIME);
						$ipAccount->IA_bytes_in = $ipAccountEntry->IA_bytes_in;
						$ipAccount->IA_bytes_out = $ipAccountEntry->IA_bytes_out;
						$ipAccount->IA_packets_in  = $ipAccountEntry->IA_packets_in;
						$ipAccount->IA_packets_out = $ipAccountEntry->IA_packets_out;
						
						$database->insertObject("ipaccount", $ipAccount, "IA_ipaccountid", false);
						
						$database->commit();
					}
				}
			}
			
			$ipAccountEntries = IpAccountDAO::getIpAccountDaySumArrayByIpID($ip->IP_ipid, $currentThreshold - 1);
			if (count($ipAccountEntries)) {
				foreach ($ipAccountEntries as $ipAccountEntry) {
					if ($ipAccountEntry->count > 1) {
						$database->startTransaction();
						
						IpAccountDAO::removeIpAccountByYearMonthDay($ip->IP_ipid, $ipAccountEntry->year, $ipAccountEntry->month, $ipAccountEntry->day);
						
						$newDate = new DateUtil();
						$newDate->set(DateUtil::YEAR, $ipAccountEntry->year);
						$newDate->set(DateUtil::MONTH, $ipAccountEntry->month);
						$newDate->set(DateUtil::DAY, $ipAccountEntry->day);
						$newDate->set(DateUtil::HOUR, 0);
						$newDate->set(DateUtil::MINUTES, 0);
						$newDate->set(DateUtil::SECONDS, 0);
						
						$ipAccount = new IpAccount();
						$ipAccount->IA_ipid = $ip->IP_ipid;
						$ipAccount->IA_datetime = $newDate->getFormattedDate(DateUtil::DB_DATETIME);
						$ipAccount->IA_bytes_in = $ipAccountEntry->IA_bytes_in;
						$ipAccount->IA_bytes_out = $ipAccountEntry->IA_bytes_out;
						$ipAccount->IA_packets_in  = $ipAccountEntry->IA_packets_in;
						$ipAccount->IA_packets_out = $ipAccountEntry->IA_packets_out;
						
						$database->insertObject("ipaccount", $ipAccount, "IA_ipaccountid", false);
						
						$database->commit();
					}
				}
			}
		}
	}
	
	public function showResult($results) {
		foreach ($results as $result) {
			echo "\n".$result[0]."";
			
			if (isset($result[1]) && $result[1]) {
				echo "\n".$result[1];
			}
			
			if (isset($result[2]) && $result[2]) {
				echo "\n".$result[2];
			}
		}
		echo "\n";
	}
} // End of Service class
?>