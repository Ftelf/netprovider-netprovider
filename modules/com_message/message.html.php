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

class HTML_message {
	/**
	 * showMessage
	 * @param $log
	 * @param $pageNav
	 */
	static function showMessage(&$messages, &$persons, &$pageNav, &$filter) {
		global $core;
?>
<script type="text/javascript" language="javascript" src="js/CalendarPopup.js"></script>
<script type="text/javascript" language="JavaScript">document.write(getCalendarStyles());</script>
<script language="JavaScript" type="text/javascript">
  	function send() {
		submitbutton('send');
  	}
  	function remove() {
		if (document.adminForm.boxchecked.value == 0) {
			alert('<?php echo _("Please select record to delete"); ?>');
		} else {
			if (window.confirm('<?php echo _("Do you really want to delete selected records ?"); ?>')) {
				submitbutton('remove');
			}
		}
  	}
  	function filterChange() {
  		document.getElementById('date_fromx').value = document.adminForm.date_from.value;
  		document.getElementById('date_tox').value = document.adminForm.date_to.value;
  		document.adminForm.submit();
  	}
  	function editP(id) {
    	var form = document.adminForm;
    	form.option.value = 'com_person';
    	form.PE_personid.value = id;
    	hideMainMenu();
   		submitform('edit');
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
            <td id="toolbar-send">
              <a href="javascript:send();">
                <span title="<?php echo _("Send"); ?>" class="icon-32-send"></span>
                <?php echo _("Send"); ?>
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

        <div class="header icon-48-messages">
          <?php echo _("Message list"); ?>
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
      <td>Filtr:</td>
      <td><input type="hidden" name="filter[date_from]" id="date_fromx" value="<?php echo $filter['date_from']; ?>" /><input type="text" name="date_from" class="inputbox" value="<?php echo $filter['date_from']; ?>" size="10" onchange="filterChange();" /></td>
      <td><a href="#" onclick="cal1x.select(document.adminForm.date_from,'anchor1x','dd.MM.yyyy'); return false;" name="anchor1x" id="anchor1x"><img src="images/22x22/apps/calendar.png" style="width: 16px; height: 16px; vertical-align: middle; position: relative; top: -2px; cursor: pointer;" alt="<?php echo _("Calendar"); ?>" /></a></td>
      <td><input type="hidden" name="filter[date_to]" id="date_tox" value="<?php $filter['date_to']; ?>" /><input type="text" name="date_to" class="inputbox" value="<?php echo $filter['date_to']; ?>" size="10" onchange="filterChange();" /></td>
      <td><a href="#" onclick="cal2x.select(document.adminForm.date_to,'anchor2x','dd.MM.yyyy'); return false;" name="anchor2x" id="anchor2x"><img src="images/22x22/apps/calendar.png" style="width: 16px; height: 16px; vertical-align: middle; position: relative; top: -2px; cursor: pointer;" alt="<?php echo _("Calendar"); ?>" /></a></td>
      <td align="right">
        <select name="filter[personid]" class="inputbox" size="1" onchange="document.adminForm.submit( );">
        <option value="0" <?php if ($filter['personid'] == 0) echo 'selected="selected"';?>>- Uživatel -</option>
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
      <th width="10%" class="title"><?php echo _("Date"); ?></th>
      <th width="16%" class="title"><?php echo _("Recipient"); ?></th>
      <th width="10%" class="title"><?php echo _("Subject"); ?></th>
      <th width="30%" class="title"><?php echo _("Message"); ?></th>
      <th width="15%" class="title"><?php echo _("Attachment"); ?></th>
      <th width="5%" class="title"><?php echo _("Status"); ?></th>
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
	foreach ($messages as &$message) {
		$linkPerson = "javascript:editP('". $message->ME_personid . "');";
		$dateTime = new DateUtil($message->ME_datetime);

		if (isset($persons[$message->ME_personid])) {
			$personName = $persons[$message->ME_personid]->PE_firstname . " ". $persons[$message->ME_personid]->PE_surname;
		} else {
			$personName = '';
		}
?>
    <tr class="<?php echo "row$k"; ?>">
      <td>
        <?php echo $i+1+$pageNav->limitstart; ?>
      </td>
      <td>
        <input type="checkbox" id="<?php echo "cb$i"; ?>" name="cid[]" value="<?php echo $message->ME_messageid; ?>" onclick="isChecked(this.checked);" />
      </td>
      <td><?php echo $dateTime->getFormattedDate(DateUtil::FORMAT_FULL); ?>
      </td>
      <td>
        <a href="<?php echo $linkPerson; ?>"><?php echo $personName; ?></a>
      </td>
      <td>
        <?php echo $message->ME_subject; ?>
      </td>
      <td>
        <?php echo $message->ME_body; ?>
      </td>
      <td>
        <?php echo $message->_attachmentText; ?>
      </td>
      <td>
        <?php echo Message::getLocalizedStatus($message->ME_status); ?>
      </td>
    </tr>
<?php
		$k = 1 - $k;
		$i++;
	}
?>
    </tbody>
    </table>
    <input type="hidden" name="option" value="com_message" />
    <input type="hidden" name="ME_messageid" value="" />
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
} // End of HTML_message class
?>
