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

	global $core;
	$seconds = round($mainframe->getTimer(), 3);
?>

  <div id="footer">
    <p class="copyright">
      COPYRIGHT &copy; 2007 Lukáš Dziadkowiec ALL RIGHTS RESERVED
    </p>
    <p class="statistics">
      <?php printf(ngettext("Page generated in %s second", "Page generated in %s seconds", $seconds), $seconds); ?>
    </p>
  </div>
