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
require_once $core->getAppRoot() . "includes/dao/HasManagedNetworkDAO.php";
require_once $core->getAppRoot() . "includes/dao/NetworkDeviceDAO.php";
require_once $core->getAppRoot() . "includes/dao/NetworkDeviceInterfaceDAO.php";
require_once $core->getAppRoot() . "includes/dao/InternetDAO.php";
require_once $core->getAppRoot() . "includes/dao/IpAccountAbsDAO.php";
require_once $core->getAppRoot() . "includes/dao/IpAccountDAO.php";
require_once $core->getAppRoot() . "includes/Executor.php";
require_once $core->getAppRoot() . "includes/utils/Utils.php";
require_once $core->getAppRoot() . "includes/utils/DiacriticsUtil.php";
require_once 'Net/IPv4.php';

/**
 * RouterOSCommander
 */
class RouterOSCommander
{
    private const FILTER_IN = 'FILTER-IN';
    private const FILTER_OUT = 'FILTER-OUT';

    private $networkDevice;

    private $rejectUnknownIP;
    private $redirectUnknownIP;
    private $redirectToIP;
    private $allowedHosts;

    public function __construct($networkDevice)
    {
        global $core;

        $this->networkDevice = $networkDevice;

        $this->rejectUnknownIP = $core->getProperty(Core::REJECT_UNKNOWN_IP);
        $this->redirectUnknownIP = $core->getProperty(Core::REDIRECT_UNKNOWN_IP);
        $this->redirectToIP = $core->getProperty(Core::REDIRECT_TO_IP);
        $this->allowedHosts = explode(";", $core->getProperty(Core::ALLOWED_HOSTS));
    }

    public function accountIP($executor): void
    {
        $this->synchronizeFilter($executor);

        global $database;

        $now = new DateUtil();
        $dateString = $now->getFormattedDate(DateUtil::DB_DATETIME);

        $ipArray = [];

        $filterInIpResult = $executor->execute(array("/ip/firewall/filter/print", sprintf("?=chain=%s", RouterOSCommander::FILTER_IN), "?=action=accept", "=stats="));
        foreach ($filterInIpResult[1] as $filterInIp) {
            $acc = [];
            $acc['bytes-in'] = $filterInIp['bytes'];
            $acc['packets-in'] = $filterInIp['packets'];
            $ipArray[$filterInIp['dst-address']] = $acc;
        }

        $filterOutIpResult = $executor->execute(array("/ip/firewall/filter/print", sprintf("?=chain=%s", RouterOSCommander::FILTER_OUT), "?=action=accept", "=stats="));
        foreach ($filterOutIpResult[1] as $filterOutIp) {
            if (isset($ipArray[$filterOutIp['src-address']])) {
                $ipArray[$filterOutIp['src-address']]['bytes-out'] = $filterOutIp['bytes'];
                $ipArray[$filterOutIp['src-address']]['packets-out'] = $filterOutIp['packets'];
            } else {
                echo "error";
            }
        }

        foreach ($ipArray as $key => $ipResult) {
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

            $ipAccount->IA_bytes_in = ($inBytes >= $ipAccountAbs->IB_bytes_in) ? $inBytes - $ipAccountAbs->IB_bytes_in : $inBytes;
            $ipAccount->IA_bytes_out = ($outBytes >= $ipAccountAbs->IB_bytes_out) ? $outBytes - $ipAccountAbs->IB_bytes_out : $outBytes;
            $ipAccount->IA_packets_in = ($inPackets >= $ipAccountAbs->IB_packets_in) ? $inPackets - $ipAccountAbs->IB_packets_in : $inPackets;
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

    public function synchronizeFilter($executor)
    {
        $diacriticsUtil = new DiacriticsUtil();
        $cmds = [];

        $this->resetIpFilter($executor, $cmds);

        $ipAddressMap = [];
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

        $resultFilterIn = $executor->execute(array("/ip/firewall/filter/print", sprintf("?chain=%s", RouterOSCommander::FILTER_IN), "=.proplist=.id,dst-address"));
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
            $cmds[] = $executor->execute(array("/ip/firewall/filter/remove", sprintf("=numbers=%s", implode(',', $idsToBeRemovedInFilterInArray))));
        }

        $ipAddressesInFilter = array_column(array_slice($filterInEntries, 0, count($filterInEntries) - 2), 'dst-address');
        $idToPlaceFilterIn = $filterInEntries[count($filterInEntries) - 2]['.id'];

        foreach ($ipAddressMap as $address => $comment) {
            if (!in_array($address, $ipAddressesInFilter)) {
                $cmds[] = $executor->execute(
                    array(
                        "/ip/firewall/filter/add",
                        sprintf("=chain=%s", RouterOSCommander::FILTER_IN),
                        sprintf("=dst-address=%s", $address),
                        sprintf("=comment=%s", $comment),
                        "=action=accept",
                        "=place-before=$idToPlaceFilterIn"
                    )
                );
            }
        }

        $resultFilterOut = $executor->execute(array("/ip/firewall/filter/print", sprintf("?chain=%s", RouterOSCommander::FILTER_OUT), "=.proplist=.id,src-address"));
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
            $cmds[] = $executor->execute(array("/ip/firewall/filter/remove", sprintf("=numbers=%s", $ids)));
        }

        $ipAddressesOutFilter = array_column(array_slice($filterOutEntries, 0, count($filterOutEntries) - 2), 'src-address');
        $idToPlaceFilterOut = $filterOutEntries[count($filterOutEntries) - 2]['.id'];

        foreach ($ipAddressMap as $address => $comment) {
            if (!in_array($address, $ipAddressesOutFilter)) {
                $cmds[] = $executor->execute(
                    array(
                        "/ip/firewall/filter/add",
                        sprintf("=chain=%s", RouterOSCommander::FILTER_OUT),
                        sprintf("=src-address=%s", $address),
                        sprintf("=comment=%s", $comment),
                        "=action=accept",
                        "=place-before=$idToPlaceFilterOut"
                    )
                );
            }
        }

        return self::parseArrayReadable($cmds);
    }

