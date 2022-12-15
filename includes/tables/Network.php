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
class Network
{
    /**
     * @var int id
     */
    public $NE_networkid;
    /**
     * @var int id of parent when nested
     */
    public $NE_parent_networkid;
    /**
     * @var int id of owner
     */
    public $NE_personid;
    /**
     * @var varchar network
     */
    public $NE_net;
    /**
     * @var varchar network
     */
    public $NE_description;
} // End of Network class
