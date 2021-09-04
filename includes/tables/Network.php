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

/**
 * Network
 */
class Network {
    /** @var int id */
    var $NE_networkid = null;
    /** @var int id of parent when nested */
    var $NE_parent_networkid = null;
    /** @var int id of owner */
    var $NE_personid = null;
    /** @var varchar network */
    var $NE_net = null;
    /** @var varchar network */
    var $NE_description = null;
} // End of Network class
?>
