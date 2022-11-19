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

  global $mainframe;
  $seconds = round($mainframe->getTimer(), 3);
?>

  <div id="footer">
    <p class="copyright">
      COPYRIGHT &copy; 2007 Lukáš Dziadkowiec ALL RIGHTS RESERVED
    </p>
    <p class="statistics">
      <?php printf(gettext("Page generated in %s seconds"), $seconds); ?>
    </p>
  </div>
