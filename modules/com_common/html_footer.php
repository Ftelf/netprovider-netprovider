<?php
//
// +----------------------------------------------------------------------+
// | Ftelf ISP billing system                                               |
// +----------------------------------------------------------------------+
// | Copyright (c) 2006-2007 Ing. Lukas Dziadkowiec                       |
// +----------------------------------------------------------------------+
// | This source file is part of Ftelf ISP billing system,                  |
// | see LICENSE for licence details.                                     |
// +----------------------------------------------------------------------+
// | Authors: Lukas Dziadkowiec <i.ftelf@gmail.com>                  |
// +----------------------------------------------------------------------+

/**
 * @author  Lukas Dziadkowiec <i.ftelf@gmail.com>
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
