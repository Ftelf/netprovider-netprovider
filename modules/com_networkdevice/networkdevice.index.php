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

/** ensure this file is being included by a parent file */
defined('VALID_MODULE') or die(_("Direct access into this section is not allowed"));

global $core;
require_once($core->getAppRoot() . "includes/dao/NetworkDeviceDAO.php");
require_once("networkdevice.html.php");
require_once($core->getAppRoot() . "includes/dao/NetworkDAO.php");
require_once($core->getAppRoot() . "includes/dao/IpDAO.php");
require_once($core->getAppRoot() . "includes/dao/HasManagedNetworkDAO.php");
require_once($core->getAppRoot() . "includes/dao/NetworkDeviceInterfaceDAO.php");
require_once($core->getAppRoot() . "includes/dao/NetworkDeviceWirelessInterfaceDAO.php");
require_once($core->getAppRoot() . "includes/dao/NetworkDevicePropertyDAO.php");
require_once($core->getAppRoot() . "includes/Executor.php");
require_once($core->getAppRoot() . "includes/net/RouterosApi.php");
require_once 'Net/IPv4.php';

$task = Utils::getParam($_REQUEST, 'task', null);
$nid = Utils::getParam($_REQUEST, 'ND_networkdeviceid', null);
$nnid = Utils::getParam($_REQUEST, 'MN_hasmanagednetworkid', null);
$pid = Utils::getParam($_REQUEST, 'NP_networkdevicepropertyid', null);
$iid = Utils::getParam($_REQUEST, 'NI_networkdeviceinterfaceid', null);
$wid = Utils::getParam($_REQUEST, 'NW_networkdevicewirelessinterfaceid', null);
$cid = Utils::getParam($_REQUEST, 'cid', array(0));
if (!is_array($cid)) {
	$cid = array (0);
}

switch ($task) {
	case 'new':
		editNetworkDevice(null);
		break;

	case 'edit':
		editNetworkDevice($nid);
		break;

	case 'editA':
		editNetworkDevice(intval($cid[0]));
		break;
		
	case 'save':
	case 'apply':
 		saveNetworkDevice($task);
		break;

	case 'remove':
		removeNetworkDevice($cid);
		break;

	case 'newNetwork':
		editNetworkDeviceNetwork($nid, null);
		break;

	case 'editNetwork':
		editNetworkDeviceNetwork($nid, $nnid);
		break;

	case 'saveNetwork':
	case 'applyNetwork':
 		saveNetworkDeviceNetwork($task);
		break;
	
	case 'removeNetwork':
		removeNetworkDeviceNetwork($nnid);
		break;
	
	case 'cancelNetwork':
		editNetworkDevice($nid);
		break;
		
	case 'newInterface':
		editNetworkDeviceInterface($nid, null);
		break;

	case 'editInterface':
		editNetworkDeviceInterface($nid, $iid);
		break;

	case 'saveInterface':
	case 'applyInterface':
 		saveNetworkDeviceInterface($task);
		break;

	case 'removeInterface':
		removeNetworkDeviceInterface($iid);
		break;
		
	case 'cancelInterface':
		editNetworkDevice($nid);
		break;

	case 'newWirelessInterface':
		editNetworkDeviceWirelessInterface($nid, null);
		break;

	case 'editWirelessInterface':
		editNetworkDeviceWirelessInterface($nid, $wid);
		break;

	case 'saveWirelessInterface':
	case 'applyWirelessInterface':
 		saveNetworkDeviceWirelessInterface($task);
		break;

	case 'removeWirelessInterface':
		removeNetworkDeviceWirelessInterface($wid);
		break;
		
	case 'cancelWirelessInterface':
		editNetworkDevice($nid);
		break;
		
	case 'newProperty':
		editNetworkDeviceProperty($nid, null);
		break;

	case 'editProperty':
		editNetworkDeviceProperty($nid, $pid);
		break;

	case 'saveProperty':
	case 'applyProperty':
 		saveNetworkDeviceProperty($task);
		break;

	case 'removeProperty':
		removeNetworkDeviceProperty($pid);
		break;
		
	case 'cancelProperty':
		editNetworkDevice($nid);
		break;
		
	case 'cancel':
		showNetworkDevice();
		break;
		
	case 'testLogin':
		testLogin();
		break;

	default:
		showNetworkDevice();
		break;
}

