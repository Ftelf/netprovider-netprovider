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
require_once($core->getAppRoot() . "/includes/tables/IpAccount.php");

/**
 *  IpAccountDAO
 */
class IpAccountDAO {
    static function getIpAccountCount() {
        global $database;
        $query = "SELECT count(*) FROM `ipaccount`";
        $database->setQuery($query);
        return $database->loadResult();
    }
    static function getIpAccountArray() {
        global $database;
        $query = "SELECT * FROM `ipaccount`";
        $database->setQuery($query);
        return $database->loadObjectList("IA_ipaccountid");
    }
    static function getIpAccountArrayByIpID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $query = "SELECT * FROM `ipaccount` WHERE `IA_ipid`='$id' ORDER BY `IA_datetime` ASC";
        $database->setQuery($query);
        return $database->loadObjectList("IA_ipaccountid");
    }
    static function getIpAccountMonthSumArrayByIpID($id, $maxThreshold) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $query = "SELECT IA_ipid, COUNT(*) as count, IA_datetime, YEAR(IA_datetime) as year, MONTH(IA_datetime) as month, SUM(IA_bytes_in) as IA_bytes_in, SUM(IA_packets_in) as IA_packets_in, SUM(IA_bytes_out) as IA_bytes_out, SUM(IA_packets_out) as IA_packets_out FROM `ipaccount` WHERE `IA_ipid`='$id' AND (12 * YEAR(IA_datetime) + MONTH(IA_datetime))<='$maxThreshold' GROUP BY YEAR(IA_datetime), MONTH(IA_datetime) ORDER BY `IA_datetime` ASC";
        $database->setQuery($query);
        return $database->loadObjectList();
    }
    static function getIpAccountDaySumArrayByIpID($id, $maxThreshold) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $query = "SELECT IA_ipid, COUNT(*) as count, IA_datetime, YEAR(IA_datetime) as year, MONTH(IA_datetime) as month, DAY(IA_datetime) as day, SUM(IA_bytes_in) as IA_bytes_in, SUM(IA_packets_in) as IA_packets_in, SUM(IA_bytes_out) as IA_bytes_out, SUM(IA_packets_out) as IA_packets_out FROM `ipaccount` WHERE `IA_ipid`='$id' AND (12 * YEAR(IA_datetime) + MONTH(IA_datetime))<='$maxThreshold' GROUP BY YEAR(IA_datetime), MONTH(IA_datetime), DAY(IA_datetime) ORDER BY `IA_datetime` ASC";
        $database->setQuery($query);
        return $database->loadObjectList();
    }
    static function getIpAccountByID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $ipAccount = new IpAccount();
        $query = "SELECT * FROM `ipaccount` WHERE `IA_ipaccountid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($ipAccount);
        return $ipAccount;
    }
    static function getIpAccountHourSumByIpID($id, $year, $month, $day, $hour) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $ipAccount = new IpAccount();
        $query = "SELECT SUM(`IA_bytes_in`) as IA_bytes_in,SUM(`IA_bytes_out`) as IA_bytes_out,SUM(`IA_packets_in`) as IA_packets_in,SUM(`IA_packets_out`) as IA_packets_out FROM `ipaccount` WHERE `IA_ipid` = '$id' AND YEAR(`IA_datetime`) = '$year' AND MONTH(`IA_datetime`) = '$month' AND DAY(`IA_datetime`) = '$day' AND HOUR(`IA_datetime`) = '$hour'";
        $database->setQuery($query);
        $database->loadObject($ipAccount);
        return $ipAccount;
    }
    static function getIpAccountDateSumByIpID($id, $year, $month, $day) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $ipAccount = new IpAccount();
        $query = "SELECT SUM(`IA_bytes_in`) as IA_bytes_in,SUM(`IA_bytes_out`) as IA_bytes_out,SUM(`IA_packets_in`) as IA_packets_in,SUM(`IA_packets_out`) as IA_packets_out FROM `ipaccount` WHERE `IA_ipid` = '$id' AND YEAR(`IA_datetime`) = '$year' AND MONTH(`IA_datetime`) = '$month' AND DAY(`IA_datetime`) = '$day'";
        $database->setQuery($query);
        $database->loadObject($ipAccount);
        return $ipAccount;
    }
    static function getIpAccountMonthSumByIpID($id, $year, $month) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $ipAccount = new IpAccount();
        $query = "SELECT SUM(`IA_bytes_in`) as IA_bytes_in,SUM(`IA_bytes_out`) as IA_bytes_out,SUM(`IA_packets_in`) as IA_packets_in,SUM(`IA_packets_out`) as IA_packets_out FROM `ipaccount` WHERE `IA_ipid` = '$id' AND YEAR(`IA_datetime`) = '$year' AND MONTH(`IA_datetime`) = '$month'";
        $database->setQuery($query);
        $database->loadObject($ipAccount);
        return $ipAccount;
    }
    static function getLastIpAccountByIpID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $ipAccount = new IpAccount();
        $query = "SELECT * FROM `ipaccount` WHERE `IA_ipid`='$id' ORDER BY `IA_datetime` DESC LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($ipAccount);
        return $ipAccount;
    }
    static function removeIpAccountByID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $query = "DELETE FROM `ipaccount` WHERE `IA_ipaccountid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }
    static function removeIpAccountByIPID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $query = "DELETE FROM `ipaccount` WHERE `IA_ipid`='$id'";
        $database->setQuery($query);
        $database->query();
    }
    static function removeIpAccountByYearMonth($id, $year, $month) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $query = "DELETE FROM `ipaccount` WHERE `IA_ipid`='$id' AND YEAR(IA_datetime)='$year' AND MONTH(IA_datetime)='$month'";
        $database->setQuery($query);
        $database->query();
    }
    static function removeIpAccountByYearMonthDay($id, $year, $month, $day) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $query = "DELETE FROM `ipaccount` WHERE `IA_ipid`='$id' AND YEAR(IA_datetime)='$year' AND MONTH(IA_datetime)='$month' AND DAY(IA_datetime)='$day'";
        $database->setQuery($query);
        $database->query();
    }
} // End of IpAccountDAO class
?>