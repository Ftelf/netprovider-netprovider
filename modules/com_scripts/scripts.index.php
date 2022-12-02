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
    $filter = [];
    // default settings if no setting in session
    // do we want Network headers for IPs to be shown ?
    $filter['execute'] = $_SESSION['UI_SETTINGS']['com_scripts']['filter'] ?? 'execute';

    $command = '';
    $messages = [];

    HTML_scripts::showScripts($command, $messages, $filter);
}

function ipFilterOn(): void
{
    $filter = [];
    $filter['execute'] = $_SESSION['UI_SETTINGS']['com_scripts']['filter'] ?? 'execute';

    $command = _("IP filter on");

    try {
        $commanderCrossbar = new CommanderCrossbar();
        $commanderCrossbar->setDryRun(!$filter['execute']);
        $commanderCrossbar->inicialize();

        $results = $commanderCrossbar->ipFilterUp();
    } catch (Exception $e) {
        $results = [[
            null,
            null,
            $e->getMessage()
        ]];
    }

    HTML_scripts::showScripts($command, $results, $filter);
}

function ipFilterOff(): void
{
    $filter = [];
    $filter['execute'] = $_SESSION['UI_SETTINGS']['com_scripts']['filter'] ?? 'execute';

    $command = _("IP filter off");

    try {
        $commanderCrossbar = new CommanderCrossbar();
        $commanderCrossbar->setDryRun(!$filter['execute']);
        $commanderCrossbar->inicialize();

        $results = $commanderCrossbar->ipFilterDown();
    } catch (Exception $e) {
        $results = [[
            null,
            null,
            $e->getMessage()
        ]];
    }

    HTML_scripts::showScripts($command, $results, $filter);
}

function synchronizeFilter(): void
{
    $filter = [];
    $filter['execute'] = $_SESSION['UI_SETTINGS']['com_scripts']['filter'] ?? 'execute';

    $command = _("Synchronize IP filter");

    try {
        $commanderCrossbar = new CommanderCrossbar();
        $commanderCrossbar->setDryRun(!$filter['execute']);
        $commanderCrossbar->inicialize();

        $results = $commanderCrossbar->synchronizeFilter();
    } catch (Exception $e) {
        $results = [[
            null,
            null,
            $e->getMessage()
        ]];
    }

    HTML_scripts::showScripts($command, $results, $filter);
}
