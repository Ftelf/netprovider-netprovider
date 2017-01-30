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
require_once($core->getAppRoot() . "/includes/tables/HasCharge.php");
require_once($core->getAppRoot() . "/includes/tables/Charge.php");
require_once($core->getAppRoot() . "/includes/tables/Internet.php");

/**
 *  HasChargeDAO
 */
class HasChargeDAO {
    static function getHasChargeCount() {
        global $database;
        $query = "SELECT count(*) FROM `hascharge`";
        $database->setQuery($query);
        return $database->loadResult();
    }
    static function getHasChargeArray($limitstart=null, $limit=null) {
        global $database;
        $query = "SELECT * FROM `hascharge`";
        if ($limitstart !== null && $limit !== null) {
            $query = " LIMIT $limitstart,$limit";
        }
        $database->setQuery($query);
        return $database->loadObjectList("HC_haschargeid");
    }
    static function getHasChargeByID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $hasCharge = new HasCharge();
        $query = "SELECT * FROM `hascharge` WHERE `HC_haschargeid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($hasCharge);
        return $hasCharge;
    }
    static function getHasChargeArrayByPersonID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $query = "SELECT HC_haschargeid, HC_chargeid, HC_personid, HC_datestart, HC_dateend, HC_status, HC_actualstate FROM `charge`, `hascharge` WHERE `HC_personid`='$id' AND `CH_chargeid` = `HC_chargeid` ORDER BY CH_priority DESC";
        $database->setQuery($query);
        return $database->loadObjectList("HC_haschargeid");
    }
    static function getHasChargeWithChargeWithPersonArrayByPersonID($pid) {
        if (!$pid) throw new Exception("no ID specified");
        global $database;
        $query = "SELECT * FROM `person` as p, `hascharge` as hc, `charge` as ch WHERE p.PE_personid='$pid' AND p.PE_personid=hc.HC_personid AND hc.HC_chargeid=ch.CH_chargeid";
        $database->setQuery($query);
        return $database->loadObjectList("HC_haschargeid");
    }
    static function getHasChargeReportArray($pid, $chid) {
        if (!$pid || !$chid) throw new Exception("not specified both IDs");
        global $database;
        $query = "SELECT * FROM `person` as p, `hascharge` as hc, `charge` as ch WHERE p.PE_personid='$pid' AND p.PE_personid=hc.HC_personid AND hc.HC_chargeid=ch.CH_chargeid AND ch.CH_chargeid='$chid' ORDER BY `PE_surname`, `PE_firstname`";
        $database->setQuery($query);
        return $database->loadObjectList("HC_haschargeid");
    }
    static function getHasChargeWithInternetChargeOnlyByPersonID($pid) {
        if (!$pid) throw new Exception("no ID specified");
        global $database;
        $query = "SELECT * FROM `hascharge` as hc, `charge` as ch, `internet` as i WHERE hc.HC_personid='$pid' AND hc.HC_chargeid=ch.CH_chargeid AND ch.CH_type=".Charge::TYPE_INTERNET_PAYMENT." AND hc.HC_actualstate=".HasCharge::ACTUALSTATE_ENABLED." AND ch.CH_internetid=i.IN_internetid";
        $database->setQuery($query);
        return $database->loadObjectList("HC_haschargeid");
    }
    static function removeHasChargeByID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $query = "DELETE FROM `hascharge` WHERE `HC_haschargeid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }
} // End of HasChargeDAO class
?>