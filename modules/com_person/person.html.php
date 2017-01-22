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
// | Authors: Lukas Dziadkowiec <stealth.home@seznam.cz>                  |
// +----------------------------------------------------------------------+

/**
 * @author  Lukas Dziadkowiec <stealth.home@seznam.cz>
 */

/** ensure this file is being included by a parent file */
defined('VALID_MODULE') or die(_("Direct access into this section is not allowed"));

/**
 * HTML_person
 */
class HTML_person {
	/**
	 * showPerson
	 * @param $persons array of users
	 * @param $groups
	 * @param $pageNav
	 * @param $filter_search
	 * @param $filter_group
	 */
	static function showPersons(&$persons, &$groups, &$pageNav, &$filter) {
		global $core;
?>

<script language="JavaScript" type="text/javascript">
	function edit(id) {
    	var form = document.adminForm;
    	form.PE_personid.value = id;
    	hideMainMenu();
   		submitform('edit');
	}
	function newP() {
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
			if (window.confirm('<?php echo _("Do you really want to delete selected records ?"); ?>') && window.confirm('<?php echo _("Users should not be deleted, just mark as detached. Do you really want to delete selected records ?"); ?>')) {
				submitbutton('remove');
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
            <td id="toolbar-new">
              <a href="javascript:newP();">
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
        
        <div class="header icon-48-person">
          <?php echo _("User management"); ?>
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
      <td><input type="text" name="filter[search]" value="<?php echo $filter['search']; ?>" onchange="document.adminForm.submit();" maxlength="255" /></td>
      <td align="right">
        <select name="filter[group]" size="1" onchange="document.adminForm.submit( );">
        <option value="0" <?php if ($filter['group'] == 0) echo ' selected="selected"';?>><?php echo _("- User group -"); ?></option>
<?php
	foreach ($groups as $group) {
		echo '<option value="' . $group->GR_groupid . '"'; if ($filter['group'] == $group->GR_groupid) echo ' selected="selected"'; echo ">$group->GR_name</option>";
	}
?>
        </select>
      </td>
      <td align="right">
        <select name="filter[status]" size="1" onchange="document.adminForm.submit( );">
        <option value="-1" <?php if ($filter['status'] == -1) echo ' selected="selected"';?>><?php echo _("- Status -"); ?></option>
<?php
	foreach (Person::$STATUS_ARRAY as $k) {
?>
          <option value="<?php echo $k; ?>" <?php echo ($filter['status'] == $k) ? 'selected="selected"' : ""; ?>><?php echo Person::getLocalizedStatus($k); ?></option>
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
      <th width="2%" class="title">#</th>
      <th width="2%" class="title"><input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo $pageNav->limit; ?>);" /></th>
      <th width="16%" class="title"><?php echo _("Company short name"); ?></th>
      <th width="10%" class="title"><?php echo _("Firstname"); ?></th>
      <th width="10%" class="title"><?php echo _("Surname"); ?></th>
      <th width="5%" class="title"><?php echo _("Nickname"); ?></th>
      <th width="10%" class="title"><?php echo _("Group"); ?></th>
      <th width="5%" class="title"><?php echo _("Phone"); ?></th>
      <th width="5%" class="title"><?php echo _("ICQ"); ?></th>
      <th width="10%" class="title"><?php echo _("Address"); ?></th>
      <th width="10%" class="title"><?php echo _("E-mail"); ?></th>
      <th width="10%" class="title"><?php echo _("Registered"); ?></th>
      <th width="5%" class="title"><?php echo _("Status"); ?></th>
    </tr>
    </thead>
    <tfoot>
    <tr>
      <td colspan="13">
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
	foreach ($persons as $person) {
		$link = "javascript:edit('$person->PE_personid');";
		$registerDate = new DateUtil($person->PE_registerdate);
		
		switch ($person->PE_status) {
			case Person::STATUS_PASSIVE:
				$imageSrc = "images/16x16/actions/agt_action_fail.png";
				$imageAlt = _("Passive");
				break;
			
			case Person::STATUS_ACTIVE:
				$imageSrc = "images/16x16/actions/agt_action_success.png";
				$imageAlt = _("Active");
				break;
				
			case Person::STATUS_DISCARTED:
				$imageSrc = "images/16x16/actions/agt_stop.png";
				$imageAlt = _("Removed");
				break;
		}
?>
    <tr class="<?php echo "row$k"; ?>">
      <td>
        <?php echo $pageNav->rowNumber($i); ?>
      </td>
      <td>
        <input type="checkbox" id="<?php echo "cb$i"; ?>" name="cid[]" value="<?php echo $person->PE_personid; ?>" onclick="isChecked(this.checked);" />
      </td>
      <td>
        <a href="<?php echo $link; ?>"><?php echo $person->PE_shortcompanyname; ?></a>
      </td>
      <td>
        <a href="<?php echo $link; ?>"><?php echo $person->PE_firstname; ?></a>
      </td>
      <td>
        <a href="<?php echo $link; ?>"><?php echo $person->PE_surname; ?></a>
      </td>
      <td>
        <a href="<?php echo $link; ?>"><?php echo $person->PE_nick; ?></a>
      </td>
      <td>
        <?php echo $groups[$person->PE_groupid]->GR_name; ?>
      </td>
      <td>
        <?php echo $person->PE_tel; ?>
      </td>
      <td>
        <?php echo $person->PE_icq; ?>
      </td>
      <td>
        <?php echo $person->PE_address; ?>
      </td>
      <td>
        <?php echo $person->PE_email; ?>
      </td>
      <td nowrap="nowrap">
        <?php echo $registerDate->getFormattedDate(DateUtil::FORMAT_FULL); ?>
      </td>
      <td nowrap="nowrap">
        <img src="<?php echo $imageSrc; ?>" alt="<?php echo $imageAlt; ?>" align="middle" border="0" />
      </td>
    </tr>
<?php
		$k = 1 - $k;
		$i++;
	}
?>
    </tbody>
    </table>
    <input type="hidden" name="option" value="com_person" />
    <input type="hidden" name="PE_personid" value="" />
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
	 * editPerson
	 * @param $person
	 * @param $groups
	 * @param $hasRoles
	 * @param $hasIps
	 * @param $roles
	 * @param $charges
	 * @param $hasCharges
	 * @param $internets
	 */
	static function editPerson(&$person, &$groups, &$hasRoles, &$hasIps, &$roles, &$charges, &$hasCharges) {
		global $core;
		$birthDate = new DateUtil($person->PE_birthdate);
		$registerDate = new DateUtil($person->PE_registerdate);
?>
<script type="text/javascript" language="JavaScript">
    	document.write(getCalendarStyles());
    	
		var cal1x = new CalendarPopup("caldiv");
		cal1x.setMonthNames("Leden","Únor","Březen","Duben","Květen","Červen","Červenec","Srpen","Září","Říjen","Listopad","Prosinec");
		cal1x.showYearNavigation(true);
		cal1x.setDayHeaders("N","P","Ú","S","Č","P","S");
		cal1x.setWeekStartDay(1);
		cal1x.setTodayText("Dnes");
		cal1x.offsetX = 0;
		cal1x.offsetY = 16;
		
		function submitbutton(pressbutton) {
			if (pressbutton == 'cancel') {
				submitform(pressbutton);
			} else if (pressbutton == 'apply') {
				hideMainMenu();
				submitform(pressbutton);
			} else if (pressbutton == 'save') {
				submitform(pressbutton);
			}
		}
		
		function addRole() {
			var form = document.adminForm;
			
			var selected = form.roles.selectedIndex;
			
			if (selected == -1) {
				alert("<?php echo _("Please select role you want to assign"); ?>");				
		} else {
			form.RO_roleid.value = form.roles.options[selected].value;
			var name = form.roles.options[selected].text;
			var value = form.roles.options[selected].value;
			submitform('addRole');
		}
	}
	function removeRole() {
		var form = document.adminForm;
		
		var selected = form.hasRoles.selectedIndex;
		
		if (selected == -1) {
			alert("<?php echo _("Please select role you want to remove"); ?>");				
		} else {
			form.RM_rolememberid.value = form.hasRoles.options[selected].value;
			submitform('removeRole');
		}
	}
	function editIP(id) {
		var form = document.adminForm;
		form.option.value = 'com_network';
		form.IP_ipid.value = id;
		hideMainMenu();
		submitform('editI');
	}
	function editHasCharge(id) {
		var form = document.adminForm;
		form.HC_haschargeid.value = id;
		hideMainMenu();
		submitform('editHasCharge');
	}
	function showHasCharge() {
		var el = document.getElementById('newHasChargeBlock');
		el.style.display = 'block';
		var el = document.getElementById('newHasChargeClick');
		el.style.display = 'none';
	}
	function newHasCharge() {
		hideMainMenu();
		var form = document.adminForm;
		if (form.CH_chargeid.value == 0) {
			alert("<?php echo _("Please select payment want to add"); ?>");
		} else {
			submitform('newHasCharge');
		}
	}
	function hasChargeAction(el, id) {
		var form = document.adminForm;
		var action = el.value;
		
		if (action == 'editHasCharge') {
			form.HC_haschargeid.value = id;
			hideMainMenu();
			submitform(action);
		} else if (action == 'removeHasCharge') {
			form.HC_haschargeid.value = id;
			hideMainMenu();
			submitform(action);
		}
	}
</script>

<div id="caldiv" style="position:absolute;visibility:hidden;background-color:white;layer-background-color:white;z-index:999;"></div>

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

        <div class="header icon-48-person">
          <?php echo _("User management"); ?>: <small><?php echo _("Edit"); ?></small>
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
    <table class="splitform">
    <tr>
      <td valign="top" width="365">
        <table class="adminform">
        <thead>
        <tr>
          <th colspan="2"><?php echo _("Personal data"); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td width="150"><?php echo _("Status:"); ?></td>
          <td width="205">
            <select name="PE_status" class="width-form">
<?php
	foreach (Person::$STATUS_ARRAY as $k) {
?>
              <option value="<?php echo $k; ?>" <?php echo ($person->PE_status == $k) ? 'selected="selected"' : ""; ?>><?php echo Person::getLocalizedStatus($k); ?></option>
<?php
	}
?>
            </select>
          </td>
        </tr>
        <tr>
          <td><?php echo _("IČ:"); ?></td>
          <td><input type="text" name="PE_ic" class="width-form" size="40" value="<?php echo $person->PE_ic; ?>" maxlength="255" /></td>
        </tr>
        <tr>
          <td><?php echo _("DIČ:"); ?></td>
          <td><input type="text" name="PE_dic" class="width-form" size="40" value="<?php echo $person->PE_dic; ?>" maxlength="255" /></td>
        </tr>
        <tr>
          <td><?php echo _("Company short name:"); ?></td>
          <td><input type="text" name="PE_shortcompanyname" class="width-form" size="40" value="<?php echo $person->PE_shortcompanyname; ?>" maxlength="255" /></td>
        </tr>
        <tr>
          <td><?php echo _("Company name:"); ?></td>
          <td><input type="text" name="PE_companyname" class="width-form" size="40" value="<?php echo $person->PE_companyname; ?>" maxlength="255" /></td>
        </tr>
        <tr>
          <td><?php echo _("Firstname:"); ?></td>
          <td><input type="text" name="PE_firstname" class="width-form" size="40" value="<?php echo $person->PE_firstname; ?>" maxlength="255" /></td>
        </tr>
        <tr>
          <td><?php echo _("Surname:"); ?></td>
          <td><input type="text" name="PE_surname" class="width-form" size="40" value="<?php echo $person->PE_surname; ?>" maxlength="255" /></td>
        </tr>
        <tr>
          <td><?php echo _("Nickname:"); ?></td>
          <td><input type="text" name="PE_nick" class="width-form" size="40" value="<?php echo $person->PE_nick; ?>" maxlength="255" /></td>
        </tr>
        <tr>
          <td><?php echo _("Sex:"); ?></td>
          <td>
            <select name="PE_gender" class="width-form">
              <option value="muž" <?php if ($person->PE_gender == "muž") echo 'selected="selected"'?>>muž</option>
              <option value="žena" <?php if ($person->PE_gender == "žena") echo 'selected="selected"'?>>žena</option>
            </select></td>
        </tr>
        <tr>
          <td><?php echo _("Degree before name:"); ?></td>
          <td><input type="text" name="PE_degree_prefix" class="width-form" size="40" value="<?php echo $person->PE_degree_prefix; ?>" maxlength="20" /></td>
        </tr>
        <tr>
          <td><?php echo _("Degree after name:"); ?></td>
          <td><input type="text" name="PE_degree_suffix" class="width-form" size="40" value="<?php echo $person->PE_degree_suffix; ?>" maxlength="20" /></td>
        </tr>
        <tr>
          <td><?php echo _("Birthdate:"); ?></td>
          <td>
            <input type="text" name="PE_birthdate" class="width-form-button" value="<?php echo $birthDate->getFormattedDate(DateUtil::FORMAT_DATE); ?>" size="10" maxlength="10" />
            <a href="#" onclick="cal1x.select(document.adminForm.PE_birthdate,'anchor1x','dd.MM.yyyy'); return false;" name="anchor1x" id="anchor1x"><img src="images/22x22/apps/calendar.png" style="width: 16px; height: 16px; vertical-align: middle; position: relative; top: -2px; cursor: pointer;" alt="<?php echo _("Calendar"); ?>" /></a>
          </td>
        </tr>
        <tr>
          <td><?php echo _("E-mail"); ?></td>
          <td><input type="text" name="PE_email" class="width-form" size="40" value="<?php echo $person->PE_email; ?>" maxlength="255" /></td>
        </tr>
        <tr>
          <td><?php echo _("ICQ:"); ?></td>
          <td><input type="text" name="PE_icq" class="width-form" size="40" value="<?php echo $person->PE_icq; ?>" maxlength="50" /></td>
        </tr>
        <tr>
          <td><?php echo _("Phone:"); ?></td>
          <td><input type="text" name="PE_tel" class="width-form" size="40" value="<?php echo $person->PE_tel; ?>" maxlength="50" /></td>
        </tr>
        <tr>
          <td><?php echo _("Address:"); ?></td>
          <td><input type="text" name="PE_address" class="width-form" size="40" value="<?php echo $person->PE_address; ?>" maxlength="255" /></td>
        </tr>
        <tr>
          <td><?php echo _("City:"); ?></td>
          <td><input type="text" name="PE_city" class="width-form" size="40" value="<?php echo $person->PE_city; ?>" maxlength="255" /></td>
        </tr>
        <tr>
          <td><?php echo _("ZIP:"); ?></td>
          <td><input type="text" name="PE_zip" class="width-form" size="40" value="<?php echo $person->PE_zip; ?>" maxlength="255" /></td>
        </tr>
        <tr>
          <td><?php echo _("Date of registration:"); ?></td>
          <td><?php echo $registerDate->getFormattedDate(DateUtil::FORMAT_FULL); ?></td>
        </tr>
        </tbody>
        </table>
      </td>
      <td width="10">
        &nbsp;
      </td>
      <td valign="top">
      <div class="tab-page" id="modules-cpanel-person">
	  <script language="JavaScript" type="text/javascript">var tabPanePerson1 = new WebFXTabPane(document.getElementById("modules-cpanel-person"), 1);</script>
      <div class="tab-page" id="module01"><h2 class="tab"><?php echo _("Login creditials"); ?></h2>
        <script language="JavaScript" type="text/javascript">tabPanePerson1.addTabPage(document.getElementById("module01"));</script>
        <table class="adminform">
        <thead>
        <tr>
          <th colspan="2"><?php echo _("System login registration"); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td width="150"><?php echo _("Login name:"); ?></td>
          <td><input type="text" name="PE_username" class="width-form" size="20" value="<?php echo $person->PE_username; ?>" maxlength="255" autocomplete="off" /></td>
        </tr>
        <tr>
          <td><?php echo _("Password:"); ?></td>
          <td><input type="password" name="PE_password1" class="width-form" size="20" value="<?php echo $person->PE_password; ?>" maxlength="255" autocomplete="off" /></td>
        </tr>
        <tr>
          <td><?php echo _("Password confirmation:"); ?></td>
          <td><input type="password" name="PE_password2" class="width-form" size="20" value="<?php echo $person->PE_password; ?>" maxlength="255" autocomplete="off" /></td>
        </tr>
        <tr>
          <td valign="top"><?php echo _("User group:"); ?></td>
          <td>
            <select name="PE_groupid" class="width-form">
<?php
	foreach($groups as $group) {
		echo '<option value="' . $group->GR_groupid . '"'; if ($person->PE_groupid == $group->GR_groupid) echo ' selected="selected"'; echo ">$group->GR_name</option>\n";
	}
?>
            </select>
          </td>
        </tr>
        </tbody>
        </table>
      </div>
      <div class="tab-page" id="module02"><h2 class="tab"><?php echo _("Role"); ?></h2>
        <script language="JavaScript" type="text/javascript">tabPanePerson1.addTabPage(document.getElementById("module02"));</script>
        <table class="adminform">
        <thead>
        <tr>
          <th colspan="5"><?php echo _("User role"); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td width="100" valign="middle"><?php echo _("Role list:"); ?></td>
          <td width="1">
            <select name="hasRoles" size="4" class="width-form">
<?php
	foreach($hasRoles as $hasRole) {
		echo '<option value="' . $hasRole->RM_rolememberid . '">' . $hasRole->RO_name . "</option>\n";
	}
?>
            </select>
          </td>
          <td valign="middle" width="50">
            <a class="text-button" href="javascript:removeRole();"><img src="images/16x16/actions/delete.png" alt="<?php echo _("Remove"); ?>" align="middle" border="0"/></a>
          </td>
          <td width="1" valign="middle">
            <select name="roles" class="width-form">
<?php
	foreach($roles as $role) {
		echo '<option value="' . $role->RO_roleid . '">' . $role->RO_name . '</option>';
	}
?>
            </select>
          </td>
          <td>
            <a href="javascript:addRole();" class="text-button" style="float: left;"><img src="images/16x16/actions/edit_add.png" alt="<?php echo _("Add"); ?>" align="middle" border="0"/></a>
          </td>
        </tr>
        </tbody>
        </table>
      </div>
      <div class="tab-page" id="module03"><h2 class="tab"><?php echo _("Payments"); ?></h2>
        <script language="JavaScript" type="text/javascript">tabPanePerson1.addTabPage(document.getElementById("module03"));</script>
        <table class="adminlist">
        <thead>
        <tr>
          <th width="10%" class="title"><?php echo _("Service name"); ?></th>
          <th width="9%" class="title"><?php echo _("Type"); ?></th>
          <th width="9%" class="title"><?php echo _("Amount without VAT"); ?></th>
          <th width="9%" class="title"><?php echo _("VAT (%)"); ?></th>
          <th width="9%" class="title"><?php echo _("Amount with VAT"); ?></th>
          <th width="9%" class="title"><?php echo _("Currency"); ?></th>
          <th width="9%" class="title"><?php echo _("Payed"); ?></th>
          <th width="9%" class="title"><?php echo _("Active from"); ?></th>
          <th width="9%" class="title"><?php echo _("Active to"); ?></th>
          <th width="9%" class="title"><?php echo _("Status"); ?></th>
          <th width="9%" class="title"><?php echo _("Action"); ?></th>
        </tr>
        </thead>
        <tbody>
<?php
	$k = 0;
	foreach($hasCharges as $hasCharge) {
		$linkEditHasCharge = "javascript:editHasCharge($hasCharge->HC_haschargeid);";
		$linkEditHasChargeOption = "hasChargeAction(this, $hasCharge->HC_haschargeid);";
		
		$dateStartObj = new DateUtil($hasCharge->HC_datestart);
		$dateEndObj = new DateUtil($hasCharge->HC_dateend);
		$dateStart = "";
		$dateEnd = "";
		
		switch ($hasCharge->CH_period) {
			case Charge::PERIOD_ONCE:
				$format = DateUtil::FORMAT_DATE;
				break;
			case Charge::PERIOD_MONTHLY:
				$format = DateUtil::FORMAT_MONTHLY;
				break;
			case Charge::PERIOD_QUARTERLY:
				$format = DateUtil::FORMAT_QUARTERLY;
				break;
			case Charge::PERIOD_HALFYEARLY:
				$format = DateUtil::FORMAT_HALFYEARLY;
				break;
			case Charge::PERIOD_YEARLY:
				$format = DateUtil::FORMAT_MONTHLY;
				break;
			default:
				$format = DateUtil::FORMAT_FULL;
		}
		
		$dateStart = $dateStartObj->getFormattedDate($format);
		$dateEnd = $dateEndObj->getFormattedDate($format);
?>
        <tr class="<?php echo "row$k"; ?>">
          <td>
            <a href="<?php echo $linkEditHasCharge; ?>"><?php echo $hasCharge->CH_name; ?></a>
          </td>
          <td>
            <?php echo Charge::getLocalizedType($hasCharge->CH_type); ?>
          </td>
          <td>
            <?php echo $hasCharge->CH_baseamount; ?>
          </td>
          <td>
            <?php echo $hasCharge->CH_vat; ?>
          </td>
          <td>
            <?php echo $hasCharge->CH_amount; ?>
          </td>
          <td>
            <?php echo $hasCharge->CH_currency; ?>
          </td>
          <td>
            <?php echo Charge::getLocalizedPeriod($hasCharge->CH_period); ?>
          </td>
          <td>
            <?php echo $dateStart; ?>
          </td>
          <td>
            <?php echo $dateEnd; ?>
          </td>
          <td>
            <?php echo HasCharge::getLocalizedStatus($hasCharge->HC_status); ?>
          </td>
          <td>
            <select style="width: 100%;" size="1" onchange="<?php echo $linkEditHasChargeOption; ?>">
              <option value="0" selected="selected"></option>
              <option value="editHasCharge"><?php echo _("Edit"); ?></option>
              <option value="removeHasCharge"><?php echo _("Remove"); ?></option>
            </select>
          </td>
        </tr>
<?php
	$k = 1 - $k;
	}
?>
        <tr class="<?php echo "irow$k"; ?>">
          <td align="right" colspan="8">
            <a id="newHasChargeClick" class="text-button" href="javascript:showHasCharge();">
              <?php echo _("New payment"); ?>
            </a>
            
            <div id="newHasChargeBlock" style="display: none;">
              <select name="CH_chargeid" style="width:400px; float: left;">
<?php
	foreach($charges as $charge) {
		echo "<option value=\"$charge->CH_chargeid\">$charge->CH_name $charge->CH_amount $charge->CH_currency (" . Charge::getLocalizedPeriod($charge->CH_period) . ")</option>\n";
	}
?>
              </select>
              <a class="text-button" href="javascript:newHasCharge();" style="margin-left: 5px;">
                <?php echo _("Add"); ?>
              </a>
            </div>
          </td>
        </tr>
        </tbody>
        </table>
      </div>
      <div class="tab-page" id="module04"><h2 class="tab"><?php echo _("IP addresses"); ?></h2>
        <script language="JavaScript" type="text/javascript">tabPanePerson1.addTabPage(document.getElementById("module04"));</script>
        <table class="adminlist">
        <thead>
        <tr>
          <th class="title" width="150"><?php echo _("IP address"); ?></th>
          <th class="title"><?php echo _("DNS name"); ?></th>
        </tr>
        </thead>
        <tbody>
<?php
	$k = 0;
	foreach($hasIps as $ip) {
		$link = "javascript:editIP('$ip->IP_ipid');"; ?>
		<tr class="<?php echo "row$k"; ?>">
		  <td width="150">
		    <a href="<?php echo $link; ?>"><?php echo $ip->IP_address; ?></a>
		  <td>
		    <?php echo $ip->IP_dns; ?>
		  </td>
		</tr>
<?php
		$k = 1 - $k;
	}
?>
        </tbody>
        </table>
      </div>
      </div>
      </td>
    </tr>
    </table>
    <input type="hidden" name="PE_personid" value="<?php echo $person->PE_personid; ?>" />
    <input type="hidden" name="option" value="com_person" />
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="RM_rolememberid" value="" />
    <input type="hidden" name="RO_roleid" value="" />
    <input type="hidden" name="IP_ipid" value="" />
    <input type="hidden" name="HC_haschargeid" value="" />
    <input type="hidden" name="hidemainmenu" value="0" />
    </form>
    </div>
    
    <div class="clr"></div>
</div>

<div class="clr"></div>
</div>

<script type="text/javascript" language="JavaScript">
	var formValidator = new Validator("adminForm");
//	formValidator.addValidation("PE_firstname","required","<?php echo _("Please enter firstname"); ?>");
//	formValidator.addValidation("PE_surname","required","<?php echo _("Please enter surname"); ?>");
	formValidator.addValidation("PE_birthdate","date=dd.MM.yyyy","<?php echo _("Birthdate is in incorrect format"); ?>");
	formValidator.addValidation("PE_username","alphanumeric","<?php echo _("Username contains invalid characters"); ?>");
	formValidator.addValidation("PE_username","minlength=3","<?php echo _("Username is too short"); ?>");
	formValidator.addValidation("PE_email","email","<?php echo _("Please enter valid E-Mail"); ?>");
	formValidator.setAddnlValidationFunction("passwordMatchValidator");
	formValidator.addValidation("PE_password1","minlength=6","<?php echo _("Password is too short"); ?>");
	
	function passwordMatchValidator() {
		var form = document.adminForm;
		
		if (form.PE_password1.value && form.PE_password1.value != form.PE_password2.value) {
			alert("<?php echo _("Passwords doesn't match"); ?>");
			return false;
		} else {
			return true;
		}
	}
</script>
<?php
	}
	/**
	 * editHasCharge
	 * @param $hascharge
	 * @param $charge
	 */
	static function editHasCharge(&$person, &$hascharge, &$charge, &$status) {
		global $core;
		switch ($charge->CH_period) {
			case Charge::PERIOD_ONCE:
				$jsFormat = 'dd.MM.yyyy';
				break;
			case Charge::PERIOD_MONTHLY:
				$jsFormat = 'MM/yyyy';
				break;
			case Charge::PERIOD_QUARTERLY:
				$jsFormat = 'm/yyyy'; //TODO> to be implemented
				break;
			case Charge::PERIOD_HALFYEARLY:
				$jsFormat = 'm/yyyy'; //TODO> to be implemented
				break;
			case Charge::PERIOD_YEARLY:
				$jsFormat = 'MM/yyyy';
				break;
		}
?>
<script type="text/javascript" language="JavaScript">
	document.write(getCalendarStyles());
	
	function submitbutton(pressbutton) {
		var form = document.adminForm;
		if (pressbutton == 'cancel') {
			hideMainMenu();
			submitform('cancelHasCharge');
		} else if (pressbutton == 'apply') {
			hideMainMenu();
			submitform('applyHasCharge');
		} else if (pressbutton == 'save') {
			hideMainMenu();
			submitform('saveHasCharge');
		}	
	}
</script>

<div id="caldiv" STYLE="position:absolute;visibility:hidden;background-color:white;layer-background-color:white;z-index:999;"></div>

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

        <div class="header icon-48-person">
          <?php echo _("User management"); ?>: <small><?php echo _("Edit payment service"); ?></small>
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
          <th colspan="2"><?php echo _("Payment service"); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td width="150"><?php echo _("Service name:"); ?></td>
          <td width="205"><input type="text" name="_CH_name" class="width-form" size="40" value="<?php echo $charge->CH_name; ?>" disabled="disabled"/></td>
        </tr>
        <tr>
          <td><?php echo _("Service description:"); ?></td>
          <td><input type="text" name="_CH_name" class="width-form" size="40" value="<?php echo $charge->CH_description; ?>" disabled="disabled"/></td>
        </tr>
        <tr>
          <td><?php echo _("Write off:"); ?></td>
          <td><input type="text" name="_CH_name" class="width-form" size="40" value="<?php echo Charge::getLocalizedPeriod($charge->CH_period); ?>" disabled="disabled"/></td>
        </tr>
        <tr>
          <td><?php echo _("Amount without VAT:"); ?></td>
          <td><input type="text" name="_CH_name" class="width-form" size="40" value="<?php echo $charge->CH_baseamount; ?>" disabled="disabled"/></td>
        </tr>
        <tr>
          <td><?php echo _("VAT (%):"); ?></td>
          <td><input type="text" name="_CH_name" class="width-form" size="40" value="<?php echo $charge->CH_vat; ?>" disabled="disabled"/></td>
        </tr>
        <tr>
          <td><?php echo _("Amount with VAT:"); ?></td>
          <td><input type="text" name="_CH_name" class="width-form" size="40" value="<?php echo $charge->CH_amount; ?>" disabled="disabled"/></td>
        </tr>
        <tr>
          <td><?php echo _("Currency:"); ?></td>
          <td><input type="text" name="_CH_name" class="width-form" size="40" value="<?php echo $charge->CH_currency; ?>" disabled="disabled"/></td>
        </tr>
        <tr>
          <td><?php echo _("Tolerance in days:"); ?></td>
          <td><input type="text" name="_CH_name" class="width-form" size="40" value="<?php echo $charge->CH_tolerance; ?>" disabled="disabled"/></td>
        </tr>
        <tr>
          <td><?php echo _("Type:"); ?></td>
          <td><input type="text" name="_CH_name" class="width-form" size="40" value="<?php echo Charge::getLocalizedType($charge->CH_type); ?>" disabled="disabled"/></td>
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
          <th colspan="2"><?php echo _("Service configuration"); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td width="150"><?php echo _("Start date:"); ?></td>
          <td width="205">
<?php
	$dateStart = new DateUtil($hascharge->HC_datestart);
	$dateEnd = new DateUtil($hascharge->HC_dateend);
	if ($status['HC_datestart']) {
		if ($charge->CH_period == Charge::PERIOD_ONCE) { ?>
            <script type="text/javascript" language="JavaScript" ID="jscal1x">
		var cal1x = new CalendarPopup("caldiv");
		cal1x.setMonthNames("Leden","Únor","Březen","Duben","Květen","Červen","Červenec","Srpen","Září","Říjen","Listopad","Prosinec");
		cal1x.setMonthAbbreviations("Led","Úno","Bře","Dub","Kvě","Čer","Čer","Srp","Zář","Říj","Lis","Pro");
		cal1x.showYearNavigation(true);
		cal1x.setDayHeaders("N","P","Ú","S","Č","P","S");
		cal1x.setWeekStartDay(1);
		cal1x.setTodayText("Dnes");
		cal1x.offsetX = 0;
		cal1x.offsetY = 16;
            </script>
            <input type="text" name="HC_datestart" value="<?php echo $dateStart->getFormattedDate(DateUtil::FORMAT_DATE); ?>" class="width-form-button" size="35" maxlength="10" />
            <a href="#" onclick="cal1x.select(document.adminForm.HC_datestart,'anchor1x','dd.MM.yyyy'); return false;" name="anchor1x" id="anchor1x"><img src="images/22x22/apps/calendar.png" style="width: 16px; height: 16px; vertical-align: middle; position: relative; top: -2px; cursor: pointer;" alt="<?php echo _("Calendar"); ?>" /></a>
<?php	} else if ($charge->CH_period == Charge::PERIOD_MONTHLY) { ?>
            <script type="text/javascript" language="JavaScript" ID="jscal1x">
		var cal1x = new CalendarPopup("caldiv");
		cal1x.setDisplayType("month");
		cal1x.setMonthNames("Leden","Únor","Březen","Duben","Květen","Červen","Červenec","Srpen","Září","Říjen","Listopad","Prosinec");
		cal1x.setMonthAbbreviations("Led","Úno","Bře","Dub","Kvě","Čer","Čer","Srp","Zář","Říj","Lis","Pro");
		cal1x.setReturnMonthFunction("myMonthReturn1x");
		cal1x.showYearNavigation(true);
		cal1x.offsetX = 0;
		cal1x.offsetY = 16;
		function myMonthReturn1x(y,m) {
			if (m < 10) m = '0' + m;
			document.adminForm.HC_datestart.value=m+"/"+y;
		}
            </script>
            <input type="text" name="HC_datestart" value="<?php echo $dateStart->getFormattedDate(DateUtil::FORMAT_MONTHLY); ?>" class="width-form-button" size="35" maxlength="10" />
            <a href="#" onclick="cal1x.showCalendar('anchor1x'); return false;" name="anchor1x" id="anchor1x"><img src="images/22x22/apps/calendar.png" style="width: 16px; height: 16px; vertical-align: middle; position: relative; top: -2px; cursor: pointer;" alt="<?php echo _("Calendar"); ?>" /></a>
<?php	} else if ($charge->CH_period == Charge::PERIOD_QUARTERLY) { ?>
            <script type="text/javascript" language="JavaScript" ID="jscal1x">
		var cal1x = new CalendarPopup("caldiv");
		cal1x.setDisplayType("quarter");
		cal1x.setReturnQuarterFunction("myQuarterReturn1x");
		cal1x.showYearNavigation(true);
		cal1x.offsetX = 0;
		cal1x.offsetY = 16;
		function myQuarterReturn1x(y,q) {
			document.adminForm.HC_datestart.value=q+"/"+y;
		}
            </script>
            <input type="text" name="HC_datestart" value="<?php echo $dateStart->getFormattedDate(DateUtil::FORMAT_QUARTERLY); ?>" class="width-form-button" size="35" maxlength="10" />
            <a href="#" onclick="cal1x.showCalendar('anchor1x'); return false;" name="anchor1x" id="anchor1x"><img src="images/22x22/apps/calendar.png" style="width: 16px; height: 16px; vertical-align: middle; position: relative; top: -2px; cursor: pointer;" alt="<?php echo _("Calendar"); ?>" /></a>
<?php	} else if ($charge->CH_period == Charge::PERIOD_HALFYEARLY) { ?>
            <script type="text/javascript" language="JavaScript" ID="jscal1x">
		var cal1x = new CalendarPopup("caldiv");
		cal1x.setDisplayType("half");
		cal1x.setReturnHalfFunction("myHalfReturn1x");
		cal1x.showYearNavigation(true);
		cal1x.offsetX = 0;
		cal1x.offsetY = 16;
		function myHalfReturn1x(y,h) {
			document.adminForm.HC_datestart.value=h+"/"+y;
		}
            </script>
            <input type="text" name="HC_datestart" value="<?php echo $dateStart->getFormattedDate(DateUtil::FORMAT_HALFYEARLY); ?>" class="width-form-button" size="35" maxlength="10" />
            <a href="#" onclick="cal1x.showCalendar('anchor1x'); return false;" name="anchor1x" id="anchor1x"><img src="images/22x22/apps/calendar.png" style="width: 16px; height: 16px; vertical-align: middle; position: relative; top: -2px; cursor: pointer;" alt="<?php echo _("Calendar"); ?>" /></a>
<?php	} if ($charge->CH_period == Charge::PERIOD_YEARLY) { ?>
            <script type="text/javascript" language="JavaScript" ID="jscal1x">
		var cal1x = new CalendarPopup("caldiv");
		cal1x.setDisplayType("month");
		cal1x.setMonthNames("Leden","Únor","Březen","Duben","Květen","Červen","Červenec","Srpen","Září","Říjen","Listopad","Prosinec");
		cal1x.setMonthAbbreviations("Led","Úno","Bře","Dub","Kvě","Čer","Čer","Srp","Zář","Říj","Lis","Pro");
		cal1x.setReturnMonthFunction("myMonthReturn1x");
		cal1x.showYearNavigation(true);
		cal1x.offsetX = 0;
		cal1x.offsetY = 16;
		function myMonthReturn1x(y,m) {
			if (m < 10) m = '0' + m;
			document.adminForm.HC_datestart.value=m+"/"+y;
		}
            </script>
            <input type="text" name="HC_datestart" value="<?php echo $dateStart->getFormattedDate(DateUtil::FORMAT_MONTHLY); ?>" class="width-form-button" size="35" maxlength="10" />
            <a href="#" onclick="cal1x.showCalendar('anchor1x'); return false;" name="anchor1x" id="anchor1x"><img src="images/22x22/apps/calendar.png" style="width: 16px; height: 16px; vertical-align: middle; position: relative; top: -2px; cursor: pointer;" alt="<?php echo _("Calendar"); ?>" /></a>
<?php	}
	} else {
		if ($charge->CH_period == Charge::PERIOD_ONCE) { ?>
            <input type="text" name="HC_datestart" value="<?php echo $dateStart->getFormattedDate(DateUtil::FORMAT_DATE); ?>" class="width-form-button" size="35" disabled="disabled" />
<?php	} else if ($charge->CH_period == Charge::PERIOD_MONTHLY) { ?>
            <input type="text" name="HC_datestart" value="<?php echo $dateStart->getFormattedDate(DateUtil::FORMAT_MONTHLY); ?>" class="width-form-button" size="35" disabled="disabled" />
<?php	} else if ($charge->CH_period == Charge::PERIOD_QUARTERLY) { ?>
            <input type="text" name="HC_datestart" value="<?php echo $dateStart->getFormattedDate(DateUtil::FORMAT_QUARTERLY); ?>" class="width-form-button" size="35" disabled="disabled" />
<?php	} else if ($charge->CH_period == Charge::PERIOD_HALFYEARLY) { ?>
            <input type="text" name="HC_datestart" value="<?php echo $dateStart->getFormattedDate(DateUtil::FORMAT_HALFYEARLY); ?>" class="width-form-button" size="35" disabled="disabled" />
<?php	} if ($charge->CH_period == Charge::PERIOD_YEARLY) { ?>
            <input type="text" name="HC_datestart" value="<?php echo $dateStart->getFormattedDate(DateUtil::FORMAT_MONTHLY); ?>" class="width-form-button" size="35" disabled="disabled" />
<?php	}
	}
?>
          </td>
        </tr>
<?php
	if ($charge->CH_period == Charge::PERIOD_ONCE) {
?>
        <tr>
          <td></td>
          <td>
            <input type="hidden" name="HC_dateend" value="" class="width-form" />
          </td>
        </tr>
<?php
	} else if ($charge->CH_period == Charge::PERIOD_MONTHLY) { ?>
        <tr>
          <td><?php echo _("End date:"); ?></td>
          <td>
            <script type="text/javascript" language="JavaScript" ID="jscal2x">
			var cal2x = new CalendarPopup("caldiv");
			cal2x.setDisplayType("month");
			cal2x.setMonthNames("Leden","Únor","Březen","Duben","Květen","Červen","Červenec","Srpen","Září","Říjen","Listopad","Prosinec");
			cal2x.setMonthAbbreviations("Led","Úno","Bře","Dub","Kvě","Čer","Čer","Srp","Zář","Říj","Lis","Pro");
			cal2x.setReturnMonthFunction("myMonthReturn2x");
			cal2x.showYearNavigation(true);
			cal2x.offsetX = 0;
			cal2x.offsetY = 16;
			function myMonthReturn2x(y,m) {
				if (m < 10) m = '0' + m;
				document.adminForm.HC_dateend.value=m+"/"+y;
			}
            </script>
            <input type="text" name="HC_dateend" value="<?php echo $dateEnd->getFormattedDate(DateUtil::FORMAT_MONTHLY); ?>" class="width-form-button" size="35" maxlength="10" />
            <a href="#" onclick="cal2x.showCalendar('anchor2x'); return false;" name="anchor2x" id="anchor2x"><img src="images/22x22/apps/calendar.png" style="width: 16px; height: 16px; vertical-align: middle; position: relative; top: -2px; cursor: pointer;" alt="<?php echo _("Calendar"); ?>" /></a>
          </td>
        </tr>
<?php
	} else if ($charge->CH_period == Charge::PERIOD_QUARTERLY) { ?>
        <tr>
          <td><?php echo _("End date:"); ?></td>
          <td>
            <script type="text/javascript" language="JavaScript" ID="jscal2x">
			var cal2x = new CalendarPopup("caldiv");
			cal2x.setDisplayType("quarter");
			cal2x.setReturnQuarterFunction("myQuarterReturn2x");
			cal2x.showYearNavigation(true);
			cal2x.offsetX = 0;
			cal2x.offsetY = 16;
			function myQuarterReturn2x(y,q) {
				document.adminForm.HC_dateend.value=q+"/"+y;
			}
            </script>
            <input type="text" name="HC_dateend" value="<?php echo $dateEnd->getFormattedDate(DateUtil::FORMAT_QUARTERLY); ?>" class="width-form-button" size="35" maxlength="10" />
            <a href="#" onclick="cal2x.showCalendar('anchor2x'); return false;" name="anchor2x" id="anchor2x"><img src="images/22x22/apps/calendar.png" style="width: 16px; height: 16px; vertical-align: middle; position: relative; top: -2px; cursor: pointer;" alt="<?php echo _("Calendar"); ?>" /></a>
          </td>
        </tr>
<?php
	} else if ($charge->CH_period == Charge::PERIOD_HALFYEARLY) { ?>
        <tr>
          <td><?php echo _("End date:"); ?></td>
          <td>
            <script type="text/javascript" language="JavaScript" ID="jscal2x">
			var cal2x = new CalendarPopup("caldiv");
			cal2x.setDisplayType("half");
			cal2x.setReturnHalfFunction("myHalfReturn2x");
			cal2x.showYearNavigation(true);
			cal2x.offsetX = 0;
			cal2x.offsetY = 16;
			function myHalfReturn2x(y,h) {
				document.adminForm.HC_dateend.value=h+"/"+y;
			}
            </script>
            <input type="text" name="HC_dateend" value="<?php echo $dateEnd->getFormattedDate(DateUtil::FORMAT_HALFYEARLY); ?>" class="width-form-button" size="35" maxlength="10" />
            <a href="#" onclick="cal2x.showCalendar('anchor2x'); return false;" name="anchor2x" id="anchor2x"><img src="images/22x22/apps/calendar.png" style="width: 16px; height: 16px; vertical-align: middle; position: relative; top: -2px; cursor: pointer;" alt="<?php echo _("Calendar"); ?>" /></a>
          </td>
        </tr>
<?php
	} else if ($charge->CH_period == Charge::PERIOD_YEARLY) { ?>
        <tr>
          <td><?php echo _("End date:"); ?></td>
          <td>
            <script type="text/javascript" language="JavaScript" ID="jscal2x">
			var cal2x = new CalendarPopup("caldiv");
			cal2x.setDisplayType("month");
			cal2x.setReturnYearFunction("myMonthReturn2x");
			cal2x.showYearNavigation(true);
			cal2x.offsetX = 0;
			cal2x.offsetY = 16;
			function myMonthReturn2x(y,m) {
				if (m < 10) m = '0' + m;
				document.adminForm.HC_dateend.value=m+"/"+y;
			}
            </script>
            <input type="text" name="HC_dateend" value="<?php echo $dateEnd->getFormattedDate(DateUtil::FORMAT_MONTHLY);; ?>" class="width-form-button" size="35" maxlength="10" />
            <a href="#" onclick="cal2x.showCalendar('anchor2x'); return false;" name="anchor2x" id="anchor2x"><img src="images/22x22/apps/calendar.png" style="width: 16px; height: 16px; vertical-align: middle; position: relative; top: -2px; cursor: pointer;" alt="<?php echo _("Calendar"); ?>" /></a>
          </td>
        </tr>
<?php
	} ?>
        <tr>
          <td><?php echo _("Status:"); ?></td>
          <td>
            <select name="HC_status" class="width-form">
<?php
	foreach(HasCharge::$STATUS_ARRAY as $k) {
?>
              <option value="<?php echo $k; ?>" <?php echo ($hascharge->HC_status == $k) ? 'selected="selected"' : ""; ?>><?php echo HasCharge::getLocalizedStatus($k); ?></option>
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
    <input type="hidden" name="option" value="com_person" />
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="HC_haschargeid" value="<?php echo $hascharge->HC_haschargeid; ?>" />
    <input type="hidden" name="PE_personid" value="<?php echo $hascharge->HC_personid; ?>" />
    <input type="hidden" name="HC_personid" value="<?php echo $hascharge->HC_personid; ?>" />
    <input type="hidden" name="HC_chargeid" value="<?php echo $charge->CH_chargeid; ?>" />
    <input type="hidden" name="hidemainmenu" value="0" />
    </form>
    </div>
    
    <div class="clr"></div>
</div>

<div class="clr"></div>
</div>

<script type="text/javascript" language="JavaScript">
	var formValidator = new Validator("adminForm");
	formValidator.addValidation("HC_datestart","required","<?php echo _("Please enter start date"); ?>");
	formValidator.addValidation("HC_datestart","date=<?php echo $jsFormat; ?>","<?php echo _("Start date is in incorrect format"); ?>");

<?php
	if ($charge->CH_period == Charge::PERIOD_MONTHLY) {
?>
		formValidator.addValidation("HC_dateend","date=MM/yyyy","<?php echo _("End date is in incorrect format"); ?>");
<?php
	} else if ($charge->CH_period == Charge::PERIOD_QUARTERLY) {
?>
//		formValidator.addValidation("HC_dateend","date=yyyy","<?php echo _("End date is in incorrect format"); ?>");
		//TODO> implement validator
<?php
	} else if ($charge->CH_period == Charge::PERIOD_HALFYEARLY) {
?>
//		formValidator.addValidation("HC_dateend","date=yyyy","<?php echo _("End date is in incorrect format"); ?>");
		//TODO implement validator
<?php
	} else if ($charge->CH_period == Charge::PERIOD_YEARLY) {
?>
		formValidator.addValidation("HC_dateend","date=MM/yyyy","<?php echo _("End date is in incorrect format"); ?>");
<?php
	}
?>
</script>
<?php
	}
} // End of HTML_person class
?>