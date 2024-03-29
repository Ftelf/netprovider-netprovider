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

/**
 * ensure this file is being included by a parent file
 */
defined('VALID_MODULE') or die(_("Direct access into this section is not allowed"));

global $core;
require_once $core->getAppRoot() . "includes/dao/IpDAO.php";
require_once $core->getAppRoot() . "includes/dao/IpAccountDAO.php";
require_once $core->getAppRoot() . "includes/dao/IpAccountAbsDAO.php";
require_once $core->getAppRoot() . "includes/dao/NetworkDAO.php";
require_once $core->getAppRoot() . "includes/dao/PersonDAO.php";
require_once 'network.html.php';
require_once 'Net/IPv4.php';

$task = Utils::getParam($_REQUEST, 'task', null);
$nid = Utils::getParam($_REQUEST, 'NE_networkid', null);
$inid = Utils::getParam($_REQUEST, 'IP_networkid', null);
$iid = Utils::getParam($_REQUEST, 'IP_ipid', null);
$cid = Utils::getParam($_REQUEST, 'cid', []);
if (!is_array($cid)) {
    $cid = [];
}

switch ($task) {
case 'newI':
    editIP(null, $nid);
    break;

case 'newN':
    editNetwork($task, $nid);
    break;

case 'editI':
    editIP($iid, $nid);
    break;

case 'editN':
    editNetwork($task, $nid);
    break;

case 'editA':
    editIP($cid[0], null);
    break;

case 'saveI':
case 'applyI':
    saveIP($task, $iid, $nid);
    break;

case 'saveN':
case 'applyN':
    saveNetwork($task);
    break;

case 'removeI':
    removeIp($cid);
    break;

case 'removeN':
    removeNetwork($nid);
    break;

case 'cancel':
default:
    showNetwork($nid);
    break;
}

/**
 * showNetwork
 * will show network with particular $nid NE_networkid highlighted
 */
