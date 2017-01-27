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

class HTML_admin {
	/**
	 * show
	 * @param $session
	 */
	static function show(&$sessions, &$logs, &$persons) {
		global $core;
?>
<script language="JavaScript" type="text/javascript">
	function force_logout(id) {
		var form = document.adminForm;
		form.SE_sessionid.value = id;
		submitbutton('force_logout');
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
      <div class="header icon-48-home">
        <?php echo _("Main page"); ?>
      </div>
    </div>

    <div class="b">
      <div class="b">
        <div class="b"></div>
      </div>
    </div>
  </div>
  
  <div class="clr"></div>
  
  <div id="element-box">
    <table class="adminform">
    <tr>
      <td width="50%" valign="top">
      <div id="cpanel">
        <div style="float: left;">
			<div class="icon">
				<a href="index2.php?option=com_person">
					<img alt="<?php echo _("Users"); ?>" src="images/48x48/apps/kuser.png"/>
					<span><?php echo _("Users"); ?></span>
				</a>
			</div>
		</div>
		
		<div style="float: left;">
			<div class="icon">
				<a href="index2.php?option=com_group">
					<img alt="<?php echo _("Groups"); ?>" src="images/48x48/apps/access.png"/>
					<span><?php echo _("User groups"); ?></span>
				</a>
			</div>
		</div>
		
		<div style="float: left;">
			<div class="icon">
				<a href="index2.php?option=com_role">
					<img alt="<?php echo _("Roles and responsibilities"); ?>" src="images/48x48/apps/Community%20Help.png"/>
					<span><?php echo _("Roles and responsibilities"); ?></span>
				</a>
			</div>
		</div>
		
		<div style="float: left;">
			<div class="icon">
				<a href="index2.php?option=com_bankaccount">
					<img alt="<?php echo _("Bank accounts"); ?>" src="images/48x48/apps/business.png"/>
					<span><?php echo _("Bank accounts"); ?></span>
				</a>
			</div>
		</div>
		
		<div style="float: left;">
			<div class="icon">
				<a href="index2.php?option=com_personaccount">
					<img alt="<?php echo _("User's accounts"); ?>" src="images/48x48/apps/kspread.png"/>
					<span><?php echo _("User's accounts"); ?></span>
				</a>
			</div>
		</div>
		
		<div style="float: left;">
			<div class="icon">
				<a href="index2.php?option=com_charge">
					<img alt="<?php echo _("Payment templates"); ?>" src="images/48x48/apps/kword.png"/>
					<span><?php echo _("Payment templates"); ?></span>
				</a>
			</div>
		</div>
		
		<div style="float: left;">
			<div class="icon">
				<a href="index2.php?option=com_network">
					<img alt="<?php echo _("IP networks"); ?>" src="images/48x48/filesystems/network.png"/>
					<span><?php echo _("IP networks"); ?></span>
				</a>
			</div>
		</div>
		
		<div style="float: left;">
			<div class="icon">
				<a href="index2.php?option=com_paymentreport">
					<img alt="<?php echo _("Payment report"); ?>" src="images/48x48/apps/icons.png"/>
					<span><?php echo _("Payment report"); ?></span>
				</a>
			</div>
		</div>
		
		<div style="float: left;">
			<div class="icon">
				<a href="index2.php?option=com_scripts">
					<img alt="<?php echo _("Scripts"); ?>" src="images/48x48/apps/clickrun.png"/>
					<span><?php echo _("Scripts"); ?></span>
				</a>
			</div>
		</div>
      </div>
      </td>
      <td width="10">
        &nbsp;
      </td>
      <td width="50%" valign="top">
        <div style="width=100%;">
          <form action="index2.php" method="post" name="adminForm">
            <div class="tab-page" id="modules-cpanel-admin">
              <script type="text/javascript">
                var tabPaneAdmin1 = new WebFXTabPane( document.getElementById( "modules-cpanel-admin" ), 1 )
              </script>
              <div class="tab-page" id="module01"><h2 class="tab"><?php echo _("Logged in"); ?></h2>
                <script type="text/javascript">
                  tabPaneAdmin1.addTabPage(document.getElementById("module01"));
                </script>
                <table class="adminlist">
                <thead>
                <tr>
                  <th colspan="6"><?php echo _("Users logged in"); ?></th>
                </tr>
                </thead>
                <tbody>
<?php
	$now = time();
	$n = 1;
	$k = 0;
	foreach ($sessions as $session) {
		$link1 = "index2.php?option=com_person&amp;task=edit&amp;hidemainmenu=1&amp;PE_personid=$session->SE_personid";
		$link2 = "javascript:force_logout('$session->SE_sessionid');";
		$seconds = $now - $session->SE_time;
?>
                <tr class="<?php echo "row$k"; ?>">
                  <td width="5%"><?php echo $n;?>.</td>
                  <td><a href="<?php echo $link1; ?>" title="<?php echo _("Edit user"); ?>"><?php echo $session->SE_username; ?></a></td>
                  <td><?php echo $session->SE_ip; ?></td>
                  <td><?php echo $session->SE_acl; ?></td>
                  <td><?php printf(ngettext("%s second", "%s seconds", $seconds), $seconds); ?></td>
                  <td>
<?php
		if ($_SESSION['SE_sessionid'] == $session->SE_sessionid) {
			echo '&nbsp;';
		} else {
?>
			<a href="<?php echo $link2; ?>" title="<?php echo _("Force logout"); ?>" class="text-button"><?php echo _("Force logout"); ?></a>
<?php
		}
?>
                  </td>
                </tr>
<?php
		$n++;
		$k = 1 - $k;
	}
?>
                </tbody>
                </table>
              </div>
              <div class="tab-page" id="module03"><h2 class="tab"><?php echo _("Log"); ?></h2>
                <script type="text/javascript">
                  tabPaneAdmin1.addTabPage(document.getElementById("module03"));
                </script>
                <table class="adminlist">
                <thead>
                <tr>
                  <th colspan="4"><?php echo _("Last 10 entries"); ?></th>
                </tr>
                </thead>
                <tbody>
<?php
	$n = 1;
	$k = 0;
	foreach ($logs as $log) {
		$logDate = new DateUtil($log->LO_datetime);
		if ($log->LO_personid == 0) {
			$loggerName = "Cron";
		} else {
			$loggerName = $persons[$log->LO_personid]->PE_firstname . " ". $persons[$log->LO_personid]->PE_surname;
		}
?>
                <tr class="<?php echo "row$k"; ?>">
                  <td width="5%"><?php echo $n;?>.</td>
                  <td><?php echo $loggerName; ?></td>
                  <td><?php echo $logDate->getFormattedDate(DateUtil::FORMAT_FULL); ?></td>
                  <td><?php echo $log->LO_log; ?></td>
                </tr>
<?php
		$n++;
		$k = 1 - $k;
	}
?>
                </tbody>
                </table>
              </div>
            </div>
            <input type="hidden" name="option" value="com_admin" />
            <input type="hidden" name="SE_sessionid" value="" />
            <input type="hidden" name="SE_personid" value="" />
            <input type="hidden" name="task" value="" />
            <input type="hidden" name="hidemainmenu" value="0" />
          </form>
        </div>
      </td>
    </tr>
    </table>
  </div>
  
  <div class="clr"></div>
  
</div>

<div class="clr"></div>

</div>
<?php
	}
}
?>