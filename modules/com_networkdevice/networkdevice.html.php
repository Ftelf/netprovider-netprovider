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

class HTML_NetworkDevice
{
    /**
     * showNetworkDevice
     *
     * @param $networkDevice
     */
    static function showNetworkDevice(&$networkDevice)
    {
        ?>
        <script type="text/javascript">
            function submitbutton(pressbutton) {
                if (pressbutton == 'testLogin') {
                    submitform(pressbutton);

                }
            }
        </script>

        <div id="content-box">
            <div class="padding">
                <div id="toolbar-box">
                    <div class="t">
                        <div class="t">
                            <div class="t"></div>
                        </div>
                    </div>

                    <div class="m">
                        <div id="toolbar" class="toolbar">
                            <table class="toolbar">
                                <tr>
                                    <td id="toolbar-test-login">
                                        <a href="javascript:submitbutton('testLogin');">
                                            <span title="<?php echo _("Test login"); ?>"
                                                  class="icon-32-test-login"></span>
                                            <?php echo _("Test login"); ?>
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="header icon-48-network-device">
                            <?php echo _("Network device"); ?>
                        </div>

                        <div class="clr"></div>
                    </div>
                    <div class="b">
                        <div class="b">
                            <div class="b"></div>
                        </div>
                    </div>
                </div>

                <div class="clr"></div>

                <div id="element-box">
                    <form action="index2.php" method="post" name="adminForm">
                        <table>
                            <tr>
                                <td width="360" valign="top">
                                    <table class="adminform">
                                        <thead>
                                        <tr>
                                            <th colspan="2"><?php echo _("Network Device"); ?></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td width="150"><?php echo _("Platform:"); ?></td>
                                            <td width="205"><?php echo $networkDevice[Core::NETWORK_DEVICE_PLATFORM]; ?></td>
                                        </tr>
                                        <tr>
                                            <td width="150"><?php echo _("Host:"); ?></td>
                                            <td width="205"><?php echo $networkDevice[Core::NETWORK_DEVICE_HOST]; ?></td>
                                        </tr>
                                        <tr>
                                            <td width="150"><?php echo _("Port:"); ?></td>
                                            <td width="205"><?php echo $networkDevice[Core::NETWORK_DEVICE_PORT]; ?></td>
                                        </tr>
                                        <tr>
                                            <td width="150"><?php echo _("Login:"); ?></td>
                                            <td width="205"><?php echo $networkDevice[Core::NETWORK_DEVICE_LOGIN]; ?></td>
                                        </tr>
                                        <tr>
                                            <td width="150"><?php echo _("Password:"); ?></td>
                                            <td width="205"><?php echo $networkDevice[Core::NETWORK_DEVICE_PASSWORD]; ?></td>
                                        </tr>
                                        <tr>
                                            <td width="150"><?php echo _("WAN Interface:"); ?></td>
                                            <td width="205"><?php echo $networkDevice[Core::NETWORK_DEVICE_WAN_INTERFACE]; ?></td>
                                        </tr>
                                        <tr>
                                            <td width="150"><?php echo _("Command sudo:"); ?></td>
                                            <td width="205"><?php echo $networkDevice[Core::NETWORK_DEVICE_COMMAND_SUDO]; ?></td>
                                        </tr>
                                        <tr>
                                            <td width="150"><?php echo _("Command iptables:"); ?></td>
                                            <td width="205"><?php echo $networkDevice[Core::NETWORK_DEVICE_COMMAND_IPTABLES]; ?></td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </table>
                        <input type="hidden" name="option" value="com_networkdevice"/>
                        <input type="hidden" name="task" value=""/>
                    </form>
                </div>

                <div class="clr"></div>
            </div>

            <div class="clr"></div>
        </div>
        <?php
    }
} // End of HTML_NetworkDevice class
?>
