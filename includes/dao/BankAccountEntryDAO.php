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
require_once($core->getAppRoot() . "/includes/tables/BankAccountEntry.php");

/**
 *  ConfigurationDAO
 */
class BankAccountEntryDAO {
    static function getBankAccountEntryCountByBankAccountID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $query = "SELECT count(*) FROM `bankaccountentry` WHERE `BE_bankaccountid`='$id'";
        $database->setQuery($query);
        return $database->loadResult();
    }
    static function getBankAccountEntryArray() {
        global $database;
        $query = "SELECT * FROM `bankaccountentry` ORDER BY `BE_datetime` ASC";
        $database->setQuery($query);
        return $database->loadObjectList("BE_bankaccountentryid");
    }
    static function getBankAccountEntryArrayByBankAccountID($bankaccountid, $limitstart=null, $limit=null) {
        global $database;
        $query = "SELECT * FROM `bankaccountentry` WHERE `BE_bankaccountid`='$bankaccountid'";
        if ($limitstart != null && $limit != null) {
            $query .= " LIMIT $limitstart,$limit";
        }
        $query .= " ORDER BY `BE_datetime` ASC";
        $database->setQuery($query);
        return $database->loadObjectList("BE_bankaccountentryid");
    }
    static function getBankAccountEntryByID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $bankAccountEntry = new BankAccountEntry();
        $query = "SELECT * FROM `bankaccountentry` WHERE `BE_bankaccountentryid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($bankAccountEntry);
        return $bankAccountEntry;
    }
    static function removeBankAccountEntryByID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $query = "DELETE FROM `bankaccountentry` WHERE `BE_bankaccountentryid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }
} // End of BankAccountEntryDAO class
?>
