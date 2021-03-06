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
require_once($core->getAppRoot() . "/includes/tables/PersonAccount.php");

/**
 *  PersonAccountDAO
 */
class PersonAccountDAO {
    static function getPersonAccountCount() {
        global $database;
        $query = "SELECT count(*) FROM `personaccount`";
        $database->setQuery($query);
        return $database->loadResult();
    }
    static function getPersonAccountArray($limitstart=null, $limit=null) {
        global $database;
        $query = "SELECT * FROM `personaccount`";
        if ($limitstart !== null && $limit !== null) {
            $query .= " LIMIT $limitstart,$limit";
        }
        $database->setQuery($query);
        return $database->loadObjectList("PA_personaccountid");
    }
    static function getPersonAccountByID($id) {
        if ($id == null) throw new Exception("no ID specified");
        global $database;
        $personAccount = new PersonAccount();
        $query = "SELECT * FROM `personaccount` WHERE `PA_personaccountid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($personAccount);
        return $personAccount;
    }
    static function removePersonAccountByID($id) {
        if ($id == null) throw new Exception("no ID specified");
        global $database;
        $query = "DELETE FROM `personaccount` WHERE `PA_personaccountid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }
} // End of PersonAccountDAO class
?>