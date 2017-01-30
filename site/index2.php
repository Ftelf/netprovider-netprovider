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
require_once($core->getAppRoot() . "includes/Mainframe.php");
require_once($core->getAppRoot() . "includes/utils/NumberFormat.php");
require_once($core->getAppRoot() . "includes/utils/DateUtil.php");
require_once($core->getAppRoot() . "includes/net/email/EmailUtil.php");
require_once($core->getAppRoot() . "includes/dao/SessionDAO.php");
require_once($core->getAppRoot() . "includes/dao/PersonDAO.php");
require_once($core->getAppRoot() . "includes/event/EventCrossBar.php");

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

$option = Utils::getParam($_REQUEST, 'option', 'com_admin');
$hidemainmenu = strtolower(Utils::getParam($_REQUEST, 'hidemainmenu', 0));

// must start the session before we create the mainframe object
session_name("NETPROVIDER");
session_start();

// initialise some common request directives
if ($option == "logout") {
    require "logout.php";
    exit();
}

$session = new Session();
$session->SE_sessionid = Utils::getParam($_SESSION, 'SE_sessionid', '');
$session->SE_personid = Utils::getParam($_SESSION, 'SE_personid', '');
$session->SE_username = Utils::getParam($_SESSION, 'SE_username', '');
$session->SE_acl = Utils::getParam($_SESSION, 'SE_acl', '');
$session->SE_time = Utils::getParam($_SESSION, 'SE_time', '');

// timeout old sessions
//
$sessions = SessionDAO::removeTimeoutedSession(1800);

// check against db record of session
if ($session->SE_sessionid != md5("$session->SE_username$session->SE_acl$session->SE_time") || !SessionDAO::checkSession($session)) {
    Core::redirect("index.php");
}
// update session timestamp
SessionDAO::updateSessionTimeout($session->SE_sessionid);

// Init Event crossbar
$eventCrossBar = new EventCrossBar();

$my = $_SESSION['USER'];
// store iu settings for user comfort
//
if (!isset($_SESSION['UI_SETTINGS'][$option])) $_SESSION['UI_SETTINGS'][$option] = array();
if (!isset($_SESSION['UI_SETTINGS'][$option]['filter'])) $_SESSION['UI_SETTINGS'][$option]['filter'] = array();
// if filter posted
//
if (isset($_POST['filter'])) {
    $_SESSION['UI_SETTINGS'][$option]['filter'] = $_POST['filter'];
}
if (!isset($_SESSION['UI_SETTINGS'][$option]['limit'])) {
    $_SESSION['UI_SETTINGS'][$option]['limit'] = "10";
}
if (!isset($_SESSION['UI_SETTINGS'][$option]['limitstart'])) {
    $_SESSION['UI_SETTINGS'][$option]['limitstart'] = "0";
}
// if limit and limitstart posted
if (isset($_POST['limit']) && isset($_POST['limitstart'])) {
    $_SESSION['UI_SETTINGS'][$option]['limit'] = $_POST['limit'];
    $_SESSION['UI_SETTINGS'][$option]['limitstart'] = $_POST['limitstart'];
}
// if limit and limitstart2 posted
if (isset($_POST['limit2']) && isset($_POST['limitstart2']) && isset($_POST['task']) && $_POST['task'] == "showBankList") {
    $_SESSION['UI_SETTINGS'][$option]['limit2'] = $_POST['limit2'];
    $_SESSION['UI_SETTINGS'][$option]['limitstart2'] = $_POST['limitstart2'];
}
// store ui settings into database
//
$person = new Person();
$person->PE_personid = $session->SE_personid;
$person->PE_uistate = serialize($_SESSION['UI_SETTINGS']);
$database->updateObject("person", $person, "PE_personid", false, false);

require($core->getAppRoot() . "modules/com_common/html_start.php");
require($core->getAppRoot() . "modules/com_common/html_header.php");

if ($my->GR_level == 0) {
    $option = 'com_myprofile';
}

if (!$hidemainmenu) {
    require($core->getAppRoot() . "modules/com_common/html_mainmenu.php");
}

// mainframe is an API workhorse
//
$mainframe = new MainFrame($database, $option, '..', null);
$mainframe->timerStart();

$appContext = $_SESSION['APP_CONTEXT'];
$appContext->setOption($option);
$mainframe->setMessages($appContext->getMessages());
$mainframe->getMsgPanel();
$appContext->cleanMessages();

try {
    require($mainframe->getPath());
} catch (Exception $e) {
    $database->log("Caught Exception: ".$e, Log::LEVEL_ERROR);

    if ($core->getProperty(Core::SEND_EMAIL_ON_CRITICAL_ERROR)) {
        $emailUtil = new EmailUtil();
        $emailUtil->sendEmailMessage($core->getProperty(Core::SUPERVISOR_EMAIL), "Net provider error", $e);
    }

    if ($core->getProperty(Core::SYSTEM_DEBUG)) {
        echo "<pre>".$e."</pre>";
    } else {
        $msg = _("Internal system error");
        $msg .= "\\n"._("Supervisor has been informed");
        $core->backWithAlert($msg);
    }
}
$mainframe->timerStop();

require($core->getAppRoot() . "modules/com_common/html_footer.php");

if ($core->getProperty(Core::SYSTEM_DEBUG)) {
    require($core->getAppRoot() . "modules/com_common/html_debug.php");
}
require($core->getAppRoot() . "modules/com_common/html_end.php");
?>