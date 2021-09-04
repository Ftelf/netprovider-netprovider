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
// | Authors: Lukas Dziadkowiec <i.ftelf@gmail.com>                  |
// +----------------------------------------------------------------------+

/**
 * @author  Lukas Dziadkowiec <i.ftelf@gmail.com>
 */

global $core;
require_once($core->getAppRoot() . "/includes/tables/IpAccountAbs.php");

/**
 *  IpAccountDAO
 */
class IpAccountAbsDAO {
    static function getIpAccountAbsCount() {
        global $database;
        $query = "SELECT count(*) FROM `ipaccountabs`";
        $database->setQuery($query);
        return $database->loadResult();
    }
    static function getIpAccountAbsArray() {
        global $database;
        $query = "SELECT * FROM `ipaccountabs`";
        $database->setQuery($query);
        return $database->loadObjectList("IB_ipid");
    }
    static function getIpAccountAbsByID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $ipAccountAbs = new IpAccountAbs();
        $query = "SELECT * FROM `ipaccountabs` WHERE `IB_ipaccountabsid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($ipAccountAbs);
        return $ipAccountAbs;
    }
    static function getIpAccountAbsByIpID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $ipAccountAbs = new IpAccountAbs();
        $query = "SELECT * FROM `ipaccountabs` WHERE `IB_ipid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($ipAccountAbs);
        return $ipAccountAbs;
    }
    static function removeIpAccountAbsByID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $query = "DELETE FROM `ipaccountabs` WHERE `IB_ipaccountabsid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }
    static function removeIpAccountAbsByIPID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $query = "DELETE FROM `ipaccountabs` WHERE `IB_ipid`='$id'";
        $database->setQuery($query);
        $database->query();
    }
} // End of IpAccountAbsDAO class
?>
