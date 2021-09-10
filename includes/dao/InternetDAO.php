<?php
//
// +----------------------------------------------------------------------+
// | Ftelf ISP billing system                                               |
// +----------------------------------------------------------------------+
// | Copyright (c) 2006-2007 Ing. Lukas Dziadkowiec                       |
// +----------------------------------------------------------------------+
// | This source file is part of Ftelf ISP billing system,                  |
// | see LICENSE for licence details.                                     |
// +----------------------------------------------------------------------+
// | Authors: Lukas Dziadkowiec <i.ftelf@gmail.com>                  |
// +----------------------------------------------------------------------+

/**
 * @author  Lukas Dziadkowiec <i.ftelf@gmail.com>
 */

global $core;
require_once($core->getAppRoot() . "/includes/tables/Internet.php");

/**
 *  InternetDAO
 */
class InternetDAO {
    static function getInternetCount() {
        global $database;
        $query = "SELECT count(*) FROM `internet`";
        $database->setQuery($query);
        return $database->loadResult();
    }
    static function getInternetArray($limitstart=null, $limit=null) {
        global $database;
        $query = "SELECT * FROM `internet`";
        if ($limitstart !== null && $limit !== null) {
            $query .= " LIMIT $limitstart,$limit";
        }
        $database->setQuery($query);
        return $database->loadObjectList("IN_internetid");
    }
    static function getInternetChargesArrayByID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $query = "SELECT * FROM `internet` as i,`charge` as ch WHERE i.IN_internetid='$id' AND i.IN_internetid=ch.CH_internetid";
        $database->setQuery($query);
        return $database->loadObjectList("IN_internetid");
    }
    static function getInternetByID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $internet = new Internet();
        $query = "SELECT * FROM `internet` WHERE `IN_internetid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($internet);
        return $internet;
    }
    static function removeInternetByID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $query = "DELETE FROM `internet` WHERE `IN_internetid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }
} // End of InternetDAO class
?>
