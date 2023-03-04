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
require_once("networkdevice.html.php");
require_once 'Net/IPv4.php';
require_once $core->getAppRoot() . "includes/net/commander/LinuxCommander.php";
require_once $core->getAppRoot() . "includes/net/commander/RouterOSCommander.php";

$task = Utils::getParam($_REQUEST, 'task', null);

switch ($task) {
    case 'testLogin':
        testLogin();
        break;

    default:
        showNetworkDevice();
        break;
}

function showNetworkDevice(): void {
    global $core;

    $networkDevice = [
        Core::NETWORK_DEVICE_PLATFORM => $core->getProperty(Core::NETWORK_DEVICE_PLATFORM),
        Core::NETWORK_DEVICE_HOST => $core->getProperty(Core::NETWORK_DEVICE_HOST),
        Core::NETWORK_DEVICE_PORT => $core->getProperty(Core::NETWORK_DEVICE_PORT),
        Core::NETWORK_DEVICE_LOGIN => $core->getProperty(Core::NETWORK_DEVICE_LOGIN),
        Core::NETWORK_DEVICE_PASSWORD => $core->getProperty(Core::NETWORK_DEVICE_PASSWORD),
        Core::NETWORK_DEVICE_WAN_INTERFACE => $core->getProperty(Core::NETWORK_DEVICE_WAN_INTERFACE),
        Core::NETWORK_DEVICE_COMMAND_SUDO => $core->getProperty(Core::NETWORK_DEVICE_COMMAND_SUDO),
        Core::NETWORK_DEVICE_COMMAND_IPTABLES => $core->getProperty(Core::NETWORK_DEVICE_COMMAND_IPTABLES)
    ];

    HTML_NetworkDevice::showNetworkDevice($networkDevice);
}

function testLogin() {
    global $core, $appContext;

    $login = $core->getProperty(Core::NETWORK_DEVICE_LOGIN);
    $host = $core->getProperty(Core::NETWORK_DEVICE_HOST);

    $platform = $core->getProperty(Core::NETWORK_DEVICE_PLATFORM);
    try {
        if ($platform === "LINUX") {
            $commandIptables = $core->getProperty(Core::NETWORK_DEVICE_COMMAND_IPTABLES);

            $commander = new LinuxCommander([], true);

            $appContext->insertMessage(sprintf(_("Login successful: ssh %s@%s"), $login, $host));

            $uname = $commander->execute("uname -a");
            $appContext->insertMessage($uname[1]);

            if ($commandIptables) {
                $iptables = $commander->execute(sprintf("%s --version", $commandIptables));
                if ($iptables[2]) {
                    $appContext->insertMessage(sprintf(_("iptables not found on specified path %s"), $commandIptables));
                } else {
                    $appContext->insertMessage($iptables[1]);
                }
            } else {
                $iptables = $commander->execute("find / -name iptables -print");
                $appContext->insertMessage($iptables[1]);
            }
        } elseif ($platform === "ROUTEROS") {
            $commander = new RouterOSCommander([], true);

            $appContext->insertMessage(sprintf(_("Login successful: mikrotik API %s@%s"), $login, $host));

            $result1 = $commander->execute(array("/system/routerboard/print"));
            $result2 = $commander->execute(array("/system/resource/print"));
            foreach ($result1[1] as $result11) {
                foreach ($result11 as $key => $value) {
                    $appContext->insertMessage($key . ": " . $value);
                }
            }

            foreach ($result2[1] as $result21) {
                foreach ($result21 as $key => $value) {
                    $appContext->insertMessage($key . ": " . $value);
                }
            }
        } else {
            throw new Exception("Unknown platform in configuration ini file: \"${platform}\"");
        }
    } catch (Exception $e) {
        $msg = sprintf(_("Login failed: %s@%s: %s"), $login, $host, $e->getMessage());
        $appContext->insertMessage($msg);
    }

    Core::redirect("index2.php?option=com_networkdevice");
}
