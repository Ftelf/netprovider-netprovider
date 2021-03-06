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
require_once($core->getAppRoot() . "includes/Executor.php");
require_once($core->getAppRoot() . "includes/utils/Utils.php");
require_once($core->getAppRoot() . "includes/net/commander/LinuxCommander.php");
require_once($core->getAppRoot() . "includes/net/commander/RouterOSCommander.php");
require_once 'Net/IPv4.php';

/**
 * CommanderCrossbar
 */
class CommanderCrossbar {
    private $dryRun = false;

    private $networkDevices;

    private $globalQOSEnabled;
    private $globalIPFilterEnabled;

    public function __construct() {
        global $core;

        $this->globalQOSEnabled = $core->getProperty(Core::GLOBAL_QOS_ENABLED);
        $this->globalIPFilterEnabled = $core->getProperty(Core::GLOBAL_IP_FILTER_ENABLED);
    }

    public function setDryRun($dryRun) {
        $this->dryRun = $dryRun;
    }

    public function inicialize() {
        global $core, $appContext;
        $networkIPArray = array();

        if (($persons = PersonDAO::getPersonArrayForQOS()) == null) {
            $persons = array();
        }

        foreach ($persons as $person) {
            if (($hasCharges = HasChargeDAO::getHasChargeWithInternetChargeOnlyByPersonID($person->PE_personid)) && ($ips = IpDAO::getIpArrayByPersonID($person->PE_personid))) {
                foreach ($hasCharges as $hasCharge) {
                    foreach ($ips as $ip) {
                        if (!isset($networkIPArray[$ip->IP_networkid])) {
                            $networkDummyTemp = array();
                            $networkDummyTemp["INTERNETS"] = array();

                            $networkIPArray[$ip->IP_networkid] = $networkDummyTemp;
                        }

                        $networkDummy = &$networkIPArray[$ip->IP_networkid];

                        if (!isset($networkDummy["INTERNETS"][$hasCharge->HC_haschargeid])) {
                            $internetDummyTemp = array();
                            $internetDummyTemp['PE_firstname'] = $person->PE_firstname;
                            $internetDummyTemp['PE_surname'] = $person->PE_surname;
                            $internetDummyTemp['IN_dnl_rate'] = $hasCharge->IN_dnl_rate;
                            $internetDummyTemp['IN_dnl_ceil'] = $hasCharge->IN_dnl_ceil;
                            $internetDummyTemp['IN_upl_rate'] = $hasCharge->IN_upl_rate;
                            $internetDummyTemp['IN_upl_ceil'] = $hasCharge->IN_upl_ceil;
                            $internetDummyTemp['IN_prio'] = $hasCharge->IN_prio;
                            $internetDummyTemp['IN_description'] = $hasCharge->IN_description;
                            $internetDummyTemp['IPS'] = array();

                            $networkDummy["INTERNETS"][$hasCharge->HC_haschargeid] = $internetDummyTemp;
                        }

                        $internetDummy = &$networkDummy["INTERNETS"][$hasCharge->HC_haschargeid];

                        $ipDummy = array();
                        $ipDummy["IP_address"] = $ip->IP_address;
                        $ipDummy["IP_dns"] = $ip->IP_dns;
                        $ipDummy["IP_networkid"] = $ip->IP_networkid;

                        $internetDummy['IPS'][] = $ipDummy;
                    }
                }
            }
        }

        $allNetworks = NetworkDAO::getNetworkArray();
        $networkDeviceArray = array();

        if ($this->globalQOSEnabled || $this->globalIPFilterEnabled) {
            $networkDevices = NetworkDeviceDAO::getNetworkDeviceArray();

            foreach ($networkDevices as &$networkDevice) {
                if ($networkDevice->ND_qosEnabled || $networkDevice->ND_ipFilterEnabled) {
                    $leafNetworks = array();

                    if ($networkDevice->ND_managementInterfaceId) {
                        $managementInterface = NetworkDeviceInterfaceDAO::getNetworkDeviceInterfaceByID($networkDevice->ND_managementInterfaceId);
                        $managementIp = IpDAO::getIpByID($managementInterface->NI_ipid);
                        $networkDevice->MANAGEMENT_IP = $managementIp->IP_address;
                    }

                    $lanInterfaces = (($networkDeviceInterfaces = NetworkDeviceInterfaceDAO::getNetworkDeviceInterfaceArrayByNetworkDeviceID($networkDevice->ND_networkdeviceid)) == null) ? array() : $networkDeviceInterfaces;
                    $networkDevice->lanInterfaces = array();

                    foreach ($lanInterfaces as $lanInterface) {
                        if ($lanInterface->NI_type == NetworkDeviceInterface::TYPE_LAN) {
                            $networkDevice->lanInterfaces[] = $lanInterface->NI_ifname;
                        }
                    }
                    if (!count($networkDevice->lanInterfaces)) {
                        throw new Exception(sprintf("Network device: %s has no lan interface defined", $networkDevice->ND_name));
                    }

                    if ($networkDevice->ND_wanInterfaceid) {
                        $wanInterface = NetworkDeviceInterfaceDAO::getNetworkDeviceInterfaceByID($networkDevice->ND_wanInterfaceid);
                        $networkDevice->wanInterface = $wanInterface->NI_ifname;
                    } else {
                        throw new Exception(sprintf("Network device: %s has no wan interface defined", $networkDevice->ND_name));
                    }

                    $networkDevice->NETWORKS = array();

                    $networks = HasManagedNetworkDAO::getHasManagedNetworkAndNetworksArrayByNetworkDeviceID($networkDevice->ND_networkdeviceid);

                    foreach ($networks as &$network) {
                        $this->getLeafNetworks($network->NE_networkid, $allNetworks, $leafNetworks);
                    }

                    foreach ($leafNetworks as $leafNetwork) {
                        if (isset($networkIPArray[$leafNetwork->NE_networkid])) {
                            $networkDummy = &$networkIPArray[$leafNetwork->NE_networkid];
                            $networkDummy["NE_net"] = $leafNetwork->NE_net;

                            $networkDevice->NETWORKS[$leafNetwork->NE_networkid] = $networkDummy;
                            $networkDeviceArray[$networkDevice->ND_networkdeviceid] = $networkDevice;
                        }
                    }
                }
            }
        }

        foreach ($networkDeviceArray as &$networkDevice) {
            $SumGuarantedDownload = 0;	//Sum of Guaranted Download
            $SumGuarantedUpload = 0;	//Sum of Guaranted Upload
            $SumCeilDownload = 0;		//Sum of Ceil Download
            $SumCeilUpload = 0;			//Sum of Ceil Upload

            foreach ($networkDevice->NETWORKS as &$network) {
                foreach ($network['INTERNETS'] as &$internet) {
                    if ( $internet['IN_dnl_rate'] == -1 ) {
                        // sumarize download CEILs for "fair" calculations (only for AUTO users)
                        //
                        $SumCeilDownload += $internet['IN_dnl_ceil'];
                    } else {
                        // sumarize guaranted downloads
                        //
                        $SumGuarantedDownload += $internet['IN_dnl_rate'];
                    }
                    if ( $internet['IN_upl_rate'] == -1 ) {
                        // sumarize upload CEILs for "fair" calculations (only for AUTO users)
                        //
                        $SumCeilUpload += $internet['IN_upl_ceil'];
                    } else {
                        // sumarize guaranted uploads
                        //
                        $SumGuarantedUpload += $internet['IN_upl_rate'];
                    }
                }
            }
            // now we know guaranted bandwidth, so we can split "not used" bandwidth to "AUTO" clients
            //
            $redistributeGuarantedDownload = ($networkDevice->ND_qosBandwidthDownload) - $SumGuarantedDownload;
            $redistributeGuarantedUpload = ($networkDevice->ND_qosBandwidthUpload) - $SumGuarantedUpload;

            foreach ($networkDevice->NETWORKS as &$network) {
                foreach ($network['INTERNETS'] as &$internet) {
                    if ($internet['IN_dnl_rate'] == -1) {
                        // if customer has set "AUTO" download bandwidth calculate guaranted bandwidth automaticaly
                        //
                        $downloadRate = floor(($internet['IN_dnl_ceil'] / $SumCeilDownload) * $redistributeGuarantedDownload);
                        if ($downloadRate < 1) {
                            $downloadRate = 1;
                        }
                        $internet['IN_dnl_rate'] = ($downloadRate > $internet['IN_dnl_ceil']) ? $internet['IN_dnl_ceil'] : $downloadRate;	// if guaranted > ceil
                    }
                    if ($internet['IN_upl_rate'] == -1) {
                        // if customer has set "AUTO" upload bandwidth calculate guaranted bandwidth automaticaly
                        //
                        $uploadRate = floor(($internet['IN_upl_ceil'] / $SumCeilUpload) * $redistributeGuarantedUpload);
                        if ($uploadRate < 1) {
                            $uploadRate = 1;
                        }
                        $internet['IN_upl_rate'] = ($uploadRate > $internet['IN_upl_ceil']) ? $internet['IN_upl_ceil'] : $uploadRate;	// if guaranted > ceil
                    }
                }
            }
        }

        // create connection for each enabled device
        foreach ($networkDeviceArray as &$networkDevice) {
            if (isset($networkDevice->MANAGEMENT_IP)) {
                if ($networkDevice->ND_platform == NetworkDevice::PLATFORM_GNU_LINUX_DEBIAN) {
                    $settings = array();
                    $settings[Executor::REMOTE_HOST] = $networkDevice->MANAGEMENT_IP;
                    $settings[Executor::REMOTE_PORT] = 22;
                    $settings[Executor::LOGIN] = $networkDevice->ND_login;
                    $settings[Executor::PASSWORD] = $networkDevice->ND_password;
                    $settings[Executor::SUDO_COMMAND] = $networkDevice->ND_commandSudo;

                    $executor = new Executor(Executor::REMOTE_SSH2, $settings, !$this->dryRun);
                } else if ($networkDevice->ND_platform == NetworkDevice::PLATFORM_ROUTEROS) {
                    $settings = array();
                    $settings[Executor::REMOTE_HOST] = $networkDevice->MANAGEMENT_IP;
                    $settings[Executor::LOGIN] = $networkDevice->ND_login;
                    $settings[Executor::PASSWORD] = $networkDevice->ND_password;

                    $executor = new Executor(Executor::REMOTE_MIKROTIK_API, $settings, !$this->dryRun);
                }
            } else {
                if ($networkDevice->ND_platform == NetworkDevice::PLATFORM_GNU_LINUX_DEBIAN) {
                    $settings = array();
                    $settings[Executor::SUDO_COMMAND] = $networkDevice->ND_commandSudo;

                    $executor = new Executor(Executor::LOCAL_COMMAND, $settings, !$this->dryRun);
                } else if ($networkDevice->ND_platform == NetworkDevice::PLATFORM_ROUTEROS) {
                    $settings = array();
                    $settings[Executor::REMOTE_HOST] = '127.0.0.1';
                    $settings[Executor::LOGIN] = $networkDevice->ND_login;
                    $settings[Executor::PASSWORD] = $networkDevice->ND_password;

                    $executor = new Executor(Executor::REMOTE_MIKROTIK_API, $settings, !$this->dryRun);

                    $appContext->insertMessage(sprintf(_("Login successfull: mikrotik API %s@%s"), $networkDevice->ND_login, $managementIp->IP_address));
                }
            }

            $networkDevice->EXECUTOR = $executor;
        }

        $this->networkDevices = $networkDeviceArray;
    }

