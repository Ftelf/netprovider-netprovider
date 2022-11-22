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
require_once($core->getAppRoot() . "/includes/tables/Session.php");

/**
 *  SessionDAO
 */
class SessionDAO {
    static function getSessionCount() {
        global $database;
        $query = "SELECT count(*) FROM `session`";
        $database->setQuery($query);
        return $database->loadResult();
    }
    static function getSessionArray() {
        global $database;
        $query = "SELECT * FROM `session`";
        $database->setQuery($query);
        return $database->loadObjectList("SE_sessionid");
    }
    static function getSessionByID($id) {
        if ($id == null) throw new Exception("no ID specified");
        global $database;
        $session = new Session();
        $query = "SELECT * FROM `session` WHERE `SE_sessionid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($session);
        return $session;
    }
    static function removeSessionByID($id) {
        if ($id == null) throw new Exception("no ID specified");
        global $database;
        $query = "DELETE FROM `session` WHERE `SE_sessionid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }
    static function removeSessionByPersonID($id) {
        if ($id == null) throw new Exception("no ID specified");
        global $database;
        $query = "DELETE FROM `session` WHERE `SE_personid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }
    static function removeTimeoutedSession($offset='1800') {
        global $database;
        // at first get all timeouted sessions
        //
        $past = time() - $offset;
        $query = "SELECT * FROM `session` WHERE `SE_time`<'$past'";
        $database->setQuery($query);
        if (($sessions = $database->loadObjectList("SE_sessionid")) == null) $sessions = array();
        //then remove them from session table
        //
        foreach ($sessions as $session) {
            SessionDAO::removeSessionByID($session->SE_sessionid);
        }
        // finally return this array
        //
        return $sessions;
    }

    /**
     * @param $session
     * @return The|null
     * @throws Exception
     */
    static function checkSession($session) {
        if ($session === null ) throw new Exception("no Session specified");
        global $database;
        $query = "SELECT count(*) FROM `session` WHERE `SE_sessionid`='$session->SE_sessionid' AND `SE_username`='$session->SE_username' AND `SE_personid`='$session->SE_personid' LIMIT 1";
        $database->setQuery($query);
        return $database->loadResult();
    }

    /**
     * @param $id
     * @return void
     * @throws Exception
     */
    static function updateSessionTimeout($id) {
        if ($id === null) throw new Exception("no ID specified");
        global $database;
        $query = "UPDATE `session` SET `SE_time`='" . time() . "' WHERE `SE_sessionid`='$id'";
        $database->setQuery($query);
        $database->query();
    }
} // End of SessionDAO class
?>
