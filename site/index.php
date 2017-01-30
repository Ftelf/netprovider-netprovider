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
// | Authors: Lukas Dziadkowiec <stealth.home@seznam.cz>                  |
// +----------------------------------------------------------------------+

/**
 * @author  Lukas Dziadkowiec <stealth.home@seznam.cz>
 */

require_once(dirname(__FILE__) . "/../includes/Core.php");
$core = new Core();
require_once($core->getAppRoot() . "includes/Constants.php");
require_once($core->getAppRoot() . "includes/AppContext.php");
require_once($core->getAppRoot() . "includes/Database.php");
require_once($core->getAppRoot() . "includes/utils/Utils.php");
require_once($core->getAppRoot() . "includes/tables/Session.php");
require_once($core->getAppRoot() . "includes/dao/PersonDAO.php");

try {
    $database = new Database(
        $core->getProperty(Core::DATABASE_HOST),
        $core->getProperty(Core::DATABASE_USERNAME),
        $core->getProperty(Core::DATABASE_PASSWORD),
        $core->getProperty(Core::DATABASE_NAME)
    );
} catch (Exception $e) {
    $core->alert(_("Cannot connect to database"));
    exit();
}

// if we have POST, ie. attempt to login
if (isset($_POST['submit'])) {
    // get username and pass from POST
    $username = Utils::getParam( $_POST, 'usrname', '');
    $pass = Utils::getParam( $_POST, 'pass', '');

    if (strlen($pass) < 6) {
        $core->redirect('index.php', _("Password minimal length is 6 characters. Please try again."));
    }

    $pass = md5($pass);
    
    try {
        $my = PersonDAO::getPersonWithGroupByUsername($username);
    } catch (Exception $e) {
        $database->log(sprintf(_("Unauthorized login, username: %s"), $username), Log::LEVEL_SECURITY);
        $core->redirect('index.php', _("Wrong username, password or access rights, please try again"));
    }

    if ($my->PE_password != $pass) {
        $database->log(sprintf(_("Incorect password for username: %s"), $username), Log::LEVEL_SECURITY);
        $core->redirect('index.php', _("Wrong username, password or access rights, please try again"));
    }

    session_name("NETPROVIDER");
    session_start();

    $logintime = time();

    $session = new Session();
    $session->SE_sessionid = md5("$username$my->GR_acl$logintime");
    $session->SE_time = $logintime;
    $session->SE_personid = $my->PE_personid;
    $session->SE_acl = $my->GR_acl;
    $session->SE_username = $username;
    $session->SE_ip = $_SERVER['REMOTE_ADDR'];

    try {
        $database->insertObject("session", $session, null, false);
    } catch (Exception $e) {
        $database->log($e, Log::LEVEL_ERROR);
        $core->redirect('index.php');
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
    //
    if (($_SESSION['UI_SETTINGS'] = unserialize($my->PE_uistate)) == null) $_SESSION['UI_SETTINGS'] = array();

    session_write_close();
    $core->redirect('index2.php');
} else {
    try {
        $personByIP = PersonDAO::getPersonByIP($_SERVER['REMOTE_ADDR']);
        $foundUsername = $personByIP->PE_username;
    } catch (Exception $e) {
        $foundUsername = "";
    }

    require_once($core->getAppRoot() . "modules/com_common/login.php");
}
?>