    private function getLeafNetworks($id, &$allNetworks, &$leafNetworks) {
        $childrenNetworks = array();
        $ipv4 = new Net_IPv4();
        foreach ($allNetworks as $network) {
            if ($network->NE_parent_networkid == $id) {
                $netParse = $ipv4->parseAddress($network->NE_net);
                $childrenNetworks[ip2long($netParse->network)] = clone $network;
            }
        }
        if (sizeof($childrenNetworks)) {
            ksort($childrenNetworks);

            foreach ($childrenNetworks as $network) {
                $netParse = $ipv4->parseAddress($network->NE_net);
                $this->getLeafNetworks($network->NE_networkid, $allNetworks, $leafNetworks);
            }
        } else {
            $leafNetworks[$id] = clone $allNetworks[$id];
        }
    }

    public function ipFilterDown() {
        $result = array();

        foreach ($this->networkDevices as &$networkDevice) {
            if ($this->globalIPFilterEnabled && $networkDevice->ND_ipFilterEnabled) {
                $commander = $this->getCommander($networkDevice);

                $results = $commander->getIPFilterDown($networkDevice->EXECUTOR);

                $result = array_merge($result, $results);
            }
        }

        return $result;
    }

    public function ipFilterUp() {
        $result = array();

        foreach ($this->networkDevices as &$networkDevice) {
            if ($this->globalIPFilterEnabled && $networkDevice->ND_ipFilterEnabled) {
                $commander = $this->getCommander($networkDevice);

                $results = $commander->getIPFilterUp($networkDevice->EXECUTOR);

                $result = array_merge($result, $results);
            }
        }

        return $result;
    }

