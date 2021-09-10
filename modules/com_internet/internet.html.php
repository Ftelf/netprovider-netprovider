<?php
//
// +----------------------------------------------------------------------+
// | Ftelf ISP billing system                                               |
// +----------------------------------------------------------------------+
// | Copyright (c) 2006-2007 Ing. Lukas Dziadkowiec                       |
// +----------------------------------------------------------------------+
// | This source file is part of Ftelf ISP billing system,                  |
// | see LICENSE for licence details.                                     |
// +----------------------------------------------------------------------+
// | Authors: Lukas Dziadkowiec <i.ftelf@gmail.com>                  |
// +----------------------------------------------------------------------+

/**
 * @author  Lukas Dziadkowiec <i.ftelf@gmail.com>
 */

/** ensure this file is being included by a parent file */
defined('VALID_MODULE') or die(_("Direct access into this section is not allowed"));

/**
 * HTML_internet
 */
class HTML_internet {
	/**
	 * showInternet
	 * @param $internet
	 * @param $pageNav
	 */
	static function showInternet(&$internets, &$pageNav) {
		global $core;
?>
<script language="JavaScript" type="text/javascript">
	function edit(id) {
    	var form = document.adminForm;
    	form.IN_internetid.value = id;
    	hideMainMenu();
   		submitform('edit');
	}
	function newI() {
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
              <a href="javascript:newI();">
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
        
        <div class="header icon-48-internet-teplate">
          <?php echo _("Internet templates management"); ?>
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
     <th width="16%" class="title"><?php echo _("Template name"); ?></th>
     <th width="13%" class="title"><?php echo _("Guarant download"); ?></th>
     <th width="13%" class="title"><?php echo _("Guarant upload"); ?></th>
     <th width="12%" class="title"><?php echo _("Maximum download"); ?></th>
     <th width="12%" class="title"><?php echo _("Maximum upload"); ?></th>
     <th width="5%" class="title"><?php echo _("Priority"); ?></th>
     <th width="25%" class="title"><?php echo _("Description"); ?></th>
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
	foreach ($internets as $internet) {
		$link = "javascript:edit('$internet->IN_internetid');";
?>
   <tr class="<?php echo "row$k"; ?>">
     <td>
       <?php echo $i+1+$pageNav->limitstart; ?>
     </td>
     <td>
        <input type="checkbox" id="<?php echo "cb$i"; ?>" name="cid[]" value="<?php echo $internet->IN_internetid; ?>" onclick="isChecked(this.checked);" />
     </td>
     <td>
       <a href="<?php echo $link; ?>"><?php echo $internet->IN_name; ?></a>
     </td>
     <td>
       <?php echo ($internet->IN_dnl_rate == -1) ? "AUTO" : $internet->IN_dnl_rate . " kbps"; ?>
     </td>
     <td>
       <?php echo ($internet->IN_upl_rate == -1) ? "AUTO" : $internet->IN_upl_rate . " kbps"; ?>
     </td>
     <td>
       <?php echo $internet->IN_dnl_ceil . " kbps"; ?>
     </td>
     <td>
       <?php echo $internet->IN_upl_ceil . " kbps"; ?>
     </td>
     <td>
       <?php echo $internet->IN_prio; ?>
     </td>
     <td>
       <?php echo $internet->IN_description; ?>
     </td>
   </tr>
<?php
		$k = 1 - $k;
		$i++;
	}
?>
   </tbody>
   </table>
   <input type="hidden" name="option" value="com_internet" />
   <input type="hidden" name="IN_internetid" value="" />
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
	 * editInternet
	 * @param $internet
	 */
	static function editInternet($internet) {
		global $core;
?>
    <script language="javascript" type="text/javascript">
		function submitbutton(pressbutton) {
			if (pressbutton == 'cancel') {
				submitform( pressbutton );
				return;
			}
			if (pressbutton == 'apply') {
				hideMainMenu();
			}
			
			var form = document.adminForm;
			
			var IN_dnl_rate_temp = parseInt(form.IN_dnl_rate.value);
			var IN_dnl_ceil_temp = parseInt(form.IN_dnl_ceil.value);
			var IN_upl_rate_temp = parseInt(form.IN_upl_rate.value);
			var IN_upl_ceil_temp = parseInt(form.IN_upl_ceil.value);
			// do field validation
			//
			if (trim(form.IN_name.value) == "") {
				alert("<?php echo _("Please enter template name"); ?>");
			} else if (trim(form.IN_description.value) == "") {
				alert("<?php echo _("Please enter template description"); ?>");
			} else if (!form.IN_dnl_rate_cb.checked && (String(IN_dnl_rate_temp) != form.IN_dnl_rate.value || IN_dnl_rate_temp < 0)) {
				alert("<?php echo _("Guarant download is not in proper number format"); ?>");
			} else if (String(IN_dnl_ceil_temp) != form.IN_dnl_ceil.value || IN_dnl_rate_temp <= 0) {
				alert("<?php echo _("Maximum download is not in proper number format"); ?>");
			} else if (!form.IN_upl_rate_cb.checked && (String(IN_upl_rate_temp) != form.IN_upl_rate.value || IN_upl_rate_temp < 0)) {
				alert("<?php echo _("Guarant upload is not in proper number format"); ?>");
			} else if (String(IN_upl_ceil_temp) != form.IN_upl_ceil.value || IN_upl_ceil_temp <= 0) {
				alert("<?php echo _("Maximum upload is not in proper number format"); ?>");
			} else {
				submitform( pressbutton );
			}
		}
		function dnl_cb(checked) {
			document.adminForm.IN_dnl_rate.disabled = checked;
		}
		function upl_cb(checked) {
			document.adminForm.IN_upl_rate.disabled = checked;
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

        <div class="header icon-48-internet-teplate">
          <?php echo _("Internet templates management"); ?>: <small><?php echo _("Edit"); ?></small>
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
      <td width="460" valign="top">
        <table class="adminform">
        <thead>
        <tr>
          <th colspan="4"><?php echo _("Internet template"); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td width="250"><?php echo _("Template name:"); ?></td>
          <td colspan="3"><input type="text" name="IN_name" class="inputbox" size="40" value="<?php echo $internet->IN_name;?>" /></td>
        </tr>
        <tr>
          <td><?php echo _("Description:"); ?></td>
          <td colspan="3"><input type="text" name="IN_description" class="inputbox" size="40" value="<?php echo $internet->IN_description;?>" /></td>
        </tr>
        <tr>
          <td><?php echo _("Guarant download (kbps):"); ?></td>
          <td width="205"><input type="text" name="IN_dnl_rate" class="inputbox" size="40" value="<?php echo ($internet->IN_dnl_rate == -1) ?  "" : $internet->IN_dnl_rate;?>" <?php if ($internet->IN_dnl_rate == -1) echo 'disabled="disabled"';?>/></td>
          <td><input type="checkbox" name="IN_dnl_rate_cb" value="1" onclick="dnl_cb(this.checked);" <?php if ($internet->IN_dnl_rate == -1) echo 'checked="checked"';?>/></td>
          <td>Auto</td>
        </tr>
        <tr>
          <td><?php echo _("Guarant upload (kbps):"); ?></td>
          <td><input type="text" name="IN_upl_rate" class="inputbox" size="40" value="<?php echo ($internet->IN_upl_rate == -1) ?  "" : $internet->IN_upl_rate;?>" <?php if ($internet->IN_upl_rate == -1) echo 'disabled="disabled"';?>/></td>
          <td><input type="checkbox" name="IN_upl_rate_cb" value="1" onclick="upl_cb(this.checked);" <?php if ($internet->IN_upl_rate == -1) echo 'checked="checked"';?>/></td>
          <td>Auto</td>
        </tr>
        <tr>
          <td><?php echo _("Maximum download (kbps):"); ?></td>
          <td colspan="3"><input type="text" name="IN_dnl_ceil" class="inputbox" size="40" value="<?php echo $internet->IN_dnl_ceil;?>" /></td>
        </tr>
        <tr>
          <td><?php echo _("Maximum upload (kbps):"); ?></td>
          <td colspan="3"><input type="text" name="IN_upl_ceil" class="inputbox" size="40" value="<?php echo $internet->IN_upl_ceil;?>" /></td>
        </tr>
        <tr>
          <td><?php echo _("Priority:"); ?></td>
          <td colspan="3">
            <select name="IN_prio" class="inputbox" size="1">
<?php
	for ($i = 0; $i < 10; $i++) {
		echo '<option value="' . $i . '"' ; if ($internet->IN_prio == $i) echo ' selected="selected"';echo ">$i</option>\n";
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
    <input type="hidden" name="IN_internetid" value="<?php echo $internet->IN_internetid; ?>" />
    <input type="hidden" name="option" value="com_internet" />
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
} // End of HTML_internet class
?>
