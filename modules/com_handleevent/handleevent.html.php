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

/**
 * HTML_handleevent
 */
class HTML_handleevent {
	/**
	 * showGroups
	 * @param $groups
	 * @param $pageNav
	 */
	static function showHandleEvents(&$handleevents, &$persons, &$pageNav) {
		global $core;
?>
<script language="JavaScript" type="text/javascript">
	function edit(id) {
    	var form = document.adminForm;
    	form.HE_handleeventid.value = id;
    	hideMainMenu();
   		submitform('edit');
	}
	function newHE() {
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
			var confirm = window.confirm('<?php echo _("Do you really want to delete selected records ?"); ?>');
			if (confirm) {
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
              <a href="javascript:newHE();">
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

        <div class="header icon-48-event-handler">
          <?php echo _("Event handlers"); ?>
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
    <table class="adminlist">
    <thead>
      <tr>
       <th width="2%" class="title">#</th>
       <th width="2%" class="title"><input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo $pageNav->limit; ?>);" /></th>
       <th width="10%" class="title"><?php echo _("Enabled"); ?></th>
       <th width="10%" class="title"><?php echo _("Type"); ?></th>
       <th width="20%" class="title"><?php echo _("Event handler name"); ?></th>
       <th width="20%" class="title"><?php echo _("Notify person"); ?></th>
       <th width="20%" class="title"><?php echo _("Notify days before turn off"); ?></th>
       <th width="16%" class="title"><?php echo _("Template file"); ?></th>
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
	foreach ($handleevents as &$handleevent) {
		$link = "javascript:edit('$handleevent->HE_handleeventid');";
		if ($handleevent->HE_notifypersonid) {
			$person = $persons[$handleevent->HE_notifypersonid];
			$personName = $person->PE_firstname." ".$person->PE_surname;
		} else {
			$personName = _("Event origin");
		}
?>
    <tr class="<?php echo "row$k"; ?>">
      <td>
        <?php echo $pageNav->rowNumber($i); ?>
      </td>
      <td>
        <input type="checkbox" id="<?php echo "cb$i"; ?>" name="cid[]" value="<?php echo $handleevent->HE_handleeventid; ?>" onclick="isChecked(this.checked);" />
      </td>
      <td>
        <?php echo HandleEvent::getLocalizedStatus($handleevent->HE_status); ?>
      </td>
      <td>
        <?php echo HandleEvent::getLocalizedType($handleevent->HE_type); ?>
      </td>
      <td>
        <a href="<?php echo $link; ?>"><?php echo $handleevent->HE_name; ?></a>
      </td>
      <td>
        <?php echo $personName; ?>
      </td>
      <td>
        <?php echo $handleevent->HE_notifydaysbeforeturnoff; ?>
      </td>
      <td>
        <?php echo $handleevent->HE_templatepath; ?>
      </td>
    </tr>
<?php
		$k = 1 - $k;
		$i++;
	}
?>
    </tbody>
    </table>
    <input type="hidden" name="option" value="com_handleevent" />
    <input type="hidden" name="HE_handleeventid" value="" />
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
	 * editGroup
	 * @param $group
	 */
	static function editHandleEvent($handleEvent, $persons, $templates) {
		global $core;
?>
<script language="javascript" type="text/javascript">
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

        <div class="header icon-48-event-handler">
          <?php echo _("Event handlers management"); ?>: <small><?php echo _("Edit"); ?></small>
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
      <td width="365" valign="top">
        <table class="adminform">
        <thead>
        <tr>
          <th colspan="2"><?php echo _("Event handler"); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td width="150"><?php echo _("Enabled:"); ?></td>
          <td width="205">
            <select name="HE_status" class="width-form" size="1">
<?php
	foreach (HandleEvent::$STATUS_ARRAY as $tk) {
?>
              <option value="<?php echo $tk; ?>" <?php echo ($handleEvent->HE_status == $tk) ? 'selected="selected"' : ""; ?>><?php echo HandleEvent::getLocalizedStatus($tk); ?></option>
<?php
	}
?>
	        </select>
        </tr>
        <tr>
          <td><?php echo _("Type:"); ?></td>
          <td>
            <select name="HE_type" class="width-form" size="1">
<?php
	foreach (HandleEvent::$TYPE_ARRAY as $tk) {
?>
              <option value="<?php echo $tk; ?>" <?php echo ($handleEvent->HE_type == $tk) ? 'selected="selected"' : ""; ?>><?php echo HandleEvent::getLocalizedType($tk); ?></option>
<?php
	}
?>
	        </select>
	      </td>
        </tr>
        <tr>
          <td><?php echo _("Name:"); ?></td>
          <td><input type="text" name="HE_name" class="width-form" size="40" value="<?php echo $handleEvent->HE_name?>" maxlength="255" /></td>
        </tr>
        <tr>
          <td><?php echo _("Notify person:"); ?></td>
          <td>
            <select name="HE_notifypersonid" class="width-form">
              <option value="0" <?php if ($handleEvent->HE_notifypersonid == null) { echo 'selected="selected"'; } ?>><?php echo _("Event origin"); ?></option>
<?php
	foreach($persons as $person) {
?>
              <option value="<?php echo $person->PE_personid; ?>"<?php echo ($handleEvent->HE_notifypersonid == $person->PE_personid) ? ' selected="selected"' : ""; ?>><?php echo $person->PE_surname." ".$person->PE_firstname." (".$person->PE_nick.")"; ?></option>
<?php
	}
?>
            </select>
          </td>
        </tr>
        <tr>
          <td><?php echo _("Notify days before turn off:"); ?></td>
          <td>
            <select name="HE_notifydaysbeforeturnoff" class="width-form" size="1">
<?php
	for ($i = - 60; $i <= 60; $i++) {
		echo '<option value="' . $i . '"' ; if ($handleEvent->HE_notifydaysbeforeturnoff == $i) echo ' selected="selected"';echo ">$i</option>\n";
	}
?>
	        </select>
          </td>
        </tr>
        <tr>
          <td><?php echo _("Email subject:"); ?></td>
          <td><input type="text" name="HE_emailsubject" class="width-form" size="40" value="<?php echo $handleEvent->HE_emailsubject?>" maxlength="255" /></td>
        </tr>
        <tr>
          <td><?php echo _("Template:"); ?></td>
          <td>
            <select name="HE_templatepath" class="width-form">
<?php
	foreach ($templates as $template) {
?>
              <option value="<?php echo $template; ?>"<?php echo ($handleEvent->HE_templatepath == $template) ? ' selected="selected"' : ""; ?>><?php echo $template; ?></option>
<?php
	}
?>
            </select>
          </td>
        </tr>
        <tr>
          <td><?php echo _("Description:"); ?></td>
          <td><input type="text" name="HE_description" class="width-form" size="40" value="<?php echo $handleEvent->HE_description?>" maxlength="255" /></td>
        </tr>
        </tbody>
        </table>
      </td>
      <td valign="top">
        &nbsp;
      </td>
    </tr>
    </table>
    <input type="hidden" name="HE_handleeventid" value="<?php echo $handleEvent->HE_handleeventid; ?>" />
    <input type="hidden" name="option" value="com_handleevent" />
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="hidemainmenu" value="0" />
    </form>
    </div>

    <div class="clr"></div>
</div>

<div class="clr"></div>
</div>

<script type="text/javascript" language="JavaScript">
	var formValidator = new Validator("adminForm");
	formValidator.addValidation("HE_name","required","<?php echo _("Please enter event handler name"); ?>");
</script>
<?php
	}
} // End of HTML_handleevent class
?>
