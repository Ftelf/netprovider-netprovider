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
require_once $core->getAppRoot() . "includes/tables/Session.php";
require_once $core->getAppRoot() . "includes/dao/PersonDAO.php";

try {
    $database = new Database(
        $core->getProperty(Core::DATABASE_HOST),
        $core->getProperty(Core::DATABASE_USERNAME),
        $core->getProperty(Core::DATABASE_PASSWORD),
        $core->getProperty(Core::DATABASE_NAME)
    );
} catch (Exception $e) {
    $core::alert(_("Cannot connect to database"));
    exit();
}

// if we have POST, ie. attempt to login
if (isset($_POST['submit'])) {
    // get username and pass from POST
    $username = $_POST['username'] ?? '';
    $pass = $_POST['pass'] ?? '';

    if (strlen($pass) < 6) {
        $core::redirect('index.php', _("Password minimal length is 6 characters. Please try again."));
    }

    $pass = md5($pass);

    try {
        $my = PersonDAO::getPersonWithGroupByUsername($username);
    } catch (Exception $e) {
        $database->log(sprintf(_("Unauthorized login, username: %s"), $username), Log::LEVEL_SECURITY);
        $core::redirect('index.php', _("Wrong username, password or access rights, please try again"));
    }

    if ($my->PE_password !== $pass) {
        $database->log(sprintf(_("Incorrect password for username: %s"), $username), Log::LEVEL_SECURITY);
        $core::redirect('index.php', _("Wrong username, password or access rights, please try again"));
    }

    session_name("NETPROVIDER");
    session_start();

    $logintime = time();
    $now = new DateUtil();

    $session = new Session();
    $session->SE_sessionid = md5("$username$my->GR_acl$logintime");
    $session->SE_time = $logintime;
    $session->SE_personid = $my->PE_personid;
    $session->SE_acl = $my->GR_acl;
    $session->SE_username = $username;
    $session->SE_ip = $_SERVER['REMOTE_ADDR'];

    $person = null;
    try {
        $person = PersonDAO::getPersonByID($my->PE_personid);
    } catch (Exception $e) {
        $database->log(sprintf(_("Cannot retrieve Person by ID: %s"), $my->PE_personid), Log::LEVEL_CRITICAL);
        $core::redirect('index.php', _("Internal error, please try again"));
    }

    $person->PE_lastloggedin = $now->getFormattedDate(DateUtil::DB_DATETIME);

    try {
        $database->updateObject("person", $person, "PE_personid", false);
        $database->insertObject("session", $session);
    } catch (Exception $e) {
        $database->log($e, Log::LEVEL_ERROR);
        $core::redirect('index.php');
    }

    $_SESSION['APP_CONTEXT'] = new AppContext();
    $_SESSION['SE_sessionid'] = $session->SE_sessionid;
    $_SESSION['SE_time'] = $session->SE_time;
    $_SESSION['SE_personid'] = $session->SE_personid;
    $_SESSION['SE_acl'] = $session->SE_acl;
    $_SESSION['SE_username'] = $session->SE_username;
    $_SESSION['SE_ip'] = $session->SE_ip;
    $_SESSION['USER'] = $my;

    // get custom user settings from database
    if (!is_array($_SESSION['UI_SETTINGS'] = unserialize($my->PE_uistate, ['allowed_classes' => false]))) {
        $_SESSION['UI_SETTINGS'] = [];
    }

    session_write_close();
    $core::redirect('index2.php');
} else {
    try {
        $personByIP = PersonDAO::getPersonByIP($_SERVER['REMOTE_ADDR']);
        $foundUsername = $personByIP->PE_username;
    } catch (Exception $e) {
        $foundUsername = "";
    }

    require_once $core->getAppRoot() . "modules/com_common/login.php";
}
