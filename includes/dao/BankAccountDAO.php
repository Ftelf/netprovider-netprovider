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
require_once $core->getAppRoot() . "/includes/tables/BankAccount.php";

/**
 *  BankAccountDAO
 */
class BankAccountDAO
{
    public static function getBankAccountCount()
    {
        global $database;
        $query = "SELECT count(*) FROM `bankaccount`";
        $database->setQuery($query);
        return $database->loadResult();
    }

    public static function getBankAccountArray($limitstart = null, $limit = null): array
    {
        global $database;
        $query = "SELECT * FROM `bankaccount`";
        if ($limitstart !== null && $limit !== null) {
            $query .= " LIMIT $limitstart,$limit";
        }
        $database->setQuery($query);
        return $database->loadObjectList("BA_bankaccountid");
    }

    public static function getBankAccountByID($id): BankAccount
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $bankAccount = new BankAccount();
        $query = "SELECT * FROM `bankaccount` WHERE `BA_bankaccountid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($bankAccount);
        return $bankAccount;
    }

    public static function removeBankAccountByID($id): void
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "DELETE FROM `bankaccount` WHERE `BA_bankaccountid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }
} // End of BankAccountDAO class
