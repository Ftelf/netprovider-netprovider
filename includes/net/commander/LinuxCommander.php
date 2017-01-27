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
require_once($core->getAppRoot() . "includes/dao/PersonDAO.php");
require_once($core->getAppRoot() . "includes/dao/ChargeDAO.php");
require_once($core->getAppRoot() . "includes/dao/HasChargeDAO.php");
require_once($core->getAppRoot() . "includes/dao/IpDAO.php");
require_once($core->getAppRoot() . "includes/dao/NetworkDAO.php");
require_once($core->getAppRoot() . "includes/dao/HasManagedNetworkDAO.php");
require_once($core->getAppRoot() . "includes/dao/NetworkDeviceDAO.php");
require_once($core->getAppRoot() . "includes/dao/NetworkDeviceInterfaceDAO.php");
require_once($core->getAppRoot() . "includes/dao/InternetDAO.php");
require_once($core->getAppRoot() . "includes/dao/IpAccountAbsDAO.php");
require_once($core->getAppRoot() . "includes/dao/IpAccountDAO.php");
require_once($core->getAppRoot() . "includes/Executor.php");
require_once($core->getAppRoot() . "includes/utils/Utils.php");
require_once 'Net/IPv4.php';

/**
 * LinuxCommander
 */
class LinuxCommander {
	const TC_CLASSID_START = 101;
	
	const FAIR_QUEUE_DISCIPLINE = "esfq";
	const USER_QUEUE_DISCIPLINE = "pfifo";
	
	const CHAIN_ACCT_IN = 'FILTER-IN';
	const CHAIN_ACCT_OUT = 'FILTER-OUT';
	
	const IPTABLES_CHAIN_HEADER = 'Chain %s (1 references)';
	const IPTABLES_LIST_HEADER = 'pkts[[:space:]]+bytes[[:space:]]+target[[:space:]]+prot[[:space:]]+opt[[:space:]]+in[[:space:]]+out[[:space:]]+source[[:space:]]+destination';
	
	const IPTABLES_LIST_ENTRY = '^([[:digit:]]+)[[:space:]]+([[:digit:]]+)[[:space:]]+ACCEPT[[:space:]]+all[[:space:]]+--[[:space:]]+\*[[:space:]]+\*[[:space:]]+([[:digit:]]{1,3}\.[[:digit:]]{1,3}\.[[:digit:]]{1,3}\.[[:digit:]]{1,3}(/[[:digit:]]{1,2})?)[[:space:]]+([[:digit:]]{1,3}\.[[:digit:]]{1,3}\.[[:digit:]]{1,3}\.[[:digit:]]{1,3}(/[[:digit:]]{1,2})?)$';
	
	private $networkDevice;
	
	private $globalIPFilterEnabled;
	private $bandwidthMargin;
	private $rejectUnknownIP;
	private $redirectUnknownIP;
	private $redirectToIP;
	private $allowedHosts;

	public function __construct($networkDevice) {
		global $core;
		
		$this->networkDevice = $networkDevice;
		
		$this->globalIPFilterEnabled = $core->getProperty(Core::GLOBAL_IP_FILTER_ENABLED);
		$this->bandwidthMargin = $core->getProperty(Core::QOS_BANDWIDTH_MARGIN_PERCENT);
		$this->rejectUnknownIP = $core->getProperty(Core::REJECT_UNKNOWN_IP);
		$this->redirectUnknownIP = $core->getProperty(Core::REDIRECT_UNKNOWN_IP);
		$this->redirectToIP = $core->getProperty(Core::REDIRECT_TO_IP);
		$this->allowedHosts = explode(";", $core->getProperty(Core::ALLOWED_HOSTS));
	}
	
