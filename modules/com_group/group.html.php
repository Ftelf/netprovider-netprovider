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
 * HTML_group
 */
class HTML_group {
	/**
	 * showGroups
	 * @param $groups
	 * @param $pageNav
	 */
	static function showGroups(&$groups, &$pageNav) {
		global $core;
?>
<script language="JavaScript" type="text/javascript">
	function edit(id) {
    	var form = document.adminForm;
    	form.GR_groupid.value = id;
    	hideMainMenu();
   		submitform('edit');
	}
	function newG() {
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
              <a href="javascript:newG();">
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
        
        <div class="header icon-48-group">
          <?php echo _("User groups"); ?>
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
       <th width="43%" class="title"><?php echo _("Group name"); ?></th>
<?php /*     <th width="56%" class="title"><?php echo _("Access rights"); ?></th>*/?>
       <th width="43%" class="title"><?php echo _("User level"); ?></th>
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
	foreach ($groups as $group) {
		$link = "javascript:edit('$group->GR_groupid');";
?>
    <tr class="<?php echo "row$k"; ?>">
      <td>
        <?php echo $pageNav->rowNumber($i); ?>
      </td>
      <td>
        <input type="checkbox" id="<?php echo "cb$i"; ?>" name="cid[]" value="<?php echo $group->GR_groupid; ?>" onclick="isChecked(this.checked);" />
      </td>
      <td>
        <a href="<?php echo $link; ?>"><?php echo $group->GR_name; ?></a>
      </td>
<?php /*     <td>
       <a href="<?php echo $link; ?>"><?php echo $group->GR_acl; ?></a>
     </td>*/?>
      <td>
        <a href="<?php echo $link; ?>"><?php echo Group::getLocalizedLevel($group->GR_level); ?></a>
      </td>
    </tr>
<?php
		$k = 1 - $k;
		$i++;
	}
?>
    </tbody>
    </table>
    <input type="hidden" name="option" value="com_group" />
    <input type="hidden" name="GR_groupid" value="" />
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
	static function editGroup($group) {
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

        <div class="header icon-48-group">
          <?php echo _("User groups management:"); ?>: <small><?php echo _("Edit"); ?></small>
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
          <th colspan="2"><?php echo _("User group"); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td width="150"><?php echo _("Group name:"); ?></td>
          <td width="205"><input type="text" name="GR_name" class="width-form" size="40" value="<?php echo $group->GR_name?>" maxlength="255" /></td>
        </tr>
<?php /*        <tr>
          <td><?php echo _("Access rights:"); ?></td>
          <td><input type="text" name="GR_acl" class="width-form" size="40" value="<?php echo $group->GR_acl?>" /></td>
        </tr> */ ?>
        <tr>
          <td><?php echo _("User level:"); ?></td>
          <td>
            <select name="GR_level" class="width-form" size="1">
<?php
	foreach (Group::$LEVEL_ARRAY as $gk) {
?>
              <option value="<?php echo $gk; ?>" <?php echo ($group->GR_level == $gk) ? 'selected="selected"' : ""; ?>><?php echo Group::getLocalizedLevel($gk); ?></option>
<?php
	}
?>
	        </select>
	      </td>
        </tr>
        </tbody>
        </table>
      </td>
      <td valign="top">
        &nbsp;
      </td>
    </tr>
    </table>
    <input type="hidden" name="GR_groupid" value="<?php echo $group->GR_groupid; ?>" />
    <input type="hidden" name="option" value="com_group" />
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
	formValidator.addValidation("GR_name","required","<?php echo _("Please enter group name"); ?>");
</script>
<?php
	}
} // End of HTML_group class
?>