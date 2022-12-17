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
require_once $core->getAppRoot() . "/includes/tables/IpAccountAbs.php";

/**
 *  IpAccountDAO
 */
class IpAccountAbsDAO
{
    public static function getIpAccountAbsCount()
    {
        global $database;
        $query = "SELECT count(*) FROM `ipaccountabs`";
        $database->setQuery($query);
        return $database->loadResult();
    }

    public static function getIpAccountAbsArray(): array
    {
        global $database;
        $query = "SELECT * FROM `ipaccountabs`";
        $database->setQuery($query);
        return $database->loadObjectList("IB_ipid");
    }

    public static function getIpAccountAbsByID($id): IpAccountAbs
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $ipAccountAbs = new IpAccountAbs();
        $query = "SELECT * FROM `ipaccountabs` WHERE `IB_ipaccountabsid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($ipAccountAbs);
        return $ipAccountAbs;
    }

    public static function getIpAccountAbsByIpID($id): IpAccountAbs
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $ipAccountAbs = new IpAccountAbs();
        $query = "SELECT * FROM `ipaccountabs` WHERE `IB_ipid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($ipAccountAbs);
        return $ipAccountAbs;
    }

    public static function removeIpAccountAbsByID($id): void
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "DELETE FROM `ipaccountabs` WHERE `IB_ipaccountabsid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }

    public static function removeIpAccountAbsByIPID($id): void
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "DELETE FROM `ipaccountabs` WHERE `IB_ipid`='$id'";
        $database->setQuery($query);
        $database->query();
    }
} // End of IpAccountAbsDAO class
