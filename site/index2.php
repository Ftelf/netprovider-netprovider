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
require_once $core->getAppRoot() . "includes/Constants.php";
require_once $core->getAppRoot() . "includes/AppContext.php";
require_once $core->getAppRoot() . "includes/Database.php";
require_once $core->getAppRoot() . "includes/utils/Utils.php";
require_once $core->getAppRoot() . "includes/Mainframe.php";
require_once $core->getAppRoot() . "includes/utils/NumberFormat.php";
require_once $core->getAppRoot() . "includes/utils/DateUtil.php";
require_once $core->getAppRoot() . "includes/net/email/EmailUtil.php";
require_once $core->getAppRoot() . "includes/dao/SessionDAO.php";
require_once $core->getAppRoot() . "includes/dao/PersonDAO.php";
require_once $core->getAppRoot() . "includes/event/EventCrossBar.php";

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

$option = $_REQUEST['option'] ?? 'com_admin';
$hideMainMenu = $_REQUEST['hidemainmenu'] ?? '0';

// must start the session before we create the mainframe object
session_name("NETPROVIDER");
session_start();

// initialise some common request directives
if ($option === "logout") {
    require "logout.php";
    exit();
}

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

// Init Event crossbar
$eventCrossBar = new EventCrossBar();

$my = $_SESSION['USER'];

// store iu settings for user comfort
$_SESSION['UI_SETTINGS'][$option] ??= [];

// is filter posted ?
$_SESSION['UI_SETTINGS'][$option]['filter'] = $_POST['filter'] ?? $_SESSION['UI_SETTINGS'][$option]['filter'] ?? [];

$_SESSION['UI_SETTINGS'][$option]['limit'] ??= "10";
$_SESSION['UI_SETTINGS'][$option]['limitstart'] ??= "0";

// if limit and limitstart posted
if (isset($_POST['limit'], $_POST['limitstart'])) {
    $_SESSION['UI_SETTINGS'][$option]['limit'] = $_POST['limit'];
    $_SESSION['UI_SETTINGS'][$option]['limitstart'] = $_POST['limitstart'];
}
// if limit2 and limitstart2 posted
if (isset($_POST['limit2'], $_POST['limitstart2'], $_POST['task']) && $_POST['task'] === "showBankList") {
    $_SESSION['UI_SETTINGS'][$option]['limit2'] = $_POST['limit2'];
    $_SESSION['UI_SETTINGS'][$option]['limitstart2'] = $_POST['limitstart2'];
}
// store ui settings into database
$person = new Person();
$person->PE_personid = $session->SE_personid;
$person->PE_uistate = serialize($_SESSION['UI_SETTINGS']);
try {
    $database->updateObject("person", $person, "PE_personid", false);
} catch (Exception $e) {
    $database->log(sprintf(_("Internal error, cannot update user UI settings: %s"), $person->PE_personid), Log::LEVEL_CRITICAL);
    $core::redirect('index.php', _("Internal error"));
}

require $core->getAppRoot() . "modules/com_common/html_start.php";
require $core->getAppRoot() . "modules/com_common/html_header.php";

if ($my->GR_level === 0) {
    $option = 'com_myprofile';
}

if ($hideMainMenu === '0') {
    require $core->getAppRoot() . "modules/com_common/html_mainmenu.php";
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
    require $mainframe->getPath();
} catch (Exception $e) {
    $database->log("Caught Exception: " . $e, Log::LEVEL_ERROR);

    if ($core->getProperty(Core::SEND_EMAIL_ON_CRITICAL_ERROR)) {
        $emailUtil = new EmailUtil();
        $emailUtil->sendEmailMessage($core->getProperty(Core::SUPERVISOR_EMAIL), "Net provider error", $e);
    }

    if ($core->getProperty(Core::SYSTEM_DEBUG, false)) {
        echo "<pre>" . $e . "</pre>";
    } else {
        $msg = _("Internal system error");
        $msg .= "\\n" . _("Supervisor has been informed");
        $core::backWithAlert($msg);
    }
}
$mainframe->timerStop();

require $core->getAppRoot() . "modules/com_common/html_footer.php";

if ($core->getProperty(Core::SYSTEM_DEBUG, false)) {
    require $core->getAppRoot() . "modules/com_common/html_debug.php";
}
require $core->getAppRoot() . "modules/com_common/html_end.php";
