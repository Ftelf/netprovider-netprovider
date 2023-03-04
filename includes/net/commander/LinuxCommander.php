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
require_once $core->getAppRoot() . "includes/dao/PersonDAO.php";
require_once $core->getAppRoot() . "includes/dao/ChargeDAO.php";
require_once $core->getAppRoot() . "includes/dao/HasChargeDAO.php";
require_once $core->getAppRoot() . "includes/dao/IpDAO.php";
require_once $core->getAppRoot() . "includes/dao/NetworkDAO.php";
require_once $core->getAppRoot() . "includes/dao/InternetDAO.php";
require_once $core->getAppRoot() . "includes/dao/IpAccountAbsDAO.php";
require_once $core->getAppRoot() . "includes/dao/IpAccountDAO.php";
require_once $core->getAppRoot() . "includes/utils/Utils.php";
require_once 'Net/IPv4.php';
require_once($core->getAppRoot() . "includes/net/SSH2.php");

/**
 * LinuxCommander
 */
class LinuxCommander
{
    private const CHAIN_ACCT_IN = 'FILTER-IN';
    private const CHAIN_ACCT_OUT = 'FILTER-OUT';

    private const IPTABLES_CHAIN_HEADER = 'Chain %s (1 references)';
    private const IPTABLES_LIST_HEADER = 'pkts\s+bytes\s+target\s+prot\s+opt\s+in\s+out\s+source\s+destination';

    private const IPTABLES_LIST_ENTRY = '^([[:digit:]]+)\s+([[:digit:]]+)\s+ACCEPT\s+all\s+--\s+\*\s+\*\s+([[:digit:]]{1,3}\.[[:digit:]]{1,3}\.[[:digit:]]{1,3}\.[[:digit:]]{1,3}(/[[:digit:]]{1,2})?)\s+([[:digit:]]{1,3}\.[[:digit:]]{1,3}\.[[:digit:]]{1,3}\.[[:digit:]]{1,3}(/[[:digit:]]{1,2})?)$';

    private $ssh2;

    private $networks;

    private $isExecutionEnabled;

    private $host;

    private $port;

    private $login;

    private $password;

    private $wanInterface;

    private $commandSudo;

    private $commandIptables;

    private $isIPAccountingEnabled;

    private $isIPFilterEnabled;

    public function __construct($networks, $isExecutionEnabled)
    {
        global $core;

        $this->networks = $networks;
        $this->isExecutionEnabled = $isExecutionEnabled;

        $this->host = $core->getProperty(Core::NETWORK_DEVICE_HOST);
        $this->port = $core->getProperty(Core::NETWORK_DEVICE_PORT);
        $this->login = $core->getProperty(Core::NETWORK_DEVICE_LOGIN);
        $this->password = $core->getProperty(Core::NETWORK_DEVICE_PASSWORD);
        $this->wanInterface = $core->getProperty(Core::NETWORK_DEVICE_WAN_INTERFACE);
        $this->commandSudo = $core->getProperty(Core::NETWORK_DEVICE_COMMAND_SUDO);
        $this->commandIptables = $core->getProperty(Core::NETWORK_DEVICE_COMMAND_IPTABLES);
        $this->isIPAccountingEnabled = $core->getProperty(Core::NETWORK_DEVICE_IP_ACCOUNTING);
        $this->isIPFilterEnabled = $core->getProperty(Core::NETWORK_DEVICE_IP_FILTER);

        $this->login();
    }

    private function login() {
        if (!$this->host) {
            throw new Exception("No host specified");
        }

        if (!$this->port) {
            $this->port = 22;
        }

        if (!$this->login) {
            throw new Exception("Configuration settings for API must be specified");
        }

        if (!$this->password) {
            throw new Exception("Configuration settings for SSH2 must be specified");
        }

        if ($this->isExecutionEnabled) {
            try {
                $this->ssh2 = new SSH2($this->host, $this->port);
                $this->ssh2->login($this->login, $this->password);
            } catch (Exception $e) {
                throw new Exception(sprintf(_("SSH2 login failed at %s@%s"), $this->login, $this->host));
            }
        }
    }

