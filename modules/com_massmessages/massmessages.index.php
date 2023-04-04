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
require_once $core->getAppRoot() . "includes/smsgateapi_sluzba_cz/apixml30.php";
require_once 'massmessages.html.php';
require_once 'Net/IPv4.php';

$task = Utils::getParam($_REQUEST, 'task', null);
$nid = Utils::getParam($_REQUEST, 'NE_networkid', null);
$cid = Utils::getParam($_REQUEST, 'cid', []);
if (!is_array($cid)) {
    $cid = [];
}

switch ($task) {
case 'newMessage':
    newMessage($cid);
    break;

case 'sendMessage':
    sendMessage($cid);
    break;

default:
    showNetwork($nid);
    break;
}

/**
 * showNetwork
 * will show network with particular $nid NE_networkid highlighted
 *
 * @param $nid NE_networkid of network to highlight
 */
function showNetwork()
{
    global $database;

    $filter = [];
    // default settings if no setting in session
    $filter['NE_networkid'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_massmessages']['filter'], 'NE_networkid', null);
    $nid = $filter['NE_networkid'];

    // Load all networks
    $allNetworks = NetworkDAO::getNetworkArray();

    // Load all IPs
    $allIps = IpDAO::getIpArray();

    // Load all persons
    $persons = PersonDAO::getPersonArray();

    // Load network that will be highlighted or root network if $nid is null specified
    if ($nid && isset($allNetworks[$nid])) {
        $selectedNetwork = $allNetworks[$nid];
    } else {
        $selectedNetwork = NetworkDAO::getFirstNetworkByParentNetworkID(0);
        $nid = $selectedNetwork->NE_networkid;
    }
    $ipv4 = new Net_IPv4();

    // Build comlete network in tree
    $networkTree = buildNetworkTree(0, $allNetworks);

    // Build array of arrays with networkid keys containing array of IPs in this network
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
    $isLeafNetwork = NetworkDAO::isLeafNetwork($nid);

    // According to, if we highlight leaf network or not
    $leafSubNetworks = [];
    if ($isLeafNetwork) {
        $leafSubNetworks[$nid] = $selectedNetwork;
    } else {
        // Build array of all leaf subnetworks for highlighted network
        // Only in leaf subnets can be IPs
        findLeafSubnets($nid, $networkTree, $leafSubNetworks, false);
    }

    // Build array with all ips in current leaf subnetworks
    $ipShowListTemp = [];
    foreach ($leafSubNetworks as $leafSubNetwork) {
        if (isset($ipList[$leafSubNetwork->NE_networkid])) {
            $ipShowListTemp[] = $ipList[$leafSubNetwork->NE_networkid];
        }
    }
    $ipShowList = array_merge([], ...$ipShowListTemp);

    HTML_massmessages::showNetwork($networkTree, $selectedNetwork, $leafSubNetworks, $ipShowList, $persons);
}

/**
 * newMessage
 */
function newMessage($cid)
{
    $persons = [];
    foreach ($cid as $ipId) {
        $person = PersonDAO::getPersonByIPId($ipId);
        $person->_IP = IpDAO::getIpByID($ipId);

        $persons[$person->PE_personid] = $person;
    }

    function isNullOrEmptyString($str)
    {
        return (!isset($str) || trim($str) === '');
    }

    function personsWithoutMobile($person)
    {
        return isNullOrEmptyString($person->PE_tel) && $person->PE_status == Person::STATUS_ACTIVE;
    }

    function personsWithMobile($person)
    {
        return !isNullOrEmptyString($person->PE_tel) && $person->PE_status == Person::STATUS_ACTIVE;
    }

    function inactivePersons($person)
    {
        return $person->PE_status != Person::STATUS_ACTIVE;
    }

    $inactivePersons = array_filter($persons, "inactivePersons");
    $personsWithoutMobile = array_filter($persons, "personsWithoutMobile");
    $persons = array_filter($persons, "personsWithMobile");

    HTML_massmessages::newMessage($personsWithoutMobile, $persons, $inactivePersons);
}

/**
 * newMessage
 */
function sendMessage()
{
    global $database, $appContext, $core;

    $message = Utils::getParam($_REQUEST, 'message', '');
    $pid = Utils::getParam($_REQUEST, 'pid', []);

    $username = $core->getProperty(Core::SMS_USERNAME);
    $password = $core->getProperty(Core::SMS_PASSWORD);

    if (trim($message) !== "") {
        foreach ($pid as $personId) {
            $person = PersonDAO::getPersonById($personId);

            $apixml = new ApiXml30($username, $password);
            $result = $apixml->send_message($person->PE_tel, $message, null, 0);
            $xml = new SimpleXMLElement($result);

            $commonMessage = "$person->PE_firstname $person->PE_surname <$person->PE_tel> SMS: \"$message\"";
            if ($xml->getName() === "status") {
                $logMessage = "$commonMessage chyba: $xml->message";
                $database->log($logMessage, Log::LEVEL_ERROR);
                $appContext->insertMessage($logMessage);
                continue;
            }

            if ($xml->getName() === "messages") {
                $logMessage = "$commonMessage odesláno";
                $database->log($logMessage, Log::LEVEL_INFO);
                $appContext->insertMessage($logMessage);
                continue;
            }

            $logMessage = "$commonMessage neznámá chyba při odesílání: $result";
            $database->log($logMessage, Log::LEVEL_ERROR);
            $appContext->insertMessage($logMessage);
        }
    }

    Core::redirect("index2.php?option=com_massmessages");
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
