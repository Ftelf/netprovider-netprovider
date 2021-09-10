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
require_once($core->getAppRoot() . "/includes/tables/EmailList.php");

/**
 *  EmailListDAO
 */
class EmailListDAO {
    static function getEmailListCount() {
        global $database;
        $query = "SELECT count(*) FROM `emaillist`";
        $database->setQuery($query);
        return $database->loadResult();
    }
    static function getEmailListArray($limitstart=null, $limit=null) {
        global $database;
        $query = "SELECT * FROM `emaillist`";
        if ($limitstart !== null && $limit !== null) {
             $query .= " LIMIT $limitstart,$limit";
        }
        $database->setQuery($query);
        return $database->loadObjectList("EL_emaillistid");
    }
    static function getEmailListYears() {
        global $database;
        $query = "SELECT DISTINCT EL_year FROM `emaillist` ORDER BY `EL_year` ASC";
        $database->setQuery($query);
        return $database->loadObjectList();
    }
    static function getEmailListNamesByYear($year) {
        global $database;
        $query = "SELECT EL_emaillistid, EL_name, EL_no, EL_datefrom, EL_dateto FROM `emaillist` WHERE `EL_year`='$year' ORDER BY `EL_no` ASC";
        $database->setQuery($query);
        return $database->loadObjectList("EL_emaillistid");
    }
    static function getEmailListArrayByBankAccountID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $query = "SELECT * FROM `emaillist` WHERE `EL_bankaccountid`='$id' ORDER BY `EL_year` ASC, `EL_no` ASC";
        $database->setQuery($query);
        return $database->loadObjectList("EL_emaillistid");
    }
    static function getEmailListByID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $emailList = new EmailList();
        $query = "SELECT * FROM `emaillist` WHERE `EL_emaillistid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($emailList);
        return $emailList;
    }
    static function removeEmailListByID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $query = "DELETE FROM `emaillist` WHERE `EL_emaillistid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }
} // End of EmailListDAO class
?>
