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
require_once $core->getAppRoot() . "/includes/tables/PersonAccount.php";

/**
 *  PersonAccountDAO
 */
class PersonAccountDAO
{
    public static function getPersonAccountCount()
    {
        global $database;
        $query = "SELECT count(*) FROM `personaccount`";
        $database->setQuery($query);
        return $database->loadResult();
    }

    public static function getPersonAccountArray($limitstart = null, $limit = null): array
    {
        global $database;
        $query = "SELECT * FROM `personaccount`";
        if ($limitstart !== null && $limit !== null) {
            $query .= " LIMIT $limitstart,$limit";
        }
        $database->setQuery($query);
        return $database->loadObjectList("PA_personaccountid");
    }

    public static function getPersonAccountByID($id): PersonAccount
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $personAccount = new PersonAccount();
        $query = "SELECT * FROM `personaccount` WHERE `PA_personaccountid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($personAccount);
        return $personAccount;
    }

    public static function removePersonAccountByID($id): void
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "DELETE FROM `personaccount` WHERE `PA_personaccountid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }
} // End of PersonAccountDAO class
