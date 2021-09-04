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

require_once(dirname(__FILE__) . "/../includes/Core.php");
$core = new Core();
require_once($core->getAppRoot() . "includes/Constants.php");
require_once($core->getAppRoot() . "includes/AppContext.php");
require_once($core->getAppRoot() . "includes/Database.php");
require_once($core->getAppRoot() . "includes/utils/Utils.php");
require_once($core->getAppRoot() . "includes/Mainframe.php");
require_once($core->getAppRoot() . "includes/utils/DateUtil.php");
require_once($core->getAppRoot() . "includes/dao/EmailListDAO.php");
require_once($core->getAppRoot() . "includes/dao/SessionDAO.php");

try {
    $database = new Database(
        $core->getProperty(Core::DATABASE_HOST),
        $core->getProperty(Core::DATABASE_USERNAME),
        $core->getProperty(Core::DATABASE_PASSWORD),
        $core->getProperty(Core::DATABASE_NAME)
    );
} catch (Exception $e) {
    $core->alert('_("Cannot connect to database")');
    exit();
}

$option = strtolower(Utils::getParam($_REQUEST, 'option', 'com_admin'));
$task = Utils::getParam($_REQUEST, 'task', null);

// must start the session before we create the mainframe object
session_name("NETPROVIDER");
session_start();

// mainframe is an API workhorse
$mainframe = new MainFrame($database, $option, '..', null);

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

if ($option == 'com_bankaccount') {
    if ($task == 'download') {
        $lid = Utils::getParam($_REQUEST, 'EL_emaillistid', null);

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
}
?>