function showNetwork()
{
    global $database, $core;
    require_once $core->getAppRoot() . 'modules/com_common/PageNav.php';

    $filter = [];
    // default settings if no setting in session
    // do we want Network headers for IPs to be shown ?
    //
    $filter['netheaders'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_network']['filter'], 'netheaders', null);
    $filter['NE_networkid'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_network']['filter'], 'NE_networkid', null);
    $nid = $filter['NE_networkid'];

    $limit = Utils::getParam($_SESSION['UI_SETTINGS']['com_network'], 'limit', 10);
    $limitstart = Utils::getParam($_SESSION['UI_SETTINGS']['com_network'], 'limitstart', 0);

    // Load all networks
    //
    $allNetworks = NetworkDAO::getNetworkArray();
    // Load all IPs
    //
    $allIps = IpDAO::getIpArray();
    // Load all persons
    //
    $persons = PersonDAO::getPersonArray();
    // Load network that will be highlighted or root network if $nid is null specified
    //
    if ($nid && isset($allNetworks[$nid])) {
        $selectedNetwork = $allNetworks[$nid];
    } else {
        $selectedNetwork = NetworkDAO::getFirstNetworkByParentNetworkID(0);
        $nid = $selectedNetwork->NE_networkid;
    }
    $ipv4 = new Net_IPv4();
    $selectedNetworkParsed = $ipv4->parseAddress($selectedNetwork->NE_net);
    // Build comlete network in tree
    //
    $networkTree = buildNetworkTree(0, $allNetworks);
    // Build array of arrays with networkid keys containing array of IPs in this network
    //
    $ipList = [];
    foreach ($allIps as $ip) {
        $ipList[$ip->IP_networkid][ip2long($ip->IP_address)] = $ip;

        // Validate all ip if does not match right subnet, then quietly log
        // DEBUG
        if (!$ipv4->ipInNetwork($ip->IP_address, $allNetworks[$ip->IP_networkid]->NE_net)) {
            $database->log("ERROR: IPID '$ip->IP_ipid' IP '$ip->IP_address' nepatří do sítě ID '" . $allNetworks[$ip->IP_networkid]->NE_networkid . "' síť '" . $allNetworks[$ip->IP_networkid]->NE_net . "'", Log::LEVEL_ERROR);
        }
    }
    // sort ips for each network
    foreach ($ipList as &$ipL) {
        ksort($ipL);
    }
    // find out if current highlighted network is also leaf network
    //
    $isLeafNetwork = NetworkDAO::isLeafNetwork($nid);
    // According to, if we highlight leaf network or not
    //
    $leafSubNetworks = [];
    if ($isLeafNetwork) {
        $leafSubNetworks[$nid] = $selectedNetwork;
        $canAddNetwork = ($selectedNetworkParsed->bitmask < 30);
    } else {
        // Build array of all leaf subnetworks for highlighted network
        // Only in leaf subnets can be IPs
        //
        findLeafSubnets($nid, $networkTree, $leafSubNetworks, false);
        // Load all direct subNetworks, childrens of current highlighted network
        //
        $directSubNetworks = NetworkDAO::getNetworkArrayByParentNetworkID($selectedNetwork->NE_networkid);
        // Sort direct subNetworks
        //
        $directSubNetworksSorted = [];
        foreach ($directSubNetworks as $directSubNetwork) {
            $directSubNetworkParsed = $ipv4->parseAddress($directSubNetwork->NE_net);
            $directSubNetworksSorted[ip2long($directSubNetworkParsed->network)] = $directSubNetwork;
        }
        ksort($directSubNetworksSorted);
        $directSubNetworks = $directSubNetworksSorted;
        // Make list of plain direct subnets
        //
        $plainDirectSubNetworks = [];
        foreach ($directSubNetworks as $directSubNetwork) {
            $plainDirectSubNetworks[] = $directSubNetwork->NE_net;
        }
        $canAddNetwork = isAnyFreeSubNetworks($selectedNetwork->NE_net, $plainDirectSubNetworks);
    }
    // Build array with all ips in current leaf subnetworks
    //
    $ipShowListTemp = [];
    foreach ($leafSubNetworks as $leafSubNetwork) {
        if (isset($ipList[$leafSubNetwork->NE_networkid])) {
            $ipShowListTemp[] = $ipList[$leafSubNetwork->NE_networkid];
        }
    }
    $ipShowList = array_merge([], ...$ipShowListTemp);
    $pageNav = new PageNav(count($ipShowList), $limitstart, $limit);
    // chop $ipShowList only to IPs that we want to display
    //
    $ipShowList = array_slice($ipShowList, $limitstart, $limit);

    $flags = [];
    if ($selectedNetwork->NE_parent_networkid == 0) { // root network selected
        $flags['net_new'] = $canAddNetwork;
        $flags['net_edit'] = false;
        $flags['net_delete'] = false;
    } else if (isset($ipList[$nid])) { // there is some ip in subnet
        $flags['net_new'] = false;
        $flags['net_edit'] = true;
        $flags['net_delete'] = false;
    } else if (!$isLeafNetwork) {
        $flags['net_new'] = $canAddNetwork; // there is some subnets
        $flags['net_edit'] = true;
        $flags['net_delete'] = false;
    } else {
        $flags['net_new'] = $canAddNetwork; // leaf subnet
        $flags['net_edit'] = true;
        $flags['net_delete'] = true;
    }
    // new IP can be added only to leaf network and if there is any room left
    //
    if ($isLeafNetwork) {
        $ipCount = (isset($ipList[$nid])) ? count($ipList[$nid]) : 0;
        $freeip = pow(2, 32 - $selectedNetworkParsed->bitmask) - 2 - $ipCount;
        $flags['ip_new'] = $freeip > 0;
    } else {
        $flags['ip_new'] = false;
    }
    $flags['ip_edit'] = true;
    $flags['ip_delete'] = true;

    HTML_Network::showNetwork($networkTree, $selectedNetwork, $leafSubNetworks, $ipList, $ipShowList, $persons, $pageNav, $filter, $flags);
}

/**
 * editIP
 *
 * @param $ipid
 * @param $nid
 */
function editIP($ipid, $nid): void
{
    global $database, $appContext;
    $ipv4 = new Net_IPv4();

    // query for IP if edit or leave blank ip Class
    //
    if ($ipid != null) {
        $ip = IpDAO::getIpByID($ipid);
        $nid = $ip->IP_networkid;
    } else {
        $ip = new Ip();
    }
    // in this network we will create new ip
    //
    $network = NetworkDAO::getNetworkByID($nid);
    // test if this is leaf network
    //
    if (!NetworkDAO::isLeafNetwork($nid)) {
        $msg = "ERROR: mod_network, pokus o otevření editIP a vložení nové IP adresy do ne-leaf sítě ID '$nid' síť '$network->NE_net'";
        $appContext->insertMessage($msg);
        $database->log($msg, Log::LEVEL_ERROR);
        Core::redirect("index2.php?option=com_network&NE_networkid=$nid");
    }
    // load Person list
    //
    $persons = PersonDAO::getPersonArray();
    // query for all ip in current network except the one we will edit
    //
    $usedIpList = IpDAO::getIpArray();
    unset($usedIpList[$ipid]);
    // If bitmask is 24 or higher, we will create ip list of possible IPs
    //
    $networkParsed = $ipv4->parseAddress($network->NE_net);
    if ($networkParsed->bitmask >= 24) {
        // This ipList will contain available options to select
        //
        $availableIpList = [];
        $broadcastIP = ip2long($networkParsed->broadcast);
        for ($iip = ip2long($networkParsed->network) + 1; $iip < $broadcastIP; $iip++) {
            $availableIpList[$iip] = long2ip($iip);
        }
        foreach ($usedIpList as $usedIp) {
            unset($availableIpList[ip2long($usedIp->IP_address)]);
        }
        if (count($availableIpList) == 0) {
            $database->log("ERROR: mod_network, pokus o otevření editIP a vložení nové IP adresy do plné sítě ID '$nid' síť '$network->NE_net'", Log::LEVEL_ERROR);
            Core::redirect("index2.php?option=com_network&NE_networkid=$nid");
        }
    } else {
        $availableIpList = null;
    }

    HTML_Network::editIP($ip, $network, $availableIpList, $persons);
}

/**
 * editNetwork
 *
 * @param $task
 * @param $nid
 */
function editNetwork($task, $nid = null)
{
    global $database, $appContext;
    $ipv4 = new Net_IPv4();

    $parentNetwork = new Network();
    // query for network if edit or new
    // if newN, $id is ID of parent network
    // else if editN $id is ID of network to edit
    //
    if ($task == 'editN') { // edit network according to networkid
        $network = NetworkDAO::getNetworkByID($nid);
        $parentNetwork = NetworkDAO::getNetworkByID($network->NE_parent_networkid);
    } else if ($task == 'newN') { // new network, we have parent networkid
        $network = new Network();
        $parentNetwork = NetworkDAO::getNetworkByID($nid);
    } else {
        $msg = "ERROR: mod_network, nedefinovaná akce '$task' s ID '$nid'";
        $appContext->insertMessage($msg);
        $database->log($msg, Log::LEVEL_ERROR);
        Core::redirect("index2.php?option=com_network&NE_networkid=$nid");
    }
    // Load all direct subNetworks
    //
    $directSubNetworks = NetworkDAO::getNetworkArrayByParentNetworkID($parentNetwork->NE_networkid);
    // Sort direct subNetworks
    //

    $directSubNetworksSorted = [];
    foreach ($directSubNetworks as $directSubNetwork) {
        $subNetworkParsed = $ipv4->parseAddress($directSubNetwork->NE_net);
        $directSubNetworksSorted[ip2long($subNetworkParsed->network)] = $directSubNetwork;
    }
    ksort($directSubNetworksSorted);
    $directSubNetworks = $directSubNetworksSorted;
    $plainSubNetworks = [];
    foreach ($directSubNetworks as $directSubNetwork) {
        $plainSubNetworks[] = $directSubNetwork->NE_net;
    }
    // load Person list
    //
    $persons = PersonDAO::getPersonArray();
    $flags = [];
    // Get list of all possible subnetwork combinations if we create new network
    //
    if ($task == 'newN') {
        $parentNetworkParsed = $ipv4->parseAddress($parentNetwork->NE_net);
        if ($parentNetworkParsed->bitmask >= 24) {
            $possibleNetworkArray = getFreeSubNetworks($parentNetwork->NE_net, $plainSubNetworks);
            if (count($possibleNetworkArray) == 0) {
                $database->log("ERROR: mod_network, pokus vložit novou podsíť do sítě, kde již není místo ID '$parentNetwork->NE_networkid' síť '$parentNetwork->NE_net'", Log::LEVEL_ERROR);
                Core::redirect("index2.php?option=com_network&NE_networkid=$nid");
            }
            $flags['NE_net'] = "LIST";
        } else {
            if (isAnyFreeSubNetworks($parentNetwork->NE_net, $plainSubNetworks)) {
                $possibleNetworkArray = null;
                $flags['NE_net'] = "TEXTBOX";
            } else {
                $msg = "ERROR: mod_network, pokus vložit novou podsíť do sítě, kde již není místo ID '$parentNetwork->NE_networkid' síť '$parentNetwork->NE_net'";
                $appContext->insertMessage($msg);
                $database->log($msg, Log::LEVEL_ERROR);
                Core::redirect("index2.php?option=com_network&NE_networkid=$nid");
            }
        }
    } else {
        $flags['NE_net'] = "DISABLED";
        $possibleNetworkArray = null;
    }

    HTML_Network::editNet($network, $parentNetwork, $possibleNetworkArray, $directSubNetworks, $persons, $flags);
}

/**
 * saveIP
 *
 * @param $task
 * @param $iid
 * @param $nid
 */
function saveIP($task, $iid, $nid)
{
    global $database, $appContext;

    $ip = new Ip();
    Database::bind($_POST, $ip);
    $isNew = !$ip->IP_ipid;

    // in this network we will create new ip
    //
    $network = NetworkDAO::getNetworkByID($nid);
    // test if this is leaf network, security
    //
    if (!NetworkDAO::isLeafNetwork($nid)) {
        $msg = "ERROR: mod_network, pokus o uložení IP adresy do ne-leaf sítě ID '$nid' síť '$network->NE_net'";
        $appContext->insertMessage($msg);
        $database->log($msg, Log::LEVEL_ERROR);
        Core::redirect("index2.php?option=com_network");
    }

    if ($isNew) {
        $database->insertObject("ip", $ip, "IP_ipid", false);
    } else {
        $database->updateObject("ip", $ip, "IP_ipid", false, false);
    }
    // get owner of the IP
    //
    $person = PersonDAO::getPersonByID($ip->IP_personid);

    switch ($task) {
    case 'applyI':
        $msg = sprintf(_("IP: %s in network %s assigned to person '%s'"), $ip->IP_address, $network->NE_net, $person->PE_firstname . " " . $person->PE_surname);
        $appContext->insertMessage($msg);
        $database->log($msg, Log::LEVEL_INFO);
        Core::redirect("index2.php?option=com_network&task=editI&IP_ipid=$ip->IP_ipid&NE_networkid=$nid&hidemainmenu=1");
        break;
    case 'saveI':
        $msg = sprintf(_("IP: %s in network %s assigned to person '%s'"), $ip->IP_address, $network->NE_net, $person->PE_firstname . " " . $person->PE_surname);
        $appContext->insertMessage($msg);
        $database->log($msg, Log::LEVEL_INFO);
    default:
        Core::redirect("index2.php?option=com_network");
        break;
    }
}

/**
 * saveNetwork
 *
 * @param $task
 */
function saveNetwork($task)
{
    global $database, $appContext;

    $ipv4 = new Net_IPv4();
    $network = new Network();
    $person = new Person();
    Database::bind($_POST, $network);
    $isNew = !$network->NE_networkid;

    // Validate network format
    //
    if (!$ipv4->parseAddress($network->NE_net)) {
        Core::backWithAlert(_("Network address has bad format"));//'Zadaná síť má špatný formát'
    }
    // get parent network
    //
    $parentNetwork = NetworkDAO::getNetworkByID($network->NE_parent_networkid);

    $parentNetworkParsed = $ipv4->parseAddress($parentNetwork->NE_net);
    if (($networkParsed = $ipv4->parseAddress($network->NE_net)) instanceof PEAR_Error) {
        Core::backWithAlert(_("Network address has bad format"));//'Zadaná síť má špatný formát'
    }
    if ($isNew) {
        if ($parentNetworkParsed->network == $networkParsed->network && $parentNetworkParsed->bitmask == $networkParsed->bitmask) {
            Core::backWithAlert(_("Cannot create network identical with it's parent'"));//"Nelze vytvořit identickou síť se svou nadsítí"
        } else {
            if (($parentNetworkParsed->long <= $networkParsed->long) && ($ipv4->ip2double($networkParsed->broadcast) <= $ipv4->ip2double($parentNetworkParsed->broadcast))) {
                // Load all direct subNetworks
                //
                if (($directSubNetworks = NetworkDAO::getNetworkArrayByParentNetworkID($parentNetwork->NE_networkid)) == null) {
                    $subNetworks = [];
                }
                //  Sort direct subNetworks
                //
                $directSubNetworksSorted = [];
                foreach ($directSubNetworks as $directSubNetwork) {
                    $subNetworkParsed = $ipv4->parseAddress($directSubNetwork->NE_net);
                    $directSubNetworksSorted[ip2long($subNetworkParsed->network)] = $directSubNetwork;
                }
                ksort($directSubNetworksSorted);
                $directSubNetworks = $directSubNetworksSorted;
                $plainSubNetworks = [];
                foreach ($directSubNetworks as $directSubNetwork) {
                    $plainSubNetworks[] = $directSubNetwork->NE_net;
                }
                if (!isSpaceForSubNetwork($parentNetwork->NE_net, $plainSubNetworks, $network->NE_net)) {
                    Core::backWithAlert(_("Entered network collide with existing networks"));//"Zadaná síť koliduje s existujícíma sítěma"
                }
            } else {
                Core::backWithAlert(_("Entered network is not subnetwork of it's parent"));//"Zadaná síť neni podsíť této sítě"
            }
        }
    } else {
        // security, if someone try to POST changed form
        //
        if (($networkFromDatabase = NetworkDAO::getNetworkByID($network->NE_networkid)) == null) {
            Core::redirect("index2.php?option=com_network");
        }
        if ($networkFromDatabase->NE_net != $network->NE_net) {
            $msg = "ERROR: pokus o post pozměněné sítě ID '$networkFromDatabase->NE_networkid' v síti '$network->NE_net' přidána uživateli '$person->PE_firstname $person->PE_surname'";
            $appContext->insertMessage($msg);
            $database->log($msg, Log::LEVEL_ERROR);
            Core::redirect("index2.php?option=com_network&task=editN&NE_networkid=$network->NE_networkid&hidemainmenu=1");
        }
    }

    if ($isNew) {
        $database->insertObject("network", $network, "NE_networkid", false);
    } else {
        $database->updateObject("network", $network, "NE_networkid", false, false);
    }

    if (($person = PersonDAO::getPersonByID($network->NE_personid)) == null) {
        Core::redirect("index2.php?option=com_network");
    }
    switch ($task) {
    case 'applyN':
        $msg = sprintf(_("Network %s assigned to user '%s'"), $network->NE_net, $person->PE_firstname . " " . $person->PE_surname);
        $appContext->insertMessage($msg);
        $database->log($msg, Log::LEVEL_INFO);
        Core::redirect("index2.php?option=com_network&task=editN&NE_networkid=$network->NE_networkid&hidemainmenu=1");
        break;
    case 'saveN':
        $msg = sprintf(_("Network %s assigned to user '%s'"), $network->NE_net, $person->PE_firstname . " " . $person->PE_surname);
        $appContext->insertMessage($msg);
        $database->log($msg, Log::LEVEL_INFO);
    default:
        Core::redirect("index2.php?option=com_network&NE_networkid=$network->NE_parent_networkid");
        break;
    }
}

/**
 * removeIp
 *
 * @param $cid
 */
function removeIp($cid)
{
    global $database, $appContext;
    if (count($cid) < 1) {
        Core::backWithAlert(_("Select IP address to delete"));//"Vyber IP adresu pro vymazání"
    }

    if (count($cid)) {
        foreach ($cid as $id) {
            if (($ip = IpDAO::getIpByID($id)) == null) {
                Core::redirect("index2.php?option=com_network");
            }
            $person = PersonDAO::getPersonByID($ip->IP_personid);

            IpDAO::removeIpByID($id);
            IpAccountDAO::removeIpAccountByIPID($id);
            IpAccountAbsDAO::removeIpAccountAbsByIPID($id);
            $msg = sprintf(_("IP %s from user %s was deleted"), $ip->IP_address, $person->PE_firstname . " " . $person->PE_surname);
            $appContext->insertMessage($msg);
            $database->log($msg, Log::LEVEL_INFO);
        }
        Core::redirect("index2.php?option=com_network");
    }
}

/**
 * removeNetwork
 *
 * @param $nid
 */
function removeNetwork($nid)
{
    global $database, $appContext;

    $network = NetworkDAO::getNetworkByID($nid);
    $person = PersonDAO::getPersonByID($network->NE_personid);
    // check if network is leaf
    //
    if (!NetworkDAO::isLeafNetwork($nid)) {
        $msg = "ERROR: pokus smazat ne-leaf síť ID '$network->NE_networkid' sít '$network->NE_net'";
        $appContext->insertMessage($msg);
        $database->log($msg, Log::LEVEL_ERROR);
        Core::redirect("index2.php?option=com_network&NE_networkid=$network->NE_parent_networkid");
    }
    // check if there is any IP
    //
    if (IpDAO::isAnyIpInNetwork($network->NE_networkid)) {
        $msg = "ERROR: pokus smazat síť s IP, ID '$network->NE_networkid' sít '$network->NE_net'";
        $appContext->insertMessage($msg);
        $database->log($msg, Log::LEVEL_ERROR);
        Core::redirect("index2.php?option=com_network&NE_networkid=$network->NE_parent_networkid");
    }
    NetworkDAO::removeNetworkByID($nid);
    $msg = sprintf(_("Network %s from user %s was deleted"), $network->NE_net, $person->PE_firstname . " " . $person->PE_surname);
    $appContext->insertMessage($msg);
    $database->log($msg, Log::LEVEL_INFO);
    // select parent network
    //
    $_SESSION['UI_SETTINGS']['com_network']['filter']['NE_networkid'] = $network->NE_parent_networkid;
    Core::redirect("index2.php?option=com_network");
}

/**
 * buildNetworkTree
 * will return network as tree
 *
 * @param  $id
 * @param  $netA
 * @return tree of networks
 */
function buildNetworkTree($id, $netA)
{
    $arr = [];
    $ipv4 = new Net_IPv4();
    foreach ($netA as $net) {
        if ($net->NE_parent_networkid == $id) {
            $netParse = $ipv4->parseAddress($net->NE_net);
            $arr[ip2long($netParse->network)] = clone $net;
        }
    }
    if (count($arr) == 0) {
        return null;
    }
    ksort($arr);

    foreach ($arr as $net) {
        $netParse = $ipv4->parseAddress($net->NE_net);
        $arr[ip2long($netParse->network)]->child = buildNetworkTree($net->NE_networkid, $netA);
    }
    return $arr;
}

/**
 * findSubnets
 * will return all leaf subnetworks as array from network with $nid NE_networkid
 *
 * @param  $nid          NE_networkid of network child where to start
 * @param  $networkTree  tree of network where we will search
 * @param  $foundSubnets result array of found leaf networks
 * @param  $found
 * @return array of subNetworks
 */
function findLeafSubnets(&$nid, &$networkTree, &$foundSubnets, $found)
{
    if ($networkTree == null) {
        return;
    }

    foreach ($networkTree as $net) {
        if ($found) {
            $netCloned = clone $net;
            if ($netCloned->child == null) {
                unset($netCloned->child);
                $foundSubnets[$netCloned->NE_networkid] = $netCloned;
            }
        }
        findLeafSubnets($nid, $net->child, $foundSubnets, $found | $net->NE_networkid == $nid);
    }
}

/**
 * getFreeSubNetworks
 * substract all subnetworks specified by $n2Array from parent network $n1
 *
 * @param  $n1      in format A.B.C.D/BITMASK
 * @param  $n2Array array of networks in following format A.B.C.D/BITMASK, has to be sorter ascendently
 * @return array of possible network permutations of free subnetworks
 */
function getFreeSubNetworks(&$n1, &$n2Array)
{
    global $database;
    $ipv4 = new Net_IPv4();

    $np1 = $ipv4->parseAddress($n1);
    if (count($n2Array) == 0) {
        return subNetworkPermutation($np1, false);
    }
    // Include only subnetworks, will be remove afterwoods
    //
    $innerNets = [];
    $countBefore = count($n2Array);
    foreach ($n2Array as $n2) {
        $np2 = $ipv4->parseAddress($n2);
        if (($np1->long <= $np2->long) && ($ipv4->ip2double($np2->broadcast) <= $ipv4->ip2double($np1->broadcast))) {
            $innerNets[] = $np2;
        }
    }
    $countAfter = count($innerNets);
    if ($countAfter != $countBefore) {
        $database->log("ERROR: Síť '$n1' obsahuje nekonzistentní subsítě. Databáze '$countBefore', po filtru '$countAfter'", Log::LEVEL_ERROR);
        return [];
    }
    if (count($innerNets) == 0) {
        $database->log("ERROR: Síť '$n1' obsahuje nekonzistentní subsítě. Databáze '$countBefore', po filtru '$countAfter'", Log::LEVEL_ERROR);
        return [];
    }
    $lastAnchor = $np1->long;
    $freeNetsTemp = [];
    foreach ($innerNets as $np2) {
        $n2long = $np2->long;
        if ($n2long > $lastAnchor) {
            $freeNetsTemp[] = subNetworkPermutationByRange(long2ip($lastAnchor), $np2->network, true);
        }
        $lastAnchor = $ipv4->ip2double($np2->broadcast) + 1;
    }
    $freeNets = array_merge([], ...$freeNetsTemp);
    // Finally add last one
    //
    if (ip2long($np2->broadcast) < ip2long($np1->broadcast)) {
        $freeNets = array_merge($freeNets, subNetworkPermutationByRange(long2ip($lastAnchor), long2ip(ip2long($np1->broadcast) + 1), true));
    }
    return $freeNets;
}

/**
 * subNetworkPermutation
 *
 * @param  $networkParsed
 * @param  $self    return this network in permutation or just subnetworks
 * @return array of available permutations
 */
function subNetworkPermutation($networkParsed, $self)
{
    $networkPermutations = [];
    $netStart = ip2long($networkParsed->network);
    $netEnd = ip2long($networkParsed->broadcast) + 1;
    if ($self) {
        $add = 0;
    } else {
        $add = 1;
    }
    for ($bitmask = $networkParsed->bitmask + $add; $bitmask < 31; $bitmask++) {
        $step = pow(2, 32 - $bitmask);
        for ($wip = $netStart; $wip < $netEnd; $wip += $step) {
            $networkPermutations[] = long2ip($wip) . "/" . $bitmask;
        }
    }
    return $networkPermutations;
}

/**
 * subNetworkPermutation
 *
 * @param  $n1
 * @param  $n2
 * @param  $self return this network in permutation or just subnetworks
 * @return array of available permutations
 */
function subNetworkPermutationByRange($n1, $n2, $self)
{
    $ipv4 = new Net_IPv4();

    $nl1 = ip2long($n1);
    $nl2 = ip2long($n2);
    $bm = 32 - ceil(log($nl2 - $nl1) / log(2));

    $networkPermutations = [];
    $n1Parsed = $ipv4->parseAddress($n1 . "/" . $bm);

    $netStart = ip2long($n1Parsed->network);
    $netEnd = ip2long($n2);
    if ($self) {
        $add = 0;
    } else {
        $add = 1;
    }
    for ($bitmask = $n1Parsed->bitmask + $add; $bitmask < 31; $bitmask++) {
        $step = pow(2, 32 - $bitmask);
        for ($wip = $netStart; $wip < $netEnd; $wip += $step) {
            if (($wip >= $nl1) && ($wip + $step <= $nl2)) {
                $networkPermutations[] = long2ip($wip) . "/" . $bitmask;
            }
        }
    }
    return $networkPermutations;
}

/**
 * isAnyFreeSubNetworks
 * substract all subnetworks specified by $n2Array from parent network $n1
 *
 * @param  $n1      in format A.B.C.D/BITMASK
 * @param  $n2Array array of networks in following format A.B.C.D/BITMASK, has to be sorter ascendedly
 * @return true if there is any place for new network
 */
function isAnyFreeSubNetworks(&$n1, &$n2Array)
{
    global $database;

    $ipv4 = new Net_IPv4();

    $np1 = $ipv4->parseAddress($n1);
    if (count($n2Array) == 0) {
        return ($np1->bitmask < 30);
    }
    // Include only subnetworks, will be remove afterwoods
    //
    $innerNets = [];
    $countBefore = count($n2Array);
    foreach ($n2Array as $n2) {
        $np2 = $ipv4->parseAddress($n2);
        if (($np1->long <= $np2->long) && ($ipv4->ip2double($np2->broadcast) <= $ipv4->ip2double($np1->broadcast))) {
            $innerNets[] = $np2;
        }
    }
    $countAfter = count($innerNets);
    if ($countAfter != $countBefore) {
        $database->log("ERROR: Síť '$n1' obsahuje nekonzistentní subsítě. Databáze '$countBefore', po filtru '$countAfter'", Log::LEVEL_ERROR);
        return false;
    }
    if ($countAfter == 0) {
        $database->log("ERROR: Síť '$n1' obsahuje nekonzistentní subsítě. Databáze '$countBefore', po filtru '$countAfter'", Log::LEVEL_ERROR);
        return false;
    }
    $lastAnchor = $np1->long;
    foreach ($innerNets as $np2) {
        $n2long = $np2->long;
        if ($n2long > $lastAnchor) {
            return true;
        }
        $lastAnchor = $ipv4->ip2double($np2->broadcast) + 1;
    }
    // Finally try last one
    //
    if ($ipv4->ip2double($np2->broadcast) < $ipv4->ip2double($np1->broadcast)) {
        return true;
    }
    return false;
}

/**
 * isSpaceForSubNetwork
 * substract all subnetworks specified by $n2Array from parent network $n1
 *
 * @param  $n1      in format A.B.C.D/BITMASK
 * @param  $n2Array array of networks in following format A.B.C.D/BITMASK, has to be sorter ascendedly
 * @param  $nn      find out if this network can be inserted
 * @return true if there is any place for new network
 */
function isSpaceForSubNetwork(&$n1, &$n2Array, &$nn)
{
    global $database;

    $ipv4 = new Net_IPv4();

    $np1 = $ipv4->parseAddress($n1);
    $nnp = $ipv4->parseAddress($nn);
    if (count($n2Array) == 0) {
        if (($np1->long <= $nnp->long) && ($ipv4->ip2double($nnp->broadcast) <= $ipv4->ip2double($np1->broadcast))) {
            return true;
        }
    }
    // Include only subnetworks, will be remove afterwoods
    //
    $innerNets = [];
    $countBefore = count($n2Array);
    foreach ($n2Array as $n2) {
        $np2 = $ipv4->parseAddress($n2);
        if (($np1->long <= $np2->long) && ($ipv4->ip2double($np2->broadcast) <= $ipv4->ip2double($np1->broadcast))) {
            $innerNets[] = $np2;
        }
    }
    $countAfter = count($innerNets);
    if ($countAfter != $countBefore) {
        $database->log("ERROR: Síť '$n1' obsahuje nekonzistentní subsítě. Databáze '$countBefore', po filtru '$countAfter'", Log::LEVEL_ERROR);
        return false;
    }
    if ($countAfter == 0) {
        $database->log("ERROR: Síť '$n1' obsahuje nekonzistentní subsítě. Databáze '$countBefore', po filtru '$countAfter'", Log::LEVEL_ERROR);
        return false;
    }
    $lastAnchor = $np1->long;
    foreach ($innerNets as $np2) {
        $n2long = $np2->long;
        if ($n2long > $lastAnchor) {
            if (($lastAnchor <= $nnp->long) && (($ipv4->ip2double($nnp->broadcast) + 1) <= $ipv4->ip2double($np2->network))) {
                return true;
            }
        }
        $lastAnchor = $ipv4->ip2double($np2->broadcast) + 1;
    }
    // Finally try last one
    //
    if ($ipv4->ip2double($np2->broadcast) < $ipv4->ip2double($np1->broadcast)) {
        if (($lastAnchor <= $nnp->long) && ($ipv4->ip2double($nnp->broadcast) <= $ipv4->ip2double($np1->broadcast))) {
            return true;
        }
    }
    return false;
}