    public function qosDown() {
        $result = array();

        foreach ($this->networkDevices as &$networkDevice) {
            if ($this->globalQOSEnabled && $networkDevice->ND_qosEnabled) {
                $commander = $this->getCommander($networkDevice);

                $results = $commander->getQosDown($networkDevice->EXECUTOR);

                $result = array_merge($result, $results);
            }
        }

        return $result;
    }

    public function qosUp() {
        $result = array();

        foreach ($this->networkDevices as &$networkDevice) {
            if ($this->globalQOSEnabled && $networkDevice->ND_qosEnabled) {
                $commander = $this->getCommander($networkDevice);

                $results = $commander->getQosUp($networkDevice->EXECUTOR);

                $result = array_merge($result, $results);
            }
        }

        return $result;
    }

    public function accountIP() {
        foreach ($this->networkDevices as &$networkDevice) {
            if ($this->globalIPFilterEnabled && $networkDevice->ND_ipFilterEnabled) {

                $commander = $this->getCommander($networkDevice);

                $commander->accountIP($networkDevice->EXECUTOR);
            }
        }
    }

    private function getCommander($networkDevice) {
        switch ($networkDevice->ND_platform) {
            case NetworkDevice::PLATFORM_GNU_LINUX_DEBIAN:
                return new LinuxCommander($networkDevice);

            case NetworkDevice::PLATFORM_ROUTEROS:
                return new RouterOSCommander($networkDevice);
        }
    }
} // End of CommanderCrossbar class
?>