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
 * HTML_charge
 */
class HTML_charge {
	/**
	 * showCharges
	 * @param $charges
	 * @param $pageNav
	 */
	static function showCharges(&$charges, &$internets, &$pageNav) {
		global $core;
		$enableVatPayerSpecifics = $core->getProperty(Core::ENABLE_VAT_PAYER_SPECIFICS);
		$chargesTableColspan = ($enableVatPayerSpecifics) ? 12 : 10;
?>
<script language="JavaScript" type="text/javascript">
	function edit(id) {
    	var form = document.adminForm;
    	form.CH_chargeid.value = id;
    	hideMainMenu();
   		submitform('edit');
	}
	function newCH() {
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
			var confirm = window.confirm('<?php echo _("Do you really want to delete selected records ?"); ?>');
			if (confirm) {
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
              <a href="javascript:newCH();">
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
        
        <div class="header icon-48-payment-teplate">
          <?php echo _("Payment templates management"); ?>
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
      <th width="20%" class="title"><?php echo _("Template name"); ?></th>
      <th width="7%" class="title"><?php echo _("Write off"); ?></th>
      <?php if ($enableVatPayerSpecifics) { ?>
      <th width="7%" class="title"><?php echo _("Amount without VAT"); ?></th>
      <th width="7%" class="title"><?php echo _("VAT (%)"); ?></th>
      <th width="7%" class="title"><?php echo _("Amount with VAT"); ?></th>
      <?php } else { ?>
      <th width="7%" class="title"><?php echo _("Amount"); ?></th>
      <?php } ?>
      <th width="5%" class="title"><?php echo _("Currency"); ?></th>
      <th width="7%" class="title"><?php echo _("Tolerance"); ?></th>
      <th width="15%" class="title"><?php echo _("Type"); ?></th>
      <th width="5%" class="title"><?php echo _("Priority"); ?></th>
      <th class="title"><?php echo _("Description"); ?></th>
    </tr>
    </thead>
    <tfoot>
    <tr>
      <td colspan="<?php echo $chargesTableColspan; ?>">
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
	foreach ($charges as $charge) {
		$link = "javascript:edit('$charge->CH_chargeid');";
?>
    <tr class="<?php echo "row$k"; ?>">
      <td>
        <?php echo $pageNav->rowNumber($i); ?>
      </td>
      <td>
         <input type="checkbox" id="<?php echo "cb$i"; ?>" name="cid[]" value="<?php echo $charge->CH_chargeid; ?>" onclick="isChecked(this.checked);" />
      </td>
      <td>
        <a href="<?php echo $link; ?>"><?php echo $charge->CH_name; ?></a>
      </td>
      <td>
        <?php echo Charge::getLocalizedPeriod($charge->CH_period); ?>
      </td>
      <?php if ($enableVatPayerSpecifics) { ?>
      <td>
        <?php echo $charge->CH_baseamount; ?>
      </td>
      <td>
        <?php echo $charge->CH_vat; ?>
      </td>
      <?php } ?>
      <td>
        <?php echo $charge->CH_amount; ?>
      </td>
      <td>
        <?php echo $charge->CH_currency; ?>
      </td>
      <td>
        <?php printf(ngettext("%s day", "%s days", $charge->CH_tolerance), $charge->CH_tolerance); ?>
      </td>
      <td
<?php
	if ($charge->CH_type == Charge::TYPE_INTERNET_PAYMENT) {
		$internet = $internets[$charge->CH_internetid];
		echo "onmouseover=\"return overlib('<strong>"._("Internet service name:")."</strong> $internet->IN_name<br /><strong>"._("Description:")."</strong> $internet->IN_description<br /><strong>"._("Maximum download:")."</strong> $internet->IN_dnl_ceil<br /><strong>"._("Maximum upload:")."</strong> $internet->IN_upl_ceil');\" onmouseout=\"return nd();\"";
	}
?>
      >
       <?php echo Charge::getLocalizedType($charge->CH_type); ?>
      </td>
      <td>
        <?php echo $charge->CH_priority; ?>
      </td>
      <td>
        <?php echo $charge->CH_description; ?>
      </td>
    </tr>
<?php
		$k = 1 - $k;
		$i++;
	}
?>
    </tbody>
    </table>
    <input type="hidden" name="option" value="com_charge" />
    <input type="hidden" name="CH_chargeid" value="" />
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
	 * editCharge
	 * @param $charge
	 * @param $tolerance
	 * @param $internets
	 */
	static function editCharge(&$charge, &$toleranceArray, &$internets) {
		global $core;
		$enableVatPayerSpecifics = $core->getProperty(Core::ENABLE_VAT_PAYER_SPECIFICS);
?>
<script language="javascript" type="text/javascript">
	var IN_name = new Array();
	var IN_description = new Array();
	var IN_dnl_rate = new Array();
	var IN_dnl_ceil = new Array();
	var IN_upl_rate = new Array();
	var IN_upl_ceil = new Array();
	var IN_prio = new Array();
<?php
	foreach ($internets as $k => $internet) {
		echo "IN_name[$k]='$internet->IN_name';\n";
	echo "IN_description[$k]='$internet->IN_description';\n";
	echo ($internet->IN_dnl_rate == -1) ? "IN_dnl_rate[$k]='AUTO';" : "IN_dnl_rate[$k]='$internet->IN_dnl_rate';\n";
	echo "IN_dnl_ceil[$k]='$internet->IN_dnl_ceil';\n";
	echo ($internet->IN_upl_rate == -1) ? "IN_upl_rate[$k]='AUTO';" : "IN_dnl_rate[$k]='$internet->IN_upl_rate';\n";
	echo "IN_upl_ceil[$k]='$internet->IN_upl_ceil';\n";
	echo "IN_prio[$k]='$internet->IN_prio';\n";
	}
?>
	function submitbutton(pressbutton) {
		var form = document.adminForm;
		if (pressbutton == 'cancel') {
			submitform( pressbutton );
			return;
		}
		if (pressbutton == 'apply') {
			hideMainMenu();
		}

		// do field validation
		if (trim(form.CH_name.value) == "") {
			alert("<?php echo _("Please enter template name"); ?>");
		} else if (trim(form.CH_description.value) == "") {
			alert("<?php echo _("Please enter template desctiption"); ?>");
		} else {
			submitform(pressbutton);
		}
	}
	function updateTypeSelect() {
		var select = document.getElementById('typeSelect').value;
		var internetid = <?php echo Charge::TYPE_INTERNET_PAYMENT;?>;
		var el = document.getElementById('internetBlock1');
		var e2 = document.getElementById('internetBlock2');
		var e3 = document.getElementById('internetBlock3');
		if (select == internetid) {
			el.style.display = 'block';
			e2.style.display = 'block';
			e3.style.display = 'block';
			updateInternetSelect();
		} else {
			el.style.display = 'none';
			e2.style.display = 'none';
			e3.style.display = 'none';
		}
	}
	function updateInternetSelect() {
		var form = document.adminForm;
		var i = form.CH_internetid.value;
		form._IN_name.value = IN_name[i];
		form._IN_description.value = IN_description[i];
		form._IN_dnl_rate.value = IN_dnl_rate[i];
		form._IN_dnl_ceil.value = IN_dnl_ceil[i];
		form._IN_upl_rate.value = IN_upl_rate[i];
		form._IN_upl_ceil.value = IN_upl_ceil[i];
		form._IN_prio.value = IN_prio[i];
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

        <div class="header icon-48-group">
          <?php echo _("Payment templates management"); ?>: <small><?php echo _("Edit"); ?></small>
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
          <th colspan="2"><?php echo _("Payment template"); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td width="150"><?php echo _("Template name:"); ?></td>
          <td width="205"><input type="text" name="CH_name" class="width-form" size="40" value="<?php echo $charge->CH_name;?>" /></td>
        </tr>
        <tr>
          <td><?php echo _("Template description:"); ?></td>
          <td><input type="text" name="CH_description" class="width-form" size="40" value="<?php echo $charge->CH_description;?>" /></td>
        </tr>
        <tr>
          <td><?php echo _("Priority:"); ?></td>
          <td>
            <select name="CH_priority" class="width-form" size="1">
<?php
	for ($i = 10; $i >= -10; $i--) {
		echo '<option value="' . $i . '"' ; if ($charge->CH_priority == $i) echo ' selected="selected"';echo ">$i</option>\n";
	}
?>
            </select>
          </td>
        </tr>
        <?php if ($enableVatPayerSpecifics) { ?>
        <tr>
          <td><?php echo _("Amount without VAT:"); ?></td>
          <td><input type="text" name="CH_baseamount" class="width-form" size="40" value="<?php echo NumberFormat::formatMoney($charge->CH_baseamount);?>" /></td>
        </tr>
        <tr>
          <td><?php echo _("VAT (%):"); ?></td>
          <td><input type="text" name="CH_vat" class="width-form" size="40" value="<?php echo NumberFormat::formatMoney($charge->CH_vat);?>" /></td>
        </tr>
        <tr>
          <td><?php echo _("Amount with VAT:"); ?></td>
          <td><input type="text" name="CH_amount" class="width-form" size="40" value="<?php echo NumberFormat::formatMoney($charge->CH_amount);?>" /></td>
        </tr>
        <?php } else { ?>
        <tr>
          <td><?php echo _("Amount:"); ?></td>
          <td><input type="text" name="CH_amount" class="width-form" size="40" value="<?php echo NumberFormat::formatMoney($charge->CH_amount);?>" /></td>
        </tr>
        <?php } ?>
        <tr>
          <td><?php echo _("Currency:"); ?></td>
          <td>
            <select name="CH_currency" class="width-form" size="1">
<?php
	foreach (BankAccount::$CURRENCY_ARRAY as $currency) {
		echo '<option value="' . $currency . '"' ; if ($charge->CH_currency == $currency) echo ' selected="selected"';echo ">$currency</option>\n";
	}
?>
	        </select>
          </td>
        </tr>
        <tr>
          <td><?php echo _("Write off:"); ?></td>
          <td>
            <select name="CH_period" class="width-form" size="1">
<?php
	foreach (Charge::$PERIOD_ARRAY as $pk) {
?>
              <option value="<?php echo $pk; ?>" <?php echo ($charge->CH_period == $pk) ? 'selected="selected"' : ""; ?>><?php echo Charge::getLocalizedPeriod($pk); ?></option>
<?php
	}
?>
	        </select>
          </td>
        </tr>
        <tr>
          <td><?php echo _("Write-off offset:"); ?></td>
          <td>
            <select name="CH_writeoffoffset" class="width-form" size="1">
<?php
	for ($i = - 360; $i < 360; $i++) {
		echo '<option value="' . $i . '"' ; if ($charge->CH_writeoffoffset == $i) echo ' selected="selected"';echo ">$i</option>\n";
	}
?>
	        </select>
          </td>
        </tr>
        <tr>
          <td><?php echo _("Tolerance:"); ?></td>
          <td>
            <select name="CH_tolerance" class="width-form" size="1">
<?php
	foreach ($toleranceArray as $tk => $tolerance) {
		echo '<option value="' . $tk . '"' ; if ($charge->CH_tolerance == $tk) echo ' selected="selected"';echo ">$tolerance</option>\n";
	}
?>
	        </select>
          </td>
        </tr>
        <tr>
          <td><?php echo _("Payment type:"); ?></td>
          <td>
            <select id="typeSelect" name="CH_type" class="width-form" size="1" onchange="updateTypeSelect();">
<?php
	foreach (Charge::$TYPE_ARRAY as $pk) {
?>
              <option value="<?php echo $pk; ?>" <?php echo ($charge->CH_type == $pk) ? 'selected="selected"' : ""; ?>><?php echo Charge::getLocalizedType($pk); ?></option>
<?php
	}
?>
	        </select>
          </td>
        </tr>
        <tr>
          <td><div id="internetBlock1"><?php echo _("Internet:"); ?></div></td>
          <td>
            <div id="internetBlock2">
            <select name="CH_internetid" class="width-form" size="1" onchange="updateInternetSelect();">
<?php
	foreach ($internets as $k => $internet) {
		echo '<option value="' . $internet->IN_internetid . '"' ; if ($charge->CH_internetid == $internet->IN_internetid) echo ' selected="selected"';echo ">$internet->IN_name</option>\n";
	}
?>
	        </select>
	        </div>
          </td>
        </tr>
        </tbody>
        </table>
      </td>
      <td width="10">
        &nbsp;
      </td>
      <td width="360" valign="top">
        <div id="internetBlock3">
          <table class="adminform">
          <thead>
          <tr>
            <th colspan="2"><?php echo _("Internet in payment:"); ?></th>
          </tr>
          </thead>
          <tbody>
          <tr>
            <td width="150"><?php echo _("Internet service name:"); ?></td>
            <td width="205"><input type="text" name="_IN_name" class="width-form" size="40" value="" disabled="disabled" /></td>
          </tr>
          <tr>
            <td><?php echo _("Description:"); ?></td>
            <td><input type="text" name="_IN_description" class="width-form" size="40" value="" disabled="disabled" /></td>
          </tr>
          <tr>
            <td><?php echo _("Garanteed download:"); ?></td>
            <td><input type="text" name="_IN_dnl_rate" class="width-form" size="40" value="" disabled="disabled" /></td>
          </tr>
          <tr>
            <td><?php echo _("Garanteed upload:"); ?></td>
            <td><input type="text" name="_IN_upl_rate" class="width-form" size="40" value="" disabled="disabled" /></td>
          </tr>
          <tr>
            <td><?php echo _("Maximum download:"); ?></td>
            <td><input type="text" name="_IN_dnl_ceil" class="width-form" size="40" value="" disabled="disabled" /></td>
          </tr>
          <tr>
            <td><?php echo _("Maximum upload:"); ?></td>
            <td><input type="text" name="_IN_upl_ceil" class="width-form" size="40" value="" disabled="disabled" /></td>
          </tr>
          <tr>
            <td><?php echo _("Priority:"); ?></td>
            <td><input type="text" name="_IN_prio" class="width-form" size="40" value="" disabled="disabled" /></td>
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
    <input type="hidden" name="CH_chargeid" value="<?php echo $charge->CH_chargeid; ?>" />
    <input type="hidden" name="option" value="com_charge" />
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="hidemainmenu" value="0" />
    </form>
    </div>
    
    <div class="clr"></div>
</div>

<div class="clr"></div>
</div>
<script language="JavaScript" type="text/javascript">
  updateTypeSelect();
</script>
<?php
	}
} // End of HTML_charge class
?>