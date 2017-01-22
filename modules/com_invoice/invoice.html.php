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
 * HTML_invoice
 */
class HTML_invoice {
	/**
	 * showInvoices
	 * @param $invoices
	 * @param $persons
	 * @param $pageNav
	 */
	function showInvoices(&$invoices, &$groups, &$pageNav, &$filter) {
		global $core;
?>
<script type="text/javascript" language="javascript" src="js/CalendarPopup.js"></script>
<script type="text/javascript" language="JavaScript">
	document.write(getCalendarStyles());
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
	function download(id) {
		window.open("download.php?option=com_invoice&task=download&IN_invoiceid="+id,'_blank','');
	}
  	function createList() {
  		window.open("download.php?option=com_invoice&task=createList",'_blank','');
  	}
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
            <td id="toolbar-create-invoice-set">
              <a href="javascript:createList();">
                <span title="<?php echo _("Create invoice list"); ?>" class="icon-32-create-invoice-list"></span>
                <?php echo _("Create invoice set"); ?>
              </a>
            </td>
          </tr>
          </table>
        </div>
        
        <div class="header icon-48-invoices">
          <?php echo _("Invoices"); ?>
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
        <select name="filter[person_status]" size="1" onchange="document.adminForm.submit( );">
        <option value="-1" <?php if ($filter['person_status'] == -1) echo ' selected="selected"';?>><?php echo _("- Status -"); ?></option>
<?php
	foreach (Person::$STATUS_ARRAY as $k) {
?>
          <option value="<?php echo $k; ?>" <?php echo ($filter['person_status'] == $k) ? 'selected="selected"' : ""; ?>><?php echo Person::getLocalizedStatus($k); ?></option>
<?php
	}
?>
        </select>
      </td>
      <td align="right">
        <select name="filter[chargeentry_status]" size="1" onchange="document.adminForm.submit( );">
        <option value="-1" <?php if ($filter['chargeentry_status'] == -1) echo ' selected="selected"';?>><?php echo _("- Status -"); ?></option>
<?php
	foreach (ChargeEntry::$STATUS_ARRAY as $k) {
?>
          <option value="<?php echo $k; ?>" <?php echo ($filter['chargeentry_status'] == $k) ? 'selected="selected"' : ""; ?>><?php echo ChargeEntry::getLocalizedStatus($k); ?></option>
<?php
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
    </tr>
    </table>
    <table class="adminlist">
    <thead>
    <tr>
      <th width="2%" class="title">#</th>
      <th width="2%" class="title"><input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo $pageNav->limit; ?>);" /></th>
      <th width="10%" class="title"><?php echo _("Invoice number"); ?></th>
      <th width="10%" class="title"><?php echo _("Invoice date"); ?></th>
      <th width="10%" class="title"><?php echo _("Date of write off"); ?></th>
      <th width="10%" class="title"><?php echo _("Delayed in days"); ?></th>
      <th width="20%" class="title"><?php echo _("Person"); ?></th>
      <th width="6%" class="title"><?php echo _("Group"); ?></th>
      <th width="10%" class="title"><?php echo _("Email"); ?></th>
      <th width="10%" class="title"><?php echo _("Amount without VAT"); ?></th>
      <th width="10%" class="title"><?php echo _("Amount with VAT"); ?></th>
      <th width="10%" class="title"><?php echo _("Currency"); ?></th>
      <th width="10%" class="title"><?php echo _("Status"); ?></th>
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
	foreach ($invoices as $invoice) {
		$link = "javascript:download('$invoice->IN_invoiceid');";
		$invoiceDate = new DateUtil($invoice->IN_invoicedate);
		$dateOfPayDate = new DateUtil($invoice->IN_dateofpay);
?>
    <tr class="<?php echo "row$k"; ?>">
      <td>
        <?php echo $pageNav->rowNumber($i); ?>
      </td>
      <td>
         <input type="checkbox" id="<?php echo "cb$i"; ?>" name="cid[]" value="<?php echo $invoice->IN_invoiceid; ?>" onclick="isChecked(this.checked);" />
      </td>
      <td>
        <a href="<?php echo $link; ?>"><?php echo $invoice->IN_invoicenumber; ?></a>
      </td>
      <td>
        <?php echo $invoiceDate->getFormattedDate(DateUtil::FORMAT_DATE); ?>
      </td>
      <td>
        <?php echo $dateOfPayDate->getFormattedDate(DateUtil::FORMAT_DATE); ?>
      </td>
      <td>
        <?php echo $invoice->CE_overdue; ?>
      </td>
      <td>
        <?php echo $invoice->PE_firstname.' '.$invoice->PE_surname; ?>
      </td>
      <td>
        <?php echo $invoice->GR_name; ?>
      </td>
      <td>
        <?php echo $invoice->PE_email; ?>
      </td>
      <td>
        <?php echo NumberFormat::formatMoney($invoice->IN_baseamount); ?>
      </td>
      <td>
        <?php echo NumberFormat::formatMoney($invoice->IN_amount); ?>
      </td>
      <td>
        <?php echo $invoice->CE_currency; ?>
      </td>
      <td>
        <?php echo ChargeEntry::getLocalizedStatus($invoice->CE_status); ?>
      </td>
    </tr>
<?php
		$k = 1 - $k;
		$i++;
	}
?>
    </tbody>
    </table>
    <input type="hidden" name="option" value="com_invoice" />
    <input type="hidden" name="IN_invoiceid" value="" />
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
} // End of HTML_invoice class
?>