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
 * 
 */
class HTML_role {
	/**
	 * showRoles
	 * @param $roles
	 * @param $pageNav
	 */
	static function showRoles(&$roles, &$pageNav) {
		global $core;
?>
<script language="JavaScript" type="text/javascript">
	function edit(id) {
    	var form = document.adminForm;
    	form.RO_roleid.value = id;
    	hideMainMenu();
   		submitform('edit');
	}
	function newR() {
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
              <a href="javascript:newR();">
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
        
        <div class="header icon-48-role">
          <?php echo _("User roles management"); ?>
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
      <th width="30%" class="title"><?php echo _("Role name"); ?></th>
      <th width="66%" class="title"><?php echo _("Description"); ?></th>
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
	foreach ($roles as $role) {
		$link = "javascript:edit('$role->RO_roleid');";
		
?>
    <tr class="<?php echo "row$k"; ?>">
      <td>
        <?php echo $pageNav->rowNumber($i); ?>
      </td>
      <td>
        <input type="checkbox" id="<?php echo "cb$i"; ?>" name="cid[]" value="<?php echo $role->RO_roleid; ?>" onclick="isChecked(this.checked);" />
      </td>
      <td>
        <a href="<?php echo $link; ?>"><?php echo $role->RO_name; ?></a>
      </td>
      <td>
        <a href="<?php echo $link; ?>"><?php echo $role->RO_description; ?></a>
      </td>
    </tr>
<?php
		$k = 1 - $k;
		$i++;
	}
?>
    </tbody>
    </table>
    <input type="hidden" name="option" value="com_role" />
    <input type="hidden" name="RO_roleid" value="" />
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
	 * editRole
	 * @param $role
	 */
	static function editRole($role) {
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

        <div class="header icon-48-role">
          <?php echo _("User roles management"); ?>: <small><?php echo _("Edit"); ?></small>
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
          <th colspan="2"><?php echo _("User role"); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td width="150"><?php echo _("Role name:"); ?></td>
          <td width="205"><input type="text" name="RO_name" class="width-form" size="40" value="<?php echo $role->RO_name?>" maxlength="255" /></td>
        </tr>
        <tr>
          <td><?php echo _("Description:"); ?></td>
          <td><input type="text" name="RO_description" class="width-form" size="40" value="<?php echo $role->RO_description?>" maxlength="255" /></td>
        </tr>
        </tbody>
        </table>
      </td>
      <td valign="top">
        &nbsp;
      </td>
    </tr>
    </table>
    <input type="hidden" name="RO_roleid" value="<?php echo $role->RO_roleid; ?>" />
    <input type="hidden" name="option" value="com_role" />
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
	formValidator.addValidation("RO_name","required","<?php echo _("Please enter role name"); ?>");
</script>
<?php
	}
} // End of HTML_role class
?>