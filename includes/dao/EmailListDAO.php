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
require_once $core->getAppRoot() . "/includes/tables/EmailList.php";

/**
 *  EmailListDAO
 */
class EmailListDAO
{
    public static function getEmailListCount()
    {
        global $database;
        $query = "SELECT count(*) FROM `emaillist`";
        $database->setQuery($query);
        return $database->loadResult();
    }

    public static function getEmailListArray($limitstart = null, $limit = null): array
    {
        global $database;
        $query = "SELECT * FROM `emaillist`";
        if ($limitstart !== null && $limit !== null) {
            $query .= " LIMIT $limitstart,$limit";
        }
        $database->setQuery($query);
        return $database->loadObjectList("EL_emaillistid");
    }

    public static function getEmailListYears(): array
    {
        global $database;
        $query = "SELECT DISTINCT EL_year FROM `emaillist` ORDER BY `EL_year` ASC";
        $database->setQuery($query);
        return $database->loadObjectList();
    }

    public static function getEmailListNamesByYear($year): array
    {
        global $database;
        $query = "SELECT EL_emaillistid, EL_name, EL_no, EL_datefrom, EL_dateto FROM `emaillist` WHERE `EL_year`='$year' ORDER BY `EL_no` ASC";
        $database->setQuery($query);
        return $database->loadObjectList("EL_emaillistid");
    }

    public static function getEmailListArrayByBankAccountID($id): array
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "SELECT * FROM `emaillist` WHERE `EL_bankaccountid`='$id' ORDER BY `EL_year` ASC, `EL_no` ASC";
        $database->setQuery($query);
        return $database->loadObjectList("EL_emaillistid");
    }

    public static function getEmailListByID($id): EmailList
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $emailList = new EmailList();
        $query = "SELECT * FROM `emaillist` WHERE `EL_emaillistid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($emailList);
        return $emailList;
    }

    public static function removeEmailListByID($id): void
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "DELETE FROM `emaillist` WHERE `EL_emaillistid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }
} // End of EmailListDAO class
