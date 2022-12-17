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
require_once $core->getAppRoot() . "/includes/tables/Person.php";
require_once $core->getAppRoot() . "/includes/tables/PersonAccount.php";
require_once $core->getAppRoot() . "/includes/tables/Group.php";

/**
 *  PersonDAO
 */
class PersonDAO
{
    public static function getPersonCount($search = "", $group = 0, $status = -1)
    {
        global $database;

        $query = "SELECT count(*) FROM `person` WHERE 1";

        if ($search != "") {
            $query .= " AND (`PE_firstname` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_surname` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_nick` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_username` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_email` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_tel` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_secondary_phone_number` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_icq` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_jabber` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_address` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_city` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_zip` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_ic` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_dic` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_shortcompanyname` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_companyname` COLLATE utf8_general_ci LIKE '%$search%')";
        }
        if ($group != 0) {
            $query .= " AND `PE_groupid`='$group'";
        }
        if ($status != -1) {
            $query .= " AND `PE_status`='$status'";
        }

        $database->setQuery($query);
        return $database->loadResult();
    }

    public static function getPersonArray($search = "", $group = 0, $status = -1, $limitstart = null, $limit = null): array
    {
        global $database;

        $query = "SELECT * FROM `person` WHERE 1";

        if ($search != "") {
            $query .= " AND (`PE_firstname` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_surname` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_nick` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_username` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_email` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_tel` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_secondary_phone_number` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_icq` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_jabber` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_address` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_city` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_zip` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_ic` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_dic` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_shortcompanyname` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_companyname` COLLATE utf8_general_ci LIKE '%$search%')";
        }
        if ($group != 0) {
            $query .= " AND `PE_groupid`='$group'";
        }
        if ($status != -1) {
            $query .= " AND `PE_status`='$status'";
        }

        $query .= " ORDER BY `PE_surname`, `PE_firstname`";

        if ($limitstart !== null && $limit !== null) {
            $query .= " LIMIT $limitstart, $limit";
        }

        $database->setQuery($query);
        return $database->loadObjectList('PE_personid');
    }

    public static function getPersonArrayByGroupID($id): array
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "SELECT * FROM `person` WHERE `PE_groupid`='$id'";
        $database->setQuery($query);
        return $database->loadObjectList('PE_personid');
    }

    /**
     * @param  $id
     * @return Person
     * @throws Exception
     */
    public static function getPersonByID($id): Person
    {
        if ($id === null) {
            throw new Exception("no ID specified");
        }
        global $database;
        $person = new Person();
        $query = "SELECT * FROM `person` WHERE `PE_personid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($person);
        return $person;
    }

    public static function getPersonByPersonAccountID($id): Person
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $person = new Person();
        $query = "SELECT * FROM `person` WHERE `PE_personaccountid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($person);
        return $person;
    }

    public static function getPersonByIP($ip): Person
    {
        if ($ip == null) {
            throw new Exception("no IP specified");
        }
        global $database;
        $person = new Person();
        $query = "SELECT * FROM `person`, `ip` WHERE `PE_personid`=`IP_personid` AND `IP_address`='$ip' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($person);
        return $person;
    }

    public static function getPersonByIPId($ip): Person
    {
        if ($ip == null) {
            throw new Exception("no IP specified");
        }
        global $database;
        $person = new Person();
        $query = "SELECT * FROM `person`, `ip` WHERE `PE_personid`=`IP_personid` AND `IP_ipid`='$ip' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($person);
        return $person;
    }

    public static function removePersonByID($id): void
    {
        if (!$id) {
            throw new Exception("no ID specified");
        }
        global $database;
        $query = "DELETE FROM `person` WHERE `PE_personid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }

    public static function getPersonWithAccountArray($search = "", $group = 0, $status = -1, $limitstart = null, $limit = null): array
    {
        global $database;

        $query = "SELECT * FROM `person` as pe, `personaccount` as pa WHERE pe.PE_personaccountid=pa.PA_personaccountid";

        if ($search != "") {
            $query .= " AND (`PE_firstname` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_surname` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_nick` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_username` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_email` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_tel` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_secondary_phone_number` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_icq` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_jabber` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_address` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_city` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_zip` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_ic` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_dic` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_shortcompanyname` COLLATE utf8_general_ci LIKE '%$search%' OR `PE_companyname` COLLATE utf8_general_ci LIKE '%$search%')";
        }

        if ($group != 0) {
            $query .= " AND `PE_groupid`='$group'";
        }

        if ($status != -1) {
            $query .= " AND `PE_status`='$status'";
        }

        $query .= " ORDER BY pe.PE_surname, pe.PE_firstname, PE_nick";

        $database->setQuery($query);
        return $database->loadObjectList('PE_personid');
    }

    /**
     * @param  $username
     * @return null
     * @throws Exception
     */
    public static function getPersonWithGroupByUsername($username)
    {
        if ($username === null) {
            throw new Exception("no username specified");
        }
        global $database;
        $personWithGroup = null;
        $query = "SELECT * FROM `person`,`group` WHERE `PE_username`='$username' AND `PE_groupid`=`GR_groupid` LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($personWithGroup);
        return $personWithGroup;
    }

    public static function getPersonArrayForQOS(): array
    {
        global $database;

        $query = "SELECT * FROM `person` WHERE `PE_status`='" . Person::STATUS_ACTIVE . "' ORDER BY `PE_surname`, `PE_firstname`";

        $database->setQuery($query);
        return $database->loadObjectList('PE_personid');
    }

    public static function getSuperAdministratorsPersonArray(): array
    {
        global $database;

        $query = "SELECT * FROM `person` as pe, `group` as gr WHERE pe.PE_status='" . Person::STATUS_ACTIVE . "' AND pe.PE_groupid = gr.GR_groupid AND gr.GR_level='" . Group::SUPER_ADMINISTRATOR . "' ORDER BY pe.PE_surname, pe.PE_firstname";

        $database->setQuery($query);
        return $database->loadObjectList('PE_personid');
    }
} // End of PersonDAO class