    public function disconnect()
    {
        if ($this->isExecutionEnabled) {
            return $this->ssh2->exec(sprintf("%s %s", $this->commandSudo, 'exit'));
        }
    }

    public function execute($command)
    {
        if ($this->isExecutionEnabled) {
            return $this->ssh2->exec(sprintf("%s %s", $this->commandSudo, $command));
        } else {
            return array(
                sprintf("ssh %s@%s %s %s", $this->login, $this->host, $this->commandSudo, $command),
                null,
                null
            );
        }
    }

    public function executeArray($commands)
    {
        if (!isset($commands) || !is_array($commands)) {
            throw new Exception("parameter must be array");
        }

        $results = array();

        foreach ($commands as $command) {
            $results[] = $this->execute($command);
        }

        return $results;
    }

//    public function accountIP(): void
//    {
//        global $database;
//
//        $now = new DateUtil();
//        $dateString = $now->getFormattedDate(DateUtil::DB_DATETIME);
//
//        foreach ($networkDevices as &$networkDevice) {
//            if ($this->globalIPFilterEnabled && $networkDevice->ND_ipFilterEnabled) {
//                if (isset($networkDevice->MANAGEMENT_IP)) {
//                    $settings = [];
//                    $settings[Executor::REMOTE_HOST] = $networkDevice->MANAGEMENT_IP;
//                    $settings[Executor::REMOTE_PORT] = 22;
//                    $settings[Executor::LOGIN] = $networkDevice->ND_login;
//                    $settings[Executor::PASSWORD] = $networkDevice->ND_password;
//                    $settings[Executor::SUDO_COMMAND] = $networkDevice->ND_commandSudo;
//
//                    $executor = new Executor(Executor::REMOTE_SSH2, $settings, true);
//                } else {
//                    $settings = [];
//                    $settings[Executor::SUDO_COMMAND] = $networkDevice->ND_commandSudo;
//
////                    $executor = new Executor(Executor::LOCAL_COMMAND, $settings, true);
//                }
//
//                $filterIn = null;
//                $filterOut = null;
//
//                $cmdIn = sprintf("%s -xvnL %s", $networkDevice->ND_commandIptables, LinuxCommander::CHAIN_ACCT_IN);
//                $filterIn = $executor->executeArray(array($cmdIn));
//                if ($filterIn[0][2]) {
//                    throw new Exception(sprintf("error proceeding command '%s' on network device '%s'", $cmdIn, $networkDevice->ND_name));
//                }
//
//                $cmdOut = sprintf("%s -xvnL %s", $networkDevice->ND_commandIptables, LinuxCommander::CHAIN_ACCT_OUT);
//                $filterOut = $executor->executeArray(array($cmdOut));
//                if ($filterOut[0][2]) {
//                    throw new Exception(sprintf("error proceeding command '%s' on network device '%s'", $cmdOut, $networkDevice->ND_name));
//                }
//
//                $filterInArr = Utils::stringAsLineArray($filterIn[0][1]);
//                $filterOutArr = Utils::stringAsLineArray($filterOut[0][1]);
//
//
//                if ($filterInArr[0] != sprintf(LinuxCommander::IPTABLES_CHAIN_HEADER, LinuxCommander::CHAIN_ACCT_IN)) {
//                    throw new Exception(sprintf("cannot match chain head in FILTER-IN: %s", $filterInArr[0]));
//                }
//
//                if ($filterOutArr[0] != sprintf(LinuxCommander::IPTABLES_CHAIN_HEADER, LinuxCommander::CHAIN_ACCT_OUT)) {
//                    throw new Exception(sprintf("cannot match chain head in FILTER-OUT: %s", $filterOutArr[0]));
//                }
//
//                if (!preg_match(LinuxCommander::IPTABLES_LIST_HEADER, $filterInArr[1], $matches)) {
//                    throw new Exception(sprintf("cannot match head in FILTER-IN: %s", $filterInArr[1]));
//                }
//
//                if (!preg_match(LinuxCommander::IPTABLES_LIST_HEADER, $filterOutArr[1], $matches)) {
//                    throw new Exception(sprintf("cannot match head in FILTER-OUT: %s", $filterOutArr[1]));
//                }
//
//                $ipArray = [];
//                $l = count($filterInArr);
//                for ($i = 2; $i < $l; $i++) {
//                    $iIn = trim($filterInArr[$i]);
//                    $iOut = trim($filterOutArr[$i]);
//
//                    if (preg_match(LinuxCommander::IPTABLES_LIST_ENTRY, $iIn, $matchesIn) && preg_match(LinuxCommander::IPTABLES_LIST_ENTRY, $iOut, $matchesOut)) {
//                        if ($matchesIn[5] == $matchesOut[3] && $matchesIn[3] == $matchesOut[5]) {
//                            $ipArray[] = [
//                                "IP-SRC" => $matchesIn[5],
//                                "IP-DST" => $matchesIn[3],
//                                "BYTES-IN" => $matchesIn[2],
//                                "BYTES-OUT" => $matchesOut[2],
//                                "PACKETS-IN" => $matchesIn[1],
//                                "PACKETS-OUT" => $matchesOut[1],
//                            ];
//                        } else {
//                            throw new Exception(sprintf("iptables FILTER-IN and FILTER-OUT mismatch IPs: %s != %s OR %s != %s", $matchesIn[5], $matchesOut[3], $matchesIn[3], $matchesOut[5]));
//                        }
//                    } else {
//                        break;
//                    }
//                }
//                foreach ($ipArray as $accountedIP) {
//                    try {
//                        $ip = IpDAO::getIpByIP($accountedIP["IP-SRC"]);
//                    } catch (Exception $e) {
//                        throw new Exception(sprintf("there is no IP %s in database", $accountedIP["IP-SRC"]));
//                    }
//
//                    try {
//                        $ipAccountAbs = IpAccountAbsDAO::getIpAccountAbsByIpID($ip->IP_ipid);
//                    } catch (Exception $e) {
//                        $ipAccountAbs = new IpAccountAbs();
//                        $ipAccountAbs->IB_ipid = $ip->IP_ipid;
//                        $ipAccountAbs->IB_bytes_in = $accountedIP["BYTES-IN"];
//                        $ipAccountAbs->IB_bytes_out = $accountedIP["BYTES-OUT"];
//                        $ipAccountAbs->IB_packets_in = $accountedIP["PACKETS-IN"];
//                        $ipAccountAbs->IB_packets_out = $accountedIP["PACKETS-OUT"];
//                        $database->insertObject("ipaccountabs", $ipAccountAbs, "IB_ipaccountabsid", false);
//                    }
//
//                    $ipAccount = new IpAccount();
//                    $ipAccount->IA_ipid = $ip->IP_ipid;
//
//                    $ipAccount->IA_bytes_in = ($accountedIP["BYTES-IN"] >= $ipAccountAbs->IB_bytes_in) ? $accountedIP["BYTES-IN"] - $ipAccountAbs->IB_bytes_in : $accountedIP["BYTES-IN"];
//                    $ipAccount->IA_bytes_out = ($accountedIP["BYTES-OUT"] >= $ipAccountAbs->IB_bytes_out) ? $accountedIP["BYTES-OUT"] - $ipAccountAbs->IB_bytes_out : $accountedIP["BYTES-OUT"];
//                    $ipAccount->IA_packets_in = ($accountedIP["PACKETS-IN"] >= $ipAccountAbs->IB_packets_in) ? $accountedIP["PACKETS-IN"] - $ipAccountAbs->IB_packets_in : $accountedIP["PACKETS-IN"];
//                    $ipAccount->IA_packets_out = ($accountedIP["PACKETS-OUT"] >= $ipAccountAbs->IB_packets_out) ? $accountedIP["PACKETS-OUT"] - $ipAccountAbs->IB_packets_out : $accountedIP["PACKETS-OUT"];
//
//                    $ipAccountAbs->IB_bytes_in = $accountedIP["BYTES-IN"];
//                    $ipAccountAbs->IB_bytes_out = $accountedIP["BYTES-OUT"];
//                    $ipAccountAbs->IB_packets_in = $accountedIP["PACKETS-IN"];
//                    $ipAccountAbs->IB_packets_out = $accountedIP["PACKETS-OUT"];
//
//                    $ipAccount->IA_datetime = $dateString;
//
//                    try {
//                        $database->startTransaction();
//                        $database->insertObject("ipaccount", $ipAccount, "IA_ipaccountid", false);
//                        $database->updateObject("ipaccountabs", $ipAccountAbs, "IB_ipaccountabsid", false, false);
//                        $database->commit();
//                    } catch (Exception $e) {
//                        $database->rollback();
//                        throw $e;
//                    }
//                }
//            }
//        }
//    }

