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
require_once $core->getAppRoot() . "includes/utils/Utils.php";
require_once $core->getAppRoot() . "includes/net/commander/LinuxCommander.php";
require_once $core->getAppRoot() . "includes/net/commander/RouterOSCommander.php";

/**
 * CommanderCrossbar
 */
class CommanderCrossbar
{
    private $commander;

    public function __construct()
    {
        global $core;

        $networks = [];
        $persons = PersonDAO::getPersonArray("", 0, Person::STATUS_ACTIVE);

        foreach ($persons as $person) {
            if (($hasCharges = HasChargeDAO::getHasChargeWithInternetChargeOnlyByPersonID($person->PE_personid)) && ($ips = IpDAO::getIpArrayByPersonID($person->PE_personid))) {
                $hasCharge = reset($hasCharges);
                foreach ($ips as $ip) {
                    $network = $networks[$ip->IP_networkid] ?? NetworkDAO::getNetworkByID($ip->IP_networkid);

                    $internetServices = $network->INTERNET_SERVICES ?? [];

                    $internetService = $internetServices[$hasCharge->HC_haschargeid] ?? [
                        "PE_firstname" => $person->PE_firstname,
                        "PE_surname" => $person->PE_surname,
                        "IN_dnl_rate" => $hasCharge->IN_dnl_rate,
                        "IN_dnl_ceil" => $hasCharge->IN_dnl_ceil,
                        "IN_upl_rate" => $hasCharge->IN_upl_rate,
                        "IN_upl_ceil" => $hasCharge->IN_upl_ceil,
                        "IN_prio" => $hasCharge->IN_prio,
                        "IN_description" => $hasCharge->IN_description,
                        "IPS" => []
                    ];

                    $internetService["IPS"][] = [
                        "IP_address" => $ip->IP_address,
                        "IP_dns" => $ip->IP_dns
                    ];

                    $internetServices[$hasCharge->HC_haschargeid] = $internetService;
                    $network->INTERNET_SERVICES = $internetServices;
                    $networks[$ip->IP_networkid] = $network;
                }
            }
        }

        $platform = $core->getProperty(Core::NETWORK_DEVICE_PLATFORM);
        if ($platform === "LINUX") {
            $this->commander = new LinuxCommander($networks);
        } elseif ($platform === "ROUTEROS") {
            $this->commander = new RouterOSCommander($networks);
        } else {
            throw new Exception("Unknown platform in configuration ini file: \"${platform}\"");
        }
    }

    public function synchronizeFilter(): array
    {
        return $this->commander->synchronizeFilter();
    }

    public function ipFilterDown(): array
    {
        return $this->commander->getIPFilterDown();
    }

    public function ipFilterUp(): array
    {
        return $this->commander->getIPFilterUp();
    }

    public function accountIP(): void
    {
        $this->commander->accountIP();
    }
} // End of CommanderCrossbar class