function showNetworkDevice() {
	global $database, $mainframe, $acl, $core;
	require_once($core->getAppRoot() . 'modules/com_common/PageNav.php');
	$ipv4 = new Net_IPv4();
	
	$filter = array();
	$filter['platform'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_networkdevice']['filter'], 'platform', -1);
	
	$limit = Utils::getParam($_SESSION['UI_SETTINGS']['com_networkdevice'], 'limit', 10);
	$limitstart = Utils::getParam($_SESSION['UI_SETTINGS']['com_networkdevice'], 'limitstart', 0);
	
	if ($filter['platform'] == -1) {
		$total = NetworkDeviceDAO::getNetworkDeviceCount();
		$networkDevices = NetworkDeviceDAO::getNetworkDeviceArray($limitstart, $limit);
	} else {
		$total = NetworkDeviceDAO::getNetworkDeviceCountWherePlatform($filter['platform']);
		$networkDevices = NetworkDeviceDAO::getNetworkDeviceArrayWherePlatform($filter['platform'], $limitstart, $limit);
	}
	$networks = NetworkDAO::getNetworkArray();
	$ips = IpDAO::getIpArray();
	
	foreach ($networkDevices as $networkDevice) {
		$networkDevice->interfaces = (($networkDeviceInterfaces = NetworkDeviceInterfaceDAO::getNetworkDeviceInterfaceArrayByNetworkDeviceID($networkDevice->ND_networkdeviceid)) == null) ? array() : $networkDeviceInterfaces;
		foreach ($networkDevice->interfaces as $networkDeviceInterface) {
			if (isset($ips[$networkDeviceInterface->NI_ipid])) {
				$ip = $ips[$networkDeviceInterface->NI_ipid];
				$network = $networks[$ip->IP_networkid];
				$pNetwork = $ipv4->parseAddress($network->NE_net);
				$pIP = $ipv4->parseAddress($ip->IP_address . '/' . $pNetwork->bitmask);
				
				$networkDeviceInterface->ip = $pIP->ip . '/' . $pIP->bitmask;;
				$networkDeviceInterface->dns = $ip->IP_dns;
			} else {
				$networkDeviceInterface->ip = "N/A";
				$networkDeviceInterface->dns = "N/A";
			}
		}
		$networkDevice->wirelessInterfaces = (($networkDeviceWirelessInterfaces = NetworkDeviceWirelessInterfaceDAO::getNetworkDeviceWirelessInterfaceArrayByNetworkDeviceID($networkDevice->ND_networkdeviceid)) == null) ? array() : $networkDeviceWirelessInterfaces;;
		foreach ($networkDevice->wirelessInterfaces as $networkDeviceWirelessInterface) {
			$networkDeviceWirelessInterface->sss  = "ssS";
			if (isset($ips[$networkDeviceWirelessInterface->NW_ipid])) {
				$ip = $ips[$networkDeviceWirelessInterface->NW_ipid];
				$network = $networks[$ip->IP_networkid];
				$pNetwork = $ipv4->parseAddress($network->NE_net);
				$pIP = $ipv4->parseAddress($ip->IP_address . '/' . $pNetwork->bitmask);
				
				$networkDeviceWirelessInterface->ip = $pIP->ip . '/' . $pIP->bitmask;
				$networkDeviceWirelessInterface->dns = $ip->IP_dns;
			} else {
				$networkDeviceWirelessInterface->ip = "N/A";
				$networkDeviceWirelessInterface->dns = "N/A";
			}				
			$frequencies = NetworkDeviceWirelessInterface::getFrequencyConstants();
			$networkDeviceWirelessInterface->channel = $frequencies[$networkDeviceWirelessInterface->NW_frequency];
		}
		
		$networkDevice->properties = NetworkDevicePropertyDAO::getNetworkDevicePropertyArrayByNetworkDeviceID($networkDevice->ND_networkdeviceid);
	}
	$pageNav = new PageNav($total, $limitstart, $limit);
	HTML_NetworkDevice::showNetworkDevices($networkDevices, $filter, $pageNav);
}

function editNetworkDevice($nid=null) {
	global $database, $my, $acl;
	$ipv4 = new Net_IPv4();
	
	if ($nid != null) {
		$networkDevice = NetworkDeviceDAO::getNetworkDeviceByID($nid);
		
		if ($networkDevice->ND_password != null) {
			$networkDevice->ND_password = "******";
		}
		
		$networkDevice->networks = HasManagedNetworkDAO::getHasManagedNetworkAndNetworksArrayByNetworkDeviceID($nid);
		
		$allNetworks = NetworkDAO::getNetworkArray();
		
		$networkDevice->leafNetworks = array();
		
		foreach ($networkDevice->networks as $network) {
			getLeafNetworks($network->NE_networkid, $allNetworks, $networkDevice->leafNetworks);
		}
		
		$networkDevice->interfaces = (($networkDeviceInterfaces = NetworkDeviceInterfaceDAO::getNetworkDeviceInterfaceArrayByNetworkDeviceID($networkDevice->ND_networkdeviceid)) == null) ? array() : $networkDeviceInterfaces;;
		foreach ($networkDevice->interfaces as $networkDeviceInterface) {
			try {
				$ip = IpDAO::getIpByID($networkDeviceInterface->NI_ipid);
				$network = NetworkDAO::getNetworkByID($ip->IP_networkid);
				$pNetwork = $ipv4->parseAddress($network->NE_net);
				$pIP = $ipv4->parseAddress($ip->IP_address . '/' . $pNetwork->bitmask);
			
				$networkDeviceInterface->ip = $pIP->ip . '/' . $pIP->bitmask;;
				$networkDeviceInterface->dns = $ip->IP_dns;
			} catch (Exception $e) {
				$networkDeviceInterface->ip = "N/A";
				$networkDeviceInterface->dns = "N/A";
			}
		}
		$networkDevice->wirelessInterfaces = (($networkDeviceWirelessInterfaces = NetworkDeviceWirelessInterfaceDAO::getNetworkDeviceWirelessInterfaceArrayByNetworkDeviceID($networkDevice->ND_networkdeviceid)) == null) ? array() : $networkDeviceWirelessInterfaces;;
		foreach ($networkDevice->wirelessInterfaces as $networkDeviceWirelessInterface) {
			try {
				$ip = IpDAO::getIpByID($networkDeviceWirelessInterface->NW_ipid);
				$network = NetworkDAO::getNetworkByID($ip->IP_networkid);
				$pNetwork = $ipv4->parseAddress($network->NE_net);
				$pIP = $ipv4->parseAddress($ip->IP_address . '/' . $pNetwork->bitmask);
				
				$networkDeviceWirelessInterface->ip = $pIP->ip . '/' . $pIP->bitmask;
				$networkDeviceWirelessInterface->dns = $ip->IP_dns;
			} catch (Exception $e) {
				$networkDeviceWirelessInterface->ip = "N/A";
				$networkDeviceWirelessInterface->dns = "N/A";
			}		
			$frequencies = NetworkDeviceWirelessInterface::getFrequencyConstants();
			$networkDeviceWirelessInterface->channel = $frequencies[$networkDeviceWirelessInterface->NW_frequency];
		}
		
		$networkDevice->properties = NetworkDevicePropertyDAO::getNetworkDevicePropertyArrayByNetworkDeviceID($networkDevice->ND_networkdeviceid);
	} else {
		$networkDevice = new NetworkDevice();
		$networkDevice->networks = array();
		$networkDevice->leafNetworks = array();
		$networkDevice->properties = array();
		$networkDevice->interfaces = array();
		$networkDevice->wirelessInterfaces = array();
	}
	HTML_NetworkDevice::editNetworkDevice($networkDevice);
}

function saveNetworkDevice($task) {
	global $core, $database, $mainframe, $my, $acl, $appContext;
	$ipv4 = new Net_IPv4();

	$networkDevice = new NetworkDevice();
	database::bind($_POST, $networkDevice);
	$isNew 	= !$networkDevice->ND_networkdeviceid;
	
	$networkDevice->ND_useCommandSudo = Utils::getParam($_POST, 'ND_useCommandSudo', 0);
	$networkDevice->ND_qosEnabled = Utils::getParam($_POST, 'ND_qosEnabled', 0);
	$networkDevice->ND_ipFilterEnabled = Utils::getParam($_POST, 'ND_ipFilterEnabled', 0);
	
	$ND_password1 = trim(Utils::getParam($_POST, 'ND_password1', ""));
	$ND_password2 = trim(Utils::getParam($_POST, 'ND_password2', ""));
	
	$showAgain = false;
	
	if (($ND_password1 == "******" && $ND_password2 == "******") || ($ND_password1 == "" && $ND_password2 == "")) {
		$networkDevice->ND_password = null;
	} else if ($ND_password1 == $ND_password2) {
		$networkDevice->ND_password = $ND_password1;
	} else {
		$core->alert(_("User passwords are not same"));
		
		$showAgain = true;
	}
	
	if (!is_numeric($networkDevice->ND_qosBandwidthDownload)) {
		if ($networkDevice->ND_qosBandwidthDownload == '') {
			$networkDevice->ND_qosBandwidthDownload = 0;
		} else {
			$core->alert(_("Internet download bandwidth (kbps): is not numeric value"));
			$networkDevice->ND_qosBandwidthDownload = 0;
			$showAgain = true;
		}
	}
	
	if (!is_numeric($networkDevice->ND_qosBandwidthUpload)) {
		if ($networkDevice->ND_qosBandwidthUpload == '') {
			$networkDevice->ND_qosBandwidthUpload = 0;
		} else {
			$core->alert(_("Internet upload bandwidth (kbps): is not numeric value"));
			$networkDevice->ND_qosBandwidthUpload = 0;
			$showAgain = true;
		}
	}
	
	if (!$networkDevice->ND_qosBandwidthDownload) {
		$networkDevice->ND_qosBandwidthDownload = 0;
	}
	
	if (!$networkDevice->ND_qosBandwidthUpload) {
		$networkDevice->ND_qosBandwidthUpload = 0;
	}
	
	if ($showAgain) {
		if ($networkDevice->ND_networkdeviceid != null) {
			$networkDevice = NetworkDeviceDAO::getNetworkDeviceByID($networkDevice->ND_networkdeviceid);
			
			if ($networkDevice->ND_password != null) {
				$networkDevice->ND_password = "******";
			}
			
			$networkDevice->networks = HasManagedNetworkDAO::getHasManagedNetworkAndNetworksArrayByNetworkDeviceID($networkDevice->ND_networkdeviceid);
			
			$allNetworks = NetworkDAO::getNetworkArray();
		
			$networkDevice->leafNetworks = array();
			
			foreach ($networkDevice->networks as $network) {
				getLeafNetworks($network->NE_networkid, $allNetworks, $networkDevice->leafNetworks);
			}
			
			$networkDevice->interfaces = (($networkDeviceInterfaces = NetworkDeviceInterfaceDAO::getNetworkDeviceInterfaceArrayByNetworkDeviceID($networkDevice->ND_networkdeviceid)) == null) ? array() : $networkDeviceInterfaces;
			foreach ($networkDevice->interfaces as $networkDeviceInterface) {
				try {
					$ip = IpDAO::getIpByID($networkDeviceInterface->NI_ipid);
					$network = NetworkDAO::getNetworkByID($ip->IP_networkid);
					$pNetwork = $ipv4->parseAddress($network->NE_net);
					$pIP = $ipv4->parseAddress($ip->IP_address . '/' . $pNetwork->bitmask);
				
					$networkDeviceInterface->ip = $pIP->ip . '/' . $pIP->bitmask;;
					$networkDeviceInterface->dns = $ip->IP_dns;
				} catch (Exception $e) {
					$networkDeviceInterface->ip = "N/A";
					$networkDeviceInterface->dns = "N/A";
				}
			}
			$networkDevice->wirelessInterfaces = (($networkDeviceWirelessInterfaces = NetworkDeviceWirelessInterfaceDAO::getNetworkDeviceWirelessInterfaceArrayByNetworkDeviceID($networkDevice->ND_networkdeviceid)) == null) ? array() : $networkDeviceWirelessInterfaces;;
			foreach ($networkDevice->wirelessInterfaces as $networkDeviceWirelessInterface) {
				try {
					$ip = IpDAO::getIpByID($networkDeviceWirelessInterface->NW_ipid);
					$network = NetworkDAO::getNetworkByID($ip->IP_networkid);
					$pNetwork = $ipv4->parseAddress($network->NE_net);
					$pIP = $ipv4->parseAddress($ip->IP_address . '/' . $pNetwork->bitmask);
					
					$networkDeviceWirelessInterface->ip = $pIP->ip . '/' . $pIP->bitmask;
					$networkDeviceWirelessInterface->dns = $ip->IP_dns;
				} catch (Exception $e) {
					$networkDeviceWirelessInterface->ip = "N/A";
					$networkDeviceWirelessInterface->dns = "N/A";
				}		
				$frequencies = NetworkDeviceWirelessInterface::getFrequencyConstants();
				$networkDeviceWirelessInterface->channel = $frequencies[$networkDeviceWirelessInterface->NW_frequency];
			}
			
			$networkDevice->properties = NetworkDevicePropertyDAO::getNetworkDevicePropertyArrayByNetworkDeviceID($networkDevice->ND_networkdeviceid);
		} else {
			$networkDevice->networks = array();
			$networkDevice->leafNetworks = array();
			$networkDevice->properties = array();
			$networkDevice->interfaces = array();
			$networkDevice->wirelessInterfaces = array();
		}
		
		HTML_NetworkDevice::editNetworkDevice($networkDevice);
		return;
	}
	
	if ($networkDevice->ND_networkdeviceid) {
		$allInterfaces = NetworkDeviceInterfaceDAO::getNetworkDeviceInterfaceArrayByNetworkDeviceID($networkDevice->ND_networkdeviceid);
	} else {
		$allInterfaces = array();
	}
	$lanInterfaces = Utils::getParam($_POST, 'lanInterfaces', array());
	
	foreach ($allInterfaces as $networkDeviceInterface) {
		if (in_array($networkDeviceInterface->NI_networkdeviceinterfaceid, $lanInterfaces)) {
			$networkDeviceInterface->NI_type = NetworkDeviceInterface::TYPE_LAN;
		} else {
			$networkDeviceInterface->NI_type = NetworkDeviceInterface::TYPE_UNSPECIFIED;
		}
		$database->updateObject("networkdeviceinterface", $networkDeviceInterface, "NI_networkdeviceinterfaceid", false, false);
	}
	
	if ($isNew) {
		$database->insertObject("networkdevice", $networkDevice, "ND_networkdeviceid", false);
	} else {
		$database->updateObject("networkdevice", $networkDevice, "ND_networkdeviceid", false, false);
	}
	
	switch ($task) {
		case 'apply':
			$msg = sprintf(_("Network device '%s' updated"), $networkDevice->ND_name);
			$appContext->insertMessage($msg);
			$database->log($msg, LOG::LEVEL_INFO);
			Core::redirect("index2.php?option=com_networkdevice&task=edit&ND_networkdeviceid=$networkDevice->ND_networkdeviceid&hidemainmenu=1");
		case 'save':
			$msg = sprintf(_("Network device '%s' saved"), $networkDevice->ND_name);
			$appContext->insertMessage($msg);
			$database->log($msg, LOG::LEVEL_INFO);
		default:
			Core::redirect("index2.php?option=com_networkdevice");
	}
}

function removeNetworkDevice($cid) {
	global $database, $mainframe, $my, $acl, $appContext;
	if (count($cid) < 1) {
		Core::backWithAlert(_("Please select record to erase"));
	}

	if (count($cid)) {
		foreach ($cid as $id) {
			$networkDevice = NetworkDeviceDAO::getNetworkDeviceByID($id);
			try {
				$database->startTransaction();
				HasManagedNetworkDAO::removeHasManagedNetworksByManagedDeviceID($networkDevice->ND_networkdeviceid);
				NetworkDeviceInterfaceDAO::removeNetworkDeviceInterfaceByNetworkDeviceID($networkDevice->ND_networkdeviceid);
				NetworkDevicePropertyDAO::removeNetworkDevicePropertyByNetworkDeviceID($networkDevice->ND_networkdeviceid);
				NetworkDeviceWirelessInterfaceDAO::removeNetworkDeviceWirelessInterfaceByNetworkDeviceID($networkDevice->ND_networkdeviceid);
				NetworkDeviceDAO::removeNetworkDevicebyID($id);
				$database->commit();
			} catch (Exception $e) {
				$database->rollback();
				throw $e;
			}
			
			$msg = sprintf(_("Network device '%s' deleted"), $networkDevice->ND_name);
			$appContext->insertMessage($msg);
			$database->log($msg, LOG::LEVEL_INFO);
		}
		Core::redirect("index2.php?option=com_networkdevice");
	}
}

function editNetworkDeviceNetwork($nid, $nnid=null) {
	global $database, $my, $acl;
	
	if ($nnid != null) {
		$HasManagedNetwork = HasManagedNetworkDAO::getHasManagedNetworkByID($nnid);
	} else if ($nid != null) {
		$HasManagedNetwork = new HasManagedNetwork();
		$HasManagedNetwork->MN_networkdeviceid = $nid;
	} else {
		Core::redirect("index2.php?option=com_networkdevice");
	}
	
	$networks = NetworkDAO::getNetworkArray();
	
	$hasNetworks = HasManagedNetworkDAO::getHasManagedNetworkAndNetworksArrayByNetworkDeviceID($nid);
	
	foreach ($hasNetworks as $hasNetwork) {
		if ($HasManagedNetwork->MN_networkid != $hasNetwork->MN_networkid) {
			unset($networks[$hasNetwork->MN_networkid]);
		}
	}
	
	HTML_NetworkDevice::editNetworkDeviceNetwork($HasManagedNetwork, $networks);
}

function saveNetworkDeviceNetwork($task) {
	global $database, $mainframe, $my, $acl, $appContext;

	$hasManagedNetwork = new HasManagedNetwork();
	database::bind($_POST, $hasManagedNetwork);
	$isNew 	= !$hasManagedNetwork->MN_hasmanagednetworkid;
	
	if ($isNew) {
		$database->insertObject("hasmanagednetwork", $hasManagedNetwork, "MN_hasmanagednetworkid", false);
	} else {
		$database->updateObject("hasmanagednetwork", $hasManagedNetwork, "MN_hasmanagednetworkid", false, false);
	}
	
	$network = NetworkDAO::getNetworkByID($hasManagedNetwork->MN_networkid);
	$networkDevice = NetworkDeviceDAO::getNetworkDeviceByID($hasManagedNetwork->MN_networkdeviceid);
	
	switch ($task) {
		case 'applyNetwork':
			$msg = sprintf(_("Network device network '%s' updated for network device %s"), $network->NE_net, $networkDevice->ND_name);
			$appContext->insertMessage($msg);
			$database->log($msg, LOG::LEVEL_INFO);
			Core::redirect("index2.php?option=com_networkdevice&task=editNetwork&MN_hasmanagednetworkid=$hasManagedNetwork->MN_hasmanagednetworkid&hidemainmenu=1");
		case 'saveNetwork':
			$msg = sprintf(_("Network device network '%s' saved for network device %s"), $network->NE_net, $networkDevice->ND_name);
			$appContext->insertMessage($msg);
			$database->log($msg, LOG::LEVEL_INFO);
		default:
			Core::redirect("index2.php?option=com_networkdevice&task=edit&ND_networkdeviceid=$hasManagedNetwork->MN_networkdeviceid&hidemainmenu=1");
	}
}

function removeNetworkDeviceNetwork($nnid) {
	global $database, $mainframe, $my, $acl, $appContext;
	
	if ($nnid != null) {
		$hasManagedNetwork = HasManagedNetworkDAO::getHasManagedNetworkByID($nnid);
		
		$network = NetworkDAO::getNetworkByID($hasManagedNetwork->MN_networkid);
		$networkDevice = NetworkDeviceDAO::getNetworkDeviceByID($hasManagedNetwork->MN_networkdeviceid);
		
		HasManagedNetworkDAO::removeHasManagedNetworkByID($nnid);
		
		$msg = sprintf(_("Network device managed network '%s' for network device '%s' deleted"), $network->NE_net, $networkDevice->ND_name);
		$appContext->insertMessage($msg);
		$database->log($msg, LOG::LEVEL_INFO);
	}
	
	Core::redirect("index2.php?option=com_networkdevice&task=edit&ND_networkdeviceid=$networkDevice->ND_networkdeviceid&hidemainmenu=1");
}

function editNetworkDeviceInterface($nid, $iid=null) {
	global $database, $my, $acl;
	
	if ($iid != null) {
		$networkDeviceInterface = NetworkDeviceInterfaceDAO::getNetworkDeviceInterfaceByID($iid);
	} else if ($nid != null) {
		$networkDeviceInterface = new NetworkDeviceInterface();
		$networkDeviceInterface->NI_networkdeviceid = $nid;
		$networkDeviceInterface->NI_ipid = 0;
	} else {
		Core::redirect("index2.php?option=com_networkdevice");
	}
	$ips = IpDAO::getIpArray();
	$sortedIps = Array();
	
	foreach ($ips as $ip) {
		$sortedIps[ip2long($ip->IP_address)] = $ip;
	}
	ksort($sortedIps);
	
	HTML_NetworkDevice::editNetworkDeviceInterface($networkDeviceInterface, $sortedIps);
}

function saveNetworkDeviceInterface($task) {
	global $database, $mainframe, $my, $acl, $appContext;

	$networkDeviceInterface = new NetworkDeviceInterface();
	database::bind($_POST, $networkDeviceInterface);
	$isNew 	= !$networkDeviceInterface->NI_networkdeviceinterfaceid;
	
	if ($isNew) {
		$networkDeviceInterface->NI_type = NetworkDeviceInterface::TYPE_UNSPECIFIED;
		$database->insertObject("networkdeviceinterface", $networkDeviceInterface, "NI_networkdeviceinterfaceid", false);
	} else {
		$database->updateObject("networkdeviceinterface", $networkDeviceInterface, "NI_networkdeviceinterfaceid", false, false);
	}
	
	switch ($task) {
		case 'applyInterface':
			$msg = sprintf(_("Network interface '%s' updated"), $networkDeviceInterface->NI_ifname);
			$appContext->insertMessage($msg);
			$database->log($msg, LOG::LEVEL_INFO);
			Core::redirect("index2.php?option=com_networkdevice&task=editInterface&ND_networkdeviceid=$networkDeviceInterface->NI_networkdeviceid&NI_networkdeviceinterfaceid=$networkDeviceInterface->NI_networkdeviceinterfaceid&hidemainmenu=1");
		case 'saveInterface':
			$msg = sprintf(_("Network interface '%s' saved"), $networkDeviceInterface->NI_ifname);
			$appContext->insertMessage($msg);
			$database->log($msg, LOG::LEVEL_INFO);
		default:
			Core::redirect("index2.php?option=com_networkdevice&task=edit&ND_networkdeviceid=$networkDeviceInterface->NI_networkdeviceid&hidemainmenu=1");
	}
}

function removeNetworkDeviceInterface($iid) {
	global $database, $mainframe, $my, $acl, $appContext;
	
	if ($iid != null) {
		$networkDeviceInterface = NetworkDeviceInterfaceDAO::getNetworkDeviceInterfaceByID($iid);
		$networkDevice = NetworkDeviceDAO::getNetworkDeviceByID($networkDeviceInterface->NI_networkdeviceid);
		
		NetworkDeviceInterfaceDAO::removeNetworkDeviceInterfaceByID($iid);
		
		$msg = sprintf(_("Network interface '%s' for network device '%s' deleted"), $networkDeviceInterface->NI_ifname, $networkDevice->ND_name);
		$appContext->insertMessage($msg);
		$database->log($msg, LOG::LEVEL_INFO);
	}
	
	Core::redirect("index2.php?option=com_networkdevice&task=edit&ND_networkdeviceid=$networkDevice->ND_networkdeviceid&hidemainmenu=1");
}

function editNetworkDeviceWirelessInterface($nid, $wid=null) {
	global $database, $my, $acl;
	
	if ($wid != null) {
		$networkDeviceWirelessInterface = NetworkDeviceWirelessInterfaceDAO::getNetworkDeviceWirelessInterfaceByID($wid);
	} else if ($nid != null) {
		$networkDeviceWirelessInterface = new NetworkDeviceWirelessInterface();
		$networkDeviceWirelessInterface->NW_networkdeviceid = $nid;
	} else {
		Core::redirect("index2.php?option=com_networkdevice");
	}
	$ips = IpDAO::getIpArray();
	$sortedIps = Array();
	
	foreach ($ips as $ip) {
		$sortedIps[ip2long($ip->IP_address)] = $ip;
	}
	ksort($sortedIps);
		
	HTML_NetworkDevice::editNetworkDeviceWirelessInterface($networkDeviceWirelessInterface, $sortedIps);
}

function saveNetworkDeviceWirelessInterface($task) {
	global $database, $mainframe, $my, $acl, $appContext;

	$networkDeviceWirelessInterface = new NetworkDeviceWirelessInterface();
	database::bind($_POST, $networkDeviceWirelessInterface);
	$isNew 	= !$networkDeviceWirelessInterface->NW_networkdevicewirelessinterfaceid;

	if ($isNew) {
		$database->insertObject("networkdevicewirelessinterface", $networkDeviceWirelessInterface, "NW_networkdevicewirelessinterfaceid", false);
	} else {
		$database->updateObject("networkdevicewirelessinterface", $networkDeviceWirelessInterface, "NW_networkdevicewirelessinterfaceid", false, false);
	}
	
	switch ($task) {
		case 'applyWirelessInterface':
			$msg = sprintf(_("Network wireless interface '%s' updated"), $networkDeviceWirelessInterface->NW_ifname);
			$appContext->insertMessage($msg);
			$database->log($msg, LOG::LEVEL_INFO);
			Core::redirect("index2.php?option=com_networkdevice&task=editWirelessInterface&NW_networkdevicewirelessinterfaceid=$networkDeviceWirelessInterface->NW_networkdevicewirelessinterfaceid&hidemainmenu=1");
		case 'saveWirelessInterface':
			$msg = sprintf(_("Network wireless interface '%s' saved"), $networkDeviceWirelessInterface->NW_ifname);
			$appContext->insertMessage($msg);
			$database->log($msg, LOG::LEVEL_INFO);
		default:
			Core::redirect("index2.php?option=com_networkdevice&task=edit&ND_networkdeviceid=$networkDeviceWirelessInterface->NW_networkdeviceid&hidemainmenu=1");
	}
}

function removeNetworkDeviceWirelessInterface($wid) {
	global $database, $mainframe, $my, $acl, $appContext;
	
	if ($wid != null) {
		$networkDeviceWirelessInterface = NetworkDeviceWirelessInterfaceDAO::getNetworkDeviceWirelessInterfaceByID($wid);
		$networkDevice = NetworkDeviceDAO::getNetworkDeviceByID($networkDeviceWirelessInterface->NW_networkdeviceid);
		
		NetworkDeviceWirelessInterfaceDAO::removeNetworkDeviceWirelessInterfaceByID($wid);
		
		$msg = sprintf(_("Network wireless interface '%s' for network device '%s' deleted"), $networkDeviceWirelessInterface->NW_ifname, $networkDevice->ND_name);
		$appContext->insertMessage($msg);
		$database->log($msg, LOG::LEVEL_INFO);
	}
	
	Core::redirect("index2.php?option=com_networkdevice&task=edit&ND_networkdeviceid=$networkDevice->ND_networkdeviceid&hidemainmenu=1");
}

function editNetworkDeviceProperty($nid, $pid=null) {
	global $database, $my, $acl;
	
	if ($pid != null) {
		$networkDeviceProperty = NetworkDevicePropertyDAO::getNetworkDevicePropertyByID($pid);
	} else if ($nid != null) {
		$networkDeviceProperty = new NetworkDeviceProperty();
		$networkDeviceProperty->NP_networkdeviceid = $nid;
	} else {
		Core::redirect("index2.php?option=com_networkdevice");
	}
		
	HTML_NetworkDevice::editNetworkDeviceProperty($networkDeviceProperty);
}

function saveNetworkDeviceProperty($task) {
	global $database, $mainframe, $my, $acl, $appContext;

	$networkDeviceProperty = new NetworkDeviceProperty();
	database::bind($_POST, $networkDeviceProperty);
	$isNew 	= !$networkDeviceProperty->NP_networkdevicepropertyid;

	if ($isNew) {
		$database->insertObject("networkdeviceproperty", $networkDeviceProperty, "NP_networkdevicepropertyid", false);
	} else {
		$database->updateObject("networkdeviceproperty", $networkDeviceProperty, "NP_networkdevicepropertyid", false, false);
	}
	
	switch ($task) {
		case 'applyProperty':
			$msg = sprintf(_("Network device property '%s' updated"), $networkDeviceProperty->NP_name);
			$appContext->insertMessage($msg);
			$database->log($msg, LOG::LEVEL_INFO);
			Core::redirect("index2.php?option=com_networkdevice&task=editProperty&NP_networkdevicepropertyid=$networkDeviceProperty->NP_networkdevicepropertyid&hidemainmenu=1");
		case 'saveProperty':
			$msg = sprintf(_("Network device property '%s' saved"), $networkDeviceProperty->NP_name);
			$appContext->insertMessage($msg);
			$database->log($msg, LOG::LEVEL_INFO);
		default:
			Core::redirect("index2.php?option=com_networkdevice&task=edit&ND_networkdeviceid=$networkDeviceProperty->NP_networkdeviceid&hidemainmenu=1");
	}
}

function removeNetworkDeviceProperty($pid) {
	global $database, $mainframe, $my, $acl, $appContext;
	
	if ($pid != null) {
		$networkDeviceProperty = NetworkDevicePropertyDAO::getNetworkDevicePropertyByID($pid);
		$networkDevice = NetworkDeviceDAO::getNetworkDeviceByID($networkDeviceProperty->NP_networkdeviceid);
		
		NetworkDevicePropertyDAO::removeNetworkDevicePropertyByID($pid);
		
		$msg = sprintf(_("Network device property '%s' for network device '%s' deleted"), $networkDeviceProperty->NP_name, $networkDevice->ND_name);
		$appContext->insertMessage($msg);
		$database->log($msg, LOG::LEVEL_INFO);
	}
	
	Core::redirect("index2.php?option=com_networkdevice&task=edit&ND_networkdeviceid=$networkDevice->ND_networkdeviceid&hidemainmenu=1");
}

function testLogin() {
	global $core, $database, $mainframe, $my, $acl, $appContext;

	$networkDevice = new NetworkDevice();
	database::bind($_POST, $networkDevice);
	
	if (!$networkDevice->ND_networkdeviceid) {
		editNetworkDevice($networkDevice->ND_networkdeviceid);
	}
	
	$ND_password1 = trim(Utils::getParam($_POST, 'ND_password1', ""));
	$ND_password2 = trim(Utils::getParam($_POST, 'ND_password2', ""));
	
	if (($ND_password1 == "******" && $ND_password2 == "******") || ($ND_password1 == "" && $ND_password2 == "")) {
		$storedNetworkDevice = NetworkDeviceDAO::getNetworkDeviceByID($networkDevice->ND_networkdeviceid);
		$networkDevice->ND_password = $storedNetworkDevice->ND_password;
	} else if ($ND_password1 == $ND_password2) {
		$networkDevice->ND_password = $ND_password1;
	} else {
		$core->alert(_("User passwords are not same"));
		
		editNetworkDevice($networkDevice->ND_networkdeviceid);
	}

	try {
		if ($networkDevice->ND_managementInterfaceId) {
			$managementInterface = NetworkDeviceInterfaceDAO::getNetworkDeviceInterfaceByID($networkDevice->ND_managementInterfaceId);
			$managementIp = IpDAO::getIpByID($managementInterface->NI_ipid);

			if ($networkDevice->ND_platform == NetworkDevice::PLATFORM_GNU_LINUX_DEBIAN) {
				$settings = array();
				$settings[Executor::REMOTE_HOST] = $managementIp->IP_address;
				$settings[Executor::REMOTE_PORT] = 22;
				$settings[Executor::LOGIN] = $networkDevice->ND_login;
				$settings[Executor::PASSWORD] = $networkDevice->ND_password;
				$settings[Executor::SUDO_COMMAND] = $networkDevice->ND_commandSudo;
				
				$executor = new Executor(Executor::REMOTE_SSH2, $settings, true);
				
				$appContext->insertMessage(sprintf(_("Login successfull: ssh %s@%s"), $networkDevice->ND_login, $managementIp->IP_address));
			} else if ($networkDevice->ND_platform == NetworkDevice::PLATFORM_ROUTEROS) {
				$settings = array();
				$settings[Executor::REMOTE_HOST] = $managementIp->IP_address;
				$settings[Executor::LOGIN] = $networkDevice->ND_login;
				$settings[Executor::PASSWORD] = $networkDevice->ND_password;

				$executor = new Executor(Executor::REMOTE_MIKROTIK_API, $settings, true);
				
				$appContext->insertMessage(sprintf(_("Login successfull: mikrotik API %s@%s"), $networkDevice->ND_login, $managementIp->IP_address));
			}
			
		} else {
			if ($networkDevice->ND_platform == NetworkDevice::PLATFORM_GNU_LINUX_DEBIAN) {
				$settings = array();
				$settings[Executor::SUDO_COMMAND] = $networkDevice->ND_commandSudo;
				
				$executor = new Executor(Executor::LOCAL_COMMAND, $settings, true);
			} else if ($networkDevice->ND_platform == NetworkDevice::PLATFORM_ROUTEROS) {
				$settings = array();
				$settings[Executor::REMOTE_HOST] = '127.0.0.1';
				$settings[Executor::LOGIN] = $networkDevice->ND_login;
				$settings[Executor::PASSWORD] = $networkDevice->ND_password;
				
				$executor = new Executor(Executor::REMOTE_MIKROTIK_API, $settings, true);
				
				$appContext->insertMessage(sprintf(_("Login successfull: mikrotik API %s@%s"), $networkDevice->ND_login, '127.0.0.1'));
			}
		}
		
		if ($networkDevice->ND_platform == NetworkDevice::PLATFORM_GNU_LINUX_DEBIAN) {
			$uname = $executor->execute("uname -a");
			$appContext->insertMessage($uname[1]);
			
			if ($networkDevice->ND_commandIptables) {
				$iptables = $executor->execute(sprintf("%s --version", $networkDevice->ND_commandIptables));
				if ($iptables[2]) {
					$appContext->insertMessage(sprintf(_("iptables not found on specified path %s"), $networkDevice->ND_commandIptables));
				} else {
					$appContext->insertMessage($iptables[1]);
				}
			} else {
				$iptables = $executor->execute("find / -name iptables -print");
				$appContext->insertMessage($iptables[1]);
			}
		} else if ($networkDevice->ND_platform == NetworkDevice::PLATFORM_ROUTEROS) {
			
			$result1 = $executor->execute(array("/system/routerboard/print"));
			$result2 = $executor->execute(array("/system/resource/print"));
			foreach ($result1[1] as $result11) {
				foreach ($result11 as $key=>$value) {
					$appContext->insertMessage($key.": ".$value);
				}
			}
			
			foreach ($result2[1] as $result21) {
				foreach ($result21 as $key=>$value) {
					$appContext->insertMessage($key.": ".$value);
				}
			}
		}
	} catch (Exception $e) {
		$msg = sprintf(_("Login failed: %s@%s: %s"), $networkDevice->ND_login, $managementIp->IP_address, $e->getMessage());
		$appContext->insertMessage($msg);
	}
	
	Core::redirect("index2.php?option=com_networkdevice&task=edit&ND_networkdeviceid=$networkDevice->ND_networkdeviceid&hidemainmenu=1");
}

function getLeafNetworks($id, &$allNetworks, &$networks) {
	$ipv4 = new Net_IPv4();
	$childrenNetworks = array();
	foreach ($allNetworks as &$network) {
		if ($network->NE_parent_networkid == $id) {
			$netParse = $ipv4->parseAddress($network->NE_net);
			$childrenNetworks[ip2long($netParse->network)] = clone $network;
		}
	}
	if (sizeof($childrenNetworks)) {
		ksort($childrenNetworks);
		
		foreach ($childrenNetworks as $network) {
			$netParse = $ipv4->parseAddress($network->NE_net);
			getLeafNetworks($network->NE_networkid, $allNetworks, $networks);
		}
	} else {
		$networks[$id] = clone $allNetworks[$id];
	}
}
?>