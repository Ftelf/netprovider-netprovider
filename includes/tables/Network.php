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
