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
 * HTML_myprofile
 */
class HTML_myprofile {
	/**
	 * editPerson
	 * @param $person
	 * @param $groups
	 * @param $hasRoles
	 * @param $hasIps
	 * @param $roles
	 * @param $charges
	 * @param $hasCharges
	 * @param $internets
	 */
	static function showMyProfile($person, $personAccount, $bankAccountEntries, $personAccountEntries, $charges, $internets, $hasCharges, $group, $roles, $ips, $networks, $messages, $traffic) {
		global $core;
		$allowFirmRegistration = $core->getProperty(Core::ALLOW_FIRM_REGISTRATION);
		$enableVatPayerSpecifics = $core->getProperty(Core::ENABLE_VAT_PAYER_SPECIFICS);
		$chargesTableColspan = ($enableVatPayerSpecifics) ? 11 : 9;
		$enableInvoiceModule = $core->getProperty(Core::ENABLE_INVOICE_MODULE);
		$birthDate = new DateUtil($person->PE_birthdate);
		$registerDate = new DateUtil($person->PE_registerdate);
?>
<script type="text/javascript" language="javascript">
	document.write(getCalendarStyles());
	
	function edit() {
    	hideMainMenu();
   		submitform('edit');
	}
	var cal1x = new CalendarPopup("caldiv");
	cal1x.setDisplayType("month");
	cal1x.setMonthNames("Leden","Únor","Březen","Duben","Květen","Červen","Červenec","Srpen","Září","Říjen","Listopad","Prosinec");
	cal1x.setMonthAbbreviations("Led","Úno","Bře","Dub","Kvě","Čer","Čer","Srp","Zář","Říj","Lis","Pro");
	cal1x.setReturnMonthFunction("myMonthReturn1x");
	cal1x.showYearNavigation(true);
	cal1x.offsetX = 0;
	cal1x.offsetY = 16;
	function myMonthReturn1x(y,m) {
		if (m < 10) m = '0' + m;
		document.adminForm.date_month.value=m+"/"+y;
		document.getElementById('date_monthx').value = document.adminForm.date_month.value;
  		document.adminForm.submit();
	}

	<?php if ($core->getProperty(Core::ENABLE_INVOICE_MODULE)) { ?>
	function generateInvoice(id) {
		window.open("download.php?option=com_myprofile&task=invoice&IN_invoiceid="+id,'_blank','');
	}
	<?php } ?>
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
            <td id="toolbar-edit">
              <a href="javascript:edit();">
                <span title="<?php echo _("Edit"); ?>" class="icon-32-edit"></span>
                <?php echo _("Edit"); ?>
              </a>
            </td>
            
            <td id="toolbar-logout">
              <a href="index2.php?option=logout">
                <span title="<?php echo _("Logout"); ?>" class="icon-32-logout"></span>
                <?php echo _("Logout"); ?>
              </a>
            </td>
          </tr>
          </table>
        </div>
        
        <div class="header icon-48-my-profile">
          <?php echo _("My profile"); ?>
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
      <td width="365" valign="top">
        <table class="adminform">
        <thead>
        <tr>
          <th colspan="2"><?php echo _("Personal data"); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td width="150"><?php echo _("Status:"); ?></td>
          <td width="205"><?php echo Person::getLocalizedStatus($person->PE_status); ?></td>
        </tr>
        <tr>
          <td><?php echo _("User group:"); ?></td>
          <td><?php echo $group->GR_name; ?></td>
        </tr>
		<?php if ($allowFirmRegistration) { ?>
        <tr>
            <td><?php echo _("IČ:"); ?></td>
            <td><?php echo $person->PE_ic; ?></td>
        </tr>
        <tr>
            <td><?php echo _("DIČ:"); ?></td>
            <td><?php echo $person->PE_dic; ?></td>
        </tr>
        <tr>
            <td><?php echo _("Company short name:"); ?></td>
            <td><?php echo $person->PE_shortcompanyname; ?></td>
        </tr>
        <tr>
            <td><?php echo _("Company name:"); ?></td>
            <td><?php echo $person->PE_companyname; ?></td>
        </tr>
		<?php } ?>
        <tr>
          <td><?php echo _("Firstname:"); ?></td>
          <td><?php echo $person->PE_firstname; ?></td>
        </tr>
        <tr>
          <td><?php echo _("Surname:"); ?></td>
          <td><?php echo $person->PE_surname; ?></td>
        </tr>
        <tr>
          <td><?php echo _("Nickname:"); ?></td>
          <td><?php echo $person->PE_nick; ?></td>
        </tr>
        <tr>
          <td><?php echo _("Sex:"); ?></td>
          <td><?php echo $person->PE_gender; ?></td>
        </tr>
        <tr>
          <td><?php echo _("Degree before name:"); ?></td>
          <td><?php echo $person->PE_degree_prefix; ?></td>
        </tr>
        <tr>
          <td><?php echo _("Degree after name:"); ?></td>
          <td><?php echo $person->PE_degree_suffix; ?></td>
        </tr>
        <tr>
          <td><?php echo _("Birthdate:"); ?></td>
          <td><?php echo $birthDate->getFormattedDate(DateUtil::FORMAT_DATE); ?></td>
        </tr>
        <tr>
          <td><?php echo _("E-mail"); ?></td>
          <td><?php echo $person->PE_email; ?></td>
        </tr>
        <tr>
          <td><?php echo _("ICQ:"); ?></td>
          <td><?php echo $person->PE_icq; ?></td>
        </tr>
        <tr>
          <td><?php echo _("Phone:"); ?></td>
          <td><?php echo $person->PE_tel; ?></td>
        </tr>
        <tr>
          <td><?php echo _("Address:"); ?></td>
          <td><?php echo $person->PE_address; ?></td>
        </tr>
        <tr>
          <td><?php echo _("City:"); ?></td>
          <td><?php echo $person->PE_city; ?></td>
        </tr>
        <tr>
          <td><?php echo _("ZIP:"); ?></td>
          <td><?php echo $person->PE_zip; ?></td>
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
        <tr>
          <td><?php echo _("Date of registration:"); ?></td>
          <td><?php echo $registerDate->getFormattedDate(DateUtil::FORMAT_FULL); ?></td>
        </tr>
        </tbody>
        </table>
      </td>
      <td width="10">
        &nbsp;
      </td>
      <td valign="top">
      <div class="tab-page" id="modules-cpanel-myperson">
	  <script language="JavaScript" type="text/javascript">var tabPanePerson1 = new WebFXTabPane(document.getElementById("modules-cpanel-myperson"), 1);</script>
	  <div class="tab-page" id="module01"><h2 class="tab"><?php echo _("Login creditials"); ?></h2>
        <script language="JavaScript" type="text/javascript">tabPanePerson1.addTabPage(document.getElementById("module01"));</script>
        <table class="adminform">
        <thead>
        <tr>
          <th colspan="2"><?php echo _("System login registration"); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td width="150"><?php echo _("Login name:"); ?></td>
          <td><?php echo $person->PE_username; ?></td>
        </tr>
        <tr>
          <td><?php echo _("Password:"); ?></td>
          <td><?php echo ($person->PE_password) ? "******" : ""; ?></td>
        </tr>
        </tbody>
        </table>
      </div>
      <div class="tab-page" id="module02"><h2 class="tab"><?php echo _("Role"); ?></h2>
        <script language="JavaScript" type="text/javascript">tabPanePerson1.addTabPage(document.getElementById("module02"));</script>
        <table class="adminform">
        <thead>
        <tr>
          <th colspan="2"><?php echo _("User role"); ?></th>
        </tr>
        </thead>
        <tbody>
<?php
	$k = 0;
	foreach($roles as $role) { ?>
        <tr class="<?php echo "row$k"; ?>">
          <td align="left">
            <?php echo $role->RO_name; ?>
          </td>
		</tr>
<?php
		$k = 1 - $k;
	} ?>
        </tbody>
        </table>
      </div>
      <div class="tab-page" id="module03"><h2 class="tab"><?php echo _("Payments"); ?></h2>
        <script language="JavaScript" type="text/javascript">tabPanePerson1.addTabPage(document.getElementById("module03"));</script>
      <table class="adminlist">
      <thead>
      <tr>
        <th width="2%" class="title">#</th>
        <th width="17%" class="title"><?php echo _("Service name"); ?></th>
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
        <th width="9%" class="title" colspan="2"><?php echo _("Actual status"); ?></th>
      </tr>
      </thead>
      <tbody>
<?php
	$i1 = 0;
	foreach ($hasCharges as $hasCharge) {
		$dateStart = new DateUtil($hasCharge->HC_datestart);
		$dateEnd = new DateUtil($hasCharge->HC_dateend);
		
		if ($charges[$hasCharge->HC_chargeid]->CH_period == Charge::PERIOD_ONCE) {
			$till = "-";
		} else if ($dateEnd->getTime() == null) {
			$till = _("Not limited");
		} else {
			$till = $dateEnd->getFormattedDate(DateUtil::FORMAT_DATE);
		}
		
		if ($hasCharge->HC_actualstate == HasCharge::ACTUALSTATE_ENABLED) {
			$imageActualState = "images/16x16/actions/agt_action_success.png";
			$altActualState = _("Enabled");
		} else if ($hasCharge->HC_actualstate == Hascharge::ACTUALSTATE_DISABLED) {
			$imageActualState = "images/16x16/actions/agt_stop.png";
			$altActualState = _("Disabled");
		}
?>
      <tr class="row0">
        <td align="left">
          <?php echo $i1+1; ?>
        </td>
        <td align="left">
          <?php echo $charges[$hasCharge->HC_chargeid]->CH_name; ?>
        </td>
        <?php if ($enableVatPayerSpecifics) { ?>
        <td align="left">
          <?php echo NumberFormat::formatMoney($charges[$hasCharge->HC_chargeid]->CH_baseamount); ?>
        </td>
        <td align="left">
          <?php echo NumberFormat::formatMoney($charges[$hasCharge->HC_chargeid]->CH_vat); ?>
        </td>
        <?php } ?>
        <td align="left">
          <?php echo NumberFormat::formatMoney($charges[$hasCharge->HC_chargeid]->CH_amount); ?>
        </td>
        <td align="left">
          <?php echo $charges[$hasCharge->HC_chargeid]->CH_currency; ?>
        </td>
        <td align="left">
          <?php echo Charge::getLocalizedPeriod($charges[$hasCharge->HC_chargeid]->CH_period); ?>
        </td>
        <td align="left">
          <?php echo $dateStart->getFormattedDate(DateUtil::FORMAT_DATE); ?>
        </td>
        <td align="left">
          <?php echo $till; ?>
        </td>
        <td align="left">
          <?php echo Hascharge::getLocalizedStatus($hasCharge->HC_status); ?>
        </td>
        <td align="left">
          <?php echo Hascharge::getLocalizedActualState($hasCharge->HC_actualstate); ?>
        </td>
        <td align="left">
          <img src="<?php echo $imageActualState; ?>" alt="<?php echo $altActualState; ?>" align="middle" border="0"/>
        </td>
      </tr>
      <tr>
        <td>
        </td>
        <td colspan="<?php echo $chargesTableColspan; ?>">
          <table class="adminlist">
          <thead>
          <tr>
            <th width="2%" class="title">#</th>
            <th width="8%" class="title"><?php echo _("Date/period"); ?></th>
            <th width="8%" class="title"><?php echo _("Date of payment"); ?></th>
            <th width="8%" class="title"><?php echo _("Tolerance till"); ?></th>
            <th width="7%" class="title"><?php echo _("Write off date"); ?></th>
            <th width="7%" class="title"><?php echo _("Delay in days"); ?></th>
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
            <td align="left">
              <?php echo $i2+1; ?>
            </td>
            <td align="left">
<?php
	switch ($charges[$hasCharge->HC_chargeid]->CH_period) {
		case Charge::PERIOD_ONCE:
			$format = DateUtil::FORMAT_DATE;
			break;
		case Charge::PERIOD_MONTHLY:
			$format = DateUtil::FORMAT_MONTHLY;
			break;
		case Charge::PERIOD_QUARTERLY:
			$format = DateUtil::FORMAT_QUARTERLY;
			break;
		case Charge::PERIOD_HALFYEARLY:
			$format = DateUtil::FORMAT_HALFYEARLY;
			break;
		case Charge::PERIOD_YEARLY:
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
            <td align="left">
              <?php echo $writeOffDate->getFormattedDate(DateUtil::FORMAT_DATE); ?>
            </td>
            <td align="left">
              <?php echo $toleranceDate->getFormattedDate(DateUtil::FORMAT_DATE); ?>
            </td>
            <td align="left">
              <?php echo $realizedDate->getFormattedDate(DateUtil::FORMAT_DATE); ?>
            </td>
            <td align="left">
              <?php echo $chargeEntry->CE_overdue; ?>
            </td>
            <td align="left">
              <?php echo ChargeEntry::getLocalizedStatus($chargeEntry->CE_status); ?>
            </td>
            <?php if ($enableVatPayerSpecifics) { ?>
            <td align="left">
                <?php echo NumberFormat::formatMoney($chargeEntry->CE_baseamount); ?>
            </td>
            <td align="left">
                <?php echo NumberFormat::formatMoney($chargeEntry->CE_vat); ?>
            </td>
            <?php } ?>
            <td align="left">
              <?php echo NumberFormat::formatMoney($chargeEntry->CE_amount); ?>
            </td>
            <td align="left">
              <?php echo $chargeEntry->CE_currency; ?>
            </td>
            <td align="left">
<?php
	if ($enableInvoiceModule && $chargeEntry->_invoice) {
		
?>
              <a href="javascript:generateInvoice(<?php echo $chargeEntry->_invoice->IN_invoiceid; ?>)"><?php echo _("Invoice"); ?></a>
<?php
	} else {
		
?>
              N/A
<?php
	}
?>
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
      </tbody>
      </table>
      </div>
      <div class="tab-page" id="module04"><h2 class="tab"><?php echo _("Incoming payments"); ?></h2>
        <script language="JavaScript" type="text/javascript">tabPanePerson1.addTabPage(document.getElementById("module04"));</script>
    <table class="adminlist">
    <thead>
    <tr>
      <th width="2%" class="title">#</th>
      <th width="10%" class="title"><?php echo _("Date"); ?></th>
      <th width="20%" class="title"><?php echo _("Source"); ?></th>
      <th width="10%" class="title"><?php echo _("Amount"); ?></th>
      <th width="10%" class="title"><?php echo _("Currency"); ?></th>
      <th width="28%" class="title"><?php echo _("Comment"); ?></th>
      <th width="10%" class="title"><?php echo _("Account name"); ?></th>
      <th width="10%" class="title"><?php echo _("Account number"); ?></th>
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
      <td align="left">
        <?php echo $i+1; ?>
      </td>
      <td align="left">
        <?php echo $date->getFormattedDate(DateUtil::FORMAT_DATE); ?>
      </td>
      <td align="left">
        <?php echo PersonAccountEntry::getLocalizedSource($personAccountEntry->PN_source); ?>
      </td>
      <td align="left">
        <?php echo NumberFormat::formatMoney($personAccountEntry->PN_amount); ?>
      </td>
      <td align="left">
        <?php echo NumberFormat::formatMoney($personAccountEntry->PN_currency); ?>
      </td>
      <td align="left">
        <?php echo $personAccountEntry->PN_comment; ?>
      </td>
      <td align="left">
<?php
	if ($personAccountEntry->PN_source == PersonAccountEntry::SOURCE_BANKACCOUNT) {
		echo $bankAccountEntries[$personAccountEntry->PN_bankaccountentryid]->BE_accountname;
	} else {
		echo "n/a";
	}
?>
      </td>
      <td align="left">
<?php
	if ($personAccountEntry->PN_source == PersonAccountEntry::SOURCE_BANKACCOUNT) {
		echo $bankAccountEntries[$personAccountEntry->PN_bankaccountentryid]->BE_accountnumber . "/" . $bankAccountEntries[$personAccountEntry->PN_bankaccountentryid]->BE_banknumber;
	} else {
		echo "n/a";
	}
?>
      </td>
    </tr>
<?php
		$k = 1 - $k;
		$i++;
	}
?>
    </tbody>
    </table>
      </div>
      <div class="tab-page" id="module05"><h2 class="tab"><?php echo _("IP addresses"); ?></h2>
        <script language="JavaScript" type="text/javascript">tabPanePerson1.addTabPage(document.getElementById("module05"));</script>
        <table class="adminlist">
        <thead>
        <tr>
          <th class="title" width="150"><?php echo _("IP address"); ?></th>
          <th class="title"><?php echo _("DNS name"); ?></th>
        </tr>
        </thead>
        <tbody>
<?php
	$k = 0;
	foreach($ips as $ip) { ?>
		<tr class="<?php echo "row$k"; ?>">
          <td align="left"><?php echo $ip->IP_address; ?></td>
          <td align="left"><?php echo $ip->IP_dns; ?></td>
		</tr>
<?php
		$k = 1 - $k;
	}
?>
        </tbody>
        </table>
      </div>
      <div class="tab-page" id="module06"><h2 class="tab"><?php echo _("Networks"); ?></h2>
        <script type="text/javascript">tabPanePerson1.addTabPage(document.getElementById("module06"));</script>
        <table class="adminlist">
        <thead>
        <tr>
          <th class="title" width="150"><?php echo _("Network address"); ?></th>
          <th class="title"><?php echo _("Description"); ?></th>
        </tr>
        </thead>
        <tbody>
<?php
	$k = 0;
	foreach($networks as $network) { ?>
		<tr class="<?php echo "row$k"; ?>">
          <td align="left"><?php echo $network->NE_net; ?></td>
          <td align="left"><?php echo $network->NE_description; ?></td>
		</tr>
<?php
		$k = 1 - $k;
	}
?>
        </tbody>
        </table>
      </div>
      <div class="tab-page" id="module07"><h2 class="tab"><?php echo _("Messages"); ?></h2>
        <script language="JavaScript" type="text/javascript">tabPanePerson1.addTabPage(document.getElementById("module07"));</script>
      <table class="adminlist">
      <thead>
      <tr>
       <th width="2%" class="title">#</th>
       <th width="10%" class="title"><?php echo _("Date"); ?></th>
       <th width="15%" class="title"><?php echo _("Subject"); ?></th>
       <th width="56%" class="title"><?php echo _("Message"); ?></th>
       <th width="10%" class="title"><?php echo _("Status"); ?></th>
     </tr>
     </thead>
     <tbody>
<?php
	$k = 0;
	$i = 0;
	foreach ($messages as &$message) {
		$dateTime = new DateUtil($message->ME_datetime);
?>
     <tr class="<?php echo "row$k"; ?>">
       <td align="left">
         <?php echo $i+1; ?>
       </td>
       <td align="left"><?php echo $dateTime->getFormattedDate(DateUtil::FORMAT_FULL); ?>
       </td>
       <td align="left">
         <?php echo $message->ME_subject; ?>
       </td>
       <td align="left">
         <?php echo $message->ME_body; ?>
       </td>
       <td align="left">
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
      </div>
      <div class="tab-page" id="module08"><h2 class="tab"><?php echo _("Data traffic"); ?></h2>
        <script language="JavaScript" type="text/javascript">tabPanePerson1.addTabPage(document.getElementById("module08"));</script>
      <table class="adminlist">
      <thead>
      <tr>
       <th width="20%" class="title"><?php echo _("Month period:"); ?>
         <input type="hidden" name="filter[date_month]" id="date_monthx" value="<?php echo $traffic['DATE_MONTH']->getFormattedDate(DateUtil::FORMAT_MONTHLY); ?>" />
         <input type="text" name="date_month" value="<?php echo $traffic['DATE_MONTH']->getFormattedDate(DateUtil::FORMAT_MONTHLY); ?>" class="width-form-button" style="width: 60px;" size="35" maxlength="10" />
         <a href="#" onclick="cal1x.showCalendar('anchor1x'); return false;" name="anchor1x" id="anchor1x"><img src="images/22x22/apps/calendar.png" style="width: 16px; height: 16px; vertical-align: middle; position: relative; top: -2px; cursor: pointer;" alt="<?php echo _("Calendar"); ?>" /></a>
       </th>
       <th width="10%" class="title"><?php echo _("IP address"); ?></th>
       <th width="15%" class="title"><?php echo _("Data download"); ?></th>
       <th class="title"><?php echo _("Data upload"); ?></th>
     </tr>
     </thead>
     <tbody>
<?php
	$k = 0;
	$i = 0;
	foreach ($traffic['TRAFFIC_MONTH'] as &$ip) {
?>
     <tr class="<?php echo "row$k"; ?>">
       <td>
       </td>
       <td align="left">
         <?php echo $ip['IP']; ?>
       </td>
       <td align="left">
         <?php echo NumberFormat::formatMB($ip['DATA_IN']); ?>
       </td>
       <td align="left">
         <?php echo NumberFormat::formatMB($ip['DATA_OUT']); ?>
       </td>
     </tr>
<?php
		$k = 1 - $k;
		$i++;
	}
    $ip = $traffic['SUMMARY'];
?>
     <tr class="<?php echo "row$k"; ?>">
       <td align="left" colspan="2">
         <?php echo _('Summary'); ?>
       </td>
       <td align="left">
         <?php echo NumberFormat::formatMB($ip['DATA_IN']); ?>
       </td>
       <td align="left">
         <?php echo NumberFormat::formatMB($ip['DATA_OUT']); ?>
       </td>
     </tr>
     </tbody>
     </table>
      </div>
      </div>
        </td>
        </tr>
        </table>
    <input type="hidden" name="option" value="com_myprofile" />
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
	static function editMyProfile($person) {
		global $core;
		$allowFirmRegistration = $core->getProperty(Core::ALLOW_FIRM_REGISTRATION);
		$birthDate = new DateUtil($person->PE_birthdate);
		$registerDate = new DateUtil($person->PE_registerdate);
?>
<script type="text/javascript" language="JavaScript">
	document.write(getCalendarStyles());
	
	var cal1x = new CalendarPopup("caldiv");
	cal1x.setMonthNames("Leden","Únor","Březen","Duben","Květen","Červen","Červenec","Srpen","Září","Říjen","Listopad","Prosinec");
	cal1x.showYearNavigation(true);
	cal1x.setDayHeaders("N","P","Ú","S","Č","P","S");
	cal1x.setWeekStartDay(1);
	cal1x.setTodayText("Dnes");
	cal1x.offsetX = 0;
	cal1x.offsetY = 16;
	
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

        <div class="header icon-48-my-profile">
          <?php echo _("My profile"); ?>: <small><?php echo _("Edit"); ?></small>
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
      <td width="365" valign="top">
        <table class="adminform">
        <thead>
        <tr>
          <th colspan="2"><?php echo _("Personal data"); ?></th>
        </tr>
        </thead>
        <tbody>
		<?php if ($allowFirmRegistration) { ?>
        <tr>
            <td><?php echo _("IČ:"); ?></td>
            <td><input type="text" name="PE_ic" class="width-form" size="40" value="<?php echo $person->PE_ic; ?>" maxlength="255" /></td>
        </tr>
        <tr>
            <td><?php echo _("DIČ:"); ?></td>
            <td><input type="text" name="PE_dic" class="width-form" size="40" value="<?php echo $person->PE_dic; ?>" maxlength="255" /></td>
        </tr>
        <tr>
            <td><?php echo _("Company short name:"); ?></td>
            <td><input type="text" name="PE_shortcompanyname" class="width-form" size="40" value="<?php echo $person->PE_shortcompanyname; ?>" maxlength="255" /></td>
        </tr>
        <tr>
            <td><?php echo _("Company name:"); ?></td>
            <td><input type="text" name="PE_companyname" class="width-form" size="40" value="<?php echo $person->PE_companyname; ?>" maxlength="255" /></td>
        </tr>
		<?php } ?>
        <tr>
          <td width="150"><?php echo _("Firstname:"); ?></td>
          <td width="205"><input type="text" name="PE_firstname" class="width-form" size="40" value="<?php echo $person->PE_firstname; ?>" maxlength="255" /></td>
        </tr>
        <tr>
          <td><?php echo _("Surname:"); ?></td>
          <td><input type="text" name="PE_surname" class="width-form" size="40" value="<?php echo $person->PE_surname; ?>" maxlength="255" /></td>
        </tr>
        <tr>
          <td><?php echo _("Nickname:"); ?></td>
          <td><input type="text" name="PE_nick" class="width-form" size="40" value="<?php echo $person->PE_nick; ?>" maxlength="255" /></td>
        </tr>
        <tr>
          <td><?php echo _("Sex:"); ?></td>
          <td>
            <select name="PE_gender" class="width-form">
              <option value="muž" <?php if ($person->PE_gender == "muž") echo 'selected="selected"'?>>muž</option>
              <option value="žena" <?php if ($person->PE_gender == "žena") echo 'selected="selected"'?>>žena</option>
            </select></td>
        </tr>
        <tr>
          <td><?php echo _("Degree before name:"); ?></td>
          <td><input type="text" name="PE_degree_prefix" class="width-form" size="40" value="<?php echo $person->PE_degree_prefix; ?>" maxlength="20" /></td>
        </tr>
        <tr>
          <td><?php echo _("Degree after name:"); ?></td>
          <td><input type="text" name="PE_degree_suffix" class="width-form" size="40" value="<?php echo $person->PE_degree_suffix; ?>" maxlength="20" /></td>
        </tr>
        <tr>
          <td><?php echo _("Birthdate:"); ?></td>
          <td>
            <input type="text" name="PE_birthdate" class="width-form-button" value="<?php echo $birthDate->getFormattedDate(DateUtil::FORMAT_DATE); ?>" size="35" maxlength="10" />
            <a href="#" onclick="cal1x.select(document.adminForm.PE_birthdate,'anchor1x','dd.MM.yyyy'); return false;" name="anchor1x" id="anchor1x"><img src="images/22x22/apps/calendar.png" style="width: 16px; height: 16px; vertical-align: middle; position: relative; top: -2px; cursor: pointer;" alt="<?php echo _("Calendar"); ?>" /></a>
          </td>
        </tr>
        <tr>
          <td><?php echo _("E-mail"); ?></td>
          <td><input type="text" name="PE_email" class="width-form" size="40" value="<?php echo $person->PE_email; ?>" maxlength="255" /></td>
        </tr>
        <tr>
          <td><?php echo _("ICQ:"); ?></td>
          <td><input type="text" name="PE_icq" class="width-form" size="40" value="<?php echo $person->PE_icq; ?>" maxlength="50" /></td>
        </tr>
        <tr>
          <td><?php echo _("Phone:"); ?></td>
          <td><input type="text" name="PE_tel" class="width-form" size="40" value="<?php echo $person->PE_tel; ?>" maxlength="50" /></td>
        </tr>
        <tr>
          <td><?php echo _("Address:"); ?></td>
          <td><input type="text" name="PE_address" class="width-form" size="40" value="<?php echo $person->PE_address; ?>" maxlength="255" /></td>
        </tr>
        <tr>
          <td><?php echo _("City:"); ?></td>
          <td><input type="text" name="PE_city" class="width-form" size="40" value="<?php echo $person->PE_city; ?>" maxlength="255" /></td>
        </tr>
        <tr>
          <td><?php echo _("ZIP:"); ?></td>
          <td><input type="text" name="PE_zip" class="width-form" size="40" value="<?php echo $person->PE_zip; ?>" maxlength="255" /></td>
        </tr>
        </tbody>
        </table>
      </td>
      <td width="10">
        &nbsp;
      </td>
      <td valign="top">
    <div class="tab-page" id="modules-cpanel-editmyprofile">
      <script language="JavaScript" type="text/javascript">var tabPanePerson1 = new WebFXTabPane(document.getElementById("modules-cpanel-editmyprofile"), 1);</script>
      <div class="tab-page" id="module01"><h2 class="tab"><?php echo _("Login creditials"); ?></h2>
        <script language="JavaScript" type="text/javascript">tabPanePerson1.addTabPage(document.getElementById("module01"));</script>
        <table class="adminform">
        <thead>
        <tr>
          <th colspan="2"><?php echo _("System login registration"); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td width="150"><?php echo _("Login name:"); ?></td>
          <td><input type="text" name="void" class="width-form" disabled="disabled" size="20" value="<?php echo $person->PE_username; ?>" /></td>
        </tr>
        <tr>
          <td><?php echo _("Password:"); ?></td>
          <td><input type="password" name="PE_password1" class="width-form" size="20" value="" maxlength="255" /></td>
        </tr>
        <tr>
          <td><?php echo _("Password confirmation:"); ?></td>
          <td><input type="password" name="PE_password2" class="width-form" size="20" value="" maxlength="255" /></td>
        </tr>
        </tbody>
        </table>
      </div>
      </div>
      </td>
    </tr>
    </table>
    <input type="hidden" name="PE_personid" value="<?php echo $person->PE_personid; ?>" />
    <input type="hidden" name="option" value="com_myprofile" />
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
	formValidator.addValidation("PE_firstname","required","<?php echo _("Please enter firstname"); ?>");
	formValidator.addValidation("PE_surname","required","<?php echo _("Please enter surname"); ?>");
	formValidator.addValidation("PE_birthdate","date=dd.MM.yyyy","<?php echo _("Birthdate is in incorrect format"); ?>");
	formValidator.addValidation("PE_email","email","<?php echo _("Please enter valid E-Mail"); ?>");
	formValidator.setAddnlValidationFunction(passwordMatchValidator);
	formValidator.addValidation("PE_password1","minlength=6","<?php echo _("Password is too short"); ?>");
	
	function passwordMatchValidator() {
		var form = document.adminForm;
		
		if (form.PE_password1.value && form.PE_password1.value != form.PE_password2.value) {
			alert("<?php echo _("Passwords doesn't match"); ?>");
			return false;
		} else {
			return true;
		}
	}
</script>
<?php
	}
} // End of HTML_person class
?>