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
require_once $core->getAppRoot() . "/includes/tables/HasCharge.php";
require_once $core->getAppRoot() . "/includes/tables/Charge.php";
require_once $core->getAppRoot() . "/includes/tables/Internet.php";

/**
 *  HasChargeDAO
 */
class HasChargeDAO
{
    public static function getHasChargeCount()
    {
        global $database;
        $query = "SELECT count(*) FROM `hascharge`";
        $database->setQuery($query);
        return $database->loadResult();
    }

    public static function getHasChargeArray($limitstart = null, $limit = null): array
    {
        global $database;
        $query = "SELECT * FROM `hascharge`";
        if ($limitstart !== null && $limit !== null) {
            $query = " LIMIT $limitstart,$limit";
        }
        $database->setQuery($query);
        return $database->loadObjectList("HC_haschargeid");
    }

    public static function getHasChargeByID($id): HasCharge
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $hasCharge = new HasCharge();
        $query = "SELECT * FROM `hascharge` WHERE `HC_haschargeid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($hasCharge);
        return $hasCharge;
    }

    public static function getHasChargeArrayByPersonID($id): array
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "SELECT HC_haschargeid, HC_chargeid, HC_personid, HC_datestart, HC_dateend, HC_status, HC_actualstate FROM `charge`, `hascharge` WHERE `HC_personid`='$id' AND `CH_chargeid` = `HC_chargeid` ORDER BY CH_priority DESC";
        $database->setQuery($query);
        return $database->loadObjectList("HC_haschargeid");
    }

    public static function getHasChargeWithChargeWithPersonArrayByPersonID($pid): array
    {
        if (!$pid) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "SELECT * FROM `person` as p, `hascharge` as hc, `charge` as ch WHERE p.PE_personid='$pid' AND p.PE_personid=hc.HC_personid AND hc.HC_chargeid=ch.CH_chargeid";
        $database->setQuery($query);
        return $database->loadObjectList("HC_haschargeid");
    }

    public static function getHasChargeReportArray($pid, $chargesids, $status = -1, $actualState = -1, $dateFrom = null, $dateTo = null): array
    {
        if (!$pid || !is_array($chargesids)) {
            throw new Exception("not specified both IDs");
        }
        global $database;
        $query =
            "SELECT * FROM `person` as p, `hascharge` as hc, `charge` as ch " .
            "WHERE p.PE_personid='$pid' AND p.PE_personid=hc.HC_personid AND hc.HC_chargeid=ch.CH_chargeid";

        if (count($chargesids)) {
            $query .= " AND ch.CH_chargeid IN (" . (implode(',', $chargesids)) . ")";
        } else {
            $query .= " AND false";
        }

        if ($status != -1) {
            $query .= " AND hc.HC_status = $status";
        }

        if ($actualState != -1) {
            $query .= " AND hc.HC_actualstate = $actualState";
        }

        if ($dateFrom !== null) {
            $dateFromFormatted = $dateFrom->getFormattedDate(DateUtil::DB_DATE);
            $dateToFormatted = ($dateTo === null) ? DateUtil::DB_MAX_DATE : $dateTo->getFormattedDate(DateUtil::DB_DATE);

            $query .= " AND ( ( hc.HC_datestart <= date '$dateToFormatted' ) AND ( IF(hc.HC_dateend = '0000-00-00', date '9999-12-31', hc.HC_dateend) >= date '$dateFromFormatted' ) )";
        }

        $query .= " ORDER BY p.PE_surname, p.PE_firstname, p.PE_nick";

        $database->setQuery($query);
        return $database->loadObjectList("HC_haschargeid");
    }

    public static function getHasChargeWithInternetChargeOnlyByPersonID($pid): array
    {
        if (!$pid) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "SELECT * FROM `hascharge` as hc, `charge` as ch, `internet` as i WHERE hc.HC_personid='$pid' AND hc.HC_chargeid=ch.CH_chargeid AND ch.CH_type=" . Charge::TYPE_INTERNET_PAYMENT . " AND hc.HC_actualstate=" . HasCharge::ACTUALSTATE_ENABLED . " AND ch.CH_internetid=i.IN_internetid";
        $database->setQuery($query);
        return $database->loadObjectList("HC_haschargeid");
    }

    public static function removeHasChargeByID($id): void
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "DELETE FROM `hascharge` WHERE `HC_haschargeid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }
} // End of HasChargeDAO class
