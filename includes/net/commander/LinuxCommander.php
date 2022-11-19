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
    const CHAIN_ACCT_IN = 'FILTER-IN';
    const CHAIN_ACCT_OUT = 'FILTER-OUT';

    const IPTABLES_CHAIN_HEADER = 'Chain %s (1 references)';
    const IPTABLES_LIST_HEADER = 'pkts[[:space:]]+bytes[[:space:]]+target[[:space:]]+prot[[:space:]]+opt[[:space:]]+in[[:space:]]+out[[:space:]]+source[[:space:]]+destination';

    const IPTABLES_LIST_ENTRY = '^([[:digit:]]+)[[:space:]]+([[:digit:]]+)[[:space:]]+ACCEPT[[:space:]]+all[[:space:]]+--[[:space:]]+\*[[:space:]]+\*[[:space:]]+([[:digit:]]{1,3}\.[[:digit:]]{1,3}\.[[:digit:]]{1,3}\.[[:digit:]]{1,3}(/[[:digit:]]{1,2})?)[[:space:]]+([[:digit:]]{1,3}\.[[:digit:]]{1,3}\.[[:digit:]]{1,3}\.[[:digit:]]{1,3}(/[[:digit:]]{1,2})?)$';

    private $networkDevice;

    private $globalIPFilterEnabled;
    private $rejectUnknownIP;
    private $redirectUnknownIP;
    private $redirectToIP;
    private $allowedHosts;

    public function __construct($networkDevice) {
        global $core;

        $this->networkDevice = $networkDevice;

        $this->globalIPFilterEnabled = $core->getProperty(Core::GLOBAL_IP_FILTER_ENABLED);
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

    static function parseCommandReadable($array) {
        return $array;
    }

    static function parseArrayReadable($array) {
        return $array;
    }
} // End of LinuxCommander class
?>
