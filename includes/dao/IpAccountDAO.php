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
require_once $core->getAppRoot() . "/includes/tables/IpAccount.php";

/**
 *  IpAccountDAO
 */
class IpAccountDAO
{
    public static function getIpAccountCount()
    {
        global $database;
        $query = "SELECT count(*) FROM `ipaccount`";
        $database->setQuery($query);
        return $database->loadResult();
    }

    public static function getIpAccountArray(): array
    {
        global $database;
        $query = "SELECT * FROM `ipaccount`";
        $database->setQuery($query);
        return $database->loadObjectList("IA_ipaccountid");
    }

    public static function getIpAccountArrayByIpID($id): array
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "SELECT * FROM `ipaccount` WHERE `IA_ipid`='$id' ORDER BY `IA_datetime` ASC";
        $database->setQuery($query);
        return $database->loadObjectList("IA_ipaccountid");
    }

    public static function getIpAccountMonthSumArrayByIpID($id, $maxThreshold): array
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "SELECT IA_ipid, COUNT(*) as count, IA_datetime, YEAR(IA_datetime) as year, MONTH(IA_datetime) as month, SUM(IA_bytes_in) as IA_bytes_in, SUM(IA_packets_in) as IA_packets_in, SUM(IA_bytes_out) as IA_bytes_out, SUM(IA_packets_out) as IA_packets_out FROM `ipaccount` WHERE `IA_ipid`='$id' AND (12 * YEAR(IA_datetime) + MONTH(IA_datetime))<='$maxThreshold' GROUP BY YEAR(IA_datetime), MONTH(IA_datetime) ORDER BY `IA_datetime` ASC";
        $database->setQuery($query);
        return $database->loadObjectList();
    }

    public static function getIpAccountDaySumArrayByIpID($id, $maxThreshold): array
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "SELECT IA_ipid, COUNT(*) as count, IA_datetime, YEAR(IA_datetime) as year, MONTH(IA_datetime) as month, DAY(IA_datetime) as day, SUM(IA_bytes_in) as IA_bytes_in, SUM(IA_packets_in) as IA_packets_in, SUM(IA_bytes_out) as IA_bytes_out, SUM(IA_packets_out) as IA_packets_out FROM `ipaccount` WHERE `IA_ipid`='$id' AND (12 * YEAR(IA_datetime) + MONTH(IA_datetime))<='$maxThreshold' GROUP BY YEAR(IA_datetime), MONTH(IA_datetime), DAY(IA_datetime) ORDER BY `IA_datetime` ASC";
        $database->setQuery($query);
        return $database->loadObjectList();
    }

    public static function getIpAccountByID($id): IpAccount
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $ipAccount = new IpAccount();
        $query = "SELECT * FROM `ipaccount` WHERE `IA_ipaccountid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($ipAccount);
        return $ipAccount;
    }

    public static function getIpAccountHourSumByIpID($id, $dateFrom, $dateTo, $divider): array
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $sqlDateFrom = $dateFrom->getFormattedDate(DateUtil::DB_DATETIME);
        $sqlDateTo = $dateTo->getFormattedDate(DateUtil::DB_DATETIME);
        if ($divider) {
            $query = "SELECT CONCAT(DATE_FORMAT(IA_datetime,'%H')+0,'-',DATE_FORMAT(IA_datetime,'%H')+1) as date, SUM(`IA_bytes_in` + `IA_bytes_out`) as bytes_sum, SUM(`IA_bytes_in`/$divider) as IA_bytes_in, SUM(`IA_bytes_out`/$divider) as IA_bytes_out, SUM(`IA_packets_in`/$divider) as IA_packets_in, SUM(`IA_packets_out`/$divider) as IA_packets_out FROM `ipaccount` WHERE `IA_ipid` = '$id' AND `IA_datetime` >= '$sqlDateFrom' AND `IA_datetime` <= '$sqlDateTo' GROUP BY DATE_FORMAT(IA_datetime,'%Y-%m-%d-%H') ORDER BY date ASC";
        } else {
            $query = "SELECT CONCAT(DATE_FORMAT(IA_datetime,'%H')+0,'-',DATE_FORMAT(IA_datetime,'%H')+1) as date, SUM(`IA_bytes_in` + `IA_bytes_out`) as bytes_sum, SUM(`IA_bytes_in`) as IA_bytes_in, SUM(`IA_bytes_out`) as IA_bytes_out, SUM(`IA_packets_in`) as IA_packets_in, SUM(`IA_packets_out`) as IA_packets_out FROM `ipaccount` WHERE `IA_ipid` = '$id' AND `IA_datetime` >= '$sqlDateFrom' AND `IA_datetime` <= '$sqlDateTo' GROUP BY DATE_FORMAT(IA_datetime,'%Y-%m-%d-%H') ORDER BY date ASC";
        }
        $database->setQuery($query);
        return $database->loadObjectList('date');
    }

    public static function getIpAccountDateSumByIpID($id, $dateFrom, $dateTo, $divider): array
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $sqlDateFrom = $dateFrom->getFormattedDate(DateUtil::DB_DATETIME);
        $sqlDateTo = $dateTo->getFormattedDate(DateUtil::DB_DATETIME);
        if ($divider) {
            $query = "SELECT DATE_FORMAT(IA_datetime,'%d.%m.%Y') as date, SUM(`IA_bytes_in` + `IA_bytes_out`) as bytes_sum, SUM(`IA_bytes_in`/$divider) as IA_bytes_in, SUM(`IA_bytes_out`/$divider) as IA_bytes_out, SUM(`IA_packets_in`/$divider) as IA_packets_in, SUM(`IA_packets_out`/$divider) as IA_packets_out FROM `ipaccount` WHERE `IA_ipid` = '$id' AND `IA_datetime` >= '$sqlDateFrom' AND `IA_datetime` <= '$sqlDateTo' GROUP BY DATE_FORMAT(IA_datetime,'%Y-%m-%d') ORDER BY date ASC";
        } else {
            $query = "SELECT DATE_FORMAT(IA_datetime,'%d.%m.%Y') as date, SUM(`IA_bytes_in` + `IA_bytes_out`) as bytes_sum, SUM(`IA_bytes_in`) as IA_bytes_in, SUM(`IA_bytes_out`) as IA_bytes_out, SUM(`IA_packets_in`) as IA_packets_in, SUM(`IA_packets_out`) as IA_packets_out FROM `ipaccount` WHERE `IA_ipid` = '$id' AND `IA_datetime` >= '$sqlDateFrom' AND `IA_datetime` <= '$sqlDateTo' GROUP BY DATE_FORMAT(IA_datetime,'%Y-%m-%d') ORDER BY date ASC";
        }
        $database->setQuery($query);
        return $database->loadObjectList('date');
    }

    public static function getIpAccountMonthSumByIpID($id, $dateFrom, $dateTo, $divider = null): array
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $sqlDateFrom = $dateFrom->getFormattedDate(DateUtil::DB_DATE);
        $sqlDateTo = $dateTo->getFormattedDate(DateUtil::DB_DATE);
        if ($divider) {
            $query = "SELECT DATE_FORMAT(IA_datetime,'%m/%Y') as date, SUM(`IA_bytes_in` + `IA_bytes_out`) as bytes_sum, SUM(`IA_bytes_in`)/$divider as IA_bytes_in, SUM(`IA_bytes_out`)/$divider as IA_bytes_out, SUM(`IA_packets_in`)/$divider as IA_packets_in, SUM(`IA_packets_out`)/$divider as IA_packets_out FROM `ipaccount` WHERE `IA_ipid` = '$id' AND `IA_datetime` >= '$sqlDateFrom' AND `IA_datetime` <= '$sqlDateTo' GROUP BY DATE_FORMAT(IA_datetime,'%Y-%m') ORDER BY date ASC";
        } else {
            $query = "SELECT DATE_FORMAT(IA_datetime,'%m/%Y') as date, SUM(`IA_bytes_in` + `IA_bytes_out`) as bytes_sum, SUM(`IA_bytes_in`) as IA_bytes_in, SUM(`IA_bytes_out`) as IA_bytes_out, SUM(`IA_packets_in`) as IA_packets_in, SUM(`IA_packets_out`) as IA_packets_out FROM `ipaccount` WHERE `IA_ipid` = '$id' AND `IA_datetime` >= '$sqlDateFrom' AND `IA_datetime` <= '$sqlDateTo' GROUP BY DATE_FORMAT(IA_datetime,'%Y-%m') ORDER BY date ASC";
        }
        $database->setQuery($query);
        return $database->loadObjectList('date');
    }

    public static function getLastIpAccountByIpID($id): IpAccount
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $ipAccount = new IpAccount();
        $query = "SELECT * FROM `ipaccount` WHERE `IA_ipid`='$id' ORDER BY `IA_datetime` DESC LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($ipAccount);
        return $ipAccount;
    }

    public static function removeIpAccountByID($id): void
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "DELETE FROM `ipaccount` WHERE `IA_ipaccountid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }

    public static function removeIpAccountByIPID($id): void
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "DELETE FROM `ipaccount` WHERE `IA_ipid`='$id'";
        $database->setQuery($query);
        $database->query();
    }

    public static function removeIpAccountByYearMonth($id, $year, $month): void
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "DELETE FROM `ipaccount` WHERE `IA_ipid`='$id' AND YEAR(IA_datetime)='$year' AND MONTH(IA_datetime)='$month'";
        $database->setQuery($query);
        $database->query();
    }

    public static function removeIpAccountByYearMonthDay($id, $year, $month, $day): void
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "DELETE FROM `ipaccount` WHERE `IA_ipid`='$id' AND YEAR(IA_datetime)='$year' AND MONTH(IA_datetime)='$month' AND DAY(IA_datetime)='$day'";
        $database->setQuery($query);
        $database->query();
    }
} // End of IpAccountDAO class
