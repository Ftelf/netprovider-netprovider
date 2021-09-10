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
require_once($core->getAppRoot() . "/includes/tables/PersonAccountEntry.php");

/**
 *  PersonAccountEntryDAO
 */
class PersonAccountEntryDAO {
    static function getPersonAccountEntryArrayByPersonAccountID($id) {
        if ($id == null) throw new Exception("no ID specified");
        global $database;
        $query = "SELECT * FROM `personaccountentry` WHERE `PN_personaccountid`='$id' ORDER BY `PN_date` ASC";
        $database->setQuery($query);
        return $database->loadObjectList("PN_personaccountentryid");
    }
    static function getPersonAccountEntryArrayByBankAccountEntryID($id) {
        if ($id == null) throw new Exception("no ID specified");
        global $database;
        $query = "SELECT * FROM `personaccountentry` WHERE `PN_bankaccountentryid`='$id' ORDER BY `PN_date` ASC";
        $database->setQuery($query);
        return $database->loadObjectList("PN_personaccountentryid");
    }
    static function getPersonNameArrayByBankAccountEntryID($id) {
        if ($id == null) throw new Exception("no ID specified");
        global $database;
        $query = "SELECT * FROM `person`, `personaccount`, `personaccountentry` WHERE `PE_personaccountid` = `PA_personaccountid` AND `PA_personaccountid` = `PN_personaccountid` AND `PN_bankaccountentryid`='$id' ORDER BY `PE_surname` ASC";
        $database->setQuery($query);
        return $database->loadObjectList("PE_personid");
    }
    static function getPersonAccountEntryByID($id) {
        if ($id == null) throw new Exception("no ID specified");
        global $database;
        $personAccountEntry = new PersonAccountEntry();
        $query = "SELECT * FROM `personaccountentry` WHERE `PN_personaccountentryid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($personAccountEntry);
        return $personAccountEntry;
    }
    static function removePersonAccountEntryByID($id) {
        if ($id == null) throw new Exception("no ID specified");
        global $database;
        $query = "DELETE FROM `personaccountentry` WHERE `PN_personaccountentryid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }
} // End of PersonAccountEntryDAO class
?>
