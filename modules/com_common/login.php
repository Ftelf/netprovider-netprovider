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

	global $core;
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title><?php echo $core->getProperty(Core::UI_TITLE); ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="Content-Language" content="cz" />
    <link rel="stylesheet" href="css/login.css" type="text/css" />
    <link rel="shortcut icon" href="favicon.ico" />
    <script type="text/javascript">
	function setFocus() {
		document.loginForm.username.select();
		document.loginForm.username.focus();
	}
    </script>
  </head>

  <body id="wrapper" onload="setFocus();">
    <div id="header-top">
      <div>
        <span class="title"><?php echo $core->getProperty(Core::UI_VENDOR); ?></span>
      </div>
    </div>

    <div id="ctr" align="center">

	  <div id="login-box">
        <div class="t">
          <div class="t">
            <div class="t"></div>
          </div>
        </div>

        <div class="m">
	      <div class="login-form">
            <form action="index.php" method="post" name="loginForm" id="loginForm">

              <div id="form-box">
                <div class="t">
                  <div class="t">
                    <div class="t"></div>
                  </div>
                </div>

                <div class="m">
                  <div class="inputlabel"><?php echo _("Username");?></div>
                  <div><input name="username" type="text" class="inputbox" size="15" value="<?php echo $foundUsername; ?>" /></div>
                  <div class="inputlabel"><?php echo _("Password");?></div>
                  <div><input name="pass" type="password" class="inputbox" size="15" /></div>
                  <div align="left"><input type="submit" name="submit" class="button" value="<?php echo _("Login"); ?>" /></div>

                  <div class="clr"></div>

                </div>

                <div class="b">
                  <div class="b">
                    <div class="b"></div>
                  </div>
                </div>
              </div>

            </form>
          </div>

          <div class="login-text">
            <div class="ctr"><img src="images/64x64/mimetypes/encrypted.png" width="64" height="64" alt="security" /></div>
            <p><?php echo _("Welcome to Net Provider"); ?></p>
            <p><?php echo _("Please use valid username and password to gain access into the system."); ?></p>
          </div>

          <div class="clr"></div>
        </div>

        <div class="b">
          <div class="b">
            <div class="b"></div>
          </div>
        </div>
      </div>
    </div>

    <div id="break"></div>

    <noscript><?php echo _("Javascript must be enabled to function system properly"); ?></noscript>

    <div class="footer" align="center">
      <div align="center">COPYRIGHT &copy; 2007 Ing. Lukáš Dziadkowiec ALL RIGHTS RESERVED</div>
    </div>
  </body>
</html>
