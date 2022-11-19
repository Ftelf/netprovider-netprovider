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
require_once("changelog.html.php");

show();

/**
 * show
 * @return void void
 */
function show() {
    global $database, $mainframe, $acl, $core;

    $changelogFile = $core->getAppRoot() . "CHANGELOG.md";
    $changelogHandle = fopen($changelogFile, "r");

    $changelogText = fread($changelogHandle, filesize($changelogFile));

    HTML_changelog::showChangelog($changelogText);
}
?>
