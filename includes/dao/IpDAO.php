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
require_once($core->getAppRoot() . "/includes/tables/Ip.php");
require_once($core->getAppRoot() . "/includes/tables/Person.php");

/**
 *  IpDAO
 */
class IpDAO {
    const none = 0;
    const PE_firstname = 1;
    const PE_surname = 2;
    const PE_nick = 3;
    const IP_address = 4;
    const data = 5;

    static function getIpCount() {
        global $database;
        $query = "SELECT count(*) FROM `ip`";
        $database->setQuery($query);
        return $database->loadResult();
    }
    static function getIpArray() {
        global $database;
        $query = "SELECT * FROM `ip`";
        $database->setQuery($query);
        return $database->loadObjectList("IP_ipid");
    }
    static function getIpWithPersonArray($sort, $search, $limitstart=null, $limit=null) {
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
    static function getIpWithPersonArrayCount($search) {
        global $database;

        $query = "SELECT count(*) FROM `ip`, `person` WHERE IP_personid=PE_personid";

        if ($search != "") {
            $query .= " AND (`PE_firstname` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_surname` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_nick` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_username` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_email` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_tel` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_secondary_phone_number` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_icq` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_jabber` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_address` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_city` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_zip` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_ic` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_dic` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_shortcompanyname` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_companyname` COLLATE utf8_general_ci LIKE '%$search%')";
        }

        $database->setQuery($query);
        return $database->loadResult();
    }
    static function getFreeIpArray() {
        global $database;
        $query = "SELECT * FROM `ip` WHERE `IP_personid`=NULL";
        $database->setQuery($query);
        return $database->loadObjectList("IP_ipid");
    }
    static function getIpByID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $ip = new Ip();
        $query = "SELECT * FROM `ip` WHERE `IP_ipid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($ip);
        return $ip;
    }
    static function getIpByIP($ipq) {
        if (!$ipq) throw new Exception("no ID specified");
        global $database;
        $ip = new Ip();
        $query = "SELECT * FROM `ip` WHERE `IP_address`='$ipq' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($ip);
        return $ip;
    }
    static function getIpArrayByPersonID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $query = "SELECT * FROM `ip` WHERE `IP_personid`='$id'";
        $database->setQuery($query);
        return $database->loadObjectList("IP_ipid");
    }
    static function removeIpByID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $query = "DELETE FROM `ip` WHERE `IP_ipid`='$id' LIMIT 1";
        $database->setQuery($query);
        return $database->query();
    }
    static function isAnyIpInNetwork($id) {
        if (!$id) throw new Exception("no ID specified");
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
?>
