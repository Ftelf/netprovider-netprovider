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
 * HTML_PersonAccount
 */
class HTML_PersonAccount {
	/**
	 * showPersonAccounts for selected BankAccount
	 * @param $persons
	 * @param $personAccounts
	 * @param $pageNav
	 * @param $filter
	 * @param $msgs
	 */
	static function showEntries(&$persons, &$personAccounts, &$pageNav, &$filter, $msgs) {
		global $core, $my;
?>
<script type="text/javascript" src="js/CalendarPopup.js"></script>
<script type="text/javascript">document.write(getCalendarStyles());</script>
<script type="text/javascript">
	function showDetail(id) {
    	var form = document.adminForm;
    	form.PE_personid.value = id;
    	hideMainMenu();
   		submitform('showDetail');
	}
	function showDetailA() {
		if (document.adminForm.boxchecked.value == 0) {
			alert('<?php echo _("Please select record to edit"); ?>');
		} else {
	    	hideMainMenu();
   			submitform('showDetailA');
		}
	}
	function createBlankCharges() {
   		submitform('createBlankCharges');
	}
	function proceedCharges() {
   		submitform('proceedCharges');
	}
</script>
<?php
	if (count($msgs)) {
?>
<div id="message-box">
      <strong><?php echo _("Conflicting variable symbols"); ?></strong>
      <br/>
        <?php echo implode("<br/>", $msgs); ?>
</div>
<?php
	}
?>

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
<?php
	if ($my->GR_level == Group::SUPER_ADMININSTRATOR) {
?>
          <tr>
            <td id="toolbar-create-blank-charges">
              <a href="javascript:createBlankCharges();">
                <span title="<?php echo _("Create blank charges"); ?>" class="icon-32-create-blank-charges"></span>
                <?php echo _("Create blank charges"); ?>
              </a>
            </td>

            <td id="toolbar-proceed-charges">
              <a href="javascript:proceedCharges();">
                <span title="<?php echo _("Pay payables"); ?>" class="icon-32-proceed-charges"></span>
                <?php echo _("Pay payables"); ?>
              </a>
            </td>
<?php
	}
?>
            <td id="toolbar-edit">
              <a href="javascript:showDetailA();">
                <span title="<?php echo _("Edit"); ?>" class="icon-32-edit"></span>
                <?php echo _("Edit"); ?>
              </a>
            </td>
          </tr>
          </table>
        </div>

        <div class="header icon-48-user-account">
          <?php echo _("User's accounts"); ?>
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
      <td valign="middle"><?php echo _("Filter:"); ?></td>
      <td><input type="text" name="filter[search]" value="<?php echo $filter['search']; ?>" class="width-form" onchange="document.adminForm.submit();" /></td>
      <td align="right">
        <select name="filter[status]" class="width-form" size="1" onchange="document.adminForm.submit();">
        <option value="-1" <?php if ($filter['status'] == -1) echo ' selected="selected"';?>><?php echo _("- Status -"); ?></option>
<?php
	foreach (Person::$STATUS_ARRAY as $k) {
?>
          <option value="<?php echo $k; ?>" <?php echo ($filter['status'] == $k) ? 'selected="selected"' : ""; ?>><?php echo Person::getLocalizedStatus($k); ?></option>
<?php
	}
?>
        </select>
      </td>
      <td align="right">
        <select name="filter[bilance]" class="width-form" size="1" onchange="document.adminForm.submit();">
          <option value="-1" <?php if ($filter['bilance'] == -1) echo ' selected="selected"';?>><?php echo _("- All accounts -"); ?></option>
          <option value="1" <?php if ($filter['bilance'] == 1) echo ' selected="selected"';?>><?php echo _("Accounts with negative bilance"); ?></option>
          <option value="2" <?php if ($filter['bilance'] == 2) echo ' selected="selected"';?>><?php echo _("Accounts with zero bilance"); ?></option>
          <option value="3" <?php if ($filter['bilance'] == 3) echo ' selected="selected"';?>><?php echo _("Account with positive bilance"); ?></option>
        </select>
      </td>
    </tr>
    </table>

    <table class="adminlist">
    <thead>
    <tr>
      <th width="2%" class="title">#</th>
      <th width="2%" class="title"><input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo $pageNav->limit; ?>);" /></th>
      <th width="10%" class="title"><?php echo _("Firstname"); ?></th>
      <th width="15%" class="title"><?php echo _("Surname"); ?></th>
      <th width="13%" class="title"><?php echo _("Starting balance"); ?></th>
      <th width="12%" class="title"><?php echo _("Total income"); ?></th>
      <th width="12%" class="title"><?php echo _("Total outbound"); ?></th>
      <th width="10%" class="title"><?php echo _("Balance"); ?></th>
      <th width="8%" class="title"><?php echo _("V.S."); ?></th>
      <th width="8%" class="title"><?php echo _("K.S."); ?></th>
      <th width="8%" class="title"><?php echo _("S.S."); ?></th>
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
	foreach ($persons as $person) {
		$personAccount = $personAccounts[$person->PE_personaccountid];
		$link = "javascript:showDetail('$person->PE_personid');";
?>
    <tr class="<?php echo "row$k"; ?>">
      <td>
        <?php echo $pageNav->rowNumber($i); ?>
      </td>
      <td>
        <input type="checkbox" id="<?php echo "cb$i"; ?>" name="cid[]" value="<?php echo $person->PE_personid; ?>" onclick="isChecked(this.checked);" />
      </td>
      <td>
        <a href="<?php echo $link; ?>"><?php echo $person->PE_firstname; ?></a>
      </td>
      <td>
        <a href="<?php echo $link; ?>"><?php echo $person->PE_surname; ?></a>
      </td>
      <td>
        <?php echo NumberFormat::formatMoney($personAccount->PA_startbalance) . " " . $personAccount->PA_currency; ?>
      </td>
      <td>
        <?php echo NumberFormat::formatMoney($personAccount->PA_income) . " " . $personAccount->PA_currency; ?>
      </td>
      <td>
        <?php echo NumberFormat::formatMoney($personAccount->PA_outcome) . " " . $personAccount->PA_currency; ?>
      </td>
      <td>
        <?php echo NumberFormat::formatMoney($personAccount->PA_balance) . " " . $personAccount->PA_currency; ?>
      </td>
      <td>
        <?php echo (!$personAccount->PA_variablesymbol) ? "N/A" : $personAccount->PA_variablesymbol; ?>
      </td>
      <td>
        <?php echo (!$personAccount->PA_constantsymbol) ? "N/A" : $personAccount->PA_constantsymbol; ?>
      </td>
      <td>
        <?php echo (!$personAccount->PA_specificsymbol) ? "N/A" : $personAccount->PA_specificsymbol; ?>
      </td>
    </tr>
<?php
		$k = 1 - $k;
		$i++;
	}
?>
    </tbody>
    </table>
   <input type="hidden" name="option" value="com_personaccount" />
   <input type="hidden" name="task" value="" />
   <input type="hidden" name="PE_personid" value="" />
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
	 * showPersonAccountDetail
	 * @param $person
	 * @param $personAccount
	 * @param $bankAccountEntries
	 * @param $personAccountEntries
	 * @param $chargeEntries
	 * @param $charges
	 * @param $charges
	 */
	static function showPersonAccountDetail(&$person, &$personAccount, &$bankAccountEntries, &$personAccountEntries, &$chargeEntries, &$hasCharges, &$charges) {
		global $core;
		$enableVatPayerSpecifics = $core->getProperty(Core::ENABLE_VAT_PAYER_SPECIFICS);
		$entriesColspan = ($enableVatPayerSpecifics) ? 11 : 9;
?>
<script type="text/javascript">
	function submitbutton(pressbutton) {
		var form = document.adminForm;
		if (pressbutton == 'cancel') {
			submitform(pressbutton);
			return;
		}

		if (pressbutton == 'edit') {
			hideMainMenu();
			submitform('edit');
		} else if (pressbutton == 'newPAE') {
			hideMainMenu();
			submitform('newPAE');
		}
	}

	function personAccountEntryAction(el, id) {
		var form = document.adminForm;
		var action = el.value;

		if (action == 'returnPayment') {
			if (window.confirm("<?php echo _("Return payment for new process ?"); ?>")) {
				hideMainMenu();
				form.PN_personaccountentryid.value = id;
				submitform(action);
			} else {
				el.selectedIndex = 0;
			}
		}
	}

	function changeEntryAction(el, id) {
		var form = document.adminForm;
		var action = el.value;

		if (action == 'freeCharge') {
			var confirm = window.confirm("<?php echo _("Excuse payment and let use service for free for this period ?"); ?>");
		} else if (action == 'ignoreCharge') {
			var confirm = window.confirm("<?php echo _("Ignore payment and disable service for this period ?"); ?>");
		} else if (action == 'removeCharge') {
			var confirm = window.confirm("<?php echo _("Remove this payment ? It it was already payed, amount is benefited to user's account"); ?>");
		} else {
			return;
		}


		if (confirm) {
			hideMainMenu();
			form.CE_chargeentryid.value = id;
			submitform(action);
		} else {
			el.selectedIndex = 0;
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
              <a href="javascript:submitbutton('newPAE');">
                <span title="<?php echo _("New payment"); ?>" class="icon-32-proceed-new-payment"></span>
                <?php echo _("New payment"); ?>
              </a>
            </td>

            <td id="toolbar-edit">
              <a href="javascript:submitbutton('edit');">
                <span title="<?php echo _("Edit"); ?>>" class="icon-32-edit"></span>
                <?php echo _("Edit"); ?>
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

        <div class="header icon-48-person">
          <?php echo _("User's accounts"); ?>: <small><?php echo _("User's account detail"); ?></small>
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
    <td width="460">
    <table class="adminform">
    <thead>
    <tr>
      <th colspan="2" class="title"><?php echo _("User's account"); ?></th>
    </tr>
    </thead>
    <tbody>
    <tr>
      <td width="250"><?php echo _("Name:"); ?></td>
      <td width="205"><?php echo $person->PE_firstname." ".$person->PE_surname; ?></td>
    </tr>
    <tr>
      <td><?php echo _("Currency:"); ?></td>
      <td><?php echo $personAccount->PA_currency; ?></td>
    </tr>
    <tr>
      <td><?php echo _("Starting balance:"); ?></td>
      <td><?php echo $personAccount->PA_startbalance . " " . $personAccount->PA_currency; ?></td>
    </tr>
    <tr>
      <td><?php echo _("Total income:"); ?></td>
      <td><?php echo NumberFormat::formatMoney($personAccount->PA_income) . " " . $personAccount->PA_currency; ?></td>
    </tr>
    <tr>
      <td><?php echo _("Total outbound:"); ?></td>
      <td><?php echo NumberFormat::formatMoney($personAccount->PA_outcome) . " " . $personAccount->PA_currency; ?></td>
    </tr>
    <tr>
      <td><?php echo _("Account balance:"); ?></td>
      <td><?php echo NumberFormat::formatMoney($personAccount->PA_balance) . " " . $personAccount->PA_currency; ?></td>
    </tr>
    <tr>
      <td><?php echo _("Variable symbol:"); ?></td>
      <td><?php echo (!$personAccount->PA_variablesymbol) ? "N/A" : $personAccount->PA_variablesymbol; ?></td>
    </tr>
    <tr>
      <td><?php echo _("Constant symbol:"); ?></td>
      <td><?php echo (!$personAccount->PA_constantsymbol) ? "N/A" : $personAccount->PA_constantsymbol; ?></td>
    </tr>
    <tr>
      <td><?php echo _("Specific symbol:"); ?></td>
      <td><?php echo (!$personAccount->PA_specificsymbol) ? "N/A" : $personAccount->PA_specificsymbol; ?></td>
    </tr>
    </tbody>
    </table>
    </td>
    <td>
    </td>
    </tr>
    </table>

    <br/>

    <table class="adminlist">
    <thead>
    <tr>
      <th colspan="9"><?php echo _("Incoming payments"); ?></th>
    </tr>
    <tr>
      <th width="2%" class="title">#</th>
      <th width="8%" class="title"><?php echo _("Date"); ?></th>
      <th width="15%" class="title"><?php echo _("Source"); ?></th>
      <th width="10%" class="title"><?php echo _("Amount"); ?></th>
      <th width="15%" class="title"><?php echo _("Message"); ?></th>
      <th width="15%" class="title"><?php echo _("Account name"); ?></th>
      <th width="15%" class="title"><?php echo _("Account number"); ?></th>
      <th width="10%" class="title"><?php echo _("Variable symbol"); ?></th>
      <th width="10%" class="title"><?php echo _("Action"); ?></th>
    </tr>
    </thead>
    <tbody>
<?php
	$k = 0;
	$i = 0;
	foreach ($personAccountEntries as $personAccountEntry) {
		$date = new DateUtil($personAccountEntry->PN_date);
?>
    <tr class="<?php echo "row$k"; ?>">
      <td>
        <?php echo $i+1; ?>
      </td>
      <td>
        <?php echo $date->getFormattedDate(DateUtil::FORMAT_DATE); ?>
      </td>
      <td>
        <?php echo PersonAccountEntry::getLocalizedSource($personAccountEntry->PN_source); ?>
      </td>
      <td>
        <?php echo NumberFormat::formatMoney($personAccountEntry->PN_amount) . " " . $personAccountEntry->PN_currency; ?>
      </td>
      <td>
<?php
	if ($personAccountEntry->PN_source == PersonAccountEntry::SOURCE_BANKACCOUNT) {
		echo $bankAccountEntries[$personAccountEntry->PN_bankaccountentryid]->BE_message;
	} else {
		echo $personAccountEntry->PN_comment;
	}
?>
      </td>
      <td>
<?php
	if ($personAccountEntry->PN_source == PersonAccountEntry::SOURCE_BANKACCOUNT) {
		echo $bankAccountEntries[$personAccountEntry->PN_bankaccountentryid]->BE_accountname;
	} else {
		echo "n/a";
	}
?>
      </td>
      <td>
<?php
	if ($personAccountEntry->PN_source == PersonAccountEntry::SOURCE_BANKACCOUNT) {
		echo $bankAccountEntries[$personAccountEntry->PN_bankaccountentryid]->BE_accountnumber . "/" . $bankAccountEntries[$personAccountEntry->PN_bankaccountentryid]->BE_banknumber;
	} else {
		echo "n/a";
	}
?>
      </td>
      <td>
<?php
	if ($personAccountEntry->PN_source == PersonAccountEntry::SOURCE_BANKACCOUNT) {
		echo $bankAccountEntries[$personAccountEntry->PN_bankaccountentryid]->BE_variablesymbol;
	} else {
		echo "n/a";
	}
?>
      </td>
      <td>
        <select size="1" style="width: 100px;" onchange="personAccountEntryAction(this, <?php echo $personAccountEntry->PN_personaccountentryid; ?>);">
          <option value="0" selected="selected"></option>
          <option value="returnPayment"><?php echo _("Return"); ?></option>
        </select>
      </td>
    </tr>
<?php
		$k = 1 - $k;
		$i++;
	}
?>
    </table>

    <br/>

    <table class="adminlist">
    <thead>
    <tr>
      <th colspan="12"><?php echo _("Service payments"); ?></th>
    </tr>
    <tr>
      <th width="2%" class="title">#</th>
      <th width="14%" class="title"><?php echo _("Service name"); ?></th>
      <?php if ($enableVatPayerSpecifics) { ?>
      <th width="9%" class="title"><?php echo _("Amount without VAT"); ?></th>
      <th width="9%" class="title"><?php echo _("VAT (%)"); ?></th>
      <th width="9%" class="title"><?php echo _("Amount with VAT"); ?></th>
      <?php } else { ?>
      <th width="9%" class="title"><?php echo _("Amount"); ?></th>
      <?php } ?>
      <th width="9%" class="title"><?php echo _("Currency"); ?></th>
      <th width="9%" class="title"><?php echo _("Write off"); ?></th>
      <th width="9%" class="title"><?php echo _("Since"); ?></th>
      <th width="9%" class="title"><?php echo _("Till"); ?></th>
      <th width="9%" class="title"><?php echo _("Status"); ?></th>
      <th width="9%" class="title" colspan="2"><?php echo _("Actual state"); ?></th>
    </tr>
    </thead>
    <tbody>
<?php
	$i1 = 0;
	foreach ($hasCharges as $hasCharge) {
		$dateStart = new DateUtil($hasCharge->HC_datestart);
		$dateEnd = new DateUtil($hasCharge->HC_dateend);
?>
    <tr class="row0">
      <td>
        <?php echo $i1+1; ?>
      </td>
      <td>
        <?php echo $charges[$hasCharge->HC_chargeid]->CH_name; ?>
      </td>
      <?php if ($enableVatPayerSpecifics) { ?>
      <td>
        <?php echo NumberFormat::formatMoney($charges[$hasCharge->HC_chargeid]->CH_baseamount); ?>
      </td>
      <td>
        <?php echo NumberFormat::formatMoney($charges[$hasCharge->HC_chargeid]->CH_vat); ?>
      </td>
      <?php } ?>
      <td>
        <?php echo NumberFormat::formatMoney($charges[$hasCharge->HC_chargeid]->CH_amount); ?>
      </td>
      <td>
        <?php echo $charges[$hasCharge->HC_chargeid]->CH_currency; ?>
      </td>
      <td>
        <?php echo Charge::getLocalizedPeriod($charges[$hasCharge->HC_chargeid]->CH_period); ?>
      </td>
      <td>
        <?php echo $dateStart->getFormattedDate(DateUtil::FORMAT_DATE); ?>
      </td>
      <td>
<?php
	if ($dateEnd->getTime() == null) {
		echo _("Not limited");
	} else {
		echo $dateEnd->getFormattedDate(DateUtil::FORMAT_DATE);;
	}
?>
      </td>
      <td>
        <?php echo Hascharge::getLocalizedStatus($hasCharge->HC_status); ?>
      </td>
      <td>
        <?php echo Hascharge::getLocalizedActualState($hasCharge->HC_actualstate); ?>
      </td>
      <td>
<?php
	if ($hasCharge->HC_actualstate == HasCharge::ACTUALSTATE_ENABLED) { ?>
        <img src="images/16x16/actions/agt_action_success.png" alt="active" align="middle" border="0"/>
<?php
	} else if ($hasCharge->HC_actualstate == Hascharge::ACTUALSTATE_DISABLED) { ?>
        <img src="images/16x16/actions/agt_stop.png" alt="removed" align="middle" border="0"/>
<?php
	}
?>
      </td>
    </tr>
    <tr>
      <td>
      </td>
      <td colspan="<?php echo $entriesColspan; ?>">
    <table class="adminlist">
    <thead>
    <tr>
      <th width="2%" class="title">#</th>
      <th width="8%" class="title"><?php echo _("Date/period"); ?></th>
      <th width="8%" class="title"><?php echo _("Date of payment"); ?></th>
      <th width="8%" class="title"><?php echo _("Tolerance till"); ?></th>
      <th width="7%" class="title"><?php echo _("Date of write off"); ?></th>
      <th width="7%" class="title"><?php echo _("Delayed in days"); ?></th>
      <th width="10%" class="title"><?php echo _("Status"); ?></th>
      <?php if ($enableVatPayerSpecifics) { ?>
      <th width="10%" class="title"><?php echo _("Amount without VAT"); ?></th>
      <th width="10%" class="title"><?php echo _("VAT (%)"); ?></th>
      <th width="10%" class="title"><?php echo _("Amount with VAT"); ?></th>
      <?php } else { ?>
      <th width="10%" class="title"><?php echo _("Amount"); ?></th>
      <?php } ?>
      <th width="10%" class="title"><?php echo _("Currency"); ?></th>
      <th width="10%" class="title"><?php echo _("Action"); ?></th>
    </tr>
    </thead>
    <tbody>
<?php
	$k2 = 0;
	$i2 = 0;
	foreach ($hasCharge->_chargeEntries as $chargeEntry) {
		$periodDate = new DateUtil($chargeEntry->CE_period_date);
		$toleranceDate = clone $periodDate;
		$toleranceDate->add(DateUtil::DAY, $charges[$hasCharge->HC_chargeid]->CH_tolerance);
		$realizedDate = new DateUtil($chargeEntry->CE_realize_date);
?>
    <tr class="<?php echo "row$k2"; ?>">
      <td>
        <?php echo $i2+1; ?>
      </td>
      <td>
<?php
		switch ($charges[$hasCharge->HC_chargeid]->CH_period) {
			case Charge::PERIOD_MONTHLY:
				$format = DateUtil::FORMAT_MONTHLY;
				break;
			default:
				$format = DateUtil::FORMAT_FULL;
		}
		echo $periodDate->getFormattedDate($format);

		$writeOffDate = clone $periodDate;
		$writeOffDate->add(DateUtil::DAY, $chargeEntry->CE_writeoffoffset);
?>
      </td>
      <td>
        <?php echo $writeOffDate->getFormattedDate(DateUtil::FORMAT_DATE); ?>
      </td>
      <td>
		<?php echo $toleranceDate->getFormattedDate(DateUtil::FORMAT_DATE); ?>
      </td>
      <td>
        <?php echo $realizedDate->getFormattedDate(DateUtil::FORMAT_DATE); ?>
      </td>
      <td>
        <?php echo $chargeEntry->CE_overdue; ?>
      </td>
      <td>
        <?php echo ChargeEntry::getLocalizedStatus($chargeEntry->CE_status); ?>
      </td>
      <?php if ($enableVatPayerSpecifics) { ?>
      <td>
        <?php echo NumberFormat::formatMoney($chargeEntry->CE_baseamount); ?>
      </td>
      <td>
        <?php echo NumberFormat::formatMoney($chargeEntry->CE_vat); ?>
      </td>
      <?php } ?>
      <td>
        <?php echo NumberFormat::formatMoney($chargeEntry->CE_amount); ?>
      </td>
      <td>
        <?php echo $chargeEntry->CE_currency; ?>
      </td>
      <td>
        <select size="1" style="width: 100px;" onchange="changeEntryAction(this, <?php echo $chargeEntry->CE_chargeentryid; ?>);">
          <option value="0" selected="selected"></option>
<?php
	if ($chargeEntry->CE_status == ChargeEntry::STATUS_PENDING ||
		$chargeEntry->CE_status == ChargeEntry::STATUS_PENDING_INSUFFICIENTFUNDS) {
?>
          <option value="freeCharge"><?php echo _("Excuse"); ?></option>
          <option value="ignoreCharge"><?php echo _("Ignore"); ?></option>
<?php
	}
	if ($chargeEntry->CE_status == ChargeEntry::STATUS_FINISHED ||
		$chargeEntry->CE_status == ChargeEntry::STATUS_PENDING ||
		$chargeEntry->CE_status == ChargeEntry::STATUS_PENDING_INSUFFICIENTFUNDS ||
		$chargeEntry->CE_status == ChargeEntry::STATUS_TESTINGFREEOFCHARGE ||
		$chargeEntry->CE_status == ChargeEntry::STATUS_DISABLED) {
?>
          <option value="removeCharge"><?php echo _("Remove"); ?></option>
<?php
	}
?>
        </select>
      </td>
    </tr>
<?php
		$k2 = 1 - $k2;
		$i2++;
	}
?>
    </tbody>
    </table>
      </td>
    </tr>
<?php
		$i1++;
	}
?>
    </table>
    <input type="hidden" name="PE_personid" value="<?php echo $person->PE_personid; ?>" />
    <input type="hidden" name="PN_personaccountentryid" value="" />
    <input type="hidden" name="CE_chargeentryid" value="" />
    <input type="hidden" name="option" value="com_personaccount" />
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
	/**
	 * editPersonAccountDetail
	 * @param $person
	 */
	static function editPersonAccountDetail(&$person, &$personAccount) {
		global $core;
?>
<script type="text/javascript">
	function submitbutton(pressbutton) {
		var form = document.adminForm;
		if (pressbutton == 'cancel') {
			hideMainMenu();
			submitform('cancelEdit');
		} else if (pressbutton == 'apply') {
			hideMainMenu();
			submitform('apply');
		} else if (pressbutton == 'save') {
			submitform('save');
		}
	}
	function vs(checked) {
		document.adminForm.PA_variablesymbol.disabled = !checked;
	}
	function cs(checked) {
		document.adminForm.PA_constantsymbol.disabled = !checked;
	}
	function ss(checked) {
		document.adminForm.PA_specificsymbol.disabled = !checked;
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

        <div class="header icon-48-person">
          <?php echo _("User's accounts"); ?>: <small><?php echo _("User's account edit"); ?></small>
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
    <td width="480">
    <table class="adminform">
    <thead>
    <tr>
      <th colspan="3" class="title"><?php echo _("User's account"); ?></th>
    </tr>
    </thead>
    <tbody>
    <tr>
      <td width="250"><?php echo _("Name:"); ?></td>
      <td width="205" colspan="2"><?php echo $person->PE_firstname . ' ' . $person->PE_surname; ?></td>
    </tr>
    <tr>
      <td><?php echo _("Currency:"); ?></td>
      <td colspan="2"><?php echo $personAccount->PA_currency; ?></td>
    </tr>
    <tr>
      <td><?php echo _("Starting balance:"); ?></td>
      <td colspan="2"><?php echo $personAccount->PA_startbalance; ?></td>
    </tr>
    <tr>
      <td><?php echo _("Total income:"); ?></td>
      <td colspan="2"><?php echo NumberFormat::formatMoney($personAccount->PA_income) . " " . $personAccount->PA_currency;; ?></td>
    </tr>
    <tr>
      <td><?php echo _("Total outbound:"); ?></td>
      <td colspan="2"><?php echo NumberFormat::formatMoney($personAccount->PA_outcome) . " " . $personAccount->PA_currency;; ?></td>
    </tr>
    <tr>
      <td><?php echo _("Account balance:"); ?></td>
      <td colspan="2"><?php echo NumberFormat::formatMoney($personAccount->PA_balance) . " " . $personAccount->PA_currency;; ?></td>
    </tr>
    <tr>
      <td><?php echo _("Variable symbol:"); ?></td>
      <td><input type="text" name="PA_variablesymbol" class="width-form" size="40" value="<?php echo $personAccount->PA_variablesymbol; ?>" <?php if (!$personAccount->PA_variablesymbol) echo 'disabled="disabled"';?> /></td>
      <td width="20"><input type="checkbox" name="_CB_PA_variablesymbol" value="1" onchange="vs(this.checked);" <?php if ($personAccount->PA_variablesymbol) echo 'checked="checked"';?>/></td>
    </tr>
    <tr>
      <td><?php echo _("Constant symbol:"); ?></td>
      <td><input type="text" name="PA_constantsymbol" class="width-form" size="40" value="<?php echo $personAccount->PA_constantsymbol; ?>" <?php if (!$personAccount->PA_constantsymbol) echo 'disabled="disabled"';?> /></td>
      <td><input type="checkbox" name="_CB_PA_constantsymbol" value="1" onchange="cs(this.checked);" <?php if ($personAccount->PA_constantsymbol) echo 'checked="checked"';?>/></td>
    </tr>
    <tr>
      <td><?php echo _("Specific symbol:"); ?></td>
      <td><input type="text" name="PA_specificsymbol" class="width-form" size="40" value="<?php echo $personAccount->PA_specificsymbol; ?>" <?php if (!$personAccount->PA_specificsymbol) echo 'disabled="disabled"';?> /></td>
      <td><input type="checkbox" name="_CB_PA_specificsymbol" value="1" onchange="ss(this.checked);" <?php if ($personAccount->PA_specificsymbol) echo 'checked="checked"';?>/></td>
    </tr>
    </tbody>
    </table>
    </td>
    <td>
    </td>
    </tr>
    </table>
    <input type="hidden" name="PE_personid" value="<?php echo $person->PE_personid; ?>" />
    <input type="hidden" name="PA_personaccountid" value="<?php echo $personAccount->PA_personaccountid; ?>" />
    <input type="hidden" name="option" value="com_personaccount" />
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
	/**
	 * editPersonAccountEntry
	 * @param $person
	 * @param $personAccount
	 * @param $personAccountEntry
	 */
	static function editPersonAccountEntry(&$person, &$personAccount, &$personAccountEntry) {
		global $core, $my;
		$date = new DateUtil($personAccountEntry->PN_date);
?>
<script type="text/javascript">
	document.write(getCalendarStyles());

	var cal1x = new CalendarPopup("caldiv");
	cal1x.setMonthNames("Leden","Únor","Březen","Duben","Květen","Červen","Červenec","Srpen","Září","Říjen","Listopad","Prosinec");
	cal1x.showYearNavigation(true);
	cal1x.setDayHeaders("N","P","Ú","S","Č","P","S");
	cal1x.setWeekStartDay(1);
	cal1x.setTodayText("Dnes");
	cal1x.offsetX = 0;
	cal1x.offsetY = 20;

	function submitbutton(pressbutton) {
		var form = document.adminForm;
		if (pressbutton == 'cancel') {
			hideMainMenu();
			submitform('cancelPAE');
			return;
		}
		var amount = form.PN_amount.value.replace(',', '.');
		if (amount == '' || amount != parseFloat(amount)) {
			alert('<?php echo _("Please enter amount in valid number format"); ?>');
		} else if (!isDate(form.PN_date.value, 'dd.MM.yyyy')) {
			alert('<?php echo _("Please enter date in valid format"); ?>');
		} else {
			if (pressbutton == 'save') {
				submitform('savePAE');
			}
		}
	}
</script>
<div id="caldiv" STYLE="position:absolute;visibility:hidden;background-color:white;layer-background-color:white;z-index:999;"></div>

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

        <div class="header icon-48-person">
          <?php echo _("User's account"); ?>: <small><?php echo _("New payment"); ?></small>
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
      <th colspan="2" class="title"><?php echo _("User's account"); ?></th>
    </tr>
    </thead>
    <tbody>
    <tr>
      <td width="250"><?php echo _("Name:"); ?></td>
      <td width="205"><?php echo $person->PE_firstname . ' ' . $person->PE_surname; ?></td>
    </tr>
    <tr>
      <td><?php echo _("Currency:"); ?></td>
      <td><?php echo $personAccount->PA_currency; ?></td>
    </tr>
    <tr>
      <td><?php echo _("Starting balance:"); ?></td>
      <td><?php echo $personAccount->PA_startbalance; ?></td>
    </tr>
    <tr>
      <td><?php echo _("Total income:"); ?></td>
      <td><?php echo NumberFormat::formatMoney($personAccount->PA_income); ?></td>
    </tr>
    <tr>
      <td><?php echo _("Total outbound:"); ?></td>
      <td><?php echo NumberFormat::formatMoney($personAccount->PA_outcome); ?></td>
    </tr>
    <tr>
      <td><?php echo _("Account balance:"); ?></td>
      <td><?php echo NumberFormat::formatMoney($personAccount->PA_balance); ?></td>
    </tr>
    <tr>
      <td><?php echo _("Variable symbol:"); ?></td>
      <td><?php echo (!$personAccount->PA_variablesymbol) ? "N/A" : $personAccount->PA_variablesymbol; ?></td>
    </tr>
    <tr>
      <td><?php echo _("Constant symbol:"); ?></td>
      <td><?php echo (!$personAccount->PA_constantsymbol) ? "N/A" : $personAccount->PA_constantsymbol; ?></td>
    </tr>
    <tr>
      <td><?php echo _("Specific symbol:"); ?></td>
      <td><?php echo (!$personAccount->PA_specificsymbol) ? "N/A" : $personAccount->PA_specificsymbol; ?></td>
    </tr>
    </tbody>
    </table>
    </td>
      <td width="10">
        &nbsp;
      </td>
    <td width="360" valign="top">
    <table class="adminform">
    <thead>
    <tr>
      <th colspan="2" class="title"><?php echo _("New payment"); ?></th>
    </tr>
    </thead>
    <tbody>
    <tr>
      <td width="150"><?php echo _("Status:"); ?></td>
      <td width="205">
        <select name="PN_source" class="width-form">
<?php
	if ($my->GR_level == Group::SUPER_ADMININSTRATOR) {
?>
          <option value="<?php echo PersonAccountEntry::SOURCE_CASH; ?>" <?php echo ($personAccountEntry->PN_source == PersonAccountEntry::SOURCE_CASH) ? 'selected="selected"' : ""; ?>><?php echo PersonAccountEntry::getLocalizedSource(PersonAccountEntry::SOURCE_CASH); ?></option>
<?php
	}
?>
          <option value="<?php echo PersonAccountEntry::SOURCE_DISCOUNT; ?>" <?php echo ($personAccountEntry->PN_source == PersonAccountEntry::SOURCE_DISCOUNT) ? 'selected="selected"' : ""; ?>><?php echo PersonAccountEntry::getLocalizedSource(PersonAccountEntry::SOURCE_DISCOUNT); ?></option>
        </select>
      </td>
    </tr>
    <tr>
      <td width="150"><?php echo _("Amount:"); ?></td>
      <td width="205"><input type="text" name="PN_amount" class="width-form" size="40" value="<?php echo $personAccountEntry->PN_amount; ?>" /></td>
    </tr>
    <tr>
      <td><?php echo _("Date:"); ?></td>
      <td>
        <input type="text" name="PN_date" class="width-form-button" value="<?php echo $date->getFormattedDate(DateUtil::FORMAT_DATE); ?>" size="15" />
        <a href="#" onclick="cal1x.select(document.adminForm.PN_date,'anchor1x','dd.MM.yyyy'); return false;" name="anchor1x" id="anchor1x"><img src="images/22x22/apps/calendar.png" style="width: 16px; height: 16px; vertical-align: middle; position: relative; top: -2px; cursor: pointer;" alt="<?php echo _("Calendar"); ?>" /></a>
      </td>
    </tr>
    <tr>
      <td><?php echo _("Comment:"); ?></td>
      <td><input type="text" name="PN_comment" class="width-form" size="40" value="<?php echo $personAccountEntry->PN_comment; ?>" /></td>
    </tr>
    </tbody>
    </table>
    </td>
    <td>
    </td>
    </tr>
    </table>
    <input type="hidden" name="PE_personid" value="<?php echo $person->PE_personid; ?>" />
    <input type="hidden" name="option" value="com_personaccount" />
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
} // End of HTML_PersonAccount class
?>
