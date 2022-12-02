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

class HTML_massmessages {
	/**
	 * showNetwork
	 * @param $networkTree
	 * @param $selectedNetwork
	 * @param $subNetworks
	 * @param $ipShowList
	 * @param $allNetworks
	 * @param $persons
	 */
	static function showNetwork(&$networkTree, &$selectedNetwork, &$subNetworks, &$ipShowList, &$persons) {
		global $core;
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
	function newMessage() {
		hideMainMenu();
		submitbutton('newMessage');
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
            <td id="toolbar-send-message">
              <a href="javascript:newMessage();">
                <span title="<?php echo _("New mass message"); ?>" class="icon-32-send"></span>
                <?php echo _("New mass message"); ?>
              </a>
            </td>
          </tr>
          </table>
        </div>

        <div class="header icon-48-mass-messages">
          <?php echo _("Mass messages"); ?>
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

	HTML_massmessages::buildTree("d", $networkTree);
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
         <th width="30%" class="title"><?php echo _("Description"); ?></th>
         <th width="20%" class="title"><?php echo _("Owner"); ?></th>
       </tr>
       </thead>
       <tbody>
       <tr>
         <td><?php echo $selectedNetworkParsed->network . " / " . $selectedNetworkParsed->bitmask; ?></td>
         <td><?php echo $selectedNetworkParsed->netmask; ?></td>
         <td><?php echo $selectedNetworkParsed->broadcast; ?></td>
         <td><?php echo $selectedNetwork->NE_description; ?></td>
         <td><?php echo $persons[$selectedNetwork->NE_personid]->PE_firstname . " ". $persons[$selectedNetwork->NE_personid]->PE_surname; ?></td>
       </tr>
       </table>
       <table class="adminlist">
       <thead>
        <tr>
         <th width="2%" class="title">#</th>
         <th width="2%" class="title"><input type="checkbox" name="toggle" value="" onClick="checkAll(<?php echo count($ipShowList); ?>);" /></th>
         <th width="15%" class="title"><?php echo _("IP"); ?></th>
         <th width="18%" class="title"><?php echo _("DNS"); ?></th>
         <th width="23%" class="title"><?php echo _("Recipient"); ?></th>
         <th width="20%" class="title"><?php echo _("E-Mail"); ?></th>
         <th width="20%" class="title"><?php echo _("Cell phone number"); ?></th>
       </tr>
       </thead>
       <tfoot>
       <tr>
       <td colspan="7">
       </td>
       </tr>
       </tfoot>
       <tbody>
<?php
	$k = 0;
	$iip = 0;
	$nid = null;
	foreach ($ipShowList as $ip) {
		if ($ip->IP_networkid != $nid) {
			$nid = $ip->IP_networkid;
			$subNetwork = $subNetworks[$nid];
			$ipv4 = new Net_IPv4();
			$subNetworkParsed = $ipv4->parseAddress($subNetwork->NE_net);
			$link = "javascript:show('$subNetwork->NE_networkid');";
			$networkOwner = $persons[$subNetwork->NE_personid];
?>
       <tr>
       <td colspan="7" style="padding: 0;">
        <table class="adminlist">
        <tbody>
        <tr>
         <td width="10%" class="title" style="background: none; background-color: #d5d5d5;"><strong><a href="<?php echo $link; ?>"><?php echo $subNetworkParsed->network . " / " . $subNetworkParsed->bitmask; ?></a></strong></td>
         <td width="10%" class="title" style="background: none; background-color: #d5d5d5;"><strong><?php echo $subNetwork->NE_description; ?></strong></td>
         <td width="10%" class="title" style="background: none; background-color: #d5d5d5;"><strong><?php echo $networkOwner->PE_firstname . " ". $networkOwner->PE_surname; ?></strong></td>
         <td width="70%" class="title" style="background: none; background-color: #d5d5d5;">&nbsp;</td>
       </tr>
       </tbody>
       </table>
       </td>
       </tr>
<?php
		}
		$person = $persons[$ip->IP_personid];
?>
       <tr class="<?php echo "row$k"; ?>">
         <td>
           <?php echo $iip+1; ?>
         </td>
         <td>
           <input type="checkbox" id="<?php echo "cb$iip"; ?>" name="cid[]" value="<?php echo $ip->IP_ipid; ?>" onclick="isChecked(this.checked);" />
         </td>
         <td>
           <?php echo $ip->IP_address; ?>
         </td>
         <td>
       <?php echo $ip->IP_dns; ?>
         </td>
         <td>
           <?php echo $person->PE_firstname . " " . $person->PE_surname; ?>
         </td>
         <td>
           <?php echo $person->PE_email; ?>
         </td>
         <td>
           <?php echo $person->PE_tel; ?>
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
   <input type="hidden" name="option" value="com_massmessages" />
   <input type="hidden" name="NE_networkid" value="<?php echo $selectedNetwork->NE_networkid; ?>" />
   <input type="hidden" name="PE_personid" value="" />
   <input type="hidden" name="IP_ipid" value="" />
   <input type="hidden" name="task" value="" />
   <input type="hidden" name="boxchecked" value="0" />
   <input type="hidden" name="hidemainmenu" value="0" />
   <input type="hidden" id="filter_NE_networkid" name="filter[NE_networkid]" value="<?php echo $selectedNetwork->NE_networkid; ?>" />
    </form>
    </div>

    <div class="clr"></div>
</div>

<div class="clr"></div>
</div>
<?php
	}
    static function newMessage(&$personsWithoutMobile, &$persons, &$inactivePersons) {
        global $core;
        $ipv4 = new Net_IPv4();
        ?>
        <script type="text/javascript">
            function sendMessage() {
                hideMainMenu();
                submitbutton('sendMessage');
            }
            function cancel() {
                submitbutton('cancel');
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
                                    <td id="toolbar-send-message">
                                        <a href="javascript:sendMessage();">
                                            <span title="<?php echo _("New mass message"); ?>" class="icon-32-send"></span>
                                            <?php echo _("New mass message"); ?>
                                        </a>
                                    </td>

                                    <td id="toolbar-cancel">
                                        <a href="javascript:cancel();">
                                            <span title="<?php echo _("Cancel"); ?>" class="icon-32-cancel"></span>
                                            <?php echo _("Cancel"); ?>
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="header icon-48-mass-messages">
                            <?php echo _("Mass messages"); ?>
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
                        <table class="adminform">
                            <tbody>
                            <tr><textarea cols="40" rows="3" name="message" style="width: 99%;"></textarea></tr>
                            <tr>
                                <td valign="top">
                                    <table class="adminlist">
                                        <thead>
                                        <tr>
                                            <th colspan="6"><?php echo _("Recipients to be notified"); ?></th>
                                        </tr>
                                        <tr>
                                            <th width="2%" class="title">#</th>
                                            <th width="2%" class="title"><input type="checkbox" name="toggle" value="" onClick="checkAll(<?php echo count($persons); ?>);" /></th>
                                            <th width="23%" class="title"><?php echo _("IP"); ?></th>
                                            <th width="23%" class="title"><?php echo _("Recipient"); ?></th>
                                            <th width="20%" class="title"><?php echo _("E-Mail"); ?></th>
                                            <th width="20%" class="title"><?php echo _("Cell phone number"); ?></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        $k = 0;
                                        $iip = 0;
                                        foreach ($persons as $person) {
                                            ?>
                                            <tr class="<?php echo "row$k"; ?>">
                                                <td>
                                                    <?php echo $iip+1; ?>
                                                </td>
                                                <td>
                                                    <input type="checkbox" id="<?php echo "cb$iip"; ?>" name="pid[]" value="<?php echo $person->PE_personid; ?>" onclick="isChecked(this.checked);" />
                                                </td>
                                                <td>
                                                    <?php echo $person->_IP->IP_address; ?>
                                                </td>
                                                <td>
                                                    <?php echo $person->PE_firstname . " " . $person->PE_surname; ?>
                                                </td>
                                                <td>
                                                    <?php echo $person->PE_email; ?>
                                                </td>
                                                <td>
                                                    <?php echo $person->PE_tel; ?>
                                                </td>
                                            </tr>
                                            <?php
                                            $k = 1 - $k;
                                            $iip++;
                                        }
                                        ?>
                                        </tbody>
                                    </table>
                                    <table class="adminlist">
                                        <thead>
                                        <tr>
                                            <th colspan="6"><?php echo _("Recipients without valid mobile contact"); ?></th>
                                        </tr>
                                        <tr>
                                            <th width="2%" class="title">#</th>
                                            <th width="23%" class="title"><?php echo _("IP"); ?></th>
                                            <th width="23%" class="title"><?php echo _("Recipient"); ?></th>
                                            <th width="20%" class="title"><?php echo _("E-Mail"); ?></th>
                                            <th width="20%" class="title"><?php echo _("Cell phone number"); ?></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        $k = 0;
                                        $iip = 0;
                                        foreach ($personsWithoutMobile as $person) {
                                            ?>
                                            <tr class="<?php echo "row$k"; ?>">
                                                <td>
                                                    <?php echo $iip+1; ?>
                                                </td>
                                                <td>
                                                    <?php echo $person->_IP->IP_address; ?>
                                                </td>
                                                <td>
                                                    <?php echo $person->PE_firstname . " " . $person->PE_surname; ?>
                                                </td>
                                                <td>
                                                    <?php echo $person->PE_email; ?>
                                                </td>
                                                <td>
                                                    <?php echo $person->PE_tel; ?>
                                                </td>
                                            </tr>
                                            <?php
                                            $k = 1 - $k;
                                            $iip++;
                                        }
                                        ?>
                                        </tbody>
                                    </table>
                                    <table class="adminlist">
                                        <thead>
                                        <tr>
                                            <th colspan="6"><?php echo _("Inactive recipients"); ?></th>
                                        </tr>
                                        <tr>
                                            <th width="2%" class="title">#</th>
                                            <th width="23%" class="title"><?php echo _("IP"); ?></th>
                                            <th width="23%" class="title"><?php echo _("Recipient"); ?></th>
                                            <th width="20%" class="title"><?php echo _("E-Mail"); ?></th>
                                            <th width="20%" class="title"><?php echo _("Cell phone number"); ?></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        $k = 0;
                                        $iip = 0;
                                        foreach ($inactivePersons as $person) {
                                            ?>
                                            <tr class="<?php echo "row$k"; ?>">
                                                <td>
                                                    <?php echo $iip+1; ?>
                                                </td>
                                                <td>
                                                    <?php echo $person->_IP->IP_address; ?>
                                                </td>
                                                <td>
                                                    <?php echo $person->PE_firstname . " " . $person->PE_surname; ?>
                                                </td>
                                                <td>
                                                    <?php echo $person->PE_email; ?>
                                                </td>
                                                <td>
                                                    <?php echo $person->PE_tel; ?>
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
                        <input type="hidden" name="option" value="com_massmessages" />
                        <input type="hidden" name="task" value="" />
                        <input type="hidden" name="boxchecked" value="0" />
                        <input type="hidden" name="hidemainmenu" value="0" />
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
	static function buildTree($treeClassName, $networkTree) {
		foreach ($networkTree as $network) {
			$pId = $network->NE_parent_networkid;
			if ($pId == 0) $pId = -1;

			echo $treeClassName . ".add($network->NE_networkid, $pId, '$network->NE_net', 'javascript:show($network->NE_networkid);', '$network->NE_description');\n";

			if ($network->child != null) HTML_massmessages::buildTree($treeClassName, $network->child);
		}

	}
} // End of HTML_network class
?>
