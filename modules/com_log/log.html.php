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

class HTML_log {
	/**
	 * showLog
	 * @param $log
	 * @param $pageNav
	 */
	static function showLog(&$logs, &$persons, &$pageNav, &$filter) {
		global $core;
?>
<script type="text/javascript" language="javascript" src="js/CalendarPopup.js"></script>
<script type="text/javascript" language="JavaScript">document.write(getCalendarStyles());</script>
<script language="JavaScript" type="text/javascript">
	function editP(id) {
    	var form = document.adminForm;
    	form.option.value = 'com_person';
    	form.PE_personid.value = id;
    	hideMainMenu();
   		submitform('edit');
	}
  	function remove() {
		if (document.adminForm.boxchecked.value == 0) {
			alert('<?php echo _("Please select record to edit"); ?>');
		} else {
			var confirm = window.confirm("<?php echo _("Do you really want to delete selected records ?"); ?>");
			if (confirm) {
				submitbutton('remove');
			}
		}
  	}
  	function filterChange() {
  		document.getElementById('date_fromx').value = document.adminForm.date_from.value;
  		document.getElementById('date_tox').value = document.adminForm.date_to.value; 
  		document.adminForm.submit();
  	}

	var cal1x = new CalendarPopup("caldiv");
	cal1x.setMonthNames("Leden","Únor","Březen","Duben","Květen","Červen","Červenec","Srpen","Září","Říjen","Listopad","Prosinec");
	cal1x.showYearNavigation(true);
	cal1x.setDayHeaders("N","P","Ú","S","Č","P","S");
	cal1x.setWeekStartDay(1);
	cal1x.setTodayText("Dnes");
	cal1x.offsetX = 0;
	cal1x.offsetY = 20;
	cal1x.setFireFunctionOnHide('filterChange();');

	var cal2x = new CalendarPopup("caldiv");
	cal2x.setMonthNames("Leden","Únor","Březen","Duben","Květen","Červen","Červenec","Srpen","Září","Říjen","Listopad","Prosinec");
	cal2x.showYearNavigation(true);
	cal2x.setDayHeaders("N","P","Ú","S","Č","P","S");
	cal2x.setWeekStartDay(1);
	cal2x.setTodayText("Dnes");
	cal2x.offsetX = 0;
	cal2x.offsetY = 20;
	cal2x.setFireFunctionOnHide('filterChange();');
</script>
<div id="caldiv" style="position:absolute;visibility:hidden;background-color:white;layer-background-color:white;"></div>

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
            <td id="toolbar-delete">
              <a href="javascript:remove();">
                <span title="<?php echo _("Delete"); ?>" class="icon-32-delete"></span>
                <?php echo _("Delete"); ?>
              </a>
            </td>
          </tr>
          </table>
        </div>
        
        <div class="header icon-48-log">
          <?php echo _("Log list"); ?>
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
        <select name="filter[log_level]" size="1" onchange="document.adminForm.submit( );">
        <option value="0" <?php if ($filter['log_level'] == -1) echo ' selected="selected"';?>><?php echo _("- Log level -"); ?></option>
<?php
	foreach (LOG::$LEVEL_ARRAY as $logLevel) {
		echo '<option value="' . $logLevel . '"'; if ($filter['log_level'] == $logLevel) echo ' selected="selected"'; echo ">".Log::getLocalizedLevel($logLevel)."</option>";
	}
?>
        </select>
      </td>
      <td><input type="hidden" name="filter[date_from]" id="date_fromx" value="<?php echo $filter['date_from']; ?>" /><input type="text" name="date_from" class="inputbox" value="<?php echo $filter['date_from']; ?>" size="10" onchange="filterChange();" /></td>
      <td><a href="#" onclick="cal1x.select(document.adminForm.date_from,'anchor1x','dd.MM.yyyy'); return false;" name="anchor1x" id="anchor1x"><img src="images/22x22/apps/calendar.png" style="width: 16px; height: 16px; vertical-align: middle; position: relative; top: -2px; cursor: pointer;" alt="<?php echo _("Calendar"); ?>" /></a></td>
      <td><input type="hidden" name="filter[date_to]" id="date_tox" value="<?php $filter['date_to']; ?>" /><input type="text" name="date_to" class="inputbox" value="<?php echo $filter['date_to']; ?>" size="10" onchange="filterChange();" /></td>
      <td><a href="#" onclick="cal2x.select(document.adminForm.date_to,'anchor2x','dd.MM.yyyy'); return false;" name="anchor2x" id="anchor2x"><img src="images/22x22/apps/calendar.png" style="width: 16px; height: 16px; vertical-align: middle; position: relative; top: -2px; cursor: pointer;" alt="<?php echo _("Calendar"); ?>" /></a></td>
      <td align="right">
        <select name="filter[personid]" class="inputbox" size="1" onchange="document.adminForm.submit( );">
        <option value="0" <?php if ($filter['personid'] == 0) echo 'selected="selected"';?>><?php echo _("- User -"); ?></option>
<?php
	foreach ($persons as $person) {
		echo '<option value="' . $person->PE_personid . '"'; if ($filter['personid'] == $person->PE_personid) echo 'selected="selected"'; echo ">$person->PE_surname $person->PE_firstname</option>";
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
     <th width="10%" class="title"><?php echo _("Time"); ?></th>
     <th width="10%" class="title"><?php echo _("Level"); ?></th>
     <th width="16%" class="title"><?php echo _("Action taken by"); ?></th>
     <th width="60%" class="title"><?php echo _("Description"); ?></th>
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
	foreach ($logs as $log) {
		$linkPerson = "javascript:editP('". $log->LO_personid . "');";
		$logDate = new DateUtil($log->LO_datetime);
		
		if ($log->LO_personid == 0) {
			$loggerName = _("System");
		} else {
			$loggerName = $persons[$log->LO_personid]->PE_firstname . " ". $persons[$log->LO_personid]->PE_surname;
		}
?>
   <tr class="<?php echo "row$k"; ?>">
     <td>
       <?php echo $pageNav->rowNumber($i); ?>
     </td>
     <td>
       <input type="checkbox" id="<?php echo "cb$i"; ?>" name="cid[]" value="<?php echo $log->LO_logid; ?>" onclick="isChecked(this.checked);" />
     </td>
     <td><?php echo $logDate->getFormattedDate(DateUtil::FORMAT_FULL);; ?>
     </td>
      <td>
       <a href="<?php echo $linkPerson; ?>"><?php echo Log::getLocalizedLevel($log->LO_level); ?></a>
     </td>
     <td>
       <a href="<?php echo $linkPerson; ?>"><?php echo $loggerName; ?></a>
     </td>
     <td><?php echo $log->LO_log; ?>
     </td>
   </tr>
<?php
	$k = 1 - $k;
	$i++;
	}
?>
    </tbody>
    </table>
    <input type="hidden" name="option" value="com_log" />
    <input type="hidden" name="LO_logid" value="" />
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
} // End of HTML_log class
?>
