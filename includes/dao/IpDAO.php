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
require_once $core->getAppRoot() . "/includes/tables/Ip.php";
require_once $core->getAppRoot() . "/includes/tables/Person.php";

/**
 *  IpDAO
 */
class IpDAO
{
    public const none = 0;
    public const PE_firstname = 1;
    public const PE_surname = 2;
    public const PE_nick = 3;
    public const IP_address = 4;
    public const data = 5;

    public static function getIpCount()
    {
        global $database;
        $query = "SELECT count(*) FROM `ip`";
        $database->setQuery($query);
        return $database->loadResult();
    }

    public static function getIpArray(): array
    {
        global $database;
        $query = "SELECT * FROM `ip`";
        $database->setQuery($query);
        return $database->loadObjectList("IP_ipid");
    }

    public static function getIpWithPersonArray($sort, $search, $limitstart = null, $limit = null): array
    {
        global $database;

        $query = "SELECT * FROM `ip`, `person` WHERE IP_personid=PE_personid";

        if ($search != "") {
            $query .= " AND (`PE_firstname` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_surname` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_nick` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_username` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_email` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_tel` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_secondary_phone_number` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_icq` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_jabber` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_address` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_city` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_zip` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_ic` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_dic` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_shortcompanyname` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_companyname` COLLATE utf8_general_ci LIKE '%$search%')";
        }

        if ($sort == self::PE_firstname) {
            $query .= " ORDER BY PE_firstname,PE_surname,PE_nick ASC";
        } else if ($sort == self::PE_surname) {
            $query .= " ORDER BY PE_surname,PE_firstname,PE_nick ASC";
        } else if ($sort == self::PE_nick) {
            $query .= " ORDER BY PE_nick,PE_surname,PE_firstname ASC";
        } else if ($sort == self::IP_address) {
            $query .= " ORDER BY INET_ATON(IP_address) ASC";
        }

        if ($limitstart !== null && $limit !== null) {
            $query .= " LIMIT $limitstart, $limit";
        }

        $database->setQuery($query);
        return $database->loadObjectList("IP_ipid");
    }

    public static function getIpWithPersonArrayCount($search)
    {
        global $database;

        $query = "SELECT count(*) FROM `ip`, `person` WHERE IP_personid=PE_personid";

        if ($search != "") {
            $query .= " AND (`PE_firstname` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_surname` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_nick` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_username` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_email` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_tel` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_secondary_phone_number` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_icq` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_jabber` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_address` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_city` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_zip` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_ic` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_dic` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_shortcompanyname` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_companyname` COLLATE utf8_general_ci LIKE '%$search%')";
        }

        $database->setQuery($query);
        return $database->loadResult();
    }

    public static function getFreeIpArray(): array
    {
        global $database;
        $query = "SELECT * FROM `ip` WHERE `IP_personid`=NULL";
        $database->setQuery($query);
        return $database->loadObjectList("IP_ipid");
    }

    public static function getIpByID($id): Ip
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $ip = new Ip();
        $query = "SELECT * FROM `ip` WHERE `IP_ipid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($ip);
        return $ip;
    }

    public static function getIpByIP($ipq): Ip
    {
        if (!$ipq) {
            throw new Exception("no ID specified");
        }
        global $database;
        $ip = new Ip();
        $query = "SELECT * FROM `ip` WHERE `IP_address`='$ipq' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($ip);
        return $ip;
    }

    public static function getIpArrayByPersonID($id): array
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "SELECT * FROM `ip` WHERE `IP_personid`='$id'";
        $database->setQuery($query);
        return $database->loadObjectList("IP_ipid");
    }

    public static function removeIpByID($id): void
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "DELETE FROM `ip` WHERE `IP_ipid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }

    public static function isAnyIpInNetwork($id): bool
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $ip = new Ip();
        $query = "SELECT * FROM `ip` WHERE `IP_networkid`='$id' LIMIT 1";
        $database->setQuery($query);
        try {
            $database->loadObject($ip);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
} // End of IpDAO class
