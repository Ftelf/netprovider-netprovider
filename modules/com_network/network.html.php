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

class HTML_Network
{
    /**
     * showNetwork
     * @param $networkTree
     * @param $selectedNetwork
     * @param $subNetworks
     * @param $ipList
     * @param $ipShowList
     * @param $persons
     * @param $pageNav
     * @param $filter
     * @param $flags
     */
    static function showNetwork(&$networkTree, &$selectedNetwork, &$subNetworks, &$ipList, &$ipShowList, &$persons, &$pageNav, &$filter, &$flags)
    {
        $ipv4 = new Net_IPv4();
        $selectedNetworkParsed = $ipv4->parseAddress($selectedNetwork->NE_net);
        ?>
        <script type="text/javascript">
            function show(id) {
                var form = document.adminForm;
                form.NE_networkid.value = id;
                var se = document.getElementById('filter_NE_networkid');
                se.value = id;
                submitform('show');
            }

            function editI(id) {
                var form = document.adminForm;
                form.IP_ipid.value = id;
                hideMainMenu();
                submitform('editI');
            }

            function editN() {
                var form = document.adminForm;
                //form.NE_networkid.value = id;
                hideMainMenu();
                submitform('editN');
            }

            function editP(id) {
                var form = document.adminForm;
                form.option.value = 'com_person';
                form.PE_personid.value = id;
                hideMainMenu();
                submitform('edit');
            }

            function newI() {
                hideMainMenu();
                submitbutton('newI');
            }

            function newN() {
                hideMainMenu();
                submitbutton('newN');
            }

            function editIA() {
                if (document.adminForm.boxchecked.value == 0) {
                    alert("<?php echo _("Please select record to edit"); ?>");
                } else {
                    hideMainMenu();
                    submitbutton('editA');
                }
            }

            function removeI() {
                if (document.adminForm.boxchecked.value == 0) {
                    alert('<?php echo _("Please select record to delete"); ?>');
                } else {
                    if (window.confirm("<?php echo _("Do you really want to delete selected records ?"); ?>")) {
                        submitbutton('removeI');
                    }
                }
            }

            function removeN() {
                if (window.confirm("<?php echo _("Do you really want to delete actual selected network ?"); ?>")) {
                    submitbutton('removeN');
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
                                    <?php
                                    if ($flags['net_new']) {
                                        ?>
                                        <td id="toolbar-new-network">
                                            <a href="javascript:newN();">
                                                <span title="<?php echo _("New network"); ?>"
                                                      class="icon-32-new-network"></span>
                                                <?php echo _("New network"); ?>
                                            </a>
                                        </td>
                                        <?php
                                    } else {
                                        ?>
                                        <td id="toolbar-new-network">
                                            <div class="toolbar-disabled">
                                                <span title="<?php echo _("New network"); ?>"
                                                      class="icon-32-new-network"></span>
                                                <?php echo _("New network"); ?>
                                            </div>
                                        </td>
                                        <?php
                                    }
                                    if ($flags['net_edit']) {
                                        ?>
                                        <td id="toolbar-edit-network">
                                            <a href="javascript:editN();">
                                                <span title="<?php echo _("Edit network"); ?>"
                                                      class="icon-32-edit-network"></span>
                                                <?php echo _("Edit network"); ?>
                                            </a>
                                        </td>
                                        <?php
                                    } else {
                                        ?>
                                        <td id="toolbar-edit-network">
                                            <div class="toolbar-disabled">
                                                <span title="<?php echo _("Edit network"); ?>"
                                                      class="icon-32-edit-network"></span>
                                                <?php echo _("Edit network"); ?>
                                            </div>
                                        </td>
                                        <?php
                                    }
                                    if ($flags['net_delete']) {
                                        ?>
                                        <td id="toolbar-delete-network">
                                            <a href="javascript:removeN();">
                                                <span title="<?php echo _("Delete network"); ?>"
                                                      class="icon-32-delete-network"></span>
                                                <?php echo _("Delete network"); ?>
                                            </a>
                                        </td>
                                        <?php
                                    } else {
                                        ?>
                                        <td id="toolbar-delete-network">
                                            <div class="toolbar-disabled">
                                                <span title="<?php echo _("Delete network"); ?>"
                                                      class="icon-32-delete-network"></span>
                                                <?php echo _("Delete network"); ?>
                                            </div>
                                        </td>
                                        <?php
                                    }
                                    if ($flags['ip_new']) {
                                        ?>
                                        <td id="toolbar-new-ip">
                                            <a href="javascript:newI();">
                                                <span title="<?php echo _("New IP address"); ?>"
                                                      class="icon-32-new-ip"></span>
                                                <?php echo _("New IP address"); ?>
                                            </a>
                                        </td>
                                        <?php
                                    } else {
                                        ?>
                                        <td id="toolbar-new-ip">
                                            <div class="toolbar-disabled">
                                                <span title="<?php echo _("New IP address"); ?>"
                                                      class="icon-32-new-ip"></span>
                                                <?php echo _("New IP address"); ?>
                                            </div>
                                        </td>
                                        <?php
                                    }
                                    if ($flags['ip_edit']) {
                                        ?>
                                        <td id="toolbar-edit-ip">
                                            <a href="javascript:editIA();">
                                                <span title="<?php echo _("Edit IP address"); ?>"
                                                      class="icon-32-edit-ip"></span>
                                                <?php echo _("Edit IP address"); ?>
                                            </a>
                                        </td>
                                        <?php
                                    } else {
                                        ?>
                                        <td id="toolbar-edit-ip">
                                            <div class="toolbar-disabled">
                                                <span title="<?php echo _("Edit IP address"); ?>"
                                                      class="icon-32-edit-ip"></span>
                                                <?php echo _("Edit IP address"); ?>
                                            </div>
                                        </td>
                                        <?php
                                    }
                                    if ($flags['ip_delete']) {
                                        ?>
                                        <td id="toolbar-delete-ip">
                                            <a href="javascript:removeI();">
                                                <span title="<?php echo _("Delete IP address"); ?>"
                                                      class="icon-32-delete-ip"></span>
                                                <?php echo _("Delete IP address"); ?>
                                            </a>
                                        </td>
                                        <?php
                                    } else {
                                        ?>
                                        <td id="toolbar-delete-ip">
                                            <div class="toolbar-disabled">
                                                <span title="<?php echo _("Delete IP address"); ?>"
                                                      class="icon-32-delete-ip"></span>
                                                <?php echo _("Delete IP address"); ?>
                                            </div>
                                        </td>
                                        <?php
                                    }
                                    ?>
                                </tr>
                            </table>
                        </div>

                        <div class="header icon-48-network">
                            <?php echo _("IP network management"); ?>
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
                                <td><?php echo str_replace(" ", "&nbsp;", _("Show IP network headers")); ?></td>
                                <td><input type="checkbox" name="filter[netheaders]" value="checked"
                                           onChange="document.adminForm.submit();" <?php if ($filter['netheaders'] == 'checked') echo 'checked="checked"'; ?> />
                                </td>
                            </tr>
                        </table>

                        <table class="adminform">
                            <tbody>
                            <tr>
                                <td width="150" valign="top">
                                    <script type="text/javascript">
                                        <!--
                                        d = new dTree('d');
                                        <?php
                                        global $treeId, $lastParent;
                                        $treeId = 0;
                                        $lastParent = -1;

                                        HTML_Network::buildTree("d", $networkTree);
                                        $linkPerson = "javascript:editP('" . $persons[$selectedNetwork->NE_personid]->PE_personid . "');";

                                        ?>
                                        d.closeAll();
                                        document.write(d);
                                        d.openTo(<?php echo $selectedNetwork->NE_networkid; ?>, true);
                                        //-->
                                    </script>
                                </td>
                                <td valign="top">
                                    <table class="adminlist">
                                        <thead>
                                        <tr>
                                            <th width="10%" class="title"><?php echo _("Selected network"); ?></th>
                                            <th width="10%" class="title"><?php echo _("Netmask"); ?></th>
                                            <th width="10%" class="title"><?php echo _("Broadcast"); ?></th>
                                            <th width="24%" class="title"><?php echo _("Description"); ?></th>
                                            <th width="46%" class="title"><?php echo _("Owner"); ?></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td><?php echo $selectedNetworkParsed->network . " / " . $selectedNetworkParsed->bitmask; ?></td>
                                            <td><?php echo $selectedNetworkParsed->netmask; ?></td>
                                            <td><?php echo $selectedNetworkParsed->broadcast; ?></td>
                                            <td><?php echo $selectedNetwork->NE_description; ?></td>
                                            <td>
                                                <a href="<?php echo $linkPerson; ?>"><?php echo $persons[$selectedNetwork->NE_personid]->PE_firstname . " " . $persons[$selectedNetwork->NE_personid]->PE_surname; ?></a>
                                            </td>
                                        </tr>
                                    </table>
                                    <table class="adminlist">
                                        <thead>
                                        <tr>
                                            <th width="2%" class="title">#</th>
                                            <th width="2%" class="title"><input type="checkbox" name="toggle" value=""
                                                                                onClick="checkAll(<?php echo $pageNav->limit; ?>);"/>
                                            </th>
                                            <th width="20%" class="title"><?php echo _("IP"); ?></th>
                                            <th width="30%" class="title"><?php echo _("DNS"); ?></th>
                                            <th width="46%" class="title"><?php echo _("Owner"); ?></th>
                                        </tr>
                                        </thead>
                                        <tfoot>
                                        <tr>
                                            <td colspan="6">
                                                <?php
                                                echo $pageNav->getListFooter();
                                                ?>
                                            </td>
                                        </tr>
                                        </tfoot>
                                        <tbody>
                                        <?php
                                        $k = 0;
                                        $iip = 0;
                                        $nid = null;
                                        foreach ($ipShowList as $ip) {
                                            $linkPerson = "javascript:editP('" . $persons[$ip->IP_personid]->PE_personid . "');";
                                            if ($ip->IP_networkid != $nid & $filter['netheaders'] == 'checked') {
                                                $nid = $ip->IP_networkid;
                                                $subNetwork = $subNetworks[$nid];
                                                $ipv4 = new Net_IPv4();
                                                $subNetworkParsed = $ipv4->parseAddress($subNetwork->NE_net);
                                                $maxip = pow(2, 32 - $subNetworkParsed->bitmask) - 2;
                                                $freeip = $maxip - count($ipList[$nid]);
                                                $link = "javascript:show('$subNetwork->NE_networkid');";
                                                ?>
                                                <tr>
                                                    <td colspan="5" style="padding: 0;">
                                                        <table class="adminlist">
                                                            <tbody>
                                                            <tr>
                                                                <td width="15%" class="title"
                                                                    style="background-color: #d5d5d5;">
                                                                    <strong>XX<a href="<?php echo $link; ?>"><?php echo $subNetworkParsed->network . " / " . $subNetworkParsed->bitmask; ?></a></strong>
                                                                </td>
                                                                <td width="15%" class="title"
                                                                    style="background-color: #d5d5d5;">
                                                                    <strong><?php echo $subNetworkParsed->netmask; ?></strong>
                                                                </td>
                                                                <td width="15%" class="title"
                                                                    style="background-color: #d5d5d5;">
                                                                    <strong><?php echo $subNetworkParsed->broadcast; ?></strong>
                                                                </td>
                                                                <td width="15%" class="title"
                                                                    style="background-color: #d5d5d5;">
                                                                    <strong><?php echo $freeip . " z " . $maxip . " IP volných"; ?></strong>
                                                                </td>
                                                                <td width="20%" class="title"
                                                                    style="background-color: #d5d5d5;">
                                                                    <strong><?php echo $subNetwork->NE_description; ?></strong>
                                                                </td>
                                                                <td width="20%" class="title"
                                                                    style="background-color: #d5d5d5;">
                                                                    <strong><?php echo $persons[$subNetwork->NE_personid]->PE_firstname . " " . $persons[$subNetwork->NE_personid]->PE_surname; ?></strong>
                                                                </td>
                                                            </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                                <?php
                                            }
                                            $iPAddressLink = "javascript:editI('$ip->IP_ipid');";
                                            ?>
                                            <tr class="<?php echo "row$k"; ?>">
                                                <td>
                                                    <?php echo $iip + 1 + $pageNav->limitstart; ?>
                                                </td>
                                                <td>
                                                    <input type="checkbox" id="<?php echo "cb$iip"; ?>" name="cid[]"
                                                           value="<?php echo $ip->IP_ipid; ?>"
                                                           onclick="isChecked(this.checked);"/>
                                                </td>
                                                <td>
                                                    <a href="<?php echo $iPAddressLink; ?>"><?php echo $ip->IP_address; ?></a>
                                                </td>
                                                <td>
                                                    <?php echo $ip->IP_dns; ?>
                                                </td>
                                                <td>
                                                    <a href="<?php echo $linkPerson; ?>">
                                                        <?php echo $persons[$ip->IP_personid]->PE_firstname . " " . $persons[$ip->IP_personid]->PE_surname; ?>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php
                                            $k = 1 - $k;
                                            $iip++;
                                        }
                                        ?>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <input type="hidden" name="option" value="com_network"/>
                        <input type="hidden" name="NE_networkid" value="<?php echo $selectedNetwork->NE_networkid; ?>"/>
                        <input type="hidden" name="PE_personid" value=""/>
                        <input type="hidden" name="IP_ipid" value=""/>
                        <input type="hidden" name="task" value=""/>
                        <input type="hidden" name="boxchecked" value="0"/>
                        <input type="hidden" name="hidemainmenu" value="0"/>
                        <input type="hidden" id="filter_NE_networkid" name="filter[NE_networkid]"
                               value="<?php echo $selectedNetwork->NE_networkid; ?>"/>
                    </form>
                </div>

                <div class="clr"></div>
            </div>

            <div class="clr"></div>
        </div>
        <?php
    }

    /**
     * editIP
     * @param $ip
     * @param $network
     * @param $addresses
     * @param $persons
     */
    static function editIP($ip, $network, $addresses, $persons)
    {
        $ipv4 = new Net_IPv4();

        $net = $ipv4->parseAddress($network->NE_net);
        ?>
        <script type="text/javascript">
            function submitbutton(pressbutton) {
                var form = document.adminForm;
                if (pressbutton == 'cancel') {
                    submitform(pressbutton);
                    return;
                }

                // do field validation
                if (trim(form.IP_address.value) == "0") {
                    alert("<?php echo _("Please select IP address"); ?>");
                } else if (form.IP_personid.value == "0") {
                    alert("<?php echo _("Please select owner"); ?>");
                } else {
                    if (pressbutton == 'apply') {
                        hideMainMenu();
                        submitform('applyI');
                    } else if (pressbutton == 'save') {
                        submitform('saveI');
                    }
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
                                    <td id="toolbar-apply">
                                        <a href="javascript:submitbutton('apply');">
                                            <span title="<?php echo _("Apply"); ?>" class="icon-32-apply"></span>
                                            <?php echo _("Apply"); ?>
                                        </a>
                                    </td>

                                    <td id="toolbar-save">
                                        <a href="javascript:submitbutton('save');">
                                            <span title="<?php echo _("Save"); ?>" class="icon-32-save"></span>
                                            <?php echo _("Save"); ?>
                                        </a>
                                    </td>

                                    <td id="toolbar-cancel">
                                        <a href="javascript:submitbutton('cancel');">
                                            <span title="<?php echo _("Cancel"); ?>" class="icon-32-cancel"></span>
                                            <?php echo _("Cancel"); ?>
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="header icon-48-network">
                            <?php echo _("IP network management"); ?>:
                            <small><?php echo _("IP address edit"); ?></small>
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
                        <table width="100%">
                            <tr>
                                <td width="360" valign="top">
                                    <table class="adminform">
                                        <thead>
                                        <tr>
                                            <th colspan="2"><?php echo _("Network"); ?></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td width="150"><?php echo _("Network address:"); ?></td>
                                            <td width="205"><input type="text" name="PE_firstname" class="width-form"
                                                                   size="40" value="<?php echo $net->network; ?>"
                                                                   disabled="disabled"/></td>
                                        </tr>
                                        <tr>
                                            <td><?php echo _("Netmask:"); ?></td>
                                            <td><input type="text" name="PE_surname" class="width-form" size="40"
                                                       value="<?php echo $net->netmask; ?>" disabled="disabled"/></td>
                                        </tr>
                                        <tr>
                                            <td><?php echo _("Owner:"); ?></td>
                                            <td><input type="text" name="PE_surname" class="width-form" size="40"
                                                       value="<?php echo $persons[$network->NE_personid]->PE_firstname . ' ' . $persons[$network->NE_personid]->PE_surname; ?>"
                                                       disabled="disabled"/></td>
                                        </tr>
                                        </tbody>
                                    </table>

                                    <br/>

                                    <table class="adminform">
                                        <thead>
                                        <tr>
                                            <th colspan="2"><?php echo _("IPv4 address"); ?></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td width="150"><?php echo _("IP Address:"); ?></td>
                                            <td width="205">
                                                <?php
                                                if ($addresses == null) {
                                                    ?>
                                                    <input type="text" name="IP_address" class="width-form" size="40"
                                                           value="<?php echo $ip->IP_address; ?>"/>
                                                    <?php
                                                } else {
                                                    ?>
                                                    <select name="IP_address" class="width-form">
                                                        <?php
                                                        foreach ($addresses as $address) {
                                                            ?>
                                                            <option value="<?php echo $address; ?>"<?php echo ($ip->IP_address == $address) ? ' selected="selected"' : ""; ?>><?php echo $address; ?></option>
                                                            <?php
                                                        }
                                                        ?>
                                                    </select>
                                                    <?php
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><?php echo _("DNS:"); ?></td>
                                            <td><input type="text" name="IP_dns" class="width-form" size="40"
                                                       value="<?php echo $ip->IP_dns; ?>"/></td>
                                        </tr>
                                        </tbody>
                                    </table>

                                </td>
                                <td width="10">
                                    &nbsp;
                                </td>
                                <td width="360" valign="top">
                                    <table class="adminform">
                                        <thead>
                                        <tr>
                                            <th colspan="2"><?php echo _("Owner"); ?></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td width="150"><?php echo _("Owner name:"); ?></td>
                                            <td width="205">
                                                <select name="IP_personid" class="width-form">
                                                    <?php if ($ip->IP_personid == null) { ?>
                                                        <option value="0"
                                                                selected="selected"><?php echo _("- Choose owner -"); ?></option><?php } ?>
                                                    <?php
                                                    foreach ($persons as $person) {
                                                        ?>
                                                        <option value="<?php echo $person->PE_personid; ?>"<?php echo ($ip->IP_personid == $person->PE_personid) ? ' selected="selected"' : ""; ?>><?php echo $person->PE_surname . " " . $person->PE_firstname . " " . ($person->PE_nick); ?></option>
                                                        <?php
                                                    }
                                                    ?>
                                                </select>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                                <td>
                                    &nbsp;
                                </td>
                            </tr>
                        </table>
                        <input type="hidden" name="IP_networkid" value="<?php echo $network->NE_networkid; ?>"/>
                        <input type="hidden" name="NE_networkid" value="<?php echo $network->NE_networkid; ?>"/>
                        <input type="hidden" name="IP_ipid" value="<?php echo $ip->IP_ipid; ?>"/>
                        <input type="hidden" name="option" value="com_network"/>
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

    /**
     * editNet
     * @param $network
     * @param $parentNetwork
     * @param $possibleNetworkArray
     * @param $directSubNetworks
     * @param $persons
     * @param $flags
     */
    static function editNet($network, $parentNetwork, $possibleNetworkArray, $directSubNetworks, $persons, &$flags)
    {
        $ipv4 = new Net_IPv4();
        $parentNetworkParsed = $ipv4->parseAddress($parentNetwork->NE_net);
        ?>
        <script type="text/javascript">
            function submitbutton(pressbutton) {
                var form = document.adminForm;
                if (pressbutton == 'cancel') {
                    submitform(pressbutton);
                    return;
                }

                // do field validation
                if (trim(form.NE_net.value) == "0") {
                    alert("<?php echo _("Please select network"); ?>");
                } else if (form.NE_personid.value == "0") {
                    alert("<?php echo _("Please select owner"); ?>");
                } else {
                    if (pressbutton == 'apply') {
                        hideMainMenu();
                        submitform('applyN');
                    } else if (pressbutton == 'save') {
                        submitform('saveN');
                    }
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
                                    <td id="toolbar-apply">
                                        <a href="javascript:submitbutton('apply');">
                                            <span title="<?php echo _("Apply"); ?>" class="icon-32-apply"></span>
                                            <?php echo _("Apply"); ?>
                                        </a>
                                    </td>

                                    <td id="toolbar-save">
                                        <a href="javascript:submitbutton('save');">
                                            <span title="<?php echo _("Save"); ?>" class="icon-32-save"></span>
                                            <?php echo _("Save"); ?>
                                        </a>
                                    </td>

                                    <td id="toolbar-cancel">
                                        <a href="javascript:submitbutton('cancel');">
                                            <span title="<?php echo _("Cancel"); ?>" class="icon-32-cancel"></span>
                                            <?php echo _("Cancel"); ?>
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="header icon-48-network">
                            <?php echo _("IP network management"); ?>: <small><?php echo _("Network edit"); ?></small>
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
                        <table width="100%">
                            <tr>
                                <td width="360" valign="top">
                                    <table class="adminform">
                                        <thead>
                                        <tr>
                                            <th colspan="2"><?php echo _("Parent network"); ?></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td width="150"><?php echo _("Network address:"); ?></td>
                                            <td width="205"><input type="text" name="void" class="width-form" size="40"
                                                                   value="<?php echo $parentNetworkParsed->network; ?>"
                                                                   disabled="disabled"/></td>
                                        </tr>
                                        <tr>
                                            <td><?php echo _("Netmask:"); ?></td>
                                            <td><input type="text" name="void" class="width-form" size="40"
                                                       value="<?php echo $parentNetworkParsed->netmask; ?>"
                                                       disabled="disabled"/></td>
                                        </tr>
                                        <tr>
                                            <td><?php echo _("Description:"); ?></td>
                                            <td><input type="text" name="void" class="width-form" size="40"
                                                       value="<?php echo $parentNetwork->NE_description; ?>"
                                                       disabled="disabled"/></td>
                                        </tr>
                                        <tr>
                                            <td><?php echo _("Owner:"); ?></td>
                                            <td><input type="text" name="void" class="width-form" size="40"
                                                       value="<?php echo $persons[$parentNetwork->NE_personid]->PE_firstname . " " . $persons[$parentNetwork->NE_personid]->PE_surname . ' (' . $persons[$parentNetwork->NE_personid]->PE_nick . ')'; ?>"
                                                       disabled="disabled"/></td>
                                        </tr>
                                        </tbody>
                                    </table>

                                    <br/>

                                    <table class="adminform">
                                        <thead>
                                        <tr>
                                            <th colspan="2"><?php echo _("Subnet"); ?></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td width="150"><?php echo _("Network address:"); ?></td>
                                            <td width="205">
                                                <?php
                                                // If network address edit is disabled, we need hidden form element to store this number
                                                //
                                                if ($flags['NE_net'] == "DISABLED") {
                                                    echo '<input type="hidden" name="NE_net" value="' . $network->NE_net . '" />' . "\n";
                                                    echo '<input type="text" name="NE_net" class="width-form" size="40" value="' . $network->NE_net . '" disabled="disabled" />' . "\n";
                                                } else if ($flags['NE_net'] == "TEXTBOX") {
                                                    echo '<input type="text" name="NE_net" class="width-form" size="40" value="' . $network->NE_net . '" />' . "\n";
                                                } else {
                                                    ?>
                                                    <select name="NE_net" class="width-form">
                                                        <?php if ($network->NE_networkid == null) { ?>
                                                            <option value="0" selected="selected">- Vyber síť -
                                                            </option><?php echo "\n";
                                                        }
                                                        foreach ($possibleNetworkArray as $possibleNetwork) {
                                                            echo '<option value="' . $possibleNetwork . '"';
                                                            if ($possibleNetwork == $network->NE_net) echo ' selected="selected"';
                                                            echo ">$possibleNetwork</option>\n";
                                                        }
                                                        ?>
                                                    </select>
                                                    <?php
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><?php echo _("Description:"); ?></td>
                                            <td><input type="text" name="NE_description" class="width-form" size="40"
                                                       value="<?php echo $network->NE_description; ?>"/></td>
                                        </tr>
                                        <tr>
                                            <td><?php echo _("Owner name:"); ?></td>
                                            <td>
                                                <select name="NE_personid" class="width-form">
                                                    <?php if ($network->NE_personid == null) { ?>
                                                        <option value="0"
                                                                selected="selected"><?php echo _("- Choose owner -"); ?></option><?php echo "\n";
                                                    } ?>
                                                    <?php
                                                    foreach ($persons as $person) {
                                                        echo '<option value="' . $person->PE_personid . '"';
                                                        if ($network->NE_personid == $person->PE_personid) echo ' selected="selected"';
                                                        echo ">$person->PE_surname $person->PE_firstname ($person->PE_nick)</option>\n";
                                                    }
                                                    ?>
                                                </select>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                                <td width="10">
                                    &nbsp;
                                </td>
                                <td valign="top">
                                    <table class="adminform">
                                        <thead>
                                        <tr>
                                            <th colspan="4"><?php echo _("Subnets in parent network"); ?></th>
                                        </tr>
                                        <tr>
                                            <th width="20%" class="title2"><?php echo _("Network address"); ?></th>
                                            <th width="20%" class="title2"><?php echo _("Netmask"); ?></th>
                                            <th width="40%" class="title2"><?php echo _("Description"); ?></th>
                                            <th width="20%" class="title2"><?php echo _("Owner"); ?></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        foreach ($directSubNetworks as $subNetwork) {
                                            $subNetworkParsed = $ipv4->parseAddress($subNetwork->NE_net);
                                            ?>
                                            <tr>
                                                <td><?php echo $subNetwork->NE_net; ?></td>
                                                <td><?php echo $subNetworkParsed->netmask; ?></td>
                                                <td><?php echo $subNetwork->NE_description; ?></td>
                                                <td><?php echo $persons[$subNetwork->NE_personid]->PE_surname . " " . $persons[$subNetwork->NE_personid]->PE_firstname; ?></td>
                                            </tr>
                                            <?php
                                        }
                                        ?>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </table>
                        <input type="hidden" name="NE_parent_networkid"
                               value="<?php echo $parentNetwork->NE_networkid; ?>"/>
                        <input type="hidden" name="NE_networkid" value="<?php echo $network->NE_networkid; ?>"/>
                        <input type="hidden" name="option" value="com_network"/>
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

    /**
     * buildTree
     * @param $treeClassName
     * @param $networkTree
     */
    static function buildTree($treeClassName, $networkTree)
    {
        foreach ($networkTree as $network) {
            $pId = $network->NE_parent_networkid;
            if ($pId == 0) $pId = -1;

            echo $treeClassName . ".add($network->NE_networkid, $pId, '$network->NE_net', 'javascript:show($network->NE_networkid);', '$network->NE_description');\n";

            if ($network->child != null) HTML_Network::buildTree($treeClassName, $network->child);
        }

    }
} // End of HTML_network class
?>
