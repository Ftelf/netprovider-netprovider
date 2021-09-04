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
// | Authors: Lukas Dziadkowiec <i.ftelf@gmail.com>                  |
// +----------------------------------------------------------------------+

/**
 * @author  Lukas Dziadkowiec <i.ftelf@gmail.com>
 */

?>

  <div id="debug">
    <h5 onclick="document.getElementById('div_session').style.display = 'block';">SESSION</h5>
    <div style="display: none" id="div_session">
      <pre>
        <?php print_r($_SESSION); ?>
      </pre>
    </div>
    <h5 onclick="document.getElementById('div_request').style.display = 'block';">REQUEST</h5>
    <div style="display: none" id="div_request">
      <pre>
        <?php print_r($_REQUEST); ?>
      </pre>
    </div>
    <h5 onclick="document.getElementById('div_get').style.display = 'block';">GET</h5>
    <div style="display: none" id="div_get">
      <pre>
        <?php print_r($_GET); ?>
      </pre>
    </div>
    <h5 onclick="document.getElementById('div_post').style.display = 'block';">POST</h5>
    <div style="display: none" id="div_post">
      <pre>
        <?php print_r($_POST); ?>
      </pre>
    </div>
  </div>