    public function getIPFilterDown($executor)
    {
        $cmds = [];

        $cmds[] = sprintf("%s -D FORWARD -i %s -j FILTER-IN 2>/dev/null", $this->commandIptables, $this->wanInterface);
        $cmds[] = sprintf("%s -D FORWARD -o %s -j FILTER-OUT 2>/dev/null", $this->commandIptables, $this->wanInterface);
        $cmds[] = sprintf("%s -t nat -D PREROUTING -d ! 10.0.0.0/8 -j WEB-REDIRECT 2>/dev/null", $this->commandIptables);

        $cmds[] = sprintf("%s -F FILTER-IN 2>/dev/null", $this->commandIptables);
        $cmds[] = sprintf("%s -F FILTER-OUT 2>/dev/null", $this->commandIptables);

        $cmds[] = sprintf("%s -X FILTER-IN 2>/dev/null", $this->commandIptables);
        $cmds[] = sprintf("%s -X FILTER-OUT 2>/dev/null", $this->commandIptables);

        $cmds[] = sprintf("%s -t nat -F WEB-REDIRECT 2>/dev/null", $this->commandIptables);
        $cmds[] = sprintf("%s -t nat -X WEB-REDIRECT 2>/dev/null", $this->commandIptables);

        return self::parseArrayReadable($executor->executeArray($cmds));
    }

