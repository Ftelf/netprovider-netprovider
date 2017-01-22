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

class HTML_PaymentReport {
	/**
	 * showEntries for selected BankAccount
	 * @param $charges
	 * @param $paymentReport
	 * @param $report
	 * @param $filter
	 * @param $pageNav
	 */
	function showPayments(&$charges, &$paymentReport, &$report, &$filter, &$pageNav) {
		global $core;
?>
<script type="text/javascript" language="javascript" src="js/CalendarPopup.js"></script>
<script type="text/javascript" language="JavaScript">
	document.write(getCalendarStyles());
	var cal1x = new CalendarPopup("caldiv");
	cal1x.setDisplayType("month");
	cal1x.setMonthNames("Leden","Únor","Březen","Duben","Květen","Červen","Červenec","Srpen","Září","Říjen","Listopad","Prosinec");
	cal1x.setMonthAbbreviations("Led","Úno","Bře","Dub","Kvě","Čer","Čer","Srp","Zář","Říj","Lis","Pro");
	cal1x.setReturnMonthFunction("myMonthReturn1x");
	cal1x.showYearNavigation(true);
	cal1x.offsetX = -130;
	cal1x.offsetY = 41;
	function myMonthReturn1x(y,m) {
		if (m < 10) m = '0' + m;
		document.getElementById('date_from').value=m+"/"+y;
		document.adminForm.submit();
	}
	var cal2x = new CalendarPopup("caldiv");
	cal2x.setDisplayType("month");
	cal2x.setMonthNames("Leden","Únor","Březen","Duben","Květen","Červen","Červenec","Srpen","Září","Říjen","Listopad","Prosinec");
	cal2x.setMonthAbbreviations("Led","Úno","Bře","Dub","Kvě","Čer","Čer","Srp","Zář","Říj","Lis","Pro");
	cal2x.setReturnMonthFunction("myMonthReturn2x");
	cal2x.showYearNavigation(true);
	cal2x.offsetX = -130;
	cal2x.offsetY = 20;
	function myMonthReturn2x(y,m) {
		if (m < 10) m = '0' + m;
		document.getElementById('date_to').value=m+"/"+y;
		document.adminForm.submit();
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
        <div class="header icon-48-payment-report">
          <?php echo _("Payment report"); ?>
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
      <td>
        <table>
        <tr>
      <td><input type="text" name="filter[search]" value="<?php echo $filter['search']; ?>" class="width-form" onchange="document.adminForm.submit();" /></td>
      <td align="right">
        <select name="filter[PE_status]" class="width-form" size="1" onchange="document.adminForm.submit( );">
        <option value="-1" <?php if ($filter['PE_status'] == -1) echo ' selected="selected"';?>><?php echo _("- Status -"); ?></option>
<?php
	foreach (Person::$STATUS_ARRAY as $k) {
?>
         <option value="<?php echo $k; ?>" <?php echo ($filter['PE_status'] == $k) ? 'selected="selected"' : ""; ?>><?php echo Person::getLocalizedStatus($k); ?></option>
<?php
	}
?>
        </select>
      </td>
      </tr>
      <tr>
          <td align="right">
            <select name="filter[CH_chargeid]" class="width-form" size="1" onchange="document.adminForm.submit( );">
<?php
	if (count($charges) == 0) {
		echo '<option value="0" selected="selected">'._("- No payment defined -").'- Není definována žádná platba -</option>' . "\n";
	} else {
		foreach ($charges as $charge) {
			echo '<option value="' . $charge->CH_chargeid . '"'; if ($charge->CH_chargeid == $filter['CH_chargeid']) echo ' selected="selected"'; echo ">".$charge->CH_name.". (".Charge::getLocalizedPeriod($charge->CH_period) .")</option>\n";
		}
	}
?>
            </select>
          </td>
          <td align="right">
            <select name="filter[showall]" class="width-form" size="1" onchange="document.adminForm.submit( );">
		      <option value="0"<?php if (!$filter['showall']) echo 'selected="selected"'; ?>><?php echo _("Only persons with payment assigned"); ?></option>
		      <option value="1"<?php if ($filter['showall']) echo 'selected="selected"'; ?>><?php echo _("All persons"); ?></option>
            </select>
          </td>
        </tr>
        </table>
      </td>
      <td rowspan="2">
        <table>
        <tr>
          <td>Od:</td>
          <td><input type="text" id="date_from" name="filter[date_from]" class="width-form" value="<?php echo $filter['date_from']; ?>" size="10" onchange="document.adminForm.submit( );"/></td>
          <td><a href="#" onclick="cal1x.showCalendar('anchor1x'); return false;" name="anchor1x" id="anchor1x"><img src="images/22x22/apps/calendar.png" style="width: 16px; height: 16px; vertical-align: middle; position: relative; top: -2px; cursor: pointer;" alt="<?php echo _("Calendar"); ?>" /></a></td>
        </tr>
        <tr>
          <td>Do:</td>
          <td><input type="text" id="date_to" name="filter[date_to]" class="width-form" value="<?php echo $filter['date_to']; ?>" size="10" onchange="document.adminForm.submit( );"/></td>
          <td><a href="#" onclick="cal2x.showCalendar('anchor2x'); return false;" name="anchor2x" id="anchor2x"><img src="images/22x22/apps/calendar.png" style="width: 16px; height: 16px; vertical-align: middle; position: relative; top: -2px; cursor: pointer;" alt="<?php echo _("Calendar"); ?>" /></a></td>
        </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td>
      </td>
    </tr>
    </table>
<?php
	if (isset($charges[$filter['CH_chargeid']])) {
?>
    <table>
    <tr>
    <td width="360">
    <table class="adminform">
    <thead>
    <tr>
      <th colspan="2" class="title"><?php echo _("Payment"); ?></th>
    </tr>
    </thead>
    <tbody>
    <tr>
      <td width="150"><?php echo _("Payment name:"); ?></td>
      <td width="205"><?php echo $charges[$filter['CH_chargeid']]->CH_name; ?></td>
    </tr>
    <tr>
      <td><?php echo _("Description:"); ?></td>
      <td><?php echo $charges[$filter['CH_chargeid']]->CH_description; ?></td>
    </tr>
    <tr>
      <td><?php echo _("Amount:"); ?></td>
      <td><?php echo $charges[$filter['CH_chargeid']]->CH_amount . " " . $charges[$filter['CH_chargeid']]->CH_currency. " " . Charge::getLocalizedPeriod($charges[$filter['CH_chargeid']]->CH_period); ?></td>
    </tr>
    <tr>
      <td><?php echo _("Type:"); ?></td>
      <td><?php echo Charge::getLocalizedType($charges[$filter['CH_chargeid']]->CH_type); ?></td>
    </tr>
    </tbody>
    </table>
    </td>
    </tr>
    </table>

    <br/>

    <table class="adminlist">
    <thead>
    <tr>
     <th width="20" class="title">#</th>
     <th class="title"><?php echo _("Surname"); ?></th>
     <th class="title"><?php echo _("Firstname"); ?></th>
     <th width="60" class="title"><?php echo _("Balance"); ?></th>
     <th width="95" class="title"><?php echo _("Variable symbol"); ?></th>
<?php
	foreach ($report['dates'] as &$date) {
?>
     <th width="50" class="title-center"><?php echo $date['DATE_STRING']; ?></th>
<?php
	}
?>
     <th width="20" class="title">Stav</th>
   </tr>
   </thead>
    <tfoot>
    <tr>
      <td colspan="<?php echo sizeof($report['dates']) + 5; ?>">
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
	foreach ($paymentReport as $person) {
		if (!$person) {
		    continue;
		}
//		$link = "javascript:edit('$bankAccountEntry->BE_bankaccountentryid');";
?>
   <tr class="<?php echo "row$k"; ?>" >
     <td width="4%">
       <?php echo $i+1+$pageNav->limitstart; ?>
     </td>
     <td><?php echo $person->PE_surname; ?></td>
     <td><?php echo $person->PE_firstname; ?></td>
     <td><?php echo $person->PA_balance; ?></td>
     <td><?php echo $person->PA_variablesymbol; ?></td>
<?php
	foreach ($person->_dates as &$info) {
?>
     <td class="<?php echo $info['style']; ?>"><?php echo $info['text']; ?>
<?php
	}
?>
     <td>
<?php
	if ($person->_hasCharge == null) {
?>
       <img src="images/16x16/actions/agt_stop.png" alt="passive" align="middle" border="0"/>
<?php
	} else if ($person->_hasCharge->HC_actualstate == HasCharge::ACTUALSTATE_ENABLED) {
?>
       <img src="images/16x16/actions/agt_action_success.png" alt="active" align="middle" border="0"/>
<?php
	} else if ($person->_hasCharge->HC_actualstate == HasCharge::ACTUALSTATE_DISABLED) {
?>
        <img src="images/16x16/actions/agt_stop.png" alt="removed" align="middle" border="0"/>
<?php
	}
?>
     </td>
   </tr>
<?php
		$k = 1 - $k;
		$i++;
	}
?>
    <tr>
      <td colspan="5"><?php echo _("Payed"); ?></td>
<?php
	foreach ($report['dates'] as &$date) {
?>
      <td class="noWrap"><?php echo $date['summary']['payed']; ?> CZK</td>
<?php
	}
?>
      <td></td>
   </tr>
   <tr>
     <td colspan="5"><?php echo _("Payed with delay"); ?></td>
<?php
	foreach ($report['dates'] as &$date) {
?>
      <td class="noWrap"><?php echo $date['summary']['payedWithDelay']; ?> CZK</td>
<?php
	}
?>
      <td></td>
    </tr>
   <tr>
     <td colspan="5"><?php echo _("Pending payments"); ?></td>
<?php
	foreach ($report['dates'] as &$date) {
?>
      <td class="noWrap"><?php echo $date['summary']['pending']; ?> CZK</td>
<?php
	}
?>
      <td></td>
    </tr>
   <tr>
     <td colspan="5"><?php echo _("Delayed payments"); ?></td>
<?php
	foreach ($report['dates'] as &$date) {
?>
      <td class="noWrap"><?php echo $date['summary']['delayed']; ?> CZK</td>
<?php
	}
?>
      <td></td>
    </tr>
   <tr>
     <td colspan="5"><?php echo _("Number of excused payments"); ?></td>
<?php
	foreach ($report['dates'] as &$date) {
?>
      <td class="noWrap">#<?php echo $date['summary']['free']; ?></td>
<?php
	}
?>
      <td></td>
    </tr>
   <tr>
     <td colspan="5"><?php echo _("Total income"); ?></td>
<?php
	foreach ($report['dates'] as &$date) {
?>
      <td class="noWrap"><?php echo $date['summary']['payed'] + $date['summary']['payedWithDelay'] + $date['summary']['pending'] + $date['summary']['delayed']; ?> CZK</td>
<?php
	}
?>
      <td></td>
    </tr>
    </tbody>
    </table>
<?php
	}
?>
    <br/>
    <table>
    <tr>
    <td width="560">
    <table class="adminlist">
    <thead>
    <tr>
      <th colspan="2"><?php echo _("Legend"); ?></th>
    </tr>
    </thead>
    <tbody>
    <tr>
      <td width="50" class="<?php echo PaymentReportStyles::STATUS_FINISHED_IN_TIME;?>"></td>
      <td width="505"><?php echo _("Payed"); ?></td>
    </tr>
    <tr>
      <td class="<?php echo PaymentReportStyles::STATUS_FINISHED_OVERDUE;?>"></td>
      <td><?php echo _("Payed with delay"); ?></td>
    </tr>
    <tr>
      <td class="<?php echo PaymentReportStyles::STATUS_PENDING;?>"></td>
      <td><?php echo _("Pending payment"); ?></td>
    </tr>
    <tr>
      <td class="<?php echo PaymentReportStyles::STATUS_PENDING_PAYMENT_NOT_CREATED;?>"></td>
      <td><?php echo _("Waiting for pay, payment not yet created"); ?></td>
    </tr>
    <tr>
      <td class="<?php echo PaymentReportStyles::STATUS_PENDING_INSUFFICIENT_FUNDS;?>"></td>
      <td><?php echo _("Delayed payment in tolerance, number described NO of delayed days"); ?></td>
    </tr>
    <tr>
      <td class="<?php echo PaymentReportStyles::STATUS_PENDING_INSUFFICIENT_FUNDS_OVERDUE;?>"></td>
      <td><?php echo _("Delayes payment out of tolerance, number describer NO of delayed days"); ?></td>
    </tr>
    <tr>
      <td class="<?php echo PaymentReportStyles::STATUS_FREE_OF_CHARGE;?>"></td>
      <td><?php echo _("Free of charge"); ?></td>
    </tr>
    <tr>
      <td class="<?php echo PaymentReportStyles::STATUS_HAS_NO_CHARGE;?>"></td>
      <td><?php echo _("Has no charge"); ?></td>
    </tr>
    <tr>
      <td class="<?php echo PaymentReportStyles::STATUS_OTHER;?>"></td>
      <td><?php echo _("Other"); ?></td>
    </tr>
    </tbody>
    </table>
    </td>
    </tr>
    </table>
    <input type="hidden" name="option" value="com_paymentreport" />
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
}
?>