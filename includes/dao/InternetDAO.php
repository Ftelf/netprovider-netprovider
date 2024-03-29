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
require_once $core->getAppRoot() . "/includes/tables/Internet.php";

/**
 *  InternetDAO
 */
class InternetDAO
{
    public static function getInternetCount()
    {
        global $database;
        $query = "SELECT count(*) FROM `internet`";
        $database->setQuery($query);
        return $database->loadResult();
    }

    public static function getInternetArray($limitstart = null, $limit = null): array
    {
        global $database;
        $query = "SELECT * FROM `internet`";
        if ($limitstart !== null && $limit !== null) {
            $query .= " LIMIT $limitstart,$limit";
        }
        $database->setQuery($query);
        return $database->loadObjectList("IN_internetid");
    }

    public static function getInternetChargesArrayByID($id): array
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "SELECT * FROM `internet` as i,`charge` as ch WHERE i.IN_internetid='$id' AND i.IN_internetid=ch.CH_internetid";
        $database->setQuery($query);
        return $database->loadObjectList("CH_chargeid");
    }

    public static function getInternetByID($id): Internet
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $internet = new Internet();
        $query = "SELECT * FROM `internet` WHERE `IN_internetid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($internet);
        return $internet;
    }

    public static function removeInternetByID($id): void
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "DELETE FROM `internet` WHERE `IN_internetid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }
} // End of InternetDAO class
