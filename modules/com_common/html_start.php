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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title><?php echo $core->getProperty(Core::UI_TITLE); ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="Content-Language" content="<?php echo $core->getProperty(Core::UI_LOCALE); ?>" />
    <script src="js/JSCookMenu.js" type="text/javascript"></script>
    <script src="js/ThemeOffice/theme.js" type="text/javascript"></script>
    <script type="text/javascript" src="js/tabs/tabpane.js"></script>
    <script type="text/javascript" src="js/functions.js"></script>
    <script type="text/javascript" src="js/dtree.js"></script>
    <script type="text/javascript" src="js/overlib.js"></script>
    <script type="text/javascript" src="js/CalendarPopup.js"></script>
    <script type="text/javascript" src="js/validator.js"></script>
    <script type="text/javascript" src="js/jquery-3.6.3.min.js"></script>
    <script type="text/javascript" src="js/chosen.jquery.min.js"></script>
    <link rel="stylesheet" href="css/template.css" type="text/css" />
    <link rel="stylesheet" href="css/icon.css" type="text/css" />
    <link rel="stylesheet" href="css/report.css" type="text/css" />
    <link rel="stylesheet" href="css/dtree.css" type="text/css" />
    <link rel="stylesheet" href="css/calendar-popup.css" type="text/css" />
    <link rel="stylesheet" href="css/chosen.css">
    <link rel="stylesheet" href="js/ThemeOffice/theme.css" type="text/css" />
    <link rel="stylesheet" href="js/tabs/tabpane.css" type="text/css" />
    <link rel="shortcut icon" href="favicon.ico" />
  </head>
  <body id="wrapper">