	public function accountIP() {
		global $database;
		
		$now = new DateUtil();
		$dateString = $now->getFormattedDate(DateUtil::DB_DATETIME);
		
		$networkDevices = NetworkDeviceDAO::getNetworkDeviceArray();
		
		foreach ($networkDevices as &$networkDevice) {
			if ($this->globalIPFilterEnabled && $networkDevice->ND_ipFilterEnabled) {
				if (isset($networkDevice->MANAGEMENT_IP)) {
					$settings = array();
					$settings[Executor::REMOTE_HOST] = $networkDevice->MANAGEMENT_IP;
					$settings[Executor::REMOTE_PORT] = 22;
					$settings[Executor::LOGIN] = $networkDevice->ND_login;
					$settings[Executor::PASSWORD] = $networkDevice->ND_password;
					$settings[Executor::SUDO_COMMAND] = $networkDevice->ND_commandSudo;
					
					$executor = new Executor(Executor::REMOTE_SSH2, $settings, true);
				} else {
					$settings = array();
					$settings[Executor::SUDO_COMMAND] = $networkDevice->ND_commandSudo;
					
					$executor = new Executor(Executor::LOCAL_COMMAND, $settings, true);
				}
				
				$filterIn = null;
				$filterOut = null;
				
				$cmdIn = sprintf("%s -xvnL %s", $networkDevice->ND_commandIptables, LinuxCommander::CHAIN_ACCT_IN);
				$filterIn = $executor->executeArray(array($cmdIn));
				if ($filterIn[0][2]) {
					throw new Exception(sprintf("error proceeding command '%s' on network device '%s'", $cmdIn, $networkDevice->ND_name));
				}
				
				$cmdOut = sprintf("%s -xvnL %s", $networkDevice->ND_commandIptables, LinuxCommander::CHAIN_ACCT_OUT);
				$filterOut = $executor->executeArray(array($cmdOut));
				if ($filterOut[0][2]) {
					throw new Exception(sprintf("error proceeding command '%s' on network device '%s'", $cmdOut, $networkDevice->ND_name));
				}
				
				$filterInArr = Utils::stringAsLineArray($filterIn[0][1]);
				$filterOutArr = Utils::stringAsLineArray($filterOut[0][1]);
				
				
				if ($filterInArr[0] != sprintf(LinuxCommander::IPTABLES_CHAIN_HEADER, LinuxCommander::CHAIN_ACCT_IN)) {
					throw new Exception(sprintf("cannot match chain head in FILTER-IN: %s", $filterInArr[0]));
				}
				
				if ($filterOutArr[0] != sprintf(LinuxCommander::IPTABLES_CHAIN_HEADER, LinuxCommander::CHAIN_ACCT_OUT)) {
					throw new Exception(sprintf("cannot match chain head in FILTER-OUT: %s", $filterOutArr[0]));
				}
				
				if (!ereg(LinuxCommander::IPTABLES_LIST_HEADER, $filterInArr[1], $matches)) {
					throw new Exception(sprintf("cannot match head in FILTER-IN: %s", $filterInArr[1]));
				}
				
				if (!ereg(LinuxCommander::IPTABLES_LIST_HEADER, $filterOutArr[1], $matches)) {
					throw new Exception(sprintf("cannot match head in FILTER-OUT: %s", $filterOutArr[1]));
				}
				
				$ipArray = array();
				$l = count($filterInArr);
				for ($i = 2; $i < $l; $i++) {
					$iIn = trim($filterInArr[$i]);
					$iOut = trim($filterOutArr[$i]);
					
					if (ereg(LinuxCommander::IPTABLES_LIST_ENTRY, $iIn, $matchesIn) && ereg(LinuxCommander::IPTABLES_LIST_ENTRY, $iOut, $matchesOut)) {
						if ($matchesIn[5] == $matchesOut[3] && $matchesIn[3] == $matchesOut[5]) {
							$ipArray[] = array(
								"IP-SRC"		=> $matchesIn[5],
								"IP-DST"		=> $matchesIn[3],
								"BYTES-IN"		=> $matchesIn[2],
								"BYTES-OUT"		=> $matchesOut[2],
								"PACKETS-IN"	=> $matchesIn[1],
								"PACKETS-OUT"	=> $matchesOut[1],
							);
						} else {
							throw new Exception(sprintf("iptables FILTER-IN and FILTER-OUT mismatch IPs: %s != %s OR %s != %s", $matchesIn[5], $matchesOut[3], $matchesIn[3], $matchesOut[5]));
						}
					} else {
						break;
					}
				}
				foreach ($ipArray as $accountedIP) {
					try {
						$ip = IpDAO::getIpByIP($accountedIP["IP-SRC"]);
					} catch (Exception $e) {
						throw new Exception(sprintf("there is no IP %s in database", $accountedIP["IP-SRC"]));
					}
					
					try {
						$ipAccountAbs = IpAccountAbsDAO::getIpAccountAbsByIpID($ip->IP_ipid);
					} catch (Exception $e) {
						$ipAccountAbs = new IpAccountAbs();
						$ipAccountAbs->IB_ipid = $ip->IP_ipid;
						$ipAccountAbs->IB_bytes_in = $accountedIP["BYTES-IN"];
						$ipAccountAbs->IB_bytes_out = $accountedIP["BYTES-OUT"];
						$ipAccountAbs->IB_packets_in = $accountedIP["PACKETS-IN"];
						$ipAccountAbs->IB_packets_out = $accountedIP["PACKETS-OUT"];
						$database->insertObject("ipaccountabs", $ipAccountAbs, "IB_ipaccountabsid", false);
					}
					
					$ipAccount = new IpAccount();
					$ipAccount->IA_ipid = $ip->IP_ipid;
					
					$ipAccount->IA_bytes_in =  ($accountedIP["BYTES-IN"]  >= $ipAccountAbs->IB_bytes_in)  ? $accountedIP["BYTES-IN"]  - $ipAccountAbs->IB_bytes_in  : $accountedIP["BYTES-IN"];
					$ipAccount->IA_bytes_out = ($accountedIP["BYTES-OUT"] >= $ipAccountAbs->IB_bytes_out) ? $accountedIP["BYTES-OUT"] - $ipAccountAbs->IB_bytes_out : $accountedIP["BYTES-OUT"];
					$ipAccount->IA_packets_in  = ($accountedIP["PACKETS-IN"]  >= $ipAccountAbs->IB_packets_in)  ? $accountedIP["PACKETS-IN"]  - $ipAccountAbs->IB_packets_in  : $accountedIP["PACKETS-IN"];
					$ipAccount->IA_packets_out = ($accountedIP["PACKETS-OUT"] >= $ipAccountAbs->IB_packets_out) ? $accountedIP["PACKETS-OUT"] - $ipAccountAbs->IB_packets_out : $accountedIP["PACKETS-OUT"];
					
					$ipAccountAbs->IB_bytes_in = $accountedIP["BYTES-IN"];
					$ipAccountAbs->IB_bytes_out = $accountedIP["BYTES-OUT"];
					$ipAccountAbs->IB_packets_in = $accountedIP["PACKETS-IN"];
					$ipAccountAbs->IB_packets_out = $accountedIP["PACKETS-OUT"];
					
					$ipAccount->IA_datetime = $dateString;
					
					try {
						$database->startTransaction();
						$database->insertObject("ipaccount", $ipAccount, "IA_ipaccountid", false);
						$database->updateObject("ipaccountabs", $ipAccountAbs, "IB_ipaccountabsid", false, false);
						$database->commit();
					} catch (Exception $e) {
						$database->rollback();
						throw $e;
					}
				}
			}
		}
	}
	
