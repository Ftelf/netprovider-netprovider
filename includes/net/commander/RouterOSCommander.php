<?php
//
// +----------------------------------------------------------------------+
// | Ftelf ISP billing system                                               |
// +----------------------------------------------------------------------+
// | Copyright (c) 2006-2007 Ing. Lukas Dziadkowiec                       |
// +----------------------------------------------------------------------+
// | This source file is part of Ftelf ISP billing system,                  |
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

/**
 * RouterOSCommander
 */
class RouterOSCommander {
    private $networkDevice;

    private $rejectUnknownIP;
    private $redirectUnknownIP;
    private $redirectToIP;
    private $allowedHosts;

    public function __construct($networkDevice) {
        global $core;

        $this->networkDevice = $networkDevice;

        $this->rejectUnknownIP = $core->getProperty(Core::REJECT_UNKNOWN_IP);
        $this->redirectUnknownIP = $core->getProperty(Core::REDIRECT_UNKNOWN_IP);
        $this->redirectToIP = $core->getProperty(Core::REDIRECT_TO_IP);
        $this->allowedHosts = explode(";", $core->getProperty(Core::ALLOWED_HOSTS));
    }

    public function accountIP($executor) {
        $this->synchronizeFilter($executor);

        global $database;

        $now = new DateUtil();
        $dateString = $now->getFormattedDate(DateUtil::DB_DATETIME);

        $ipArray = array();

        $filterInIpCmd = array("/ip/firewall/filter/print", "?=chain=XFILTER-IN", "?=action=accept", "=stats=");
        $filterInIpResult = $executor->execute($filterInIpCmd);
        foreach ($filterInIpResult[1] as $filterInIp) {
            $acc = array();
            $acc['bytes-in'] = $filterInIp['bytes'];
            $acc['packets-in'] = $filterInIp['packets'];
            $ipArray[$filterInIp['dst-address']] = $acc;
        }

        $filterOutIpCmd = array("/ip/firewall/filter/print", "?=chain=XFILTER-OUT", "?=action=accept", "=stats=");
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

    public function synchronizeFilter($executor) {
        $diacriticsUtil = new DiacriticsUtil();
        $cmds = array();

        if (!$this->isIpFilterValid($executor)) {
            $cmd = array("/ip/firewall/filter/print", "?chain=XFILTER-IN", "=.proplist=.id");
            $filterArray = $executor->execute($cmd);
            $cmds[] = $filterArray;

            if (count($filterArray[1])) {
                $idArray = array_column($filterArray[1], '.id');
                $ids = implode(',', $idArray);

                $cmd = array("/ip/firewall/filter/remove", sprintf("=numbers=%s", $ids));
                $cmds[] = $executor->execute($cmd);
            }

            $cmd = array("/ip/firewall/filter/print", "?chain=XFILTER-OUT", "=.proplist=.id");
            $filterArray = $executor->execute($cmd);
            $cmds[] = $filterArray;

            if (count($filterArray[1])) {
                $idArray = array_column($filterArray[1], '.id');
                $ids = implode(',', $idArray);

                $cmd = array("/ip/firewall/filter/remove", sprintf("=numbers=%s", $ids));
                $cmds[] = $executor->execute($cmd);
            }

            $cmd = array("/ip/firewall/filter/add", "=chain=XFILTER-IN", "=limit=1/3600,1", "=action=log", "=log-prefix=UNKNOWN-IN:");
            $cmds[] = $executor->execute($cmd);
            $cmd = array("/ip/firewall/filter/add", "=chain=XFILTER-OUT", "=limit=1/3600,1", "=action=log", "=log-prefix=UNKNOWN-OUT:");
            $cmds[] = $executor->execute($cmd);

            $cmd= array("/ip/firewall/filter/add", "=chain=XFILTER-IN", "=action=reject", "=disabled=no");
            $cmds[] = $executor->execute($cmd);
            $cmd = array("/ip/firewall/filter/add", "=chain=XFILTER-OUT", "=action=reject", "=disabled=no");
            $cmds[] = $executor->execute($cmd);
        }

        $ipAddressMap = array();
        foreach ($this->networkDevice->NETWORKS as &$network) {
            foreach ($network['INTERNETS'] as &$internet) {
                foreach ($internet['IPS'] as &$ip) {
                    $ipAddress = $ip['IP_address'];
                    if (isset($ipAddressMap[$ipAddress])) {
                        continue;
                    }

                    $ipAddressMap[$ipAddress] = $diacriticsUtil->removeDiacritic($internet['PE_firstname'] . " " . $internet['PE_surname'] . ", " . $ip['IP_dns']);
                }
            }
        }

        $cmd = array("/ip/firewall/filter/print", "?chain=XFILTER-IN", "=.proplist=.id,dst-address");
        $resultFilterIn = $executor->execute($cmd);
        $cmds[] = $resultFilterIn;

        $filterInEntries = $resultFilterIn[1];
        $idsToBeRemovedInFilterInArray = [];
        for ($i = 0; $i < (count($filterInEntries) - 2); $i++) {
            $entryFilterIn = $filterInEntries[$i];
            $ipAddress = $entryFilterIn['dst-address'];

            if (!isset($ipAddressMap[$ipAddress])) {
                $idsToBeRemovedInFilterInArray[] = $entryFilterIn['.id'];
            }
        }

        if (count($idsToBeRemovedInFilterInArray) > 0) {
            $ids = implode(',', $idsToBeRemovedInFilterInArray);
            $cmd = array("/ip/firewall/filter/remove", sprintf("=numbers=%s", $ids));
            $cmds[] = $executor->execute($cmd);
        }

        $ipAddressesInFilter = array_column(array_slice($filterInEntries, 0, count($filterInEntries) - 2), 'dst-address');
        $idToPlaceFilterIn = $filterInEntries[count($filterInEntries) - 2]['.id'];

        foreach ($ipAddressMap as $address => $comment) {
            if (!in_array($address, $ipAddressesInFilter)) {
                $cmd = array("/ip/firewall/filter/add", "=chain=XFILTER-IN",  sprintf("=dst-address=%s", $address), sprintf("=comment=%s", $comment), "=action=accept", "=place-before=$idToPlaceFilterIn");
                $cmds[] = $executor->execute($cmd);
            }
        }


        $cmd = array("/ip/firewall/filter/print", "?chain=XFILTER-OUT", "=.proplist=.id,src-address");
        $resultFilterOut = $executor->execute($cmd);
        $cmds[] = $resultFilterOut;

        $filterOutEntries = $resultFilterOut[1];
        $idsToBeRemovedOutFilterInArray = [];
        for ($i = 0; $i < (count($filterOutEntries) - 2); $i++) {
            $entryFilterOut = $filterOutEntries[$i];
            $ipAddress = $entryFilterOut['src-address'];

            if (!isset($ipAddressMap[$ipAddress])) {
                $idsToBeRemovedOutFilterInArray[] = $entryFilterOut['.id'];
            }
        }

        if (count($idsToBeRemovedOutFilterInArray) > 0) {
            $ids = implode(',', $idsToBeRemovedOutFilterInArray);
            $cmd = array("/ip/firewall/filter/remove", sprintf("=numbers=%s", $ids));
            $cmds[] = $executor->execute($cmd);
        }

        $ipAddressesOutFilter = array_column(array_slice($filterOutEntries, 0, count($filterOutEntries) - 2), 'src-address');
        $idToPlaceFilterOut = $filterOutEntries[count($filterOutEntries) - 2]['.id'];

        foreach ($ipAddressMap as $address => $comment) {
            if (!in_array($address, $ipAddressesOutFilter)) {
                $cmd = array("/ip/firewall/filter/add", "=chain=XFILTER-OUT",  sprintf("=src-address=%s", $address), sprintf("=comment=%s", $comment), "=action=accept", "=place-before=$idToPlaceFilterOut");
                $cmds[] = $executor->execute($cmd);
            }
        }

        return self::parseArrayReadable($cmds);
    }

    public function isIpFilterValid($executor) {
        $cmds = array();

        // XFILTER-IN
        $cmd = array("/ip/firewall/filter/print", "?chain=XFILTER-IN", "=.proplist=.id,dst-address,action");
        $resultFilterIn = $executor->execute($cmd);
        $cmds[] = $resultFilterIn;

        $filterInEntries = $resultFilterIn[1];

        $filterInCount = count($filterInEntries);

        if ($filterInCount < 2) {
//            throw new Exception("Invalid entries in XFILTER-IN chain");
            return false;
        }

        $filterInLogEntry = $filterInEntries[$filterInCount - 2];

        if ($filterInLogEntry['action'] != 'log') {
//            throw new Exception("Last but one entry in XFILTER-IN should be: log, but was: " . $filterInLogEntry['action']);
            return false;
        }

        $filterInRejectEntry = $filterInEntries[$filterInCount - 1];

        if ($filterInRejectEntry['action'] != 'reject') {
//            throw new Exception("Last entry in XFILTER-IN should be: reject, but was: " . $filterInRejectEntry['action']);
            return false;
        }

        for ($i = 0; $i < $filterInCount - 2; $i++) {
            if ($filterInEntries[$i]['action'] != 'accept') {
//                throw new Exception("XFILTER-IN entry at position: $i should be accept, but was: ".$filterInEntries[$i]['action']);
                return false;
            }
        }

        // XFILTER-OUT
        $cmd = array("/ip/firewall/filter/print", "?chain=XFILTER-OUT", "=.proplist=.id,src-address,action");
        $resultFilterOut = $executor->execute($cmd);
        $cmds[] = $resultFilterOut;

        $filterOutEntries = $resultFilterOut[1];

        $filterOutCount = count($filterOutEntries);

        if ($filterOutCount < 2) {
//            throw new Exception("Invalid entries in XFILTER-OUT chain");
            return false;
        }

        $filterInLogEntry = $filterOutEntries[$filterOutCount - 2];

        if ($filterInLogEntry['action'] != 'log') {
//            throw new Exception("Last but one entry in XFILTER-OUT should be: log, but was: " . $filterInLogEntry['action']);
            return false;
        }

        $filterInRejectEntry = $filterOutEntries[$filterOutCount - 1];

        if ($filterInRejectEntry['action'] != 'reject') {
//            throw new Exception("Last entry in XFILTER-OUT should be: reject, but was: " . $filterInRejectEntry['action']);
            return false;
        }

        for ($i = 0; $i < $filterOutCount - 2; $i++) {
            if ($filterOutEntries[$i]['action'] != 'accept') {
//                throw new Exception("XFILTER-IN entry at position: $i should be accept, but was: ".$filterInEntries[$i]['action']);
                return false;
            }
        }

        // Match them together
        if ($filterInCount !== $filterOutCount) {
//            throw new Exception("Entries count of XFILTER-IN and XFILTER-OUT differs $filterInCount not equals $filterOutCount");
            return false;
        }

        for ($i = 0; $i < $filterInCount - 2; $i++) {
            if ($filterInEntries[$i]['dst-address'] != $filterOutEntries[$i]['src-address']) {
//                throw new Exception("XFILTER-IN entry at position: $i should have same dst-address: ".$filterOutEntries[$i]['src-address']." as src-address:".$filterInEntries[$i]['dst-address']." in XFILTER-OUT");
                return false;
            }
        }

        return true;
    }

    public function getIPFilterDown($executor) {
        $this->synchronizeFilter($executor);

        $cmds = array();

        $cmd = array("/ip/firewall/filter/print", "?chain=XFILTER-IN", "?action=reject", "?disabled=no");
        $resultFilterIn = $executor->execute($cmd);
        $cmds[] = $resultFilterIn;

        if (isset($resultFilterIn[1]) and count($resultFilterIn[1]) == 1) {
            $cmd = array("/ip/firewall/filter/set", sprintf("=numbers=%s", $resultFilterIn[1][0]['.id']), "=disabled=yes");
            $cmds[] = $executor->execute($cmd);
        }

        $cmd = array("/ip/firewall/filter/print", "?chain=XFILTER-OUT", "?action=reject", "?disabled=no");
        $resultFilterOut = $executor->execute($cmd);
        $cmds[] = $resultFilterOut;

        if (isset($resultFilterOut[1]) and count($resultFilterOut[1]) == 1) {
            $cmd = array("/ip/firewall/filter/set", sprintf("=numbers=%s", $resultFilterOut[1][0]['.id']), "=disabled=yes");
            $cmds[] = $executor->execute($cmd);
        }

        return self::parseArrayReadable($cmds);
    }

    public function getIPFilterUp($executor) {
        $this->synchronizeFilter($executor);

        $cmds = array();

        $cmd = array("/ip/firewall/filter/print", "?chain=XFILTER-IN", "?action=reject", "?disabled=yes");
        $resultFilterIn = $executor->execute($cmd);
        $cmds[] = $resultFilterIn;

        if (isset($resultFilterIn[1]) and count($resultFilterIn[1]) == 1) {
            $cmd = array("/ip/firewall/filter/set", sprintf("=numbers=%s", $resultFilterIn[1][0]['.id']), "=disabled=no");
            $cmds[] = $executor->execute($cmd);
        }

        $cmd = array("/ip/firewall/filter/print", "?chain=XFILTER-OUT", "?action=reject", "?disabled=yes");
        $resultFilterOut = $executor->execute($cmd);
        $cmds[] = $resultFilterOut;

        if (isset($resultFilterOut[1]) and count($resultFilterOut[1]) == 1) {
            $cmd = array("/ip/firewall/filter/set", sprintf("=numbers=%s", $resultFilterOut[1][0]['.id']), "=disabled=no");
            $cmds[] = $executor->execute($cmd);
        }

        return self::parseArrayReadable($cmds);
    }

    static function parseCommandReadable($array) {
        $resultArray = array();

        $resultArray[0] = implode('', $array[0]);

        $return1 = array();
        if (is_array($array[1])) {
            foreach ($array[1] as $returnPart) {
                $string = '';
                foreach ($returnPart as $key=>$value) {
                    $string .= "$key=$value ";
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
