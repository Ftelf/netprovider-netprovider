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
require_once $core->getAppRoot() . "/includes/tables/PersonAccountEntry.php";

/**
 *  PersonAccountEntryDAO
 */
class PersonAccountEntryDAO
{
    public static function getPersonAccountEntryArrayByPersonAccountID($id)
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "SELECT * FROM `personaccountentry` WHERE `PN_personaccountid`='$id' ORDER BY `PN_date` ASC";
        $database->setQuery($query);
        return $database->loadObjectList("PN_personaccountentryid");
    }

    public static function getPersonAccountEntryArrayByBankAccountEntryID($id): array
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "SELECT * FROM `personaccountentry` WHERE `PN_bankaccountentryid`='$id' ORDER BY `PN_date` ASC";
        $database->setQuery($query);
        return $database->loadObjectList("PN_personaccountentryid");
    }

    public static function getPersonNameArrayByBankAccountEntryID($id): array
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "SELECT * FROM `person`, `personaccount`, `personaccountentry` WHERE `PE_personaccountid` = `PA_personaccountid` AND `PA_personaccountid` = `PN_personaccountid` AND `PN_bankaccountentryid`='$id' ORDER BY `PE_surname` ASC";
        $database->setQuery($query);
        return $database->loadObjectList("PE_personid");
    }

    public static function getPersonAccountEntryByID($id): PersonAccountEntry
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $personAccountEntry = new PersonAccountEntry();
        $query = "SELECT * FROM `personaccountentry` WHERE `PN_personaccountentryid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($personAccountEntry);
        return $personAccountEntry;
    }

    public static function removePersonAccountEntryByID($id): void
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "DELETE FROM `personaccountentry` WHERE `PN_personaccountentryid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }
} // End of PersonAccountEntryDAO class