	public function getIPFilterDown($executor) {
		$cmds = array();
		
		$iptablesCommand = $this->networkDevice->ND_commandIptables;
		$wanInterface = $this->networkDevice->wanInterface;

		$cmds[] = sprintf("%s -D FORWARD -i %s -j FILTER-IN 2>/dev/null", $iptablesCommand, $wanInterface);
		$cmds[] = sprintf("%s -D FORWARD -o %s -j FILTER-OUT 2>/dev/null", $iptablesCommand, $wanInterface);
		$cmds[] = sprintf("%s -t nat -D PREROUTING -d ! 10.0.0.0/8 -j WEB-REDIRECT 2>/dev/null", $iptablesCommand);
		
		$cmds[] = sprintf("%s -F FILTER-IN 2>/dev/null", $iptablesCommand);
		$cmds[] = sprintf("%s -F FILTER-OUT 2>/dev/null", $iptablesCommand);
		
		$cmds[] = sprintf("%s -X FILTER-IN 2>/dev/null", $iptablesCommand);
		$cmds[] = sprintf("%s -X FILTER-OUT 2>/dev/null", $iptablesCommand);
		
		$cmds[] = sprintf("%s -t nat -F WEB-REDIRECT 2>/dev/null", $iptablesCommand);
		$cmds[] = sprintf("%s -t nat -X WEB-REDIRECT 2>/dev/null", $iptablesCommand);
		
		return self::parseArrayReadable($executor->executeArray($cmds));
	}
	
