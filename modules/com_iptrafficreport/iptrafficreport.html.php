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

class HTML_IpTrafficReport {
	/**
	 * showEntries for selected BankAccount
	 * @param $groups
	 * @param $pageNav
	 */
	static function showTraffic(&$ips, &$report, &$filter, &$pageNav) {
		global $core;
?>
<script type="text/javascript" language="javascript" src="js/CalendarPopup.js"></script>
<script type="text/javascript" language="JavaScript" ID="jscal1x">
	document.write(getCalendarStyles());
  	function filterChange() {
  		document.getElementById('date_fromx').value = document.adminForm.date_from.value;
  		document.getElementById('date_tox').value = document.adminForm.date_to.value; 
  		document.adminForm.submit();
  	}
  	function sortChange(sort_key, sort_direction) {
  		document.getElementById('sort_key').value = sort_key;
  		document.getElementById('sort_direction').value = sort_direction; 
  		document.adminForm.submit();
  	}
	var cal1x = new CalendarPopup("caldiv");
	cal1x.setMonthNames("Leden","Únor","Březen","Duben","Květen","Červen","Červenec","Srpen","Září","Říjen","Listopad","Prosinec");
	cal1x.showYearNavigation(true);
	cal1x.setDayHeaders("N","P","Ú","S","Č","P","S");
	cal1x.setWeekStartDay(1);
	cal1x.setTodayText("Dnes");
	cal1x.offsetX = -125;
	cal1x.offsetY = 25;
	cal1x.setFireFunctionOnHide('filterChange();');
	var cal2x = new CalendarPopup("caldiv");
	cal2x.setMonthNames("Leden","Únor","Březen","Duben","Květen","Červen","Červenec","Srpen","Září","Říjen","Listopad","Prosinec");
	cal2x.showYearNavigation(true);
	cal2x.setDayHeaders("N","P","Ú","S","Č","P","S");
	cal2x.setWeekStartDay(1);
	cal2x.setTodayText("Dnes");
	cal2x.offsetX = -125;
	cal2x.offsetY = 25;
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
        <div class="header icon-48-traffic-report">
          <?php echo _("IP data traffic report"); ?>
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
    <form action="index2.php" method="post" name="adminForm" enctype="multipart/form-data">
    <table>
    <tr>
      <td rowspan="3" valign="middle"><?php echo _("Filter:"); ?></td>
      <td align="right">
      <td><input type="text" name="filter[search]" value="<?php echo $filter['search']; ?>" class="width-form" onchange="document.adminForm.submit();" /></td>
      <td align="right">
        <select name="filter[period]" class="width-form" size="1" onchange="document.adminForm.submit( );">
<?php
	foreach ($report['options'] as $k => $period) {
		echo '<option value="' . $k . '"'; if ($filter['period'] == $k) echo 'selected="selected"'; echo ">$period</option>";
	}
?>
        </select>
      </td>
      <td rowspan="2">
        <table>
        <tr>
          <td><?php echo _("Since:"); ?></td>
          <td><input type="hidden" name="filter[date_from]" id="date_fromx" value="<?php echo $filter['date_from']; ?>" /><input type="text" name="date_from" class="width-form" value="<?php echo $filter['date_from']; ?>" size="10" onchange="filterChange()" /></td>
          <td><a href="#" onclick="cal1x.select(document.adminForm.date_from,'anchor1x','dd.MM.yyyy'); return false;" name="anchor1x" id="anchor1x"><img src="images/22x22/apps/calendar.png" style="width: 16px; height: 16px; vertical-align: middle; position: relative; top: -2px; cursor: pointer;" alt="<?php echo _("Calendar"); ?>" /></a></td>
        </tr>
        <tr>
          <td><?php echo _("Till:"); ?></td>
          <td><input type="hidden" name="filter[date_to]" id="date_tox" value="<?php echo $filter['date_to']; ?>" /><input type="text" name="date_to" class="width-form" value="<?php echo $filter['date_to']; ?>" size="10" onchange="filterChange();" /></td>
          <td><a href="#" onclick="cal2x.select(document.adminForm.date_to,'anchor2x','dd.MM.yyyy'); return false;" name="anchor2x" id="anchor2x"><img src="images/22x22/apps/calendar.png" style="width: 16px; height: 16px; vertical-align: middle; position: relative; top: -2px; cursor: pointer;" alt="<?php echo _("Calendar"); ?>" /></a></td>
        </tr>
        </table>
      </td>
      <td><input type="checkbox" name="filter[show_rate]" value="checked" onChange="document.adminForm.submit();" <?php if ($filter['show_rate'] == 'checked') echo 'checked="checked"'; ?> /><?php echo _("Show average rate"); ?></td>
    </tr>
    </table>
    <table class="adminlist">
    <thead>
    <tr>
<?php
//	$url = 'index2.php?option=com_iptrafficreport?sort_key=%s&sort_direction=%s';
//	$urlSurname = sprintf($url, "Surname", ($filter['SORT_DIRECTION'] == "ASC") ? "DESC" : "ASC");
?>
     <th width="20" class="title"><a href="#" onclick="sortChange('<?php echo IpDAO::data; ?>', 'ASC')">#</a></th>
     <th class="title"><a href="#" onclick="sortChange('<?php echo IpDAO::PE_surname; ?>', 'ASC')"><?php echo _("Surname"); ?></a></th>
     <th class="title"><a href="#" onclick="sortChange('<?php echo IpDAO::PE_firstname; ?>', 'ASC')"><?php echo _("Firstname"); ?></a></th>
     <th class="title"><a href="#" onclick="sortChange('<?php echo IpDAO::PE_nick; ?>', 'ASC')"><?php echo _("Nickname"); ?></a></th>
     <th class="title"><a href="#" onclick="sortChange('<?php echo IpDAO::IP_address; ?>', 'ASC')"><?php echo _("IP address"); ?></a></th>
<?php
	foreach ($report['intervals'] as &$column) {
?>
     <th width="50" class="title-right"><?php echo $column; ?></th>
<?php
	}
    unset($column);
?>
   </tr>
   </thead>
    <tfoot>
    <tr>
      <td colspan="<?php echo sizeof($report['intervals']) + 5; ?>">
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
	foreach ($ips as &$ip) {
?>
   <tr class="<?php echo "row$k"; ?>" >
     <td width="20">
       <?php echo $i+1+$pageNav->limitstart; ?>
     </td>
     <td width="100"><?php echo $ip->PE_surname; ?></td>
     <td width="100"><?php echo $ip->PE_firstname; ?></td>
     <td width="100"><?php echo $ip->PE_nick; ?></td>
     <td><?php echo $ip->IP_address; ?></td>
<?php
    foreach ($report['intervals'] as $column) {
        if (!isset($ip->data[$column])) {
?>
    <td class="right noWrap">N/A<br/>N/A</td>
<?php
        } else {
            $ipDateReport = $ip->data[$column];
?>
<td class="right noWrap">
    <?php
    if ($filter['show_rate'] == 'checked') {
        echo NumberFormat::formatMbitps($ipDateReport->IA_bytes_in) . "<br/>" . NumberFormat::formatMbitps($ipDateReport->IA_bytes_out);
    } else {
        echo NumberFormat::formatMB($ipDateReport->IA_bytes_in) . "<br/>" . NumberFormat::formatMB($ipDateReport->IA_bytes_out);
    }
    ?>
</td>
<?php
        }
	}
?>
   </tr>
<?php
		$k = 1 - $k;
		$i++;
	}
?>
    </tbody>
    </table>
    <input type="hidden" name="option" value="com_iptrafficreport" />
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="boxchecked" value="0" />
    <input type="hidden" name="hidemainmenu" value="0" />
    <input type="hidden" name="filter[sort_key]" id="sort_key" value="<?php echo $filter['sort_key']?>" />
    <input type="hidden" name="filter[sort_direction]" id="sort_direction" value="<?php echo $filter['sort_direction']?>" />
    </form>
    </div>
    
    <div class="clr"></div>
  
  </div>

  <div class="clr"></div>
</div>
<?php
	}
}
?>