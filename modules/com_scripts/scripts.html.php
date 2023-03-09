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

/**
 *
 */
class HTML_scripts
{
    /**
     * @param  $command
     * @param  $results
     * @return void
     */
    public static function showScripts($command, $results): void
    {
        ?>
        <script type="text/javascript">
            function submitbutton(pressbutton) {
                if (pressbutton === 'cancel') {
                    hideMainMenu();

                    return;
                }
                if (pressbutton === 'ipfilteron') {
                    submitform(pressbutton);
                } else if (pressbutton === 'ipfilteroff') {
                    submitform(pressbutton);
                } else if (pressbutton === 'synchronizeFilter') {
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
                                    <th id="toolbar-ip-filter-on">
                                        <a href="javascript:submitbutton('ipfilteron');">
                                            <span title="<?php echo _("IP filter on"); ?>"
                                                  class="icon-32-ip-filter-on"></span>
                                            <?php echo _("IP filter on"); ?>
                                        </a>
                                    </th>

                                    <th id="toolbar-ip-filter-off">
                                        <a href="javascript:submitbutton('ipfilteroff');">
                                            <span title="<?php echo _("IP filter off"); ?>"
                                                  class="icon-32-ip-filter-off"></span>
                                            <?php echo _("IP filter off"); ?>
                                        </a>
                                    </th>

                                    <th id="toolbar-synchronize-ip-filter">
                                        <a href="javascript:submitbutton('synchronizeFilter');">
                                            <span title="<?php echo _("Synchronize IP filter"); ?>"
                                                  class="icon-32-synchronize-ip-filter"></span>
                                            <?php echo _("Synchronize IP filter"); ?>
                                        </a>
                                    </th>
                                </tr>
                            </table>
                        </div>

                        <div class="header icon-48-person">
                            <?php echo _("Script:"); ?> <?php echo ($command) ? _($command) : "N/A"; ?>
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
                        <div style="background-color: black; color: #eeeeee; height: 400px; overflow: scroll; text-align: left;">
                            <?php
                            foreach ($results as $result) {
                                ?>
                                <span style="color: #7CFC00;"><?php echo $result[0]; ?></span><br/>
                                <?php if (isset($result[1]) && $result[1]) { ?><span
                                        style="color: #a470FF;"><?php echo $result[1]; ?></span><br/><?php
                                } ?>
                                <?php if (isset($result[2]) && $result[2]) { ?><span
                                        style="color: #FD7F00;"><?php echo $result[2]; ?></span><br/><?php
                                } ?>
                                <?php
                            }
                            ?>
                        </div>
                        <input type="hidden" name="option" value="com_scripts"/>
                        <input type="hidden" name="task" value=""/>
                        <input type="hidden" name="hidemainmenu" value="0"/>
                    </form>
                </div>

                <div class="clr"></div>
            </div>

            <div class="clr"></div>
        </div>
        <?php
    }
} // End of HTML_scripts class