	public function getIPFilterUp($executor) {
		$cmds = array();
		
		$iptablesCommand = $this->networkDevice->ND_commandIptables;
		$wanInterface = $this->networkDevice->wanInterface;
		$redirectIPEnabled = $this->rejectUnknownIP && $this->redirectUnknownIP;
		$redirectToIP = $this->redirectToIP;
		
		$cmds[] = sprintf("%s -N FILTER-IN", $iptablesCommand);
		$cmds[] = sprintf("%s -N FILTER-OUT", $iptablesCommand);
		
		if ($redirectIPEnabled) {
			$cmds[] = sprintf("%s -t nat -N WEB-REDIRECT", $iptablesCommand);
		}
		
		foreach ($this->networkDevice->NETWORKS as &$network) {
			foreach ($network['INTERNETS'] as &$internet) {
				foreach ($internet['IPS'] as &$ip) {
					$ipAddress = $ip['IP_address'];
					
					//setup accounting, we will try to account ip even if blocked
					//accept all known clients
					$cmds[] = sprintf("%s -A FILTER-IN  -d %s -j ACCEPT", $iptablesCommand, $ipAddress);
					$cmds[] = sprintf("%s -A FILTER-OUT -s %s -j ACCEPT", $iptablesCommand, $ipAddress);
					
					if ($redirectIPEnabled) {
						$cmds[] = sprintf("%s -t nat -A WEB-REDIRECT -s %s -j RETURN", $iptablesCommand, $ipAddress);
					}
				}
			}
		}
		// this will enable access to certail ips even when internet is not enabled for this ip
		//
		foreach ($this->allowedHosts as &$host) {
			$parts = explode(":", $host);
			
			$cmds[] = sprintf("%s -A FILTER-IN -p tcp -s %s -j ACCEPT", $iptablesCommand, $parts[0]);
			
			if (count($parts) == 1) {
				$cmds[] = sprintf("%s -A FILTER-OUT -p tcp -d %s -j ACCEPT", $iptablesCommand, $parts[0]);
			} else if (count($parts) == 2) {
				$cmds[] = sprintf("%s -A FILTER-OUT -p tcp -d %s --dport %s -j ACCEPT", $iptablesCommand, $parts[0], $parts[1]);
			}
		}
		
		/**
		 * all unknown clients will be logged
		 */
		$cmds[] = sprintf("%s -A FILTER-IN  -m limit --limit 5/h --limit-burst 3 -j LOG --log-prefix \"UNKNOWN-IN: \"", $iptablesCommand);
		$cmds[] = sprintf("%s -A FILTER-OUT  -m limit --limit 5/h --limit-burst 3 -j LOG --log-prefix \"UNKNOWN-OUT: \"", $iptablesCommand);
		if ($this->rejectUnknownIP) {
			/**
			 * all unknown clients will be rejected
			 */
			$cmds[] = sprintf("%s -A FILTER-IN  -j REJECT", $iptablesCommand);
			$cmds[] = sprintf("%s -A FILTER-OUT -j REJECT", $iptablesCommand);
		}
		
		// this will enable access to certail ips even when internet is not enabled for this ip in NAT chain
		if ($redirectIPEnabled) {
			foreach ($this->allowedHosts as &$host) {
				$parts = explode(":", $host);
				
				$cmds[] = sprintf("%s -t nat -A WEB-REDIRECT -d %s -j RETURN", $iptablesCommand, $parts[0]);
			}
			
			$cmds[] = sprintf("%s -t nat -A WEB-REDIRECT -p tcp --dport 80 -j DNAT --to-destination %s:80", $iptablesCommand, $redirectToIP);
		}
		
		$cmds[] = sprintf("%s -A FORWARD -i %s -j FILTER-IN", $iptablesCommand, $wanInterface);
		$cmds[] = sprintf("%s -A FORWARD -o %s -j FILTER-OUT", $iptablesCommand, $wanInterface);
		
		if ($redirectIPEnabled) {
			$cmds[] = sprintf("%s -t nat -A PREROUTING -d ! 10.0.0.0/8 -j WEB-REDIRECT", $iptablesCommand);
		}
		
		return self::parseArrayReadable($executor->executeArray($cmds));
	}
	
