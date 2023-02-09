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

class HTML_NetworkDevice {
	/**
	 * showNetworkDevices
	 * @param $networkDevices
	 * @param $filter
	 * @param $pageNav
	 */
	static function showNetworkDevices(&$networkDevices, &$filter, &$pageNav) {
		global $core;
?>
<script type="text/javascript">
	function edit(id) {
    	document.adminForm.ND_networkdeviceid.value = id;
    	hideMainMenu();
   		submitform('edit');
	}
	function newN() {
		hideMainMenu();
		submitbutton('new');
  	}
  	function editA() {
		if (document.adminForm.boxchecked.value == 0) {
			alert('<?php echo _("Please select record to edit"); ?>');
		} else {
			hideMainMenu();
			submitbutton('editA');
		}
  	}
  	function remove() {
		if (document.adminForm.boxchecked.value == 0) {
			alert('<?php echo _("Please select record to delete"); ?>');
		} else {
			if (window.confirm("<?php echo _("Do you really want to delete selected records ?"); ?>")) {
				submitbutton('remove');
			}
		}
  	}
  	function toggleHide(id) {
  		var image = document.getElementById('toggle-img-' + id);
  		var td1Div = document.getElementById('toggle-td1-' + id);
  		var td2Div = document.getElementById('toggle-td2-' + id);
  		var td3Div = document.getElementById('toggle-td3-' + id);

  		if (image.toggle) {
  			image.src = "images/22x22/actions/2downarrow.png";
  			td1Div.style.height = "70px";
  			td1Div.style.overflow = "hidden";
  			td2Div.style.height = "70px";
  			td2Div.style.overflow = "hidden";
  			td3Div.style.height = "70px";
  			td3Div.style.overflow = "hidden";

  			image.toggle = false;
  		} else {
  			image.src = "images/22x22/actions/2uparrow.png";
  			td1Div.style.height = "auto";
  			td1Div.style.overflow = "";
  			td2Div.style.height = "auto";
  			td2Div.style.overflow = "";
  			td3Div.style.height = "auto";
  			td3Div.style.overflow = "";

  			image.toggle = true;
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
            <td id="toolbar-new">
              <a href="javascript:newN();">
                <span title="<?php echo _("New"); ?>" class="icon-32-new"></span>
                <?php echo _("New"); ?>
              </a>
            </td>

            <td id="toolbar-edit">
              <a href="javascript:editA();">
                <span title="<?php echo _("Edit"); ?>" class="icon-32-edit"></span>
                <?php echo _("Edit"); ?>
              </a>
            </td>

            <td id="toolbar-delete">
              <a href="javascript:remove();">
                <span title="<?php echo _("Delete"); ?>" class="icon-32-delete"></span>
                <?php echo _("Delete"); ?>
              </a>
            </td>
          </tr>
          </table>
        </div>

        <div class="header icon-48-network-device">
          <?php echo _("Network devices management"); ?>
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
      <td><?php echo _("Filter:"); ?></td>
      <td align="right">
        <select name="filter[platform]" class="width-form" size="1" onchange="document.adminForm.submit( );">
        <option value="-1" <?php if ($filter['platform'] == 0) echo ' selected="selected"';?>><?php echo _("- Platform -"); ?></option>
<?php
	foreach (NetworkDevice::$PLATFORM_ARRAY as $pk) {
?>
          <option value="<?php echo $pk; ?>" <?php echo ($filter['platform'] == $pk) ? 'selected="selected"' : ""; ?>><?php echo NetworkDevice::getLocalizedPlatform($pk); ?></option>
<?php
	}
?>
        </select>
      </td>
    </tr>
    </table>

    <table class="adminlist">
    <thead>
    <tr>
     <th width="2%" class="title" align="left">#</th>
     <th width="2%" class="title" align="left"><input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo $pageNav->limit; ?>);" /></th>
     <th width="32%" class="title" align="left"><?php echo _("Network device"); ?></th>
     <th width="32%" class="title" align="left"><?php echo _("Network interface"); ?></th>
     <th width="24" class="title" align="left"></th>
   </tr>
   </thead>
    <tfoot>
    <tr>
      <td colspan="11">
<?php
	echo $pageNav->getListFooter();
?>
    </td>
    </tr>
    </tfoot>
   <tbody>
<?php
	$k = 0;
	$i = 0;
	foreach ($networkDevices as $networkDevice) {
		$link = "javascript:edit('$networkDevice->ND_networkdeviceid');";
?>
   <tr class="<?php echo "row$k"; ?>" >
     <td valign="top" align="left">
       <?php echo $i+1+$pageNav->limitstart; ?>
     </td>
     <td valign="top" align="left">
       <input type="checkbox" id="<?php echo "cb$i"; ?>" name="cid[]" value="<?php echo $networkDevice->ND_networkdeviceid; ?>" onclick="isChecked(this.checked);" />
     </td>
     <td valign="top" align="left">
       <div id="toggle-td1-<?php echo $networkDevice->ND_networkdeviceid ?>" style="overflow: hidden; height: 70px;">
       <div><strong><?php echo _("Name:"); ?></strong> <?php echo $networkDevice->ND_name; ?></div>
       <div><strong><?php echo _("Vendor:"); ?></strong> <?php echo $networkDevice->ND_vendor; ?></div>
       <div><strong><?php echo _("Type:"); ?></strong> <?php echo $networkDevice->ND_type; ?></div>
       <div><strong><?php echo _("Platform:"); ?></strong> <?php echo NetworkDevice::getLocalizedPlatform($networkDevice->ND_platform); ?></div>
       <div><strong><?php echo _("Description:"); ?></strong> <?php echo $networkDevice->ND_description; ?></div>
       <div><strong><?php echo _("Management interface:"); ?></strong> <?php echo ($networkDevice->ND_managementInterfaceId) ? $networkDevice->interfaces[$networkDevice->ND_managementInterfaceId]->NI_ifname.":".$networkDevice->interfaces[$networkDevice->ND_managementInterfaceId]->ip : "n/a"; ?></div>
     </div>
     </td>
     <td valign="top" align="left">
      <div id="toggle-td2-<?php echo $networkDevice->ND_networkdeviceid ?>" style="overflow: hidden; height: 70px;">
<?php
	foreach ($networkDevice->interfaces as $interface) {
		echo '<div><strong>' . $interface->NI_ifname . ':</strong> ' . $interface->ip . ' / ' . $interface->dns . " / ". $interface->NI_description. '</div>';
	}
?>
     </div>
     </td>
     <td valign="top" align="left">
     </td>
     <td valign="top" align="left">
       <img id="toggle-img-<?php echo $networkDevice->ND_networkdeviceid ?>" style="cursor: pointer;" src="images/22x22/actions/2downarrow.png" alt="drill-down" align="middle" border="0" onclick="toggleHide(<?php echo $networkDevice->ND_networkdeviceid ?>)" />
     </td>
   </tr>
<?php
		$k = 1 - $k;
		$i++;
	}
?>
   </tbody>
   </table>
   <input type="hidden" name="option" value="com_networkdevice" />
   <input type="hidden" name="ND_networkdeviceid" value="" />
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
	 * editNetworkDevice
	 * @param $networkDevice
	 */
	static function editNetworkDevice(&$networkDevice) {
		global $core;
?>
<script type="text/javascript">
	function submitbutton(pressbutton) {
		if (pressbutton == 'cancel') {
			submitform( pressbutton );
			return;
		} else if (pressbutton == 'testLogin'){
			submitform( pressbutton );
			return;
		}
		if (pressbutton == 'apply') {
			hideMainMenu();
		}

		var form = document.adminForm;

		// do field validation
		if (trim(form.ND_name.value) == "") {
			alert("<?php echo _("Please enter name"); ?>");
		} if (trim(form.ND_vendor.value) == "") {
			alert("<?php echo _("Please enter vendor"); ?>");
		} if (trim(form.ND_type.value) == "") {
			alert("<?php echo _("Please enter type"); ?>");
		} else {
			submitform(pressbutton);
		}
	}

	function newN() {
		hideMainMenu();
		submitbutton('newNetwork');
	}
	function editN(id) {
		document.adminForm.MN_hasmanagednetworkid.value = id;
		hideMainMenu();
		submitform('editNetwork');
	}
	function removeN(id) {
		if (window.confirm("<?php echo _("Do you really want to delete this entry ?"); ?>")) {
			document.adminForm.MN_hasmanagednetworkid.value = id;
			submitbutton('removeNetwork');
		}
	}

	function newI() {
		hideMainMenu();
		submitbutton('newInterface');
	}
	function editI(id) {
		document.adminForm.NI_networkdeviceinterfaceid.value = id;
		hideMainMenu();
		submitform('editInterface');
	}
	function removeI(id) {
		if (window.confirm("<?php echo _("Do you really want to delete this interface ?"); ?>")) {
			document.adminForm.NI_networkdeviceinterfaceid.value = id;
			submitbutton('removeInterface');
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
                <span title="<?php echo _("Test login"); ?>" class="icon-32-test-login"></span>
                <?php echo _("Test login"); ?>
              </a>
            </td>

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

        <div class="header icon-48-network-device">
          <?php echo _("Network devices management"); ?>: <small><?php echo _("Edit"); ?></small>
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
          <th colspan="2"><?php echo _("Network device"); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td width="150"><?php echo _("Name:"); ?></td>
          <td width="205"><input type="text" name="ND_name" class="width-form" size="40" value="<?php echo $networkDevice->ND_name; ?>" /></td>
        </tr>
        <tr>
          <td><?php echo _("Vendor:"); ?></td>
          <td><input type="text" name="ND_vendor" class="width-form" size="40" value="<?php echo $networkDevice->ND_vendor; ?>" /></td>
        </tr>
        <tr>
          <td><?php echo _("Type:"); ?></td>
          <td><input type="text" name="ND_type" class="width-form" size="40" value="<?php echo $networkDevice->ND_type; ?>" /></td>
        </tr>
        <tr>
          <td><?php echo _("Platform:"); ?></td>
          <td>
            <select name="ND_platform" class="width-form" size="1">
<?php
	foreach (NetworkDevice::$PLATFORM_ARRAY as $pk) {
?>
              <option value="<?php echo $pk; ?>" <?php echo ($networkDevice->ND_platform == $pk) ? 'selected="selected"' : ""; ?>><?php echo NetworkDevice::getLocalizedPlatform($pk); ?></option>
<?php
	}
?>
	        </select>
          </td>
        </tr>
        <tr>
          <td><?php echo _("Description:"); ?></td>
          <td><input type="text" name="ND_description" class="width-form" size="40" value="<?php echo $networkDevice->ND_description; ?>" /></td>
        </tr>
        </tbody>
        </table>

        <br/>

        <table class="adminform">
        <thead>
        <tr>
          <th colspan="2"><?php echo _("SSH2 login"); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td width="150"><?php echo _("Login:"); ?></td>
          <td width="205"><input type="text" name="ND_login" class="width-form" size="40" value="<?php echo $networkDevice->ND_login; ?>" /></td>
        </tr>
        <tr>
          <td><?php echo _("Password:"); ?></td>
          <td><input type="password" name="ND_password1" class="width-form" size="40" value="<?php echo $networkDevice->ND_password; ?>" /></td>
        </tr>
        <tr>
          <td><?php echo _("Password confirmation:"); ?></td>
          <td><input type="password" name="ND_password2" class="width-form" size="40" value="<?php echo $networkDevice->ND_password; ?>" /></td>
        </tr>
        <tr>
          <td><?php echo _("Management interface:"); ?></td>
          <td>
            <select name="ND_managementInterfaceId" class="width-form" size="1">
              <option value="0" <?php echo ($networkDevice->ND_managementInterfaceId == null) ? 'selected="selected"' : ""; ?>><?php echo _("Execute locally"); ?></option>
<?php
	foreach ($networkDevice->interfaces as $ik => $interface) {
?>
              <option value="<?php echo $ik; ?>" <?php echo ($networkDevice->ND_managementInterfaceId == $ik) ? 'selected="selected"' : ""; ?>><?php echo $interface->NI_ifname; ?></option>
<?php
	}
?>
	        </select>
          </td>
        </tr>
        </tbody>
        </table>

        <br/>

        <table class="adminform">
        <thead>
        <tr>
          <th colspan="2"><?php echo _("Command enviroment"); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td width="150"><?php echo _("Use sudo:"); ?></td>
          <td width="205"><input type="checkbox" name="ND_useCommandSudo" value="1" <?php if ($networkDevice->ND_useCommandSudo) echo 'checked="checked"';?>/></td>
        </tr>
        <tr>
          <td><?php echo _("sudo:"); ?></td>
          <td><input type="text" name="ND_commandSudo" class="width-form" size="40" value="<?php echo $networkDevice->ND_commandSudo; ?>" /></td>
        </tr>
        <tr>
          <td><?php echo _("iptables:"); ?></td>
          <td><input type="text" name="ND_commandIptables" class="width-form" size="40" value="<?php echo $networkDevice->ND_commandIptables; ?>" /></td>
        </tr>
        <tr>
          <td><?php echo _("ip:"); ?></td>
          <td><input type="text" name="ND_commandIp" class="width-form" size="40" value="<?php echo $networkDevice->ND_commandIp; ?>" /></td>
        </tr>
        <tr>
          <td><?php echo _("tc:"); ?></td>
          <td><input type="text" name="ND_commandTc" class="width-form" size="40" value="<?php echo $networkDevice->ND_commandTc; ?>" /></td>
        </tr>
        </tbody>
        </table>

        <br/>

        <table class="adminform">
        <thead>
        <tr>
          <th colspan="2"><?php echo _("Interfaces"); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td width="150" valign="top"><?php echo _("LAN interface:"); ?></td>
          <td width="205">
            <select name="lanInterfaces[]" class="width-form" multiple="multiple" size="5">
<?php
	foreach ($networkDevice->interfaces as $ik => $interface) {
?>
              <option value="<?php echo $ik; ?>" <?php echo ($interface->NI_type == NetworkDeviceInterface::TYPE_LAN) ? 'selected="selected"' : ""; ?>><?php echo $interface->NI_ifname; ?></option>
<?php
	}
?>
	        </select>
          </td>
        </tr>
        <tr>
          <td><?php echo _("WAN interface:"); ?></td>
          <td>
            <select name="ND_wanInterfaceid" class="width-form" size="1">
              <option value="0" <?php echo ($networkDevice->ND_wanInterfaceid == null) ? 'selected="selected"' : ""; ?>><?php echo _("None"); ?></option>
<?php
	foreach ($networkDevice->interfaces as $ik => $interface) {
?>
              <option value="<?php echo $ik; ?>" <?php echo ($networkDevice->ND_wanInterfaceid == $ik) ? 'selected="selected"' : ""; ?>><?php echo $interface->NI_ifname; ?></option>
<?php
	}
?>
	        </select>
          </td>
        </tr>
        </tbody>
        </table>

        <br/>

        <table class="adminform">
        <thead>
        <tr>
          <th colspan="2"><?php echo _("IP filter"); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td width="150"><?php echo _("Enable IP filter:"); ?></td>
          <td width="205"><input type="checkbox" name="ND_ipFilterEnabled" value="1" <?php if ($networkDevice->ND_ipFilterEnabled) echo 'checked="checked"';?>/></td>
        </tr>
        </tbody>
        </table>

        <br/>

        <table class="adminform">
        <thead>
        <tr>
          <th colspan="3"><?php echo _("Networks"); ?></th>
        </tr>
        </thead>
        <tbody>
<?php
	$k = 0;
	foreach ($networkDevice->networks as $network) {
		$linkE = "javascript:editN('$network->MN_hasmanagednetworkid');";
		$linkR = "javascript:removeN('$network->MN_hasmanagednetworkid');";
?>
        <tr class="<?php echo "row$k"; ?>">
          <td width="150"><?php echo $network->NE_net; ?></td>
          <td width="165"><?php echo $network->NE_description; ?></td>
          <td width="40"><a href="<?php echo $linkE; ?>"><?php echo _("Edit"); ?></a> <a href="<?php echo $linkR; ?>"><?php echo _("Delete"); ?></a></td>
        </tr>
<?php
	$k = 1 - $k;
	}
?>
        <tr class="<?php echo "row$k"; ?>">
          <td colspan="3"><a href="javascript:newN();"><?php echo _("New"); ?></a></td>
        </tr>
        <tr class="<?php $k = 1 - $k; echo "row$k"; ?>">
          <td colspan="3"><hr/></td>
        </tr>
<?php
	$k = 1 - $k;
	foreach ($networkDevice->leafNetworks as $leafNetwork) {
?>
        <tr class="<?php echo "row$k"; ?>">
          <td><?php echo $leafNetwork->NE_net; ?></td>
          <td colspan="2"><?php echo $leafNetwork->NE_description; ?></td>
        </tr>
<?php
	$k = 1 - $k;
	}
?>
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
          <th colspan="2"><?php echo _("Network interface"); ?></th>
        </tr>
        </thead>
        <tbody>
<?php
	$linkN = "javascript:newI();";
	$k = 0;
	foreach ($networkDevice->interfaces as $interface) {
		$linkE = "javascript:editI('$interface->NI_networkdeviceinterfaceid');";
		$linkR = "javascript:removeI('$interface->NI_networkdeviceinterfaceid');";
?>
        <tr class="<?php echo "row$k"; ?>">
        <td>
        <table class="insideadminlist">
        <tr>
          <td width="150"><?php echo _("Name:"); ?></td>
          <td width="205"><?php echo $interface->NI_ifname; ?></td>
        </tr>
        <tr>
          <td><?php echo _("IP:"); ?></td>
          <td><?php echo $interface->ip; ?></td>
        </tr>
        <tr>
          <td><?php echo _("Description:"); ?></td>
          <td><?php echo $interface->NI_description; ?></td>
        </tr>
        <tr>
          <td colspan="2" align="right"><a href="<?php echo $linkE; ?>"><?php echo _("Edit"); ?></a> <a href="<?php echo $linkR; ?>"><?php echo _("Delete"); ?></a></td>
        </tr>
        </table>
        </td>
        </tr>
<?php
	$k = 1 - $k;
	}
?>
        <tr class="<?php echo "row$k"; ?>">
          <td colspan="3"><a href="<?php echo $linkN; ?>"><?php echo _("New"); ?></a></td>
        </tr>
        </tbody>
        </table>
        </td>
      </td>
      <td>
      </td>
    </tr>
    </table>
    <input type="hidden" name="ND_networkdeviceid" value="<?php echo $networkDevice->ND_networkdeviceid; ?>" />
    <input type="hidden" name="MN_hasmanagednetworkid" value="" />
    <input type="hidden" name="NI_networkdeviceinterfaceid" value="" />
    <input type="hidden" name="option" value="com_networkdevice" />
    <input type="hidden" name="task" value="" />
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
	 * editNetworkDeviceInterface
	 * @param $networkDeviceInterface
	 * @param $ips
	 */
	static function editNetworkDeviceInterface(&$networkDeviceInterface, &$ips) {
		global $core;
?>
<script type="text/javascript">
	function submitbutton(pressbutton) {
		hideMainMenu();
		var form = document.adminForm;
		if (pressbutton == 'cancel') {
			submitform('cancelInterface');
			return;
		}

		// do field validation
		if (trim(form.NI_ifname.value) == "") {
			alert("<?php echo _("Please enter interface name"); ?>");
		} else {
			submitform(pressbutton + 'Interface');
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

        <div class="header icon-48-network-device">
          <?php echo _("Network devices management"); ?>: <small><?php echo _("Edit network interface"); ?></small>
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
          <th colspan="2"><?php echo _("Network interface"); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td width="150"><?php echo _("Name:"); ?></td>
          <td width="205"><input type="text" name="NI_ifname" class="width-form" size="40" value="<?php echo $networkDeviceInterface->NI_ifname; ?>" /></td>
        </tr>
        <tr>
          <td><?php echo _("IP:"); ?></td>
          <td>
            <select name="NI_ipid" class="width-form" size="1">
<?php
		echo '<option value="0"' ; if ($networkDeviceInterface->NI_ipid == 0) echo ' selected="selected"';echo ">- Nenastavena -</option>\n";
	foreach ($ips as $ip) {
		echo '<option value="' . $ip->IP_ipid . '"' ; if ($networkDeviceInterface->NI_ipid == $ip->IP_ipid) echo ' selected="selected"';echo ">$ip->IP_address</option>\n";
	}
?>
	        </select>
          </td>
        </tr>
        <tr>
          <td><?php echo _("Description:"); ?></td>
          <td><input type="text" name="NI_description" class="width-form" size="40" value="<?php echo $networkDeviceInterface->NI_description; ?>" /></td>
        </tr>
        </tbody>
        </table>
      </td>
      <td>
      </td>
    </tr>
    </table>
    <input type="hidden" name="ND_networkdeviceid" value="<?php echo $networkDeviceInterface->NI_networkdeviceid; ?>" />
    <input type="hidden" name="NI_networkdeviceid" value="<?php echo $networkDeviceInterface->NI_networkdeviceid; ?>" />
    <input type="hidden" name="NI_networkdeviceinterfaceid" value="<?php echo $networkDeviceInterface->NI_networkdeviceinterfaceid; ?>" />
    <input type="hidden" name="option" value="com_networkdevice" />
    <input type="hidden" name="task" value="" />
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
	 * editNetwork
	 * @param $networks
	 */
	static function editNetworkDeviceNetwork($hasManagedNetwork, &$networks) {
		global $core;
?>
<script type="text/javascript">
	function submitbutton(pressbutton) {
		hideMainMenu();
		var form = document.adminForm;
		if (pressbutton == 'cancel') {
			submitform('cancelNetwork');
			return;
		}

		// do field validation
		if (trim(form.MN_networkid.value) == "") {
			alert("<?php echo _("Please select network"); ?>");
		} else {
			submitform(pressbutton + 'Network');
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

        <div class="header icon-48-network-device">
          <?php echo _("Network devices management"); ?>: <small><?php echo _("Edit network"); ?></small>
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
      <td width="440" valign="top">
        <table class="adminform">
        <thead>
        <tr>
          <th colspan="3"><?php echo _("Network"); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td width="100"><?php echo _("Network:"); ?></td>
          <td colspan="2">
            <select name="MN_networkid" class="width-form" size="1">
<?php
	foreach ($networks as $ik => $network) {
?>
              <option value="<?php echo $ik; ?>" <?php echo ($hasManagedNetwork->MN_networkid == $ik) ? 'selected="selected"' : ""; ?>><?php echo $network->NE_net." ".$network->NE_description; ?></option>
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
      </td>
    </tr>
    </table>
    <input type="hidden" name="ND_networkdeviceid" value="<?php echo $hasManagedNetwork->MN_networkdeviceid; ?>" />
    <input type="hidden" name="MN_networkdeviceid" value="<?php echo $hasManagedNetwork->MN_networkdeviceid; ?>" />
    <input type="hidden" name="MN_hasmanagednetworkid" value="<?php echo $hasManagedNetwork->MN_hasmanagednetworkid; ?>" />
    <input type="hidden" name="option" value="com_networkdevice" />
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="hidemainmenu" value="0" />
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
