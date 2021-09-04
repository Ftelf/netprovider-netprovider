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
require_once($core->getAppRoot() . "/includes/tables/Log.php");
require_once($core->getAppRoot() . "/includes/utils/DateUtil.php");

/**
 *  LogDAO
 */
class LogDAO {
    static function getLogCount($logLevel=0, $personid=null, $dateFrom="0000-00-00 00:00:00", $dateTo="0000-00-00 00:00:00") {
        global $database;

        $query = "SELECT count(*) FROM `log` WHERE 1";

        if ($logLevel!=0) {
            $query .= " AND `LO_level`='$logLevel'";
        }
        if ($personid != "" && $personid != "0") {
            $query .= " AND `LO_personid`='$personid'";
        }
        if ($dateFrom != DateUtil::DB_NULL_DATETIME) {
            $query .= " AND `LO_datetime`>='$dateFrom'";
        }
        if ($dateTo != DateUtil::DB_NULL_DATETIME) {
            $query .= " AND `LO_datetime`<'$dateTo'";
        }

        $database->setQuery($query);
        return $database->loadResult();
    }
    static function getLogArray($logLevel=0, $personid=null, $dateFrom="0000-00-00 00:00:00", $dateTo="0000-00-00 00:00:00", $limitstart=null, $limit=null) {
        global $database;

        $query = "SELECT * FROM `log` WHERE 1";

        if ($logLevel!=0) {
            $query .= " AND `LO_level`='$logLevel'";
        }
        if ($personid != "" && $personid != "0") {
            $query .= " AND `LO_personid`='$personid'";
        }
        if ($dateFrom != DateUtil::DB_NULL_DATETIME) {
            $query .= " AND `LO_datetime`>='$dateFrom'";
        }
        if ($dateTo != DateUtil::DB_NULL_DATETIME) {
            $query .= " AND `LO_datetime`<'$dateTo'";
        }
        $query .= " ORDER BY `LO_datetime` ASC";
        if ($limitstart !== null && $limit !== null) {
            $query .= " LIMIT $limitstart, $limit";
        }

        $database->setQuery($query);
        return $database->loadObjectList('LO_logid');
    }
    static function getPersonArrayWhenInLog() {
        global $database;

        $query = "SELECT PE_personid, PE_firstname, PE_surname FROM `log`, `person` WHERE LO_personid=PE_personid GROUP BY PE_personid";

        $database->setQuery($query);
        return $database->loadObjectList('PE_personid');
    }
    static function getLastLogArray($count) {
        if (!$count) throw new Exception("count not specified");
        global $database;
        $query = "SELECT * FROM `log` ORDER BY `LO_datetime` DESC LIMIT $count";
        $database->setQuery($query);
        return $database->loadObjectList('LO_logid');
    }
    static function getLogByID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $log = new Log();
        $query = "SELECT * FROM `log` WHERE `LO_logid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($log);
        return $log;
    }
    static function removeLogByID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $query = "DELETE FROM `log` WHERE `LO_logid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }
    static function removeLogByPersonID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $query = "DELETE FROM `log` WHERE `LO_personid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }
} // End of LogDAO class
?>
