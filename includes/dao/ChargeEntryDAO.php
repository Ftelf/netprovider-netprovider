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
require_once($core->getAppRoot() . "/includes/tables/ChargeEntry.php");

/**
 *  ChargeEntryDAO
 */
class ChargeEntryDAO {
    static function getChargeEntryCount() {
        global $database;
        $query = "SELECT count(*) FROM `chargeentry`";
        $database->setQuery($query);
        return $database->loadResult();
    }
    static function getChargeEntryArray($limitstart=null, $limit=null) {
        global $database;
        $query = "SELECT * FROM `chargeentry`";
        if ($limitstart !== null && $limit !== null) {
            $query .= " LIMIT $limitstart,$limit";
        }
        $query .= " ORDER BY `CE_period_date` ASC";
        $database->setQuery($query);
        return $database->loadObjectList("CE_chargeentryid");
    }
    static function getChargeEntryArrayByHasChargeID($id, $dateFrom = null, $dateTo = null, $key = "CE_chargeentryid") {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $query = "SELECT * FROM `chargeentry` WHERE `CE_haschargeid`='$id'";

        if ($dateFrom != null) {
            $dateFromFormatted = $dateFrom->getFormattedDate(DateUtil::DB_DATE);

            $query .= " AND date '$dateFromFormatted' <= CE_period_date";
        }

        if ($dateTo != null) {
            $dateTo = $dateTo->getFormattedDate(DateUtil::DB_DATE);

            $query .= " AND CE_period_date <= date '$dateTo'";
        }

        $query .= " ORDER BY `CE_period_date` ASC";

        $database->setQuery($query);
        return $database->loadObjectList($key);
    }
    static function getChargeEntryByID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $chargeEntry = new ChargeEntry();
        $query = "SELECT * FROM `chargeentry` WHERE `CE_chargeentryid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($chargeEntry);
        return $chargeEntry;
    }
    static function removeChargeEntryByID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $query = "DELETE FROM `chargeentry` WHERE `CE_chargeentryid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }
} // End of ChargeEntryDAO class
?>