	public function getQosDown($executor) {
		$cmds = array();
		
		$iptablesCommand = $this->networkDevice->ND_commandIptables;
		$ipCommand = $this->networkDevice->ND_commandIp;
		$tcCommand = $this->networkDevice->ND_commandTc;
		$lanInterfaces = $this->networkDevice->lanInterfaces;
		$wanInterface = $this->networkDevice->wanInterface;
		
		$cmds[] = sprintf("%s -t mangle -F QOS-IN-SMARK 2>/dev/null", $iptablesCommand);
		$cmds[] = sprintf("%s -t mangle -F QOS-OUT-SMARK 2>/dev/null", $iptablesCommand);
		
		$cmds[] = sprintf("%s -t mangle -D PREROUTING -i %s -j IMQ --todev 0 2>/dev/null", $iptablesCommand, $wanInterface);
		
		foreach ($lanInterfaces as $lanInterface) {
			$cmds[] = sprintf("%s -t mangle -D POSTROUTING -o %s -s ! 10.0.0.0/8 -d 10.0.0.0/8 -j QOS-ETH-IN 2>/dev/null", $iptablesCommand, $lanInterface);
		}
		
		$cmds[] = sprintf("%s -t mangle -D QOS-ETH-IN -j QOS-IN-SMARK 2>/dev/null", $iptablesCommand);
		$cmds[] = sprintf("%s -t mangle -D QOS-ETH-IN -j IMQ --todev 2 2>/dev/null", $iptablesCommand);
		
		foreach ($lanInterfaces as $lanInterface) {
			$cmds[] = sprintf("%s -t mangle -D PREROUTING -i %s -s 10.0.0.0/8 -d ! 10.0.0.0/8 -j QOS-ETH-OUT 2>/dev/null", $iptablesCommand, $lanInterface);
		}
		
		$cmds[] = sprintf("%s -t mangle -D QOS-ETH-OUT -j QOS-OUT-SMARK 2>/dev/null", $iptablesCommand);
		$cmds[] = sprintf("%s -t mangle -D QOS-ETH-OUT -j IMQ --todev 3 2>/dev/null", $iptablesCommand);
		$cmds[] = sprintf("%s -t mangle -D POSTROUTING -o %s -j IMQ --todev 1 2>/dev/null", $iptablesCommand, $wanInterface);
		
		$cmds[] = sprintf("%s -t mangle -X QOS-ETH-IN 2>/dev/null", $iptablesCommand);
		$cmds[] = sprintf("%s -t mangle -X QOS-ETH-OUT 2>/dev/null", $iptablesCommand);
		
		$cmds[] = sprintf("%s -t mangle -X QOS-IN-SMARK 2>/dev/null", $iptablesCommand);
		$cmds[] = sprintf("%s -t mangle -X QOS-OUT-SMARK 2>/dev/null", $iptablesCommand);
		
		$cmds[] = sprintf("%s qdisc del dev imq0 root 2>/dev/null", $tcCommand);
		$cmds[] = sprintf("%s qdisc del dev imq1 root 2>/dev/null", $tcCommand);
		$cmds[] = sprintf("%s qdisc del dev imq2 root 2>/dev/null", $tcCommand);
		$cmds[] = sprintf("%s qdisc del dev imq3 root 2>/dev/null", $tcCommand);
		
		$cmds[] = sprintf("%s link set imq0 down 2>/dev/null", $ipCommand);
		$cmds[] = sprintf("%s link set imq1 down 2>/dev/null", $ipCommand);
		$cmds[] = sprintf("%s link set imq2 down 2>/dev/null", $ipCommand);
		$cmds[] = sprintf("%s link set imq3 down 2>/dev/null", $ipCommand);
		
		return self::parseArrayReadable($executor->executeArray($cmds));
	}
	
