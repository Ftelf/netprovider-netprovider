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

/** ensure this file is being included by a parent file */
defined('VALID_MODULE') or die(_("Direct access into this section is not allowed"));

global $core;
require_once($core->getAppRoot() . "includes/net/CommanderCrossbar.php");
require_once("scripts.html.php");

$task = Utils::getParam($_REQUEST, 'task', null);
$rid = Utils::getParam($_REQUEST, 'RO_roleid', null);
$cid = Utils::getParam($_REQUEST, 'cid', array(0));
if (!is_array($cid)) {
    $cid = array (0);
}

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

function show() {
    $filter = array();
    // default settings if no setting in session
    // do we want Network headers for IPs to be shown ?
    //
    $filter['execute'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_scripts']['filter'], 'execute', null);

    $command = '';
    $messages = array();

    HTML_scripts::showScripts($command, $messages, $filter);
}

function ipFilterOn() {
    $filter = array();
    // default settings if no setting in session
    // do we want Network headers for IPs to be shown ?
    //
    $filter['execute'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_scripts']['filter'], 'execute', null);

    $command = _("IP filter on");

    try {
        $commanderCrossbar = new CommanderCrossbar();
        $commanderCrossbar->setDryRun(!$filter['execute']);
        $commanderCrossbar->inicialize();

        $results = $commanderCrossbar->ipFilterUp();
    } catch (Exception $e) {
        $results = array(array(
            null,
            null,
            $e->getMessage()
        ));
    }

    HTML_scripts::showScripts($command, $results, $filter);
}

function ipFilterOff() {
    $filter = array();
    // default settings if no setting in session
    // do we want Network headers for IPs to be shown ?
    //
    $filter['execute'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_scripts']['filter'], 'execute', null);

    $command = _("IP filter off");

    try {
        $commanderCrossbar = new CommanderCrossbar();
        $commanderCrossbar->setDryRun(!$filter['execute']);
        $commanderCrossbar->inicialize();

        $results = $commanderCrossbar->ipFilterDown();
    } catch (Exception $e) {
        $results = array(array(
          null,
          null,
          $e->getMessage()
        ));
    }

    HTML_scripts::showScripts($command, $results, $filter);
}

function synchronizeFilter() {
    $filter = array();
    // default settings if no setting in session
    // do we want Network headers for IPs to be shown ?
    //
    $filter['execute'] = Utils::getParam($_SESSION['UI_SETTINGS']['com_scripts']['filter'], 'execute', null);

    $command = _("Synchronize IP filter");

    try {
        $commanderCrossbar = new CommanderCrossbar();
        $commanderCrossbar->setDryRun(!$filter['execute']);
        $commanderCrossbar->inicialize();

        $results = $commanderCrossbar->synchronizeFilter();
    } catch (Exception $e) {
        $results = array(array(
          null,
          null,
          $e->getMessage()
        ));
    }

    HTML_scripts::showScripts($command, $results, $filter);
}
?>
