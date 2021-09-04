<?php
//
// +----------------------------------------------------------------------+
// | Stealth ISP QOS system                                               |
// +----------------------------------------------------------------------+
// | Copyright (c) 2006-2007 Ing. Lukas Dziadkowiec                       |
// +----------------------------------------------------------------------+
// | This source file is part of Stealth ISP QOS system,                  |
// | see LICENSE for licence details.                                     |
// +----------------------------------------------------------------------+
// | Authors: Lukas Dziadkowiec <i.ftelf@gmail.com>                  |
// +----------------------------------------------------------------------+

/**
 * @author  Lukas Dziadkowiec <i.ftelf@gmail.com>
 */

global $core;
require_once($core->getAppRoot() . "/includes/tables/Person.php");
require_once($core->getAppRoot() . "/includes/tables/Role.php");
require_once($core->getAppRoot() . "/includes/tables/Rolemember.php");

/**
 *  RolememberDAO
 */
class RolememberDAO {
    static function getRolememberArray() {
        global $database;
        $query = "SELECT * FROM `rolemember`";
        $database->setQuery($query);
        return $database->loadObjectList("RM_rolememberid");
    }
    static function getRolememberAndPersonsArrayByRoleID($id) {
        if ($id == null) throw new Exception("no ID specified");
        global $database;
        $query = "SELECT * FROM `person` as p,`rolemember` as r WHERE r.RM_roleid='$id' AND p.PE_personid=r.RM_personid";
        $database->setQuery($query);
        return $database->loadObjectList("RM_rolememberid");
    }
    static function getRolememberAndRoleArrayByPersonID($id) {
        if ($id == null) throw new Exception("no ID specified");
        global $database;
        $query = "SELECT * FROM `rolemember`, `role` WHERE `RM_personid`='$id' AND `RM_roleid`=`RO_roleid`";
        $database->setQuery($query);
        return $database->loadObjectList("RM_rolememberid");
    }
    static function getRolememberByID($id) {
        if ($id == null) throw new Exception("no ID specified");
        global $database;
        $rolemember = new Rolemember();
        $query = "SELECT * FROM `rolemember` WHERE `RM_rolememberid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($rolemember);
        return $rolemember;
    }
    static function removeRolemembersByID($id) {
        if ($id == null) throw new Exception("no ID specified");
        global $database;
        $query = "DELETE FROM `rolemember` WHERE `RM_rolememberid`='$id'";
        $database->setQuery($query);
        $database->query();
    }
    static function removeRolemembersByPersonID($id) {
        if ($id == null) throw new Exception("no ID specified");
        global $database;
        $query = "DELETE FROM `rolemember` WHERE `RM_personid`='$id'";
        $database->setQuery($query);
        $database->query();
    }
} // End of RolememberDAO class
?>
