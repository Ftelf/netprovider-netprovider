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
require_once $core->getAppRoot() . "/includes/tables/Charge.php";
require_once "BankAccountDAO.php";

/**
 *  ChargeDAO
 */
class ChargeDAO
{
    public static function getChargeCount()
    {
        global $database;
        $query = "SELECT count(*) FROM `charge`";
        $database->setQuery($query);
        return $database->loadResult();
    }

    public static function getChargeArray($limitstart = null, $limit = null): array
    {
        global $database;
        $query = "SELECT * FROM `charge`";
        if ($limitstart !== null && $limit !== null) {
            $query .= " LIMIT $limitstart,$limit";
        }
        $database->setQuery($query);
        return $database->loadObjectList("CH_chargeid");
    }

    public static function getChargeArrayByPeriod($period): array
    {
        global $database;
        $query = "SELECT * FROM `charge` WHERE `CH_period`=$period";
        $database->setQuery($query);
        return $database->loadObjectList("CH_chargeid");
    }

    public static function getUsedChargeArray($id): array
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "SELECT * FROM `person` as p,`hascharge` as hc,`charge` as ch WHERE ch.CH_chargeid=$id AND ch.CH_chargeid=hc.HC_chargeid AND p.PE_personid=hc.HC_personid";
        $database->setQuery($query);
        return $database->loadObjectList("PE_personid");
    }

    public static function getChargeByID($id): Charge
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $charge = new Charge();
        $query = "SELECT * FROM `charge` WHERE `CH_chargeid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($charge);
        return $charge;
    }

    public static function removeChargeByID($id): void
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "DELETE FROM `charge` WHERE `CH_chargeid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }
} // End of ChargeDAO class
