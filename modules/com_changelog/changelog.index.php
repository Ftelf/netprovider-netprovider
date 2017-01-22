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

/** ensure this file is being included by a parent file */
defined('VALID_MODULE') or die(_("Direct access into this section is not allowed"));

global $core;
require_once("changelog.html.php");

show();

/**
 * show
 * @return void void
 */
function show() {
	global $database, $mainframe, $acl, $core;
	
	$changelogFile = $core->getAppRoot() . "changelog.txt";
	$changelogHandle = fopen($changelogFile, "r");
	
	$changelogText = fread($changelogHandle, filesize($changelogFile));
	
	HTML_changelog::showChangelog($changelogText);
}
?>