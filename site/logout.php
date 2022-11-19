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
require_once($core->getAppRoot() . "includes/dao/GroupDAO.php");
require_once($core->getAppRoot() . "includes/dao/ChargeDAO.php");
require_once($core->getAppRoot() . "includes/dao/InternetDAO.php");
require_once($core->getAppRoot() . "includes/dao/IpDAO.php");
require_once($core->getAppRoot() . "includes/dao/NetworkDAO.php");
require_once($core->getAppRoot() . "includes/dao/PersonDAO.php");
require_once($core->getAppRoot() . "includes/dao/RoleDAO.php");

// remove this session from session table
//
if (isset($_SESSION['SE_sessionid']) && $_SESSION['SE_sessionid']!="") {
    (SessionDAO::removeSessionByID($_SESSION['SE_sessionid']));
}

unset($_SESSION["SE_sessionid"]);
unset($_SESSION["SE_personid"]);
unset($_SESSION["SE_username"]);
unset($_SESSION["SE_acl"]);
unset($_SESSION["SE_logintime"]);
unset($_SESSION["SE_ip"]);

if (isset($_SESSION["SE_sessionid"])) {
    session_destroy();
}
if (isset($_SESSION["SE_personid"])) {
    session_destroy();
}
if (isset($_SESSION["SE_username"])) {
    session_destroy();
}
if (isset($_SESSION["SE_acl"])) {
    session_destroy();
}
if (isset($_SESSION["SE_logintime"])) {
    session_destroy();
}
if (isset($_SESSION["SE_ip"])) {
    session_destroy();
}
echo "<script>document.location.href='index.php';</script>\n";	
?>
