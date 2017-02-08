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
    static function getIpAccountHourSumByIpID($id, $dateFrom, $dateTo, $divider) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $sqlDateFrom = $dateFrom->getFormattedDate(DateUtil::DB_DATETIME);
        $sqlDateTo = $dateTo->getFormattedDate(DateUtil::DB_DATETIME);
        if ($divider) {
            $query = "SELECT CONCAT(DATE_FORMAT(IA_datetime,'%H')+0,'-',DATE_FORMAT(IA_datetime,'%H')+1) as date, SUM(`IA_bytes_in` + `IA_bytes_out`) as bytes_sum, SUM(`IA_bytes_in`/$divider) as IA_bytes_in, SUM(`IA_bytes_out`/$divider) as IA_bytes_out, SUM(`IA_packets_in`/$divider) as IA_packets_in, SUM(`IA_packets_out`/$divider) as IA_packets_out FROM `ipaccount` WHERE `IA_ipid` = '$id' AND `IA_datetime` >= '$sqlDateFrom' AND `IA_datetime` <= '$sqlDateTo' GROUP BY DATE_FORMAT(IA_datetime,'%Y-%m-%d-%H') ASC";
        } else {
            $query = "SELECT CONCAT(DATE_FORMAT(IA_datetime,'%H')+0,'-',DATE_FORMAT(IA_datetime,'%H')+1) as date, SUM(`IA_bytes_in` + `IA_bytes_out`) as bytes_sum, SUM(`IA_bytes_in`) as IA_bytes_in, SUM(`IA_bytes_out`) as IA_bytes_out, SUM(`IA_packets_in`) as IA_packets_in, SUM(`IA_packets_out`) as IA_packets_out FROM `ipaccount` WHERE `IA_ipid` = '$id' AND `IA_datetime` >= '$sqlDateFrom' AND `IA_datetime` <= '$sqlDateTo' GROUP BY DATE_FORMAT(IA_datetime,'%Y-%m-%d-%H') ASC";
        }
        $database->setQuery($query);
        return $database->loadObjectList('date');
    }
    static function getIpAccountDateSumByIpID($id, $dateFrom, $dateTo, $divider) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $sqlDateFrom = $dateFrom->getFormattedDate(DateUtil::DB_DATETIME);
        $sqlDateTo = $dateTo->getFormattedDate(DateUtil::DB_DATETIME);
        if ($divider) {
            $query = "SELECT DATE_FORMAT(IA_datetime,'%d.%m.%Y') as date, SUM(`IA_bytes_in` + `IA_bytes_out`) as bytes_sum, SUM(`IA_bytes_in`/$divider) as IA_bytes_in, SUM(`IA_bytes_out`/$divider) as IA_bytes_out, SUM(`IA_packets_in`/$divider) as IA_packets_in, SUM(`IA_packets_out`/$divider) as IA_packets_out FROM `ipaccount` WHERE `IA_ipid` = '$id' AND `IA_datetime` >= '$sqlDateFrom' AND `IA_datetime` <= '$sqlDateTo' GROUP BY DATE_FORMAT(IA_datetime,'%Y-%m-%d') ASC";
        } else {
            $query = "SELECT DATE_FORMAT(IA_datetime,'%d.%m.%Y') as date, SUM(`IA_bytes_in` + `IA_bytes_out`) as bytes_sum, SUM(`IA_bytes_in`) as IA_bytes_in, SUM(`IA_bytes_out`) as IA_bytes_out, SUM(`IA_packets_in`) as IA_packets_in, SUM(`IA_packets_out`) as IA_packets_out FROM `ipaccount` WHERE `IA_ipid` = '$id' AND `IA_datetime` >= '$sqlDateFrom' AND `IA_datetime` <= '$sqlDateTo' GROUP BY DATE_FORMAT(IA_datetime,'%Y-%m-%d') ASC";
        }
        $database->setQuery($query);
        return $database->loadObjectList('date');
    }
    static function getIpAccountMonthSumByIpID($id, $dateFrom, $dateTo, $divider = null) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $sqlDateFrom = $dateFrom->getFormattedDate(DateUtil::DB_DATE);
        $sqlDateTo = $dateTo->getFormattedDate(DateUtil::DB_DATE);
        if ($divider) {
            $query = "SELECT DATE_FORMAT(IA_datetime,'%m/%Y') as date, SUM(`IA_bytes_in` + `IA_bytes_out`) as bytes_sum, SUM(`IA_bytes_in`)/$divider as IA_bytes_in, SUM(`IA_bytes_out`)/$divider as IA_bytes_out, SUM(`IA_packets_in`)/$divider as IA_packets_in, SUM(`IA_packets_out`)/$divider as IA_packets_out FROM `ipaccount` WHERE `IA_ipid` = '$id' AND `IA_datetime` >= '$sqlDateFrom' AND `IA_datetime` <= '$sqlDateTo' GROUP BY DATE_FORMAT(IA_datetime,'%Y-%m') ASC";
        }else {
            $query = "SELECT DATE_FORMAT(IA_datetime,'%m/%Y') as date, SUM(`IA_bytes_in` + `IA_bytes_out`) as bytes_sum, SUM(`IA_bytes_in`) as IA_bytes_in, SUM(`IA_bytes_out`) as IA_bytes_out, SUM(`IA_packets_in`) as IA_packets_in, SUM(`IA_packets_out`) as IA_packets_out FROM `ipaccount` WHERE `IA_ipid` = '$id' AND `IA_datetime` >= '$sqlDateFrom' AND `IA_datetime` <= '$sqlDateTo' GROUP BY DATE_FORMAT(IA_datetime,'%Y-%m') ASC";
        }
        $database->setQuery($query);
        return $database->loadObjectList('date');
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