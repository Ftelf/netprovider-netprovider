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
// | Authors: Lukas Dziadkowiec <i.ftelf@gmail.com>                  |
// +----------------------------------------------------------------------+

/**
 * @author  Lukas Dziadkowiec <i.ftelf@gmail.com>
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
require_once($core->getAppRoot() . "includes/utils/DiacriticsUtil.php");
require_once 'Net/IPv4.php';

if (! function_exists('array_column')) {
    function array_column(array $input, $columnKey, $indexKey = null) {
        $array = array();
        foreach ($input as $value) {
            if ( !array_key_exists($columnKey, $value)) {
                trigger_error("Key \"$columnKey\" does not exist in array");
                return false;
            }
            if (is_null($indexKey)) {
                $array[] = $value[$columnKey];
            }
            else {
                if ( !array_key_exists($indexKey, $value)) {
                    trigger_error("Key \"$indexKey\" does not exist in array");
                    return false;
                }
                if ( ! is_scalar($value[$indexKey])) {
                    trigger_error("Key \"$indexKey\" does not contain scalar value");
                    return false;
                }
                $array[$value[$indexKey]] = $value[$columnKey];
            }
        }
        return $array;
    }
}

/**
 * RouterOSCommander
 */
class RouterOSCommander {
    const TC_CLASSID_START = 101;

    const FAIR_QUEUE_DISCIPLINE = "esfq";
    const USER_QUEUE_DISCIPLINE = "pfifo";

    const CHAIN_ACCT_IN = 'FILTER-IN';
    const CHAIN_ACCT_OUT = 'FILTER-OUT';

    const IPTABLES_CHAIN_HEADER = 'Chain %s (1 references)';
    const IPTABLES_LIST_HEADER = 'pkts[[:space:]]+bytes[[:space:]]+target[[:space:]]+prot[[:space:]]+opt[[:space:]]+in[[:space:]]+out[[:space:]]+source[[:space:]]+destination';

    const IPTABLES_LIST_ENTRY = '^([[:digit:]]+)[[:space:]]+([[:digit:]]+)[[:space:]]+ACCEPT[[:space:]]+all[[:space:]]+--[[:space:]]+\*[[:space:]]+\*[[:space:]]+([[:digit:]]{1,3}\.[[:digit:]]{1,3}\.[[:digit:]]{1,3}\.[[:digit:]]{1,3}(/[[:digit:]]{1,2})?)[[:space:]]+([[:digit:]]{1,3}\.[[:digit:]]{1,3}\.[[:digit:]]{1,3}\.[[:digit:]]{1,3}(/[[:digit:]]{1,2})?)$';

    private $networkDevice;

    private $bandwidthMargin;
    private $rejectUnknownIP;
    private $redirectUnknownIP;
    private $redirectToIP;
    private $allowedHosts;

    public function __construct($networkDevice) {
        global $core;

        $this->networkDevice = $networkDevice;

        $this->bandwidthMargin = $core->getProperty(Core::QOS_BANDWIDTH_MARGIN_PERCENT);
        $this->rejectUnknownIP = $core->getProperty(Core::REJECT_UNKNOWN_IP);
        $this->redirectUnknownIP = $core->getProperty(Core::REDIRECT_UNKNOWN_IP);
        $this->redirectToIP = $core->getProperty(Core::REDIRECT_TO_IP);
        $this->allowedHosts = explode(";", $core->getProperty(Core::ALLOWED_HOSTS));
    }

