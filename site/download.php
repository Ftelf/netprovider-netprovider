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

require_once __DIR__ . "/../includes/Core.php";
$core = new Core();
require_once $core->getAppRoot() . "includes/AppContext.php";
require_once $core->getAppRoot() . "includes/Database.php";
require_once $core->getAppRoot() . "includes/utils/Utils.php";
require_once $core->getAppRoot() . "includes/Mainframe.php";
require_once $core->getAppRoot() . "includes/utils/DateUtil.php";
require_once $core->getAppRoot() . "includes/dao/EmailListDAO.php";
require_once $core->getAppRoot() . "includes/dao/SessionDAO.php";

try {
    $database = new Database(
        $core->getProperty(Core::DATABASE_HOST),
        $core->getProperty(Core::DATABASE_USERNAME),
        $core->getProperty(Core::DATABASE_PASSWORD),
        $core->getProperty(Core::DATABASE_NAME)
    );
} catch (Exception $e) {
    $core::alert('_("Cannot connect to database")');
    exit();
}

$option = $_REQUEST['option'] ??= 'com_admin';
$task = $_REQUEST['task'];

// must start the session before we create the mainframe object
session_name("NETPROVIDER");
session_start();

// mainframe is an API workhorse
$mainframe = new MainFrame($database, $option, '..', null);

$session = new Session();
$session->SE_sessionid = $_SESSION['SE_sessionid'] ?? '';
$session->SE_personid = $_SESSION['SE_personid'] ?? '';
$session->SE_username = $_SESSION['SE_username'] ?? '';
$session->SE_acl = $_SESSION['SE_acl'] ?? '';
$session->SE_time = $_SESSION['SE_time'] ?? '';

// timeout old sessions
$sessions = SessionDAO::removeTimeoutedSession(1800);

// check against db record of session
try {
    if ($session->SE_sessionid !== md5("$session->SE_username$session->SE_acl$session->SE_time") || !SessionDAO::checkSession($session)) {
        Core::redirect("index.php");
    }
} catch (Exception $e) {
    Core::redirect("index.php");
}

// update session timestamp
try {
    SessionDAO::updateSessionTimeout($session->SE_sessionid);
} catch (Exception $e) {
    $database->log(sprintf(_("Internal error, cannot update session timeout: %s"), $session->SE_sessionid), Log::LEVEL_CRITICAL);
    $core::redirect('index.php', _("Internal error"));
}

$my = $_SESSION['USER'];

if ($option === 'com_bankaccount' && $task === 'download') {
    $lid = $_REQUEST['EL_emaillistid'];

    try {
        $emailList = EmailListDAO::getEmailListByID($lid);
    } catch (Exception $e) {
        exit();
    }
    header("Content-Description: File Transfer");
    header("Content-Type: application/force-download");
    header("Content-Disposition: attachment; filename=\"$emailList->EL_name\"");
    echo $emailList->EL_list;
}