    public function getIPFilterUp($executor)
    {
        $cmds = [];

        $cmds[] = sprintf("%s -N FILTER-IN", $this->commandIptables);
        $cmds[] = sprintf("%s -N FILTER-OUT", $this->commandIptables);

        foreach ($this->networks as &$network) {
            foreach ($network->INTERNET_SERVICES as &$internetService) {
                foreach ($internetService['IPS'] as &$ip) {
                    $ipAddress = $ip['IP_address'];

                    // setup accounting, we will try to account ip even if blocked
                    // accept all known clients
                    $cmds[] = sprintf("%s -A FILTER-IN  -d %s -j ACCEPT", $this->commandIptables, $ipAddress);
                    $cmds[] = sprintf("%s -A FILTER-OUT -s %s -j ACCEPT", $this->commandIptables, $ipAddress);
                }
            }
        }

        // all unknown clients will be logged
        $cmds[] = sprintf("%s -A FILTER-IN  -m limit --limit 5/h --limit-burst 3 -j LOG --log-prefix \"UNKNOWN-IN: \"", $this->commandIptables);
        $cmds[] = sprintf("%s -A FILTER-OUT  -m limit --limit 5/h --limit-burst 3 -j LOG --log-prefix \"UNKNOWN-OUT: \"", $this->commandIptables);
        if ($this->rejectUnknownIP) {
            // all unknown clients will be rejected
            $cmds[] = sprintf("%s -A FILTER-IN  -j REJECT", $this->commandIptables);
            $cmds[] = sprintf("%s -A FILTER-OUT -j REJECT", $this->commandIptables);
        }

        $cmds[] = sprintf("%s -A FORWARD -i %s -j FILTER-IN", $this->commandIptables, $this->wanInterface);
        $cmds[] = sprintf("%s -A FORWARD -o %s -j FILTER-OUT", $this->commandIptables, $this->wanInterface);

        return self::parseArrayReadable($executor->executeArray($cmds));
    }

    static function parseCommandReadable($array)
    {
        return $array;
    }

    static function parseArrayReadable($array)
    {
        return $array;
    }
} // End of LinuxCommander class