    public function resetIpFilter($executor, &$cmds): void
    {
        if (!$this->isIpFilterValid($executor, $cmds)) {
            $filterArray = $executor->execute(array("/ip/firewall/filter/print", sprintf("?chain=%s", RouterOSCommander::FILTER_IN), "=.proplist=.id"));
            $cmds[] = $filterArray;

            if (count($filterArray[1])) {
                $idArray = array_column($filterArray[1], '.id');
                $ids = implode(',', $idArray);

                $cmds[] = $executor->execute(array("/ip/firewall/filter/remove", sprintf("=numbers=%s", $ids)));
            }

            $filterArray = $executor->execute(array("/ip/firewall/filter/print", sprintf("?chain=%s", RouterOSCommander::FILTER_OUT), "=.proplist=.id"));
            $cmds[] = $filterArray;

            if (count($filterArray[1])) {
                $idArray = array_column($filterArray[1], '.id');
                $ids = implode(',', $idArray);

                $cmds[] = $executor->execute(array("/ip/firewall/filter/remove", sprintf("=numbers=%s", $ids)));
            }

            $cmds[] = $executor->execute(array("/ip/firewall/filter/add", sprintf("=chain=%s", RouterOSCommander::FILTER_IN), "=limit=1/3600,1", "=action=log", "=log-prefix=UNKNOWN-IN:"));
            $cmds[] = $executor->execute(array("/ip/firewall/filter/add", sprintf("=chain=%s", RouterOSCommander::FILTER_OUT), "=limit=1/3600,1", "=action=log", "=log-prefix=UNKNOWN-OUT:"));

            $cmds[] = $executor->execute(array("/ip/firewall/filter/add", sprintf("=chain=%s", RouterOSCommander::FILTER_IN), "=action=reject", "=disabled=no"));
            $cmds[] = $executor->execute(array("/ip/firewall/filter/add", sprintf("=chain=%s", RouterOSCommander::FILTER_OUT), "=action=reject", "=disabled=no"));
        }
    }

