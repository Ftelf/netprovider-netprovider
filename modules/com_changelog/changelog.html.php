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
 * HTML_changelog
 */
class HTML_changelog {
	/**
	 * showChangelog
	 */
	static function showChangelog($changelogText) {
		global $core;
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
        <div class="header icon-48-changelog">
          <?php echo _("Changelog"); ?>
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
    <table class="adminform">
    <tbody>
    <tr>
    <td>
    <div style="background-color: #F5F5F5; color: black; height: 400px; overflow: scroll; text-align: left;">
    <pre><?php echo $changelogText; ?></pre>
    </div>
    </td>
    </tr>
    </tbody>
    </table>
    <input type="hidden" name="option" value="com_scripts" />
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="hidemainmenu" value="0" />
    <input type="hidden" name="filter[void]" value="0" />
    </form>
    </div>

    <div class="clr"></div>

  </div>

  <div class="clr"></div>
</div>
<?php
	}
} // End of HTML_changelog class
?>