    public function accountIP($executor) {
        global $database;

        $now = new DateUtil();
        $dateString = $now->getFormattedDate(DateUtil::DB_DATETIME);

        $ipArray = array();

        $filterInIpCmd = array("/ip/firewall/filter/print", "?=chain=FILTER-IN", "?=action=accept", "=stats=");
        $filterInIpResult = $executor->execute($filterInIpCmd);
        foreach ($filterInIpResult[1] as $filterInIp) {
            $acc = array();
            $acc['bytes-in'] = $filterInIp['bytes'];
            $acc['packets-in'] = $filterInIp['packets'];
            $ipArray[$filterInIp['dst-address']] = $acc;
        }

        $filterOutIpCmd = array("/ip/firewall/filter/print", "?=chain=FILTER-OUT", "?=action=accept", "=stats=");
        $filterOutIpResult = $executor->execute($filterOutIpCmd);
        foreach ($filterOutIpResult[1] as $filterOutIp) {
            if (isset($ipArray[$filterOutIp['src-address']])) {
                $ipArray[$filterOutIp['src-address']]['bytes-out'] = $filterOutIp['bytes'];
                $ipArray[$filterOutIp['src-address']]['packets-out'] = $filterOutIp['packets'];
            } else {
                echo "error";
            }
        }

        foreach ($ipArray as $key=>$ipResult) {
            try {
                $ip = IpDAO::getIpByIP($key);
            } catch (Exception $e) {
                throw new Exception(sprintf("there is no IP %s in database", $key));
            }

            $inBytes = $ipResult['bytes-in'];
            $outBytes = $ipResult['bytes-out'];
            $inPackets = $ipResult['packets-in'];
            $outPackets = $ipResult['packets-out'];

            try {
                $ipAccountAbs = IpAccountAbsDAO::getIpAccountAbsByIpID($ip->IP_ipid);
            } catch (Exception $e) {
                $ipAccountAbs = new IpAccountAbs();
                $ipAccountAbs->IB_ipid = $ip->IP_ipid;
                $ipAccountAbs->IB_bytes_in = $inBytes;
                $ipAccountAbs->IB_bytes_out = $outBytes;
                $ipAccountAbs->IB_packets_in = $inPackets;
                $ipAccountAbs->IB_packets_out = $outPackets;
                $database->insertObject("ipaccountabs", $ipAccountAbs, "IB_ipaccountabsid", false);
            }

            $ipAccount = new IpAccount();
            $ipAccount->IA_ipid = $ip->IP_ipid;

            $ipAccount->IA_bytes_in =  ($inBytes  >= $ipAccountAbs->IB_bytes_in)  ? $inBytes  - $ipAccountAbs->IB_bytes_in  : $inBytes;
            $ipAccount->IA_bytes_out = ($outBytes >= $ipAccountAbs->IB_bytes_out) ? $outBytes - $ipAccountAbs->IB_bytes_out : $outBytes;
            $ipAccount->IA_packets_in  = ($inPackets  >= $ipAccountAbs->IB_packets_in)  ? $inPackets  - $ipAccountAbs->IB_packets_in  : $inPackets;
            $ipAccount->IA_packets_out = ($outPackets >= $ipAccountAbs->IB_packets_out) ? $outPackets - $ipAccountAbs->IB_packets_out : $outPackets;

            $ipAccountAbs->IB_bytes_in = $inBytes;
            $ipAccountAbs->IB_bytes_out = $outBytes;
            $ipAccountAbs->IB_packets_in = $inPackets;
            $ipAccountAbs->IB_packets_out = $outPackets;

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

    public function getIPFilterDown($executor) {
        $cmds = array();

        $iptablesCommand = $this->networkDevice->ND_commandIptables;
        $wanInterface = $this->networkDevice->wanInterface;

//		$cmds[] = sprintf("%s -D FORWARD -i %s -j FILTER-IN 2>/dev/null", $iptablesCommand, $wanInterface);
//		$cmds[] = sprintf("%s -D FORWARD -o %s -j FILTER-OUT 2>/dev/null", $iptablesCommand, $wanInterface);
//		$cmds[] = sprintf("%s -t nat -D PREROUTING -j WEB-REDIRECT 2>/dev/null", $iptablesCommand);
//

        $cmd = array("/ip/firewall/filter/print", "?chain=FILTER-IN", "=.proplist=.id");
        $filterArray = $executor->execute($cmd);
        $cmds[] = $filterArray;

        if (count($filterArray[1])) {
            $idArray = array_column($filterArray[1], '.id');
            $ids = implode(',', $idArray);

            $cmd = array("/ip/firewall/filter/remove", sprintf("=numbers=%s", $ids));
            $cmds[] = $executor->execute($cmd);
        }


        $cmd = array("/ip/firewall/filter/print", "?chain=FILTER-OUT", "=.proplist=.id");
        $filterArray = $executor->execute($cmd);
        $cmds[] = $filterArray;

        if (count($filterArray[1])) {
            $idArray = array_column($filterArray[1], '.id');
            $ids = implode(',', $idArray);

            $cmd = array("/ip/firewall/filter/remove", sprintf("=numbers=%s", $ids));
            $cmds[] = $executor->execute($cmd);
        }

        return self::parseArrayReadable($cmds);
    }

    public function getIPFilterUp($executor) {
        $cmds = array();

        $iptablesCommand = $this->networkDevice->ND_commandIptables;
        $wanInterface = $this->networkDevice->wanInterface;
        $redirectIPEnabled = $this->rejectUnknownIP && $this->redirectUnknownIP;
        $redirectToIP = $this->redirectToIP;
        $diacriticsUtil = new DiacriticsUtil();

//		$cmds[] = '/ip/firewall/filter/getall';
//		if ($redirectIPEnabled) {
//			$cmds[] = sprintf("%s -t nat -N WEB-REDIRECT", $iptablesCommand);
//		}

        $usedIpAddresses = array();
        foreach ($this->networkDevice->NETWORKS as &$network) {
            foreach ($network['INTERNETS'] as &$internet) {
                foreach ($internet['IPS'] as &$ip) {
                    $ipAddress = $ip['IP_address'];
                    if (array_search($ipAddress, $usedIpAddresses)) {
                        continue;
                    }
                    $usedIpAddresses[] = $ipAddress;
                    //accept all known clients
                    $comment = $diacriticsUtil->removeDiacritic($internet['PE_firstname'] . " " . $internet['PE_surname'] . ", " . $ip['IP_dns']);
                    $cmds[] = array("/ip/firewall/filter/add", "=chain=FILTER-IN",  sprintf("=dst-address=%s", $ipAddress), sprintf("=comment=%s", $comment), "=action=accept");
                    $cmds[] = array("/ip/firewall/filter/add", "=chain=FILTER-OUT", sprintf("=src-address=%s", $ipAddress), sprintf("=comment=%s", $comment), "=action=accept");
//					if ($redirectIPEnabled) {
//						$cmds[] = sprintf("/ip firewall nat add chain=WEB-REDIRECT src-address=%s action=return", $ipAddress);
//					}
                }
            }
        }

        // this will enable access to certail ips even when internet is not enabled for this ip
        //
// 		foreach ($this->allowedHosts as &$host) {
// 			$parts = explode(":", $host);

// 			$cmds[] = array("/ip/firewall/filter/add", "=chain=FILTER-IN", "=protocol=tcp",  sprintf("=src-address=%s", $parts[0]), "=action=accept");

// 			if (count($parts) == 1) {
// 				$cmds[] = array("/ip/firewall/filter/add", "=chain=FILTER-OUT", "=protocol=tcp", sprintf("=dst-address=%s", $parts[0]), "=action=accept");
// 			} else if (count($parts) == 2) {
// 				$cmds[] = array("/ip/firewall/filter/add", "=chain=FILTER-OUT", "=protocol=tcp", sprintf("=dst-address=%s", $parts[0]), sprintf("=dst-port=%s", $parts[1]), "=action=accept");
// 			}
// 		}

// 		/**
// 		 * all unknown clients will be logged
// 		 */
        $cmds[] = array("/ip/firewall/filter/add", "=chain=FILTER-IN", "=limit=1/3600,1", "=action=log", "=log-prefix=UNKNOWN-IN:");
        $cmds[] = array("/ip/firewall/filter/add", "=chain=FILTER-OUT", "=limit=1/3600,1", "=action=log", "=log-prefix=UNKNOWN-OUT:");

        $cmds[] = array("/ip/firewall/filter/add", "=chain=FILTER-IN", "=action=reject");
        $cmds[] = array("/ip/firewall/filter/add", "=chain=FILTER-OUT", "=action=reject");

// 		if ($this->rejectUnknownIP) {
// 			/**
// 			 * all unknown clients will be rejected
// 			 */
// 			$cmds[] = array("/ip/firewall/filter/add", "=chain=FILTER-IN",  "=action=reject");
// 			$cmds[] = array("/ip/firewall/filter/add", "=chain=FILTER-OUT", "=action=reject");
// 		}

        // this will enable access to certail ips even when internet is not enabled for this ip in NAT chain
//		if ($redirectIPEnabled) {
//			foreach ($this->allowedHosts as &$host) {
//				$parts = explode(":", $host);
//
//				$cmds[] = sprintf("/ip firewall nat add chain=WEB-REDIRECT drc-address=%s action=return",  $parts[0]);
//			}
//			$cmds[] = sprintf("/ip firewall nat add chain=WEB-REDIRECT protocol=tcp dst-port=80 jump-target=dstnat to-addresses=%s to-ports=80", $redirectToIP);
//		}

        //$cmds[] = array("/ip/firewall/filter/add", "=chain=forward", sprintf("=in-interface=%s", $wanInterface), "=action=jump", "=jump-target=FILTER-IN");
        //$cmds[] = array("/ip/firewall/filter/add", "=chain=forward", sprintf("=out-interface=%s", $wanInterface), "=action=jump", "=jump-target=FILTER-OUT");

        if ($redirectIPEnabled) {
//			$cmds[] = sprintf("%s -t nat -A PREROUTING -j WEB-REDIRECT", $iptablesCommand);
        }

        return self::parseArrayReadable($executor->executeArray($cmds));
    }

    public function getQosDown($executor) {
        $cmds = array();

        return self::parseArrayReadable($executor->executeArray($cmds));
    }

    public function getQosUp($executor) {
        $cmds = array();

        return self::parseArrayReadable($executor->executeArray($cmds));
    }

    static function parseCommandReadable($array) {
        $resultArray = array();

        $resultArray[0] = implode('', $array[0]);

        $return1 = array();
        if (is_array($array[1])) {
            foreach ($array[1] as $returnPart) {
                $string = '';
                foreach ($returnPart as $key=>$value) {
                    $string .= $key."=".$value.' ';
                }
                $return1[] = $string;
            }
            $resultArray[1] = implode("<br/>", $return1);
        } else {
            $resultArray[1] = null;
        }

        $resultArray[2] = null;

        return $resultArray;
    }

    static function parseArrayReadable($array) {
        $resultArray = array();

        foreach ($array as $command) {
            $resultArray[] = self::parseCommandReadable($command);
        }

        return $resultArray;
    }
} // End of RouterOSCommander class
?>