    public function isIpFilterValid($executor, &$cmds)
    {
        // FILTER-IN
        $resultFilterIn = $executor->execute(array("/ip/firewall/filter/print", sprintf("?chain=%s", RouterOSCommander::FILTER_IN), "=.proplist=.id,dst-address,action"));
        $cmds[] = $resultFilterIn;

        $filterInEntries = $resultFilterIn[1];
        $filterInIPPart = array_slice($filterInEntries, 0, count($filterInEntries) - 2);

        if (array_column(array_slice($filterInEntries, -2), "action") !== ["log", "reject"]) {
            return false;
        }

        if (count(
            array_filter(
                $filterInIPPart, function ($e) {
                return $e["action"] !== "accept";
            }
            )
        )
        ) {
            return false;
        }

        // FILTER-OUT
        $resultFilterOut = $executor->execute(array("/ip/firewall/filter/print", sprintf("?chain=%s", RouterOSCommander::FILTER_OUT), "=.proplist=.id,src-address,action"));
        $cmds[] = $resultFilterOut;

        $filterOutEntries = $resultFilterOut[1];
        $filterOutIPPart = array_slice($filterOutEntries, 0, count($filterOutEntries) - 2);

        if (array_column(array_slice($filterOutEntries, -2), "action") !== ["log", "reject"]) {
            return false;
        }

        if (count(
            array_filter(
                $filterOutIPPart, function ($e) {
                return $e["action"] !== "accept";
            }
            )
        )
        ) {
            return false;
        }

        if (array_column($filterInIPPart, "dst-address") !== array_column($filterOutIPPart, "src-address")) {
            return false;
        }

        return true;
    }

    public function getIPFilterDown($executor)
    {
        $this->synchronizeFilter($executor);

        $cmds = [];

        $resultFilterIn = $executor->execute(array("/ip/firewall/filter/print", sprintf("?chain=%s", RouterOSCommander::FILTER_IN), "?action=reject", "?disabled=no"));
        $cmds[] = $resultFilterIn;

        if (isset($resultFilterIn[1]) && count($resultFilterIn[1]) == 1) {
            $cmds[] = $executor->execute(array("/ip/firewall/filter/set", sprintf("=numbers=%s", $resultFilterIn[1][0]['.id']), "=disabled=yes"));
        }

        $resultFilterOut = $executor->execute(array("/ip/firewall/filter/print", sprintf("?chain=%s", RouterOSCommander::FILTER_OUT), "?action=reject", "?disabled=no"));
        $cmds[] = $resultFilterOut;

        if (isset($resultFilterOut[1]) && count($resultFilterOut[1]) == 1) {
            $cmds[] = $executor->execute(array("/ip/firewall/filter/set", sprintf("=numbers=%s", $resultFilterOut[1][0]['.id']), "=disabled=yes"));
        }

        return self::parseArrayReadable($cmds);
    }

    public function getIPFilterUp($executor)
    {
        $this->synchronizeFilter($executor);

        $cmds = [];

        $resultFilterIn = $executor->execute(array("/ip/firewall/filter/print", sprintf("?chain=%s", RouterOSCommander::FILTER_IN), "?action=reject", "?disabled=yes"));
        $cmds[] = $resultFilterIn;

        if (isset($resultFilterIn[1]) && count($resultFilterIn[1]) == 1) {
            $cmds[] = $executor->execute(array("/ip/firewall/filter/set", sprintf("=numbers=%s", $resultFilterIn[1][0]['.id']), "=disabled=no"));
        }

        $resultFilterOut = $executor->execute(array("/ip/firewall/filter/print", sprintf("?chain=%s", RouterOSCommander::FILTER_OUT), "?action=reject", "?disabled=yes"));
        $cmds[] = $resultFilterOut;

        if (isset($resultFilterOut[1]) && count($resultFilterOut[1]) == 1) {
            $cmds[] = $executor->execute(array("/ip/firewall/filter/set", sprintf("=numbers=%s", $resultFilterOut[1][0]['.id']), "=disabled=no"));
        }

        return self::parseArrayReadable($cmds);
    }

    static function parseCommandReadable($array)
    {
        $resultArray = [];

        $resultArray[0] = implode('', $array[0]);

        $return1 = [];
        if (is_array($array[1])) {
            foreach ($array[1] as $returnPart) {
                $string = '';
                foreach ($returnPart as $key => $value) {
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

    static function parseArrayReadable($array)
    {
        $resultArray = [];

        foreach ($array as $command) {
            $resultArray[] = self::parseCommandReadable($command);
        }

        return $resultArray;
    }
} // End of RouterOSCommander class
