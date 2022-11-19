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

/** ensure this file is being included by a parent file */
defined('VALID_MODULE') or die(_("Direct access into this section is not allowed"));

global $core;
require_once($core->getAppRoot() . "includes/dao/SessionDAO.php");
require_once($core->getAppRoot() . "includes/dao/LogDAO.php");
require_once($core->getAppRoot() . "includes/dao/PersonDAO.php");
require_once($core->getAppRoot() . "includes/utils/DateUtil.php");
require_once('admin.html.php');

$task = Utils::getParam($_REQUEST, 'task', null);
$pid = Utils::getParam($_REQUEST, 'SE_personid', null);
$sid = Utils::getParam($_REQUEST, 'SE_sessionid', null);

switch ($task) {
    case 'force_logout':
        force_logout($sid);
        break;

    default:
        show();
        break;
}

function show() {
    global $database, $mainframe, $acl;

    $sessions = SessionDAO::getSessionArray();
    $persons = PersonDAO::getPersonArray();
    $logs = LogDAO::getLastLogArray(10);

    HTML_admin::show($sessions, $logs, $persons);
}

function force_logout($sid) {
    global $database, $mainframe, $acl, $appContext;

    $session = SessionDAO::getSessionByID($sid);
    $current_sid = Utils::getParam($_SESSION, 'SE_sessionid', '');
    if ($sid != $current_sid) {
        try {
            SessionDAO::removeSessionByID($sid);
            $msg = sprintf(_("User '%s' was forcelly logged out"), $session->SE_username);
            $appContext->insertMessage($msg);
            $database->log($msg, LOG::LEVEL_INFO);
        } catch (Exception $e) {
            $msg = sprintf(_("User '%s' wasn't forcelly logged out, probably was logged out itself"), $session->SE_username);
            $appContext->insertMessage($msg);
            $database->log($msg, LOG::LEVEL_WARNING);
        }
    }
    Core::redirect("index2.php?option=com_admin");
}
?>
