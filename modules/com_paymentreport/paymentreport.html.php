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

class HTML_PaymentReport {
	/**
	 * showEntries for selected BankAccount
	 * @param $charges
	 * @param $paymentReport
	 * @param $report
	 * @param $filter
	 * @param $pageNav
	 */
	static function showPayments(&$messages, &$charges, &$paymentReport, &$report, &$filter, &$pageNav) {
		global $core;
        $enableVatPayerSpecifics = $core->getProperty(Core::ENABLE_VAT_PAYER_SPECIFICS);
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
    window.addEventListener('load', function () {
        var rotates = document.getElementsByClassName('rotate3');
        for (var i = 0; i < rotates.length; i++) {
            rotates[i].style.height = rotates[i].offsetWidth + 'px';
            rotates[i].style.width = 10 + 'px';
        }

        var lastHoverIndex = null;
        $('td').hover(function() {
            var t = parseInt($(this).index());
            if (lastHoverIndex) {
                $('tr td:nth-child(' + (lastHoverIndex+1) + ')').removeClass('hover2');
            }
            $('tr td:nth-child(' + (t+1) + ')').addClass('hover2');
            lastHoverIndex = t;
        });

    });


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
<?php if ($messages) { ?>
    <div id="message-box">
    <pre style="margin:0; padding:0;">
<?php echo implode("<br/>", $messages); ?>
    </pre>
    </div>
<?php } ?>
    <table width="100%">
    <tr>
      <td rowspan="3" valign="middle" width="35"><?php echo _("Filter:"); ?></td>
      <td>
        <table width="100%">
        <tr>
            <td colspan="3">
                <select data-placeholder="<?php echo _("- Choose Payments... -"); ?>"  id="chosen-payment" name="filter[CH_chargeid][]" onchange="document.adminForm.submit();" multiple>
                    <?php
                    if (count($charges) == 0) {
                        echo '<option value="0" selected="selected">'._("- No payment defined -").'- Není definována žádná platba -</option>' . "\n";
                    } else {
                        foreach ($charges as $charge) {
                            echo '<option value="' . $charge->CH_chargeid . '"'; if (in_array($charge->CH_chargeid, $filter['CH_chargeid'])) echo ' selected="selected"'; echo ">".$charge->CH_name.". (".Charge::getLocalizedPeriod($charge->CH_period) .")</option>\n";
                        }
                    }
                    ?>
                </select>
                <script type="text/javascript">
                    $("#chosen-payment").chosen({disable_search_threshold: 10, width: "97%"});
                </script>
            </td>
        </tr>
        <tr>
        <td><input type="text" name="filter[search]" value="<?php echo $filter['search']; ?>" class="width-form" onchange="document.adminForm.submit();" /></td>
        <td>
        <select name="filter[PE_status]" class="width-form" size="1" onchange="document.adminForm.submit( );">
        <option value="-1" <?php if ($filter['PE_status'] == -1) echo ' selected="selected"';?>><?php echo _("- Status of Person -"); ?></option>
<?php
	foreach (Person::$STATUS_ARRAY as $k) {
?>
         <option value="<?php echo $k; ?>" <?php echo ($filter['PE_status'] == $k) ? 'selected="selected"' : ""; ?>><?php echo Person::getLocalizedStatus($k); ?></option>
<?php
	}
?>
        </select>
        </td>
        <td rowspan="2" width="90%">
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
            <select name="filter[HC_status]" class="width-form" size="1" onchange="document.adminForm.submit( );">
                <option value="-1" <?php if ($filter['HC_status'] == -1) echo ' selected="selected"';?>><?php echo _("- Status of Payment -"); ?></option>
                <?php
                foreach (HasCharge::$STATUS_ARRAY as $k) {
                    ?>
                    <option value="<?php echo $k; ?>" <?php echo ($filter['HC_status'] == $k) ? 'selected="selected"' : ""; ?>><?php echo HasCharge::getLocalizedStatus($k); ?></option>
                    <?php
                }
                ?>
            </select>
        </td>
          <td align="right">
              <select name="filter[HC_actualstate]" class="width-form" size="1" onchange="document.adminForm.submit( );">
                  <option value="-1" <?php if ($filter['HC_actualstate'] == -1) echo ' selected="selected"';?>><?php echo _("- Actual state of Payment -"); ?></option>
                  <?php
                  foreach (HasCharge::$ACTUALSTATE_ARRAY as $k) {
                      ?>
                      <option value="<?php echo $k; ?>" <?php echo ($filter['HC_actualstate'] == $k) ? 'selected="selected"' : ""; ?>><?php echo HasCharge::getLocalizedActualState($k); ?></option>
                      <?php
                  }
                  ?>
              </select>
          </td>
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
	if (true) {
?>
<!--    <table>-->
<!--    <tr>-->
<!--    <td width="360">-->
<!--    <table class="adminform">-->
<!--    <thead>-->
<!--    <tr>-->
<!--      <th colspan="2" class="title">--><?php //echo _("Payment"); ?><!--</th>-->
<!--    </tr>-->
<!--    </thead>-->
<!--    <tbody>-->
<!--    <tr>-->
<!--      <td width="150">--><?php //echo _("Payment name:"); ?><!--</td>-->
<!--      <td width="205">--><?php //echo $charges[$filter['CH_chargeid'][0]]->CH_name; ?><!--</td>-->
<!--    </tr>-->
<!--    <tr>-->
<!--      <td>--><?php //echo _("Description:"); ?><!--</td>-->
<!--      <td>--><?php //echo $charges[$filter['CH_chargeid'][0]]->CH_description; ?><!--</td>-->
<!--    </tr>-->
<!--    <tr>-->
<!--      <td>--><?php //echo _("Amount:"); ?><!--</td>-->
<!--      <td>--><?php //echo $charges[$filter['CH_chargeid'][0]]->CH_amount . " " . $charges[$filter['CH_chargeid'][0]]->CH_currency. " " . Charge::getLocalizedPeriod($charges[$filter['CH_chargeid'][0]]->CH_period); ?><!--</td>-->
<!--    </tr>-->
<!--    <tr>-->
<!--      <td>--><?php //echo _("Type:"); ?><!--</td>-->
<!--      <td>--><?php //echo Charge::getLocalizedType($charges[$filter['CH_chargeid'][0]]->CH_type); ?><!--</td>-->
<!--    </tr>-->
<!--    </tbody>-->
<!--    </table>-->
<!--    </td>-->
<!--    </tr>-->
<!--    </table>-->
<!---->
<!--    <br/>-->

    <table class="adminlist">
    <thead>
    <tr>
     <th width="10" class="title">#</th>
     <th class="title"><?php echo _("Surname"); ?></th>
     <th class="title"><?php echo _("Firstname"); ?></th>
     <th class="title"><?php echo _("Balance"); ?></th>
     <th class="title"><?php echo _("Variable symbol"); ?></th>
     <th class="title"><?php echo _("Service name"); ?></th>
     <th class="title"><?php echo _("Payed"); ?></th>
<?php if ($enableVatPayerSpecifics) { ?>
     <th class="title" style="border-right: 1px solid #c5c5c5;"><?php echo _("Amount with VAT"); ?></th>
<?php } else { ?>
     <th class="title" style="border-right: 1px solid #c5c5c5;"><?php echo _("Amount"); ?></th>
<?php }?>
<?php
	foreach ($report['dates'] as &$date) {
?>
     <th width="30" class="title-center title-side-shrink" style="border-right: 1px solid #c5c5c5;"><?php echo $date['DATE_STRING']; ?></th>
<?php
	}
?>
     <th width="20" class="title"><?php echo _("Person Status"); ?></th>
     <th width="20" class="title"><?php echo _("Payment Status"); ?></th>
     <th width="20" class="title noWrap"><?php echo _("Actual state"); ?></th>
   </tr>
   </thead>
    <tfoot>
    <tr>
      <td colspan="<?php echo sizeof($report['dates']) + 11; ?>">
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
	    $firstPersonRow = true;
        switch ($person->PE_status) {
            case Person::STATUS_PASSIVE:
                $image1Src = "images/16x16/actions/agt_action_fail.png";
                $image1Alt = _("Passive");
                break;

            case Person::STATUS_ACTIVE:
                $image1Src = "images/16x16/actions/agt_action_success.png";
                $image1Alt = _("Active");
                break;

            case Person::STATUS_DISCARTED:
                $image1Src = "images/16x16/actions/agt_stop.png";
                $image1Alt = _("Removed");
                break;
        }

        $rowspan = count($person->_hasCharge);
        foreach ($person->_hasCharge as &$hasCharge) {
            if ($hasCharge == null) {
                $image2Src = "images/16x16/actions/exit.png";
                $image2Alt = _("Disabled");
            } else if ($hasCharge->HC_actualstate == HasCharge::ACTUALSTATE_ENABLED) {
                $image2Src = "images/16x16/actions/agt_runit.png";
                $image2Alt = _("Enabled");
            } else if ($hasCharge->HC_actualstate == HasCharge::ACTUALSTATE_DISABLED) {
                $image2Src = "images/16x16/actions/exit.png";
                $image2Alt = _("Disabled");
            }
//		$link = "javascript:edit('$bankAccountEntry->BE_bankaccountentryid');";
?>
   <tr class="<?php echo "row$k"; ?>" >
<?php
            if ($firstPersonRow) {
?>
     <td rowspan="<?php echo $rowspan; ?>">
       <?php echo $i+1+$pageNav->limitstart; $i++; ?>
     </td>
     <td rowspan="<?php echo $rowspan; ?>"><?php echo $person->PE_surname; ?></td>
     <td rowspan="<?php echo $rowspan; ?>"><?php echo $person->PE_firstname; ?></td>
     <td rowspan="<?php echo $rowspan; ?>"><?php echo $person->PA_balance; ?></td>
     <td rowspan="<?php echo $rowspan; ?>"><?php echo $person->PA_variablesymbol; ?></td>
<?php
            } else {
?>

<?php
            }
?>
     <td><?php echo $hasCharge->CH_name; ?></td>
     <td><?php echo Charge::getLocalizedPeriod($hasCharge->CH_period); ?></td>
     <td style="border-right: 1px solid #c5c5c5;"><?php echo $hasCharge->CH_amount . '&nbsp;' . $hasCharge->CH_currency; ?></td>
<?php
    reset($hasCharge->_dates);
	foreach ($hasCharge->_dates as &$info) {
?>
     <td colspan="<?php echo $info['colspan']; ?>" class="<?php echo $info['style']; ?> side-shrink" style="border-right: 1px solid #c5c5c5;"><?php echo $info['text']; ?></td>
<?php
	}
    if ($firstPersonRow) {
        $firstPersonRow = false;
?>
     <td rowspan="<?php echo $rowspan; ?>" style="text-align: center;">
       <img src="<?php echo $image1Src; ?>" alt="<?php echo $image1Alt; ?>" align="middle" border="0" />
     </td>
<?php
    }
?>
     <td>
       <?php echo HasCharge::getLocalizedStatus($hasCharge->HC_status); ?>
     </td>
     <td style="text-align: center;">
       <img src="<?php echo $image2Src; ?>" alt="<?php echo $image2Alt; ?>" align="middle" border="0" />
     </td>
   </tr>
<?php
        }
        $k = 1 - $k;
//		$i++;
	}
?>
    <tr>
      <td class="overflow"><?php echo _("Payed"); ?></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
<?php
	foreach ($report['dates'] as &$date) {
?>
      <td class="noWrap side-shrink rotate"><div><div><span><?php echo NumberFormat::formatMoney($date['summary']['payed']); ?> CZK</span></div></div></td>
<?php
	}
?>
      <td colspan="3"></td>
   </tr>
   <tr>
     <td class="overflow"><?php echo _("Payed with delay"); ?></td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
<?php
	foreach ($report['dates'] as &$date) {
?>
      <td class="noWrap side-shrink rotate"><div><div><span><?php echo NumberFormat::formatMoney($date['summary']['payedWithDelay']); ?> CZK</span></div></div></td>
<?php
	}
?>
      <td colspan="3"></td>
    </tr>
   <tr>
     <td class="overflow"><?php echo _("Pending payments"); ?></td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
<?php
	foreach ($report['dates'] as &$date) {
?>
      <td class="noWrap side-shrink rotate"><div><div><span><?php echo NumberFormat::formatMoney($date['summary']['pending']); ?> CZK</span></div></div></td>
<?php
	}
?>
      <td colspan="3"></td>
    </tr>
   <tr>
     <td class="overflow"><?php echo _("Delayed payments"); ?></td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
<?php
	foreach ($report['dates'] as &$date) {
?>
      <td class="noWrap side-shrink rotate"><div><div><span><?php echo NumberFormat::formatMoney($date['summary']['delayed']); ?> CZK</span></div></div></td>
<?php
	}
?>
      <td colspan="3"></td>
    </tr>
   <tr>
     <td class="overflow"><?php echo _("Number of excused payments"); ?></td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
<?php
	foreach ($report['dates'] as &$date) {
?>
      <td class="noWrap side-shrink center rotate"><div><div><span><?php echo $date['summary']['free']; ?></span></div></div></td>
<?php
	}
?>
      <td colspan="3"></td>
    </tr>
   <tr>
     <td class="overflow"><?php echo _("Total income"); ?></td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
<?php
	foreach ($report['dates'] as &$date) {
?>
        <td class="noWrap side-shrink rotate"><div><div><span><?php echo NumberFormat::formatMoney($date['summary']['payed'] + $date['summary']['payedWithDelay'] + $date['summary']['pending'] + $date['summary']['delayed']); ?> CZK</span></div></div></td>
<?php
	}
?>
      <td colspan="3"></td>
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
