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

/**
 * ensure this file is being included by a parent file
 */
defined('VALID_MODULE') or die(_("Direct access into this section is not allowed"));

global $core;
require_once $core->getAppRoot() . "includes/net/CommanderCrossbar.php";
require_once "scripts.html.php";

$task = $_REQUEST['task'] ?? null;

switch ($task) {
case 'ipfilteron':
    ipFilterOn();
    break;

case 'ipfilteroff':
    ipFilterOff();
    break;

case 'synchronizeFilter':
    synchronizeFilter();
    break;

default:
    show();
    break;
}

function show(): void
{
    HTML_scripts::showScripts('', []);
}

function ipFilterOn(): void
{
    $command = _("IP filter on");

    try {
        $commanderCrossbar = new CommanderCrossbar();

        $results = $commanderCrossbar->ipFilterUp();
    } catch (Exception $e) {
        $results = [[
            null,
            null,
            $e->getMessage()
        ]];
    }

    HTML_scripts::showScripts($command, $results);
}

function ipFilterOff(): void
{
    $command = _("IP filter off");

    try {
        $commanderCrossbar = new CommanderCrossbar();

        $results = $commanderCrossbar->ipFilterDown();
    } catch (Exception $e) {
        $results = [[
            null,
            null,
            $e->getMessage()
        ]];
    }

    HTML_scripts::showScripts($command, $results);
}

function synchronizeFilter(): void
{
    $command = _("Synchronize IP filter");

    try {
        $commanderCrossbar = new CommanderCrossbar();

        $results = $commanderCrossbar->synchronizeFilter();
    } catch (Exception $e) {
        $results = [[
            null,
            null,
            $e->getMessage()
        ]];
    }

    HTML_scripts::showScripts($command, $results);
}
