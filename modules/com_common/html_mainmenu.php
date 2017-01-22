<?php
	global $my, $core;
	if ($my->GR_level != Group::USER) {
		$loggedCount = SessionDAO::getSessionCount();
?>

<div id="header-box">
  <div id="module-status">
    <span class="loggedin-users">
      <?php echo $loggedCount ?>
    </span>
    <span class="logout">
      <a href="index2.php?option=logout"><?php echo _("Logout"); ?></a>
    </span>
    <span class="loggedin-name">
      <?php echo $my->PE_firstname . " " . $my->PE_surname . " / " . $my->PE_nick; ?>
    </span>
  </div>

  <div id="myMenuID"></div>
      <script language="JavaScript" type="text/javascript">
      var myMenu =
      [
        [null, '<?php echo addslashes(_("Home")); ?>', 'index2.php', null, '<?php echo addslashes(_("Home page")); ?>'],
        _cmSplit,
    	[null, '<?php echo addslashes(_("User agenda")); ?>', null, null, '<?php echo addslashes(_("User agenda")); ?>',
    	  ['<img src="images/22x22/apps/Community Help.png" />', '<?php echo addslashes(_("Users")); ?>', null, null, '<?php echo addslashes(_("Users")); ?>',
            ['<img src="images/22x22/apps/kuser.png" />','<?php echo addslashes(_("Users")); ?>','index2.php?option=com_person',null,'<?php echo addslashes(_("Manage users")); ?>'],
            ['<img src="images/22x22/apps/access.png" />','<?php echo addslashes(_("User groups")); ?>','index2.php?option=com_group',null,'<?php echo addslashes(_("Manage user groups")); ?>'],
            ['<img src="images/22x22/apps/Community Help.png" />','<?php echo addslashes(_("Roles and responsibilities")); ?>','index2.php?option=com_role',null,'<?php echo addslashes(_("Manage roles and responsibilities")); ?>']
          ],
          ['<img src="images/22x22/apps/personal.png" />', '<?php echo addslashes(_("My profile")); ?>', 'index2.php?option=com_myprofile', null, '<?php echo addslashes(_("Manage my profile")); ?>']
        ],
    	_cmSplit,
    	[null, '<?php echo addslashes(_("Financial")); ?>', null, null, '<?php echo addslashes(_("Financial Managenent")); ?>',
    	  ['<img src="images/22x22/apps/business.png" />', '<?php echo addslashes(_("Bank accounts")); ?>', 'index2.php?option=com_bankaccount', null, '<?php echo addslashes(_("Manage bank accounts")); ?>'],
    	  ['<img src="images/22x22/apps/kspread.png" />', '<?php echo addslashes(_("User's accounts")); ?>', 'index2.php?option=com_personaccount', null, '<?php echo addslashes(_("Manage user accounts")); ?>'],
    	   <?php if ($core->getProperty(Core::ENABLE_INVOICE_MODULE)) echo "['<img src=\"images/22x22/devices/printer1.png\" />', '" . (addslashes(_("Invoices"))) . "', 'index2.php?option=com_invoice', null, '" . (addslashes(_("Manage invoices"))) . "']," ?> 
    	  ['<img src="images/22x22/apps/kword.png" />', '<?php echo addslashes(_("Payment templates")); ?>', 'index2.php?option=com_charge', null, '<?php echo addslashes(_("Manage payment templates")); ?>']
        ],
        _cmSplit,
        [null, '<?php echo addslashes(_("Services")); ?>', null, null, '<?php echo addslashes(_("Services Managenent")); ?>',
          ['<img src="images/22x22/apps/Internet Connection Tools.png" />', '<?php echo addslashes(_("Internet services")); ?>', 'index2.php?option=com_internet', null, '<?php echo addslashes(_("Manage Internet services")); ?>']
        ],
        [null, '<?php echo addslashes(_("Network")); ?>', null, null, '<?php echo addslashes(_("Network Managenent")); ?>',
          ['<img src="images/22x22/filesystems/network.png" />', '<?php echo addslashes(_("IP networks")); ?>', 'index2.php?option=com_network', null, '<?php echo addslashes(_("Manage IP networks")); ?>'],
          ['<img src="images/22x22/apps/Network Connection Manager.png" />', '<?php echo addslashes(_("Network devices")); ?>', 'index2.php?option=com_networkdevice', null, '<?php echo addslashes(_("Manage network devices")); ?>']
        ],
        [null, '<?php echo addslashes(_("Administration")); ?>', null, null, '<?php echo addslashes(_("Administration")); ?>',
          ['<img src="images/22x22/apps/agt_runit.png" />', '<?php echo addslashes(_("Scripts")); ?>', 'index2.php?option=com_scripts', null, '<?php echo addslashes(_("Run scripts")); ?>'],
          ['<img src="images/22x22/actions/configure.png" />', '<?php echo addslashes(_("Configuration")); ?>', 'index2.php?option=com_configuration', null, '<?php echo addslashes(_("View configuration")); ?>'],
          ['<img src="images/22x22/apps/database.png" />', '<?php echo addslashes(_("Log")); ?>', 'index2.php?option=com_log', null, '<?php echo addslashes(_("View log")); ?>'],
          ['<img src="images/22x22/apps/xfmail.png" />', '<?php echo addslashes(_("Messages")); ?>', 'index2.php?option=com_message', null, '<?php echo addslashes(_("Send messages")); ?>'],
          ['<img src="images/22x22/apps/alert.png" />', '<?php echo addslashes(_("Event handlers")); ?>', 'index2.php?option=com_handleevent', null, '<?php echo addslashes(_("Event handlers")); ?>']
        ],
        [null, '<?php echo addslashes(_("Reports")); ?>', null, null, '<?php echo addslashes(_("View reports")); ?>',
          ['<img src="images/22x22/apps/icons.png" />', '<?php echo addslashes(_("Payment report")); ?>', 'index2.php?option=com_paymentreport', null, '<?php echo addslashes(_("Create payment report")); ?>'],
          ['<img src="images/22x22/filesystems/network_local.png" />', '<?php echo addslashes(_("IP data traffic report")); ?>', 'index2.php?option=com_iptrafficreport', null, '<?php echo addslashes(_("Create IP data traffic report")); ?>']
        ],
        [null, '<?php echo addslashes(_("Help")); ?>', null, null, '<?php echo addslashes(_("View reports")); ?>',
          ['<img src="images/22x22/apps/antivirus.png" />', '<?php echo addslashes(_("Changelog")); ?>', 'index2.php?option=com_changelog', null, '<?php echo addslashes(_("View changelog")); ?>'],
          ['<img src="images/22x22/actions/help.png" />', '<?php echo addslashes(_("Documentation")); ?>', 'http://netprovider.ovjih.net', null, '<?php echo addslashes(_("View documentation")); ?>']
        ]
      ];
      cmDraw ('myMenuID', myMenu, 'hbr', cmThemeOffice, 'ThemeOffice');
	  </script>
<?php
	}
?>
  <div class="clr"></div>
</div>