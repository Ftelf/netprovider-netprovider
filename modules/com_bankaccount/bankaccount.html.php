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

class HTML_BankAccount {
	/**
	 * showEntries for selected BankAccount
	 * @param $groups
	 * @param $pageNav
	 */
	static function showEntries(&$bankAccounts, $bid, &$bankAccountEntries, &$report, &$filter, &$pageNav) {
		global $core, $my;
?>
<script type="text/javascript" language="javascript" src="js/CalendarPopup.js"></script>
<script type="text/javascript" language="JavaScript">document.write(getCalendarStyles());</script>
<script language="JavaScript" type="text/javascript">
	function showBankList() {
		hideMainMenu();
		submitbutton('showBankList');
  	}
	function editBA(id) {
    	hideMainMenu();
   		submitform('editBA');
	}
//	function edit(id) {
//    	var form = document.adminForm;
//    	form.GR_groupid.value = id;
//    	hideMainMenu();
//   		submitform('edit');
//	}
  	function editBAE() {
		if (document.adminForm.boxchecked.value == 0) {
			alert('<?php echo _("Please select record to edit"); ?>');
		} else {
			hideMainMenu();
			submitbutton('editBAEA');
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
	cal1x.offsetX = -132;
	cal1x.offsetY = 38;
	cal1x.setFireFunctionOnHide('filterChange();');

	var cal2x = new CalendarPopup("caldiv");
	cal2x.setMonthNames("Leden","Únor","Březen","Duben","Květen","Červen","Červenec","Srpen","Září","Říjen","Listopad","Prosinec");
	cal2x.showYearNavigation(true);
	cal2x.setDayHeaders("N","P","Ú","S","Č","P","S");
	cal2x.setWeekStartDay(1);
	cal2x.setTodayText("Dnes");
	cal2x.offsetX = -132;
	cal2x.offsetY = 17;
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
            <td id="toolbar-show-bank-list">
              <a href="javascript:showBankList();">
                <span title="<?php echo _("Bank printouts"); ?>" class="icon-32-show-bank-list"></span>
                <?php echo _("Bank printouts"); ?>
              </a>
            </td>
<?php
	if ($my->GR_level == Group::SUPER_ADMININSTRATOR) {
?>
            <td id="toolbar-edit">
              <a href="javascript:editBA();">
                <span title="<?php echo _("Edit bank account"); ?>" class="icon-32-edit"></span>
                <?php echo _("Edit bank account"); ?>
              </a>
            </td>
<?php
	}
?>
            <td id="toolbar-edit-account-entry">
              <a href="javascript:editBAE();">
                <span title="<?php echo _("Edit account entry"); ?>" class="icon-32-edit-account-entry"></span>
                <?php echo _("Edit account entry"); ?>
              </a>
            </td>
          </tr>
          </table>
        </div>
        
        <div class="header icon-48-bank-account">
          <?php echo _("Bank account entry printout"); ?>
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
      <td class="right">
        <table>
        <tr>
          <td class="right">
            <select name="BA_bankaccountid" size="1" onchange="document.adminForm.submit( );">
<?php
	if (count($bankAccounts) == 0) {
		echo '<option value="0" selected="selected">'._("- No bank account defined -").'- Není definován žádný bankovní účet -</option>' . "\n";
	} else {
		foreach ($bankAccounts as $bankAccount) {
			echo '<option value="' . $bankAccount->BA_bankaccountid . '"'; if ($bankAccount->BA_bankaccountid == $bid) echo ' selected="selected"'; echo ">$bankAccount->BA_bankname $bankAccount->BA_accountnumber/$bankAccount->BA_banknumber</option>\n";
		}
	}
?>
            </select>
          </td>
        </tr>
        </table>
      </td>
      <td rowspan="2">
        <table>
        <tr>
          <td>Od:</td>
          <td><input type="hidden" name="filter[date_from]" id="date_fromx" value="<?php echo $filter['date_from']; ?>" /><input type="text" name="date_from" class="width-form" value="<?php echo $filter['date_from']; ?>" size="10" onchange="filterChange()" /></td>
          <td><a href="#" onclick="cal1x.select(document.adminForm.date_from,'anchor1x','dd.MM.yyyy'); return false;" name="anchor1x" id="anchor1x"><img src="images/22x22/apps/calendar.png" style="width: 16px; height: 16px; vertical-align: middle; position: relative; top: -1px; cursor: pointer;" alt="<?php echo _("Calendar"); ?>" /></a></td>
        </tr>
        <tr>
          <td>Do:</td>
          <td><input type="hidden" name="filter[date_to]" id="date_tox" value="<?php echo $filter['date_to']; ?>" /><input type="text" name="date_to" class="width-form" value="<?php echo $filter['date_to']; ?>" size="10" onchange="filterChange();" /></td>
          <td><a href="#" onclick="cal2x.select(document.adminForm.date_to,'anchor2x','dd.MM.yyyy'); return false;" name="anchor2x" id="anchor2x"><img src="images/22x22/apps/calendar.png" style="width: 16px; height: 16px; vertical-align: middle; position: relative; top: -1px; cursor: pointer;" alt="<?php echo _("Calendar"); ?>" /></a></td>
        </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td>
        <table>
        <tr>
          <td class="right">
            <select name="filter[entryTypeOfTransaction]" size="1" onchange="document.adminForm.submit( );">
              <option value="-1" <?php if ($filter['entryTypeOfTransaction'] == -1) echo ' selected="selected"';?>><?php echo _("- Type of transaction -"); ?></option>
<?php
	foreach (BankAccountEntry::$TYPE_ARRAY as $tk) {
?>
              <option value="<?php echo $tk; ?>" <?php echo ($filter['entryTypeOfTransaction'] == $tk) ? 'selected="selected"' : ""; ?>><?php echo BankAccountEntry::getLocalizedType($tk); ?></option>
<?php
	}
?>
            </select>
          </td>
          <td>
            <select name="filter[entryStatusOfTransaction]" size="1" onchange="document.adminForm.submit( );">
              <option value="-1" <?php if ($filter['entryStatusOfTransaction'] == -1) echo ' selected="selected"';?>><?php echo _("- Status -"); ?></option>
<?php
	foreach (BankAccountEntry::$STATUS_ARRAY as $sk) {
?>
                <option value="<?php echo $sk; ?>" <?php echo ($filter['entryStatusOfTransaction'] == $sk) ? 'selected="selected"' : ""; ?>><?php echo BankAccountEntry::getLocalizedStatus($sk); ?></option>
<?php
	}
?>
            </select>
          </td>
          <td>
            <select name="filter[entryIdentifyCodeOfTransaction]" size="1" onchange="document.adminForm.submit( );">
              <option value="-1" <?php if ($filter['entryIdentifyCodeOfTransaction'] == -1) echo ' selected="selected"';?>><?php echo _("- Identification -"); ?></option>
<?php
	foreach (BankAccountEntry::$IDENTIFICATION_ARRAY as $ik) {
?>
                <option value="<?php echo $ik; ?>" <?php echo ($filter['entryIdentifyCodeOfTransaction'] == $ik) ? 'selected="selected"' : ""; ?>><?php echo BankAccountEntry::getLocalizedIdentification($ik); ?></option>
<?php
	}
?>
            </select>
          </td>
        </tr>
        </table>
      </td>
    </tr>
    </table>
<?php
	if (isset($bankAccounts[$bid])) {
?>
    <table>
    <tbody>
    <tr>
    <td>
    <table class="adminform">
    <thead>
    <tr>
      <th class="title" colspan="2"><?php echo _("Bank account");?></th>
    </tr>
    </thead>
    <tbody>
    <tr>
      <td width="150"><?php echo _("Bank name:"); ?></td>
      <td><?php echo $bankAccounts[$bid]->BA_bankname; ?></td>
    </tr>
    <tr>
      <td><?php echo _("Account name:"); ?></td>
      <td><?php echo $bankAccounts[$bid]->BA_accountname?></td>
    </tr>
    <tr>
      <td><?php echo _("Account number:"); ?></td>
      <td><?php echo $bankAccounts[$bid]->BA_accountnumber . '/' . $bankAccounts[$bid]->BA_banknumber; ?></td>
    </tr>
    <tr>
      <td><?php echo _("IBAN:"); ?></td>
      <td><?php echo $bankAccounts[$bid]->BA_iban; ?></td>
    </tr>
    <tr>
      <td><?php echo _("Currency:"); ?></td>
      <td><?php echo $bankAccounts[$bid]->BA_currency; ?></td>
    </tr>
    </tbody>
    </table>
    </td>
    <td width="10">
    </td>
    <td>
    <table class="adminform">
    <thead>
    <tr>
      <th width="150">&nbsp;</th>
      <th class="right"><?php echo _("Global state"); ?></th>
      <th class="right"><?php echo _("Printout view state"); ?></th>
    </tr>
    </thead>
    <tbody>
    <tr>
      <td><?php echo _("Starting balance:"); ?></td>
      <td class="right"><?php echo NumberFormat::formatMoney($report['GLOBAL']['START']); ?></td>
      <td class="right"><?php /*echo NumberFormat::formatMoney($report['LIST']['START']);*/ ?></td>
    </tr>
    <tr>
      <td><?php echo _("Income:"); ?></td>
      <td class="right"><?php echo NumberFormat::formatMoney($report['GLOBAL']['INCOME']); ?></td>
      <td class="right"><?php echo NumberFormat::formatMoney($report['LIST']['INCOME']); ?></td>
    </tr>
    <tr>
      <td><?php echo _("Outcome:"); ?></td>
      <td class="right"><?php echo NumberFormat::formatMoney($report['GLOBAL']['EXPENSE']); ?></td>
      <td class="right"><?php echo NumberFormat::formatMoney($report['LIST']['EXPENSE']); ?></td>
    </tr>
    <tr>
      <td><?php echo _("Fees included:"); ?></td>
      <td class="right"><?php echo NumberFormat::formatMoney($report['GLOBAL']['CHARGE']); ?></td>
      <td class="right"><?php echo NumberFormat::formatMoney($report['LIST']['CHARGE']); ?></td>
    </tr>
    <tr>
      <td><?php echo _("Final balance:"); ?></td>
      <td class="right"><?php echo NumberFormat::formatMoney($report['GLOBAL']['BALANCE']); ?></td>
      <td class="right"><?php /*echo NumberFormat::formatMoney($report['LIST']['BALANCE']);*/ ?></td>
    </tr>
    </tbody>
    </table>
    </td>
    </tr>
    </tbody>
    </table>
    
    <br/>
    
    <table class="adminlist">
    <thead>
    <tr>
     <th width="4%" class="title-multiple-row">#</th>
     <th width="2%" class="title-multiple-row"><input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo $pageNav->limit; ?>, 'cb');" /></th>
     <th width="5%" class="title-multiple-row"><?php echo _("Date"); ?></th>
     <th width="29%" class="title-multiple-row"><?php echo _("Note"); ?></th>
     <th width="10%" class="title-multiple-row"><?php echo _("Write out"); ?></th>
     <th width="10%" class="title-multiple-row"><?php echo _("SS"); ?></th>
     <th width="20%" class="title-multiple-row"><?php echo _("Type of transaction"); ?></th>
     <th width="10%" class="title-multiple-row"><?php echo _("Amount"); ?></th>
     <th width="10%" class="title-multiple-row"><?php echo _("Fee"); ?></th>
   </tr>
   <tr>
     <th class="title-multiple-row">&nbsp;</th>
     <th class="title-multiple-row">&nbsp;</th>
     <th class="title-multiple-row"><?php echo _("Time"); ?></th>
     <th class="title-multiple-row"><?php echo _("Account name"); ?></th>
     <th class="title-multiple-row">&nbsp;</th>
     <th class="title-multiple-row"><?php echo _("VS"); ?></th>
     <th class="title-multiple-row"><?php echo _("Status"); ?></th>
     <th class="title-multiple-row">&nbsp;</th>
     <th class="title-multiple-row">&nbsp;</th>
   </tr>
   <tr>
     <th class="title-multiple-row">&nbsp;</th>
     <th class="title-multiple-row">&nbsp;</th>
     <th class="title-multiple-row">&nbsp;</th>
     <th class="title-multiple-row"><?php echo _("Account number"); ?></th>
     <th class="title-multiple-row">&nbsp;</th>
     <th class="title-multiple-row"><?php echo _("KS"); ?></th>
     <th class="title-multiple-row"><?php echo _("Identification"); ?></th>
     <th class="title-multiple-row">&nbsp;</th>
     <th class="title-multiple-row">&nbsp;</th>
   </tr>
   <tr>
     <th class="title-multiple-row">&nbsp;</th>
     <th class="title-multiple-row">&nbsp;</th>
     <th class="title-multiple-row">&nbsp;</th>
     <th class="title-multiple-row"><?php echo _("Message"); ?></th>
     <th class="title-multiple-row">&nbsp;</th>
     <th class="title-multiple-row">&nbsp;</th>
     <th class="title-multiple-row">&nbsp;</th>
     <th class="title-multiple-row">&nbsp;</th>
     <th class="title-multiple-row">&nbsp;</th>
   </tr>
   </thead>
    <tfoot>
    <tr>
      <td colspan="9">
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
	foreach ($bankAccountEntries as $bankAccountEntry) {
		$link = "javascript:edit('$bankAccountEntry->BE_bankaccountentryid');";
		$datetime = new DateUtil($bankAccountEntry->BE_datetime);
		$writeoffDate = new DateUtil($bankAccountEntry->BE_writeoff_date);
?>
   <tr class="<?php echo "row$k"; ?>" >
   <td colspan="9" style="padding: 0;">
   <table class="insideadminlist">
   <tbody>
   <tr>
     <td width="4%">
       <?php echo $pageNav->rowNumber($i); ?>
     </td>
     <td width="2%">
<?php
	if ($bankAccountEntry->BE_status != BankAccountEntry::STATUS_PROCESSED) { ?>
       <input type="checkbox" id="<?php echo "cb$i"; ?>" name="cid[]" value="<?php echo $bankAccountEntry->BE_bankaccountentryid; ?>" onclick="isChecked(this.checked);" />
<?php
	}
?>
     </td>
     <td width="5%"><?php echo $datetime->getFormattedDate(DateUtil::FORMAT_SHORTDATE); ?></td>
     <td width="29%"><?php echo $bankAccountEntry->BE_note; ?></td>
     <td width="10%"><?php echo $writeoffDate->getFormattedDate(DateUtil::FORMAT_DATE); ?></td>
     <td width="10%"><?php echo $bankAccountEntry->BE_specificsymbol; ?></td>
     <td width="20%"><?php echo BankAccountEntry::getLocalizedType($bankAccountEntry->BE_typeoftransaction); ?></td>
     <td width="10%"><?php echo NumberFormat::formatMoney($bankAccountEntry->BE_amount); ?></td>
     <td width="10%"><?php echo NumberFormat::formatMoney($bankAccountEntry->BE_charge); ?></td>
   </tr>
   <tr>
     <td>&nbsp;</td>
     <td>&nbsp;</td>
     <td><?php echo $datetime->getFormattedDate(DateUtil::FORMAT_SHORTTIME);; ?></td>
     <td><?php echo $bankAccountEntry->BE_accountname; ?></td>
     <td>&nbsp;</td>
     <td><?php echo $bankAccountEntry->BE_variablesymbol; ?></td>
     <td><?php echo BankAccountEntry::getLocalizedStatus($bankAccountEntry->BE_status); ?></td>
     <td>&nbsp;</td>
     <td>&nbsp;</td>
   </tr>
   <tr>
     <td>&nbsp;</td>
     <td>&nbsp;</td>
     <td>&nbsp;</td>
     <td><?php echo $bankAccountEntry->BE_accountnumber . "/" . $bankAccountEntry->BE_banknumber; ?></td>
     <td>&nbsp;</td>
     <td><?php echo $bankAccountEntry->BE_constantsymbol; ?></td>
     <td><?php echo BankAccountEntry::getLocalizedIdentification($bankAccountEntry->BE_identifycode); ?></td>
     <td>&nbsp;</td>
     <td>&nbsp;</td>
   </tr>
   <tr>
     <td class="last">&nbsp;</td>
     <td class="last">&nbsp;</td>
     <td class="last">&nbsp;</td>
     <td class="last" colspan="3"><?php echo $bankAccountEntry->BE_message; ?></td>
     <td class="last" colspan="3"><?php echo $bankAccountEntry->userAccountName; ?></td>
     <td class="last">&nbsp;</td>
   </tr>
   </tbody>
   </table>
   </td>
   </tr>
<?php
		$k = 1 - $k;
		$i++;
	}
?>
   </table>
<?php
	}
?>
   <input type="hidden" name="option" value="com_bankaccount" />
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
	 * editBankAccount
	 * @param $bankAccount
	 * @param $flags
	 */
	static function editBankAccount(&$bankAccount, &$flags) {
		global $core;
?>
<script language="javascript" type="text/javascript">
	function submitbutton(pressbutton) {
		var form = document.adminForm;
		if (pressbutton == 'cancel') {
			submitform('cancelHasCharge');
		} else if (pressbutton == 'apply') {
			hideMainMenu();
			submitform('applyBA');
		} else if (pressbutton == 'save') {
			hideMainMenu();
			submitform('saveBA');
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

        <div class="header icon-48-person">
          <?php echo _("Bank account entry printout"); ?>: <small><?php echo _("Bank account edit"); ?></small>
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
      <td width="360" valign="top">
        <table class="adminform">
        <thead>
        <tr>
          <th colspan="2"><?php echo _("Bank"); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td width="150"><?php echo _("Bank name:"); ?></td>
          <td width="205"><input type="text" name="BA_bankname" class="width-form" size="40" value="<?php echo $bankAccount->BA_bankname; ?>"<?php if (!$flags['BA_bankname']) echo ' disabled="disabled"'; ?> /></td>
        </tr>
        <tr>
          <td><?php echo _("Bank registration number:"); ?></td>
          <td><input type="text" name="BA_banknumber" class="width-form" size="40" value="<?php echo $bankAccount->BA_banknumber; ?>"<?php if (!$flags['BA_banknumber']) echo ' disabled="disabled"'; ?> /></td>
        </tr>
        </tbody>
        </table>
        
        <br/>
        
        <table class="adminform">
        <thead>
        <tr>
          <th colspan="2"><?php echo _("Account"); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td width="150"><?php echo _("Account name:"); ?></td>
          <td width="205"><input type="text" name="BA_accountname" class="width-form" size="40" value="<?php echo $bankAccount->BA_accountname; ?>"<?php if (!$flags['BA_accountname']) echo ' disabled="disabled"'; ?> /></td>
        </tr>
        <tr>
          <td><?php echo _("Account number:"); ?></td>
          <td><input type="text" name="BA_accountnumber" class="width-form" size="40" value="<?php echo $bankAccount->BA_accountnumber; ?>"<?php if (!$flags['BA_accountname']) echo ' disabled="disabled"'; ?> /></td>
        </tr>
        <tr>
          <td><?php echo _("IBAN:"); ?></td>
          <td><input type="text" name="BA_iban" class="width-form" size="40" value="<?php echo $bankAccount->BA_iban; ?>"<?php if (!$flags['BA_iban']) echo ' disabled="disabled"'; ?> /></td>
        </tr>
        <tr>
          <td><?php echo _("Currency:"); ?></td>
          <td>
            <select name="BA_currency" class="width-form" size="1">
<?php
	if (!$flags['BA_accountname']) {
		echo "<option value=\"$bankAccount->BA_currency\" selected=\"selected\">$bankAccount->BA_currency</option>\n";
	} else {
		foreach (BankAccount::$CURRENCY_ARRAY as $currency) {
			echo '<option value="' . $currency . '"' ; if ($bankAccount->BA_currency == $currency) echo ' selected="selected"';echo ">$currency</option>\n";
		}
	}
?>
	        </select>
          </td>
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
          <th colspan="2"><?php echo _("Account state"); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td width="150"><?php echo _("Starting balance"); ?></td>
          <td width="205"><input type="text" name="BA_startbalance" class="width-form" size="40" value="<?php echo NumberFormat::formatMoney($bankAccount->BA_startbalance); ?>"<?php if (!$flags['BA_startballance']) echo ' disabled="disabled"'; ?> /></td>
        </tr>
        </table>
        
        <br/>
        
        <table class="adminform">
        <thead>
        <tr>
          <th colspan="2"><?php echo _("Account printout access"); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td width="150"><?php echo _("Data retrieve method"); ?></td>
          <td width="205">
            <select name="BA_datasource" class="width-form" size="1">
<?php
	foreach (BankAccount::$datasourceArray as $dk) {
?>
              <option value="<?php echo $dk; ?>" <?php echo ($bankAccount->BA_datasource == $dk) ? 'selected="selected"' : ""; ?>><?php echo BankAccount::getLocalizedDatasource($dk); ?></option>
<?php
	}
?>
	        </select>
          </td>
        </tr>
        <tr>
          <td width="150"><?php echo _("Data type"); ?></td>
          <td width="205">
            <select name="BA_datasourcetype" class="width-form" size="1">
<?php
	foreach (BankAccount::$datasourceTypesArray as $dk) {
?>
              <option value="<?php echo $dk; ?>" <?php echo ($bankAccount->BA_datasourcetype == $dk) ? 'selected="selected"' : ""; ?>><?php echo BankAccount::getLocalizedDatasourceType($dk); ?></option>
<?php
	}
?>
	        </select>
          </td>
        </tr>
        </tbody>
        </table>
        
        <br/>
        
        <table class="adminform">
        <thead>
        <tr>
          <th colspan="2"><?php echo _("Email account with printouts"); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td width="150"><?php echo _("Server:"); ?></td>
          <td width="205"><input type="text" name="BA_emailserver" class="width-form" size="40" value="<?php echo $bankAccount->BA_emailserver; ?>" /></td>
        </tr>
        <tr>
          <td><?php echo _("Username:"); ?></td>
          <td><input type="text" name="BA_emailusername" class="width-form" size="40" value="<?php echo $bankAccount->BA_emailusername; ?>" /></td>
        </tr>
        <tr>
          <td><?php echo _("Password:"); ?></td>
          <td><input type="text" name="BA_emailpassword" class="width-form" size="40" value="<?php echo $bankAccount->BA_emailpassword; ?>" /></td>
        </tr>
        <tr>
          <td><?php echo _("Sender address:"); ?></td>
          <td><input type="text" name="BA_emailsender" class="width-form" size="40" value="<?php echo $bankAccount->BA_emailsender; ?>" /></td>
        </tr>
        <tr>
          <td><?php echo _("Subject:"); ?></td>
          <td><input type="text" name="BA_emailsubject" class="width-form" size="40" value="<?php echo $bankAccount->BA_emailsubject; ?>" /></td>
        </tr>
        </tbody>
        </table>
      </td>
      <td>
        &nbsp;
      </td>
    </tr>
    </table>
    <input type="hidden" name="BA_bankaccountid" value="<?php echo $bankAccount->BA_bankaccountid; ?>" />
    <input type="hidden" name="option" value="com_bankaccount" />
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
	formValidator.addValidation("BA_bankname","required","<?php echo _("Please enter bank name"); ?>");
	formValidator.addValidation("BA_banknumber","required","<?php echo _("Please enter bank identification number"); ?>");
	formValidator.addValidation("BA_accountname","required","<?php echo _("Please select account name"); ?>");
	formValidator.addValidation("BA_accountnumber","required","<?php echo _("Please select account number"); ?>");
	formValidator.addValidation("BA_iban","required","<?php echo _("Please select IBAN"); ?>");
	formValidator.addValidation("BA_startbalance","required","<?php echo _("Please select starting balance"); ?>");
</script>
<?php
	}
	/**
	 * showBankList for selected BankAccount
	 * @param $bankAccount
	 * @param $emailLists
	 * @param $pageNav
	 */
	static function showBankList(&$bankAccount, &$emailLists, &$pageNav) {
		global $core, $my;
?>
<script type="text/javascript" language="javascript" src="js/CalendarPopup.js"></script>
<script type="text/javascript" language="JavaScript">document.write(getCalendarStyles());</script>
<script language="JavaScript" type="text/javascript">
	function submitbutton(pressbutton) {
		var form = document.adminForm;
		if (pressbutton == 'cancel') {
			submitform(pressbutton);
		} else if (pressbutton == 'uploadBankLists') {
            hideMainMenu();
			submitform(pressbutton);
		} else if (pressbutton == 'downloadBankLists') {
            hideMainMenu();
			submitform(pressbutton);
		} else if (pressbutton == 'processBankLists') {
            hideMainMenu();
			submitform(pressbutton);
		} else if (pressbutton == 'processEntries') {
            hideMainMenu();
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
<?php
	if ($my->GR_level == Group::SUPER_ADMININSTRATOR) {
?>
            <td id="toolbar-upload-bank-lists">
              <a href="javascript:submitbutton('uploadBankLists');">
                <span title="<?php echo _("Upload new printout"); ?>" class="icon-32-upload-bank-lists"></span>
                <?php echo _("Upload new printouts"); ?>
              </a>
            </td>
<?php
	if ($bankAccount->BA_datasource == BankAccount::DATASOURCE_EMAIL_CONTENT) {
?>
            <td id="toolbar-download-bank-lists">
              <a href="javascript:submitbutton('downloadBankLists');">
                <span title="<?php echo _("Download new printouts"); ?>" class="icon-32-download-bank-lists"></span>
                <?php echo _("Download new printouts"); ?>
              </a>
            </td>
<?php
	}
?>
            <td id="toolbar-process-bank-lists">
              <a href="javascript:submitbutton('processBankLists');">
                <span title="<?php echo _("Run import"); ?>" class="icon-32-process-bank-lists"></span>
                <?php echo _("Run import"); ?>
              </a>
            </td>

            <td id="toolbar-process-entries">
              <a href="javascript:submitbutton('processEntries');">
                <span title="<?php echo _("Entries workout"); ?>" class="icon-32-process-entries"></span>
                <?php echo _("Entries workout"); ?>
              </a>
            </td>
<?php
	}
?>
            <td id="toolbar-cancel">
              <a href="javascript:submitbutton('cancel');">
                <span title="<?php echo _("Cancel"); ?>" class="icon-32-cancel"></span>
                <?php echo _("Cancel"); ?>
              </a>
            </td>
          </tr>
          </table>
        </div>

        <div class="header icon-48-bank-account">
          <?php echo _("Bank account entry printout"); ?>: <small><?php echo _("Printouts list"); ?></small>
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
    <table class="splitform">
    <tr>
    <td width="360" valign="top">
    <table class="adminform">
    <thead>
    <tr>
      <th class="title" colspan="2"><?php echo _("Bank account");?></th>
    </tr>
    </thead>
    <tbody>
    <tr>
      <td width="150"><?php echo _("Bank name:"); ?></td>
      <td width="205"><?php echo $bankAccount->BA_bankname; ?></td>
    </tr>
    <tr>
      <td><?php echo _("Account name:"); ?></td>
      <td><?php echo $bankAccount->BA_accountname; ?></td>
    </tr>
    <tr>
      <td><?php echo _("Account number:"); ?></td>
      <td><?php echo $bankAccount->BA_accountnumber . '/' . $bankAccount->BA_banknumber; ?></td>
    </tr>
    <tr>
      <td><?php echo _("IBAN:"); ?></td>
      <td><?php echo $bankAccount->BA_iban; ?></td>
    </tr>
    <tr>
      <td><?php echo _("Currency:"); ?></td>
      <td><?php echo $bankAccount->BA_currency; ?></td>
    </tr>
    </tbody>
    </table>
    <td>
      &nbsp;
    </td>
    </tr>
    </table>
    
    <br/>
    
    <table class="adminlist">
    <thead>
    <tr>
     <th width="2%" class="title">#</th>
     <th width="5%" class="title"><?php echo _("Year"); ?></th>
     <th width="10%" class="title"><?php echo _("Printout number"); ?></th>
     <th width="25%" class="title"><?php echo _("Data filename"); ?></th>
     <th width="25%" class="title"><?php echo _("Data filename type"); ?></th>
     <th width="10%" class="title"><?php echo _("Number of entries"); ?></th>
     <th width="15%" class="title"><?php echo _("since"); ?></th>
     <th width="15%" class="title"><?php echo _("Till"); ?></th>
     <th width="18%" class="title"><?php echo _("Status"); ?></th>
    </tr>
    </thead>
    <tfoot>
    <tr>
      <td colspan="8">
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
	foreach ($emailLists as $emailList) {
		$link = htmlspecialchars("download.php?option=com_bankaccount&task=download&EL_emaillistid=$emailList->EL_emaillistid", ENT_COMPAT);
		$dateFrom = new DateUtil($emailList->EL_datefrom);
		$dateTo = new DateUtil($emailList->EL_dateto);
?>
    <tr class="<?php echo "row$k"; ?>" >
      <td width="4%">
        <?php echo $pageNav->rowNumber($i); ?>
      </td>
      <td><?php echo $emailList->EL_year; ?></td>
      <td><?php echo $emailList->EL_no; ?></td>
      <td><a href="<?php echo $link; ?>" target="_blank"><?php echo $emailList->EL_name; ?></a></td>
      <td><?php echo EmailList::getLocalizedListType($emailList->EL_listtype); ?></td>
      <td><?php echo $emailList->EL_entrycount; ?></td>
      <td><?php echo $dateFrom->getFormattedDate(DateUtil::FORMAT_DATE); ?></td>
      <td><?php echo $dateTo->getFormattedDate(DateUtil::FORMAT_DATE); ?></td>
      <td><?php echo EmailList::getLocalizedStatus($emailList->EL_status); ?></td>
    </tr>
<?php
		$k = 1 - $k;
		$i++;
	}
?>
    </tbody>
    </table>
    <input type="hidden" name="option" value="com_bankaccount" />
    <input type="hidden" name="BA_bankaccountid" value="<?php echo $bankAccount->BA_bankaccountid; ?>" />
    <input type="hidden" name="EL_emaillistid" value="" />
    <input type="hidden" name="task" value="showBankList" />
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
	 * editBankAccountEntry
	 * @param $bankAccountEntry
	 * @param $persons
	 */
	static function editBankAccountEntry(&$bankAccountEntry, &$persons) {
		global $core;
		$datetime = new DateUtil($bankAccountEntry->BE_datetime);
		$writeoffDate = new DateUtil($bankAccountEntry->BE_writeoff_date);
?>
<script language="javascript" type="text/javascript">
	var amount = <?php echo $bankAccountEntry->BE_amount; ?>;

	function submitbutton(pressbutton) {
		var form = document.adminForm;
		if (pressbutton == 'cancel') {
			submitform(pressbutton);
			return;
		}
		if (form.BE_identifycode.options[form.BE_identifycode.selectedIndex].value == <?php echo BankAccountEntry::IDENTIFY_UNIDENTIFIED; ?>) {
			alert("<?php echo _("Identification wasn't performed"); ?>");
		} else if (form.BE_identifycode.options[form.BE_identifycode.selectedIndex].value == <?php echo BankAccountEntry::IDENTIFY_PERSONACCOUNT; ?> && pressbutton == 'save') {
			var rows = document.getElementById('userTable').getElementsByTagName("tbody")[0].getElementsByTagName("tr");
			
			if (rows.length > 1) {
				var sum = 0;
				for (var i = 1, l = rows.length; i < l; i++) {
					var row = rows[i];
					var subString = row.childNodes[1].childNodes[0].value;
					
					var subNumber = parseFloat(subString);
					if (subNumber && subString != subNumber) {
						alert('<?php echo _("Please enter amount in valid number format"); ?>');
						return;
					}
					sum += subNumber;
				}
				
				if (amount == sum) {
					submitform('saveBAE');
				} else {
					alert('<?php printf(_("Amount summary must be: %s"), $bankAccountEntry->BE_amount); ?>');
				}
			} else {
				alert("<?php echo _("Please select user's account"); ?>");
			}
		} else if (pressbutton == 'save') {
			submitform('saveBAE');
		}
	}
	
	function onChangeIdentification() {
		var form = document.adminForm;
		if (form.BE_identifycode.options[form.BE_identifycode.selectedIndex].value == <?php echo BankAccountEntry::IDENTIFY_PERSONACCOUNT ?>) {
			document.getElementById('PN_personaccountidBlock').style.display = 'block';
		} else {
			document.getElementById('PN_personaccountidBlock').style.display = 'none';
		}
	}
	
	function onChangeUserAccount() {
		var form = document.adminForm;
		var PN_personaccountid = form.PN_personaccountid;
		var selectedIndex = PN_personaccountid.selectedIndex;
		var selectedItem = PN_personaccountid[selectedIndex];
		var value = selectedItem.value;
		
		if (value == 0) {
			return;
		}
		
		var row = document.createElement("TR");
		//Cell 1
		var cell1 = document.createElement("td");
		var hidden = document.createElement("input");
		hidden.setAttribute("type", "hidden");
		hidden.setAttribute("name", "PN_personaccountid["+value+"]");
		hidden.setAttribute("value", value);
		cell1.appendChild(hidden);
		
		var text = document.createTextNode(selectedItem.text);
		cell1.appendChild(text);
		row.appendChild(cell1);
		
		var cell2 = document.createElement("td");
		var text = document.createElement("input");
		text.setAttribute("type", "text");
		text.setAttribute("name", "PN_personaccountid["+value+"]");
		text.setAttribute("value", "");
		text.setAttribute("class", "width-form-button");
		cell2.appendChild(text);
		
		row.appendChild(cell2);
		
		var cell3 = document.createElement("td");
		cell3.innerHTML = '<img src="images/16x16/actions/cancel.png"/ style="cursor: pointer;" onclick="removeUserAccount(this, '+value+')">';
		
		row.appendChild(cell3);
		document.getElementById('userTable').getElementsByTagName("tbody")[0].appendChild(row);
		
		PN_personaccountid.remove(selectedIndex);
		
		form.PN_personaccountid.selectedIndex = 0;
	}
	function removeUserAccount(img, id) {
		var cell = img.parentNode;
		var row = cell.parentNode;
		var name = row.getElementsByTagName("td")[0].childNodes[1].nodeValue;
		document.getElementById('userTable').getElementsByTagName("tbody")[0].removeChild(row);
		
		var option = document.createElement("option");
		option.value = id;
		option.text = name;
		
		try {
			document.adminForm.PN_personaccountid.add(option, null);
		} catch(e) {
			document.adminForm.PN_personaccountid.add(option);
		}
		sortList();
	}
	
	function sortList() {
		var lb = document.adminForm.PN_personaccountid;
		arrTexts = new Array();
		arrValues = new Array();
		arrOldTexts = new Array();

		for(i=0; i<lb.length; i++) {
			arrTexts[i] = lb.options[i].text;
			arrValues[i] = lb.options[i].value;

			arrOldTexts[i] = lb.options[i].text;
		}

		arrTexts.sort();

		for(i=0; i<lb.length; i++) {
			lb.options[i].text = arrTexts[i];
			for(j=0; j<lb.length; j++) {
				if (arrTexts[i] == arrOldTexts[j]) {
					lb.options[i].value = arrValues[j];
					j = lb.length;
				}
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

        <div class="header icon-48-bank-account">
          <?php echo _("Bank account entry printout"); ?>: <small><?php echo _("Identification"); ?></small>
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
      <td width="360" valign="top">
        <table class="adminform">
        <thead>
        <tr>
          <th colspan="2"><?php echo _("Bank entry"); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td width="150"><?php echo _("Arrival date:"); ?></td>
          <td width="205"><input type="text" name="BE_datetime" class="width-form" size="40" value="<?php echo $datetime->getFormattedDate(DateUtil::FORMAT_FULL); ?>" disabled="disabled" /></td>
        </tr>
        <tr>
          <td><?php echo _("Write off date:"); ?></td>
          <td><input type="text" name="BE_writeoff_date" class="width-form" size="40" value="<?php echo $writeoffDate->getFormattedDate(DateUtil::FORMAT_DATE); ?>" disabled="disabled" /></td>
        </tr>
        <tr>
          <td><?php echo _("Note:"); ?></td>
          <td><input type="text" name="BE_note" class="width-form" size="40" value="<?php echo $bankAccountEntry->BE_note; ?>" disabled="disabled" /></td>
        </tr>
        <tr>
          <td><?php echo _("Account name:"); ?></td>
          <td><input type="text" name="BE_accountname" class="width-form" size="40" value="<?php echo $bankAccountEntry->BE_accountname; ?>" disabled="disabled" /></td>
        </tr>
        <tr>
          <td><?php echo _("Account number:"); ?></td>
          <td><input type="text" name="BE_accountnumber" class="width-form" size="40" value="<?php echo $bankAccountEntry->BE_accountnumber; ?>" disabled="disabled" /></td>
        </tr>
        <tr>
          <td><?php echo _("Bank registration number:"); ?></td>
          <td><input type="text" name="BE_banknumber" class="width-form" size="40" value="<?php echo $bankAccountEntry->BE_banknumber; ?>" disabled="disabled" /></td>
        </tr>
        <tr>
          <td><?php echo _("Variable symbol:"); ?></td>
          <td><input type="text" name="BE_variablesymbol" class="width-form" size="40" value="<?php echo $bankAccountEntry->BE_variablesymbol; ?>" disabled="disabled" /></td>
        </tr>
        <tr>
          <td><?php echo _("Constant symbol:"); ?></td>
          <td><input type="text" name="BE_constantsymbol" class="width-form" size="40" value="<?php echo $bankAccountEntry->BE_constantsymbol; ?>" disabled="disabled" /></td>
        </tr>
        <tr>
          <td><?php echo _("Specific symbol:"); ?></td>
          <td><input type="text" name="BE_specificsymbol" class="width-form" size="40" value="<?php echo $bankAccountEntry->BE_specificsymbol; ?>" disabled="disabled" /></td>
        </tr>
        <tr>
          <td><?php echo _("Amount:"); ?></td>
          <td><input type="text" name="BE_amount" class="width-form" size="40" value="<?php echo NumberFormat::formatMoney($bankAccountEntry->BE_amount); ?>" disabled="disabled" /></td>
        </tr>
        <tr>
          <td><?php echo _("Fee:"); ?></td>
          <td><input type="text" name="BE_charge" class="width-form" size="40" value="<?php echo NumberFormat::formatMoney($bankAccountEntry->BE_charge); ?>" disabled="disabled" /></td>
        </tr>
        <tr>
          <td><?php echo _("Message:"); ?></td>
          <td><input type="text" name="BE_message" class="width-form" size="40" value="<?php echo $bankAccountEntry->BE_message; ?>" disabled="disabled" /></td>
        </tr>
        <tr>
          <td><?php echo _("Type of transaction:"); ?></td>
          <td><input type="text" name="BE_typeoftransaction" class="width-form" size="40" value="<?php echo BankAccountEntry::getLocalizedType($bankAccountEntry->BE_typeoftransaction); ?>" disabled="disabled" /></td>
        </tr>
        <tr>
          <td><?php echo _("Comment:"); ?></td>
          <td><input type="text" name="BE_comment" class="width-form" size="40" value="<?php echo $bankAccountEntry->BE_comment; ?>" /></td>
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
          <th colspan="2"><?php echo _("Identification of payment"); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td width="150"><?php echo _("Identification:"); ?></td>
          <td>
            <select name="BE_identifycode" class="width-form" onchange="onChangeIdentification();">
<?php
	foreach(BankAccountEntry::$IDENTIFICATION_ARRAY as $ik) {
?>
              <option value="<?php echo $ik; ?>"><?php echo BankAccountEntry::getLocalizedIdentification($ik); ?></option>
<?php
	}
?>
            </select>
          </td>
        </tr>
        </tbody>
        </table>
        
        <br/>
        
        <div id="PN_personaccountidBlock" style="display: none;">
        <table class="adminform" id="userTable">
        <thead>
        <tr>
          <th colspan="3"><?php echo _("Benefit user account"); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td width="150" style="border-bottom: 1px solid black;"><?php echo _("User account:"); ?></td>
          <td width="205" colspan="2" style="border-bottom: 1px solid black;">
            <select name="PN_personaccountid" class="width-form" onchange="onChangeUserAccount();">
<?php
	if ($bankAccountEntry->BE_personaccountentryid == null) {
?>
              <option value="0" selected="selected"><?php echo _("- Select user's account -"); ?></option><?php echo "\n";
	}
	
	foreach($persons as $person) {
?>
              <option value="<?php echo $person->PA_personaccountid; ?>"><?php echo $person->PE_surname." ".$person->PE_firstname; echo ($person->PE_nick) ? ", ".$person->PE_nick : ""; ?></option>
<?php
	}
?>
            </select>
          </td>
        </tr>
        </tbody>
        </table>
        </div>
      </td>
      <td>
        &nbsp;
      </td>
    </tr>
    </table>
    <input type="hidden" name="BA_bankaccountid" value="<?php echo $bankAccountEntry->BE_bankaccountid; ?>" />
    <input type="hidden" name="BE_bankaccountentryid" value="<?php echo $bankAccountEntry->BE_bankaccountentryid; ?>" />
    <input type="hidden" name="option" value="com_bankaccount" />
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
     * editBankAccountEntries
     * @param $bankAccountEntries
     */
    static function editBankAccountEntries(&$bankAccountEntries) {
        global $core;
        ?>
        <script language="javascript" type="text/javascript">
            function submitbutton(pressbutton) {
                var form = document.adminForm;
                if (pressbutton == 'cancel') {
                    submitform(pressbutton);
                    return;
                }
                if (form.BE_identifycode.options[form.BE_identifycode.selectedIndex].value == <?php echo BankAccountEntry::IDENTIFY_UNIDENTIFIED; ?>) {
                    alert("<?php echo _("Identification wasn't performed"); ?>");
                } else if (pressbutton == 'save') {
                    submitform('saveBAEA');
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

                        <div class="header icon-48-bank-account">
                            <?php echo _("Bank account entry printout"); ?>: <small><?php echo _("Identification"); ?></small>
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
                                <td width="360" valign="top">
                                    <?php
                                    foreach($bankAccountEntries as $bankAccountEntry) {
                                        $datetime = new DateUtil($bankAccountEntry->BE_datetime);
                                        $writeoffDate = new DateUtil($bankAccountEntry->BE_writeoff_date);
                                    ?>
                                    <input type="hidden" name="cid[]" value="<?php echo $bankAccountEntry->BE_bankaccountentryid; ?>" />
                                    <table class="adminform">
                                        <thead>
                                        <tr>
                                            <th colspan="2"><?php echo _("Bank entry"); ?></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td width="150"><?php echo _("Arrival date:"); ?></td>
                                            <td width="205"><input type="text" name="BE_datetime" class="width-form" size="40" value="<?php echo $datetime->getFormattedDate(DateUtil::FORMAT_FULL); ?>" disabled="disabled" /></td>
                                        </tr>
                                        <tr>
                                            <td><?php echo _("Write off date:"); ?></td>
                                            <td><input type="text" name="BE_writeoff_date" class="width-form" size="40" value="<?php echo $writeoffDate->getFormattedDate(DateUtil::FORMAT_DATE); ?>" disabled="disabled" /></td>
                                        </tr>
                                        <tr>
                                            <td><?php echo _("Note:"); ?></td>
                                            <td><input type="text" name="BE_note" class="width-form" size="40" value="<?php echo $bankAccountEntry->BE_note; ?>" disabled="disabled" /></td>
                                        </tr>
                                        <tr>
                                            <td><?php echo _("Account name:"); ?></td>
                                            <td><input type="text" name="BE_accountname" class="width-form" size="40" value="<?php echo $bankAccountEntry->BE_accountname; ?>" disabled="disabled" /></td>
                                        </tr>
                                        <tr>
                                            <td><?php echo _("Account number:"); ?></td>
                                            <td><input type="text" name="BE_accountnumber" class="width-form" size="40" value="<?php echo $bankAccountEntry->BE_accountnumber; ?>" disabled="disabled" /></td>
                                        </tr>
                                        <tr>
                                            <td><?php echo _("Bank registration number:"); ?></td>
                                            <td><input type="text" name="BE_banknumber" class="width-form" size="40" value="<?php echo $bankAccountEntry->BE_banknumber; ?>" disabled="disabled" /></td>
                                        </tr>
                                        <tr>
                                            <td><?php echo _("Variable symbol:"); ?></td>
                                            <td><input type="text" name="BE_variablesymbol" class="width-form" size="40" value="<?php echo $bankAccountEntry->BE_variablesymbol; ?>" disabled="disabled" /></td>
                                        </tr>
                                        <tr>
                                            <td><?php echo _("Constant symbol:"); ?></td>
                                            <td><input type="text" name="BE_constantsymbol" class="width-form" size="40" value="<?php echo $bankAccountEntry->BE_constantsymbol; ?>" disabled="disabled" /></td>
                                        </tr>
                                        <tr>
                                            <td><?php echo _("Specific symbol:"); ?></td>
                                            <td><input type="text" name="BE_specificsymbol" class="width-form" size="40" value="<?php echo $bankAccountEntry->BE_specificsymbol; ?>" disabled="disabled" /></td>
                                        </tr>
                                        <tr>
                                            <td><?php echo _("Amount:"); ?></td>
                                            <td><input type="text" name="BE_amount" class="width-form" size="40" value="<?php echo NumberFormat::formatMoney($bankAccountEntry->BE_amount); ?>" disabled="disabled" /></td>
                                        </tr>
                                        <tr>
                                            <td><?php echo _("Fee:"); ?></td>
                                            <td><input type="text" name="BE_charge" class="width-form" size="40" value="<?php echo NumberFormat::formatMoney($bankAccountEntry->BE_charge); ?>" disabled="disabled" /></td>
                                        </tr>
                                        <tr>
                                            <td><?php echo _("Message:"); ?></td>
                                            <td><input type="text" name="BE_message" class="width-form" size="40" value="<?php echo $bankAccountEntry->BE_message; ?>" disabled="disabled" /></td>
                                        </tr>
                                        <tr>
                                            <td><?php echo _("Type of transaction:"); ?></td>
                                            <td><input type="text" name="BE_typeoftransaction" class="width-form" size="40" value="<?php echo BankAccountEntry::getLocalizedType($bankAccountEntry->BE_typeoftransaction); ?>" disabled="disabled" /></td>
                                        </tr>
                                        </tbody>
                                    </table>
                                    <?php
                                    }
                                    ?>
                                </td>
                                <td width="10">
                                    &nbsp;
                                </td>
                                <td width="360" valign="top">
                                    <table class="adminform">
                                        <thead>
                                        <tr>
                                            <th colspan="2"><?php echo _("Identification of payment"); ?></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td width="150"><?php echo _("Identification:"); ?></td>
                                            <td>
                                                <select name="BE_identifycode" class="width-form">
                                                    <?php
                                                    foreach(array(
                                                                BankAccountEntry::IDENTIFY_UNIDENTIFIED,
                                                                BankAccountEntry::IDENTIFY_INTERNALTRANSACTION,
                                                                BankAccountEntry::IDENTIFY_IGNORE
                                                            ) as $ik) {
                                                        ?>
                                                        <option value="<?php echo $ik; ?>"><?php echo BankAccountEntry::getLocalizedIdentification($ik); ?></option>
                                                        <?php
                                                    }
                                                    ?>
                                                </select>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>

                                    <br/>

                                </td>
                                <td>
                                    &nbsp;
                                </td>
                            </tr>
                        </table>
                        <input type="hidden" name="option" value="com_bankaccount" />
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
	 * uploadBankList for selected BankAccount
	 * @param $bankAccount
	 */
	static function uploadBankLists(&$bankAccount) {
		global $core, $my;
?>
<script language="JavaScript" type="text/javascript">
	function submitbutton(pressbutton) {
		var form = document.adminForm;
		if (pressbutton == 'doUploadBankLists') {
			submitform(pressbutton);
		} else if (pressbutton == 'cancelUploadBankList') {
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
            <td id="toolbar-cancel">
              <a href="javascript:submitbutton('cancelUploadBankList');">
                <span title="<?php echo _("Cancel"); ?>" class="icon-32-cancel"></span>
                <?php echo _("Cancel"); ?>
              </a>
            </td>
          </tr>
          </table>
        </div>

        <div class="header icon-48-bank-account">
          <?php echo _("Upload bank account entry printout"); ?>
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
    <form action="index2.php" method="post" name="adminForm"  enctype="multipart/form-data">
    <input type="hidden" name="MAX_FILE_SIZE" value="2000000" />
    <table class="splitform">
    <tr>
    <td width="360" valign="top">
    <table class="adminform">
    <thead>
    <tr>
      <th class="title" colspan="2"><?php echo _("Bank account");?></th>
    </tr>
    </thead>
    <tbody>
    <tr>
      <td width="150"><?php echo _("Bank name:"); ?></td>
      <td width="205"><?php echo $bankAccount->BA_bankname; ?></td>
    </tr>
    <tr>
      <td><?php echo _("Account name:"); ?></td>
      <td><?php echo $bankAccount->BA_accountname; ?></td>
    </tr>
    <tr>
      <td><?php echo _("Account number:"); ?></td>
      <td><?php echo $bankAccount->BA_accountnumber . '/' . $bankAccount->BA_banknumber; ?></td>
    </tr>
    <tr>
      <td><?php echo _("IBAN:"); ?></td>
      <td><?php echo $bankAccount->BA_iban; ?></td>
    </tr>
    <tr>
      <td><?php echo _("Currency:"); ?></td>
      <td><?php echo $bankAccount->BA_currency; ?></td>
    </tr>
    <tr>
      <td width="20%" class="title"><input type="file" name="banklistFile" /></td>
      <td width="20%" class="title"><input type="submit" value="Nahrát výpis" onclick="submitbutton('doUploadBankLists');" /></td>
    </tr>
    </tbody>
    </table>
    <td>
      &nbsp;
    </td>
    </tr>
    </table>
    
    <input type="hidden" name="option" value="com_bankaccount" />
    <input type="hidden" name="BA_bankaccountid" value="<?php echo $bankAccount->BA_bankaccountid; ?>" />
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="boxchecked" value="0" />
    <input type="hidden" name="hidemainmenu" value="1" />
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