	public function getQosUp($executor) {
		$cmds = array();
		
		$iptablesCommand = $this->networkDevice->ND_commandIptables;
		$ipCommand = $this->networkDevice->ND_commandIp;
		$tcCommand = $this->networkDevice->ND_commandTc;
		$lanInterfaces = $this->networkDevice->lanInterfaces;
		$wanInterface = $this->networkDevice->wanInterface;
		$bandwidthMargin = $this->bandwidthMargin;
		
		$cmds[] = sprintf("%s link set imq0 up", $ipCommand);
		$cmds[] = sprintf("%s link set imq1 up", $ipCommand);
		$cmds[] = sprintf("%s link set imq2 up", $ipCommand);
		$cmds[] = sprintf("%s link set imq3 up", $ipCommand);
		
		$cmds[] = sprintf("%s -t mangle -N QOS-ETH-IN", $iptablesCommand);
		$cmds[] = sprintf("%s -t mangle -N QOS-IN-SMARK", $iptablesCommand);
		
		$cmds[] = sprintf("%s -t mangle -N QOS-ETH-OUT", $iptablesCommand);
		$cmds[] = sprintf("%s -t mangle -N QOS-OUT-SMARK", $iptablesCommand);
		
		/*
		 * Point traffic to SHAPER chains and IMQs
		 */
		 
		/*
		 * Download
		 * wan
		 * ...
		 * nat
		 * ...
		 * PREROUTING ->QOS-IN
		 * QOS-IN -> IMQ0
		 * QOS-IN -> QOS-IN-SMARK mark services
		 * QOS-IN -> IMQ2
		 * ...
		 * lan
		 */
		foreach ($lanInterfaces as $lanInterface) {
			$cmds[] = sprintf("%s -t mangle -A POSTROUTING -o %s -s ! 10.0.0.0/8 -d 10.0.0.0/8 -j QOS-ETH-IN", $iptablesCommand, $lanInterface);
		}
		$cmds[] = sprintf("%s -t mangle -A QOS-ETH-IN -j QOS-IN-SMARK", $iptablesCommand);
		$cmds[] = sprintf("%s -t mangle -A QOS-ETH-IN -j IMQ --todev 2", $iptablesCommand);
		/*
		 * Upload
		 * lan
		 * ...
		 * POSTROUTING -> QOS-OUT
		 * QOS-OUT -> IMQ1
		 * QOS-OUT -> QOS-OUT-SMARK mark services 
		 * QOS-OUT -> IMQ3
		 * ...
		 * nat
		 * ...
		 * wan
		 */
		foreach ($lanInterfaces as $lanInterface) {
			$cmds[] = sprintf("%s -t mangle -A PREROUTING -i %s -s 10.0.0.0/8 -d ! 10.0.0.0/8 -j QOS-ETH-OUT", $iptablesCommand, $lanInterface);
		}
		$cmds[] = sprintf("%s -t mangle -A QOS-ETH-OUT -j QOS-OUT-SMARK", $iptablesCommand);
		$cmds[] = sprintf("%s -t mangle -A QOS-ETH-OUT -j IMQ --todev 3", $iptablesCommand);
		
		
		$cmds[] = sprintf("%s -t mangle -A QOS-IN-SMARK -j CONNMARK --restore-mark", $iptablesCommand);
		$cmds[] = sprintf("%s -t mangle -A QOS-IN-SMARK -m mark ! --mark 0 -j RETURN", $iptablesCommand);
		
		$cmds[] = sprintf("%s -t mangle -A QOS-IN-SMARK -m ipp2p --ipp2p -j MARK --set-mark 19", $iptablesCommand);
		$cmds[] = sprintf("%s -t mangle -A QOS-IN-SMARK -m mark --mark 19 -j CONNMARK --save-mark", $iptablesCommand);
		$cmds[] = sprintf("%s -t mangle -A QOS-IN-SMARK -m mark ! --mark 0 -j RETURN", $iptablesCommand);
		$cmds[] = sprintf("%s -t mangle -A QOS-IN-SMARK -j MARK --set-mark 12", $iptablesCommand);
		
		$cmds[] = sprintf("%s -t mangle -A QOS-OUT-SMARK -j CONNMARK --restore-mark", $iptablesCommand);
		$cmds[] = sprintf("%s -t mangle -A QOS-OUT-SMARK -m mark ! --mark 0 -j RETURN", $iptablesCommand);
		
		$cmds[] = sprintf("%s -t mangle -A QOS-OUT-SMARK -m ipp2p --ipp2p -j MARK --set-mark 19", $iptablesCommand);
		$cmds[] = sprintf("%s -t mangle -A QOS-OUT-SMARK -m mark --mark 19 -j CONNMARK --save-mark", $iptablesCommand);
		$cmds[] = sprintf("%s -t mangle -A QOS-OUT-SMARK -m mark ! --mark 0 -j RETURN", $iptablesCommand);
		$cmds[] = sprintf("%s -t mangle -A QOS-OUT-SMARK -j MARK --set-mark 12", $iptablesCommand);
		
		//Define rates and ceils
		$rootDownloadCeil = $rootDownloadRate = floor($this->networkDevice->ND_qosBandwidthDownload * (100 - $bandwidthMargin) / 100);
		$rootUploadCeil   = $rootUploadRate   = floor($this->networkDevice->ND_qosBandwidthUpload   * (100 - $bandwidthMargin) / 100);
		
		
		$interactiveDownloadRate = 1;
		$interactiveDownloadCeil = floor($this->networkDevice->ND_qosBandwidthDownload * (100 - $bandwidthMargin) / 100 / 10);
		
		$interactiveUploadRate = 1;
		$interactiveUploadCeil = floor($this->networkDevice->ND_qosBandwidthUpload * (100 - $bandwidthMargin) / 100 / 10);
		
		
		$mainDownloadRate = 1;
		$mainDownloadCeil = floor($this->networkDevice->ND_qosBandwidthDownload * (100 - $bandwidthMargin) / 100);
		
		$mainUploadRate = 1;
		$mainUploadCeil = floor($this->networkDevice->ND_qosBandwidthUpload * (100 - $bandwidthMargin) / 100);
		
		
		$p2pDownloadRate = 1;
		$p2pDownloadCeil = floor($this->networkDevice->ND_qosBandwidthDownload * (100 - $bandwidthMargin) / 100);
		
		$p2pUploadRate = 1;
		$p2pUploadCeil = floor($this->networkDevice->ND_qosBandwidthUpload * (100 - $bandwidthMargin) / 100);
		
		/**
		 * QoS for services
		 * imq2 is download iface
		 * imq3 is upload iface
		 * icmp protocol and tcp SYN,RST,ACK flags will be in interactive class to speed up download and upload
		 */

		//root qdisc
		//
		$cmds[] = sprintf("%s qdisc add dev imq2 root handle 1:0 htb default 12", $tcCommand);
		$cmds[] = sprintf("%s qdisc add dev imq3 root handle 1:0 htb default 12", $tcCommand);
		
		//root class contain all the traffic to prevent queuing at ISP
		//
		$cmds[] = sprintf("%s class add dev imq2 parent 1:0 classid 1:10 htb rate %skbit ceil %skbit quantum 30000", $tcCommand, $rootDownloadRate, $rootDownloadCeil);
		$cmds[] = sprintf("%s class add dev imq3 parent 1:0 classid 1:10 htb rate %skbit ceil %skbit quantum 30000", $tcCommand, $rootUploadRate, $rootUploadCeil);

		//interactive class
		//
		$cmds[] = sprintf("%s class add dev imq2 parent 1:10 classid 1:11 htb rate %skbit ceil %skbit quantum 5000 prio 0", $tcCommand, $interactiveDownloadRate, $interactiveDownloadCeil);
		$cmds[] = sprintf("%s class add dev imq3 parent 1:10 classid 1:11 htb rate %skbit ceil %skbit quantum 5000 prio 0", $tcCommand, $interactiveUploadRate, $interactiveUploadCeil);
		
		$cmds[] = sprintf("%s qdisc add dev imq2 parent 1:11 handle 11:0 esfq perturb 10 hash dst", $tcCommand);
		$cmds[] = sprintf("%s qdisc add dev imq3 parent 1:11 handle 11:0 esfq perturb 10 hash src", $tcCommand);
		
		$cmds[] = sprintf("%s filter add dev imq2 parent 1:0 protocol ip handle 11 fw flowid 1:11", $tcCommand);
		$cmds[] = sprintf("%s filter add dev imq3 parent 1:0 protocol ip handle 11 fw flowid 1:11", $tcCommand);
		
		//main class
		//
		$cmds[] = sprintf("%s class add dev imq2 parent 1:10 classid 1:12 htb rate %skbit ceil %skbit quantum 15000 prio 1", $tcCommand, $mainDownloadRate, $mainDownloadCeil);
		$cmds[] = sprintf("%s class add dev imq3 parent 1:10 classid 1:12 htb rate %skbit ceil %skbit quantum 15000 prio 1", $tcCommand, $mainUploadRate, $mainUploadCeil);
		
		$cmds[] = sprintf("%s qdisc add dev imq2 parent 1:12 handle 12:0 esfq perturb 10 hash dst", $tcCommand);
		$cmds[] = sprintf("%s qdisc add dev imq3 parent 1:12 handle 12:0 esfq perturb 10 hash src", $tcCommand);
		
		$cmds[] = sprintf("%s filter add dev imq2 parent 1:0 protocol ip handle 12 fw flowid 1:12", $tcCommand);
		$cmds[] = sprintf("%s filter add dev imq3 parent 1:0 protocol ip handle 12 fw flowid 1:12", $tcCommand);
		
		//p2p class
		//
		$cmds[] = sprintf("%s class add dev imq2 parent 1:10 classid 1:19 htb rate %skbit ceil %skbit quantum 15000 prio 2", $tcCommand, $p2pDownloadRate, $p2pDownloadCeil);
		$cmds[] = sprintf("%s class add dev imq3 parent 1:10 classid 1:19 htb rate %skbit ceil %skbit quantum 15000 prio 2", $tcCommand, $p2pUploadRate, $p2pUploadCeil);
		
		$cmds[] = sprintf("%s qdisc add dev imq2 parent 1:19 handle 19:0 esfq perturb 10 hash dst", $tcCommand);
		$cmds[] = sprintf("%s qdisc add dev imq3 parent 1:19 handle 19:0 esfq perturb 10 hash src", $tcCommand);
		
		$cmds[] = sprintf("%s filter add dev imq2 parent 1:0 protocol ip handle 19 fw flowid 1:19", $tcCommand);
		$cmds[] = sprintf("%s filter add dev imq3 parent 1:0 protocol ip handle 19 fw flowid 1:19", $tcCommand);
		
		
		/**
		 * QOS for customers
		 * imq0 is download iface
		 * imq1 is upload iface
		 */
		 
		//root qdisc
		$cmds[] = sprintf("%s qdisc add dev imq0 root handle 1:0 htb default 100", $tcCommand);
		$cmds[] = sprintf("%s qdisc add dev imq1 root handle 1:0 htb default 100", $tcCommand);
		
		//root class contain all the traffic to prevent queuing at ISP
		$cmds[] = sprintf("%s class add dev imq0 parent 1:0 classid 1:1 htb rate %skbit ceil %skbit quantum 30000", $tcCommand, $rootDownloadRate, $rootDownloadCeil);
		$cmds[] = sprintf("%s class add dev imq1 parent 1:0 classid 1:1 htb rate %skbit ceil %skbit quantum 30000", $tcCommand, $rootUploadRate, $rootUploadCeil);
		
		// default class for unknown customers, very slow
		$cmds[] = sprintf("%s class add dev imq0 parent 1:1 classid 1:100 htb rate 16kbit ceil 16kbit", $tcCommand);
		$cmds[] = sprintf("%s class add dev imq1 parent 1:1 classid 1:100 htb rate 16kbit ceil 16kbit", $tcCommand);
		
		
		// Each customer will get class, sfq qdisc and filter that will redirect traffic to it's class according to packet MARK
		$classid = self::TC_CLASSID_START;
		foreach ($this->networkDevice->NETWORKS as &$network) {
			foreach ($network['INTERNETS'] as &$internet) {
				$cmds[] = sprintf("%s class add dev imq0 parent 1:1 classid 1:%s htb rate %skbit ceil %skbit quantum 15000 burst 16k cburst 16k prio 1", $tcCommand, $classid, $internet['IN_dnl_rate'], $internet['IN_dnl_ceil']);
				$cmds[] = sprintf("%s class add dev imq1 parent 1:1 classid 1:%s htb rate %skbit ceil %skbit quantum 15000 burst 16k cburst 16k prio 1", $tcCommand, $classid, $internet['IN_upl_rate'], $internet['IN_upl_ceil']);
				
				$cmds[] = sprintf("%s qdisc add dev imq0 parent 1:%s handle %s:0 pfifo limit 10", $tcCommand, $classid, $classid);
				$cmds[] = sprintf("%s qdisc add dev imq1 parent 1:%s handle %s:0 pfifo limit 10", $tcCommand, $classid, $classid);
				
				foreach ($internet['IPS'] as &$ip) {
					$ipAddress = $ip['IP_address'];
					
					$cmds[] = sprintf("%s filter add dev imq0 parent 1:0 protocol ip prio 1 u32 match ip dst %s flowid 1:%s", $tcCommand, $ipAddress, $classid);
					$cmds[] = sprintf("%s filter add dev imq1 parent 1:0 protocol ip prio 1 u32 match ip src %s flowid 1:%s", $tcCommand, $ipAddress, $classid);
				}
				$classid++;
			}
		}
		
		$cmds[] = sprintf("%s -t mangle -A PREROUTING  -i %s -j IMQ --todev 0", $iptablesCommand, $wanInterface);
		$cmds[] = sprintf("%s -t mangle -A POSTROUTING -o %s -j IMQ --todev 1", $iptablesCommand, $wanInterface);
		
		return self::parseArrayReadable($executor->executeArray($cmds));
	}
	
	static function parseCommandReadable($array) {
		return $array;
	}
	
	static function parseArrayReadable($array) {
		return $array;
	}
} // End of LinuxCommander class
?>