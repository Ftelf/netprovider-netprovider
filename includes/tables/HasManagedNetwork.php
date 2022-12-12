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
 *  Rolemember
 */
class HasManagedNetwork
{
    /**
     * @var int has managed network id PK
     */
    public $MN_hasmanagednetworkid;
    /**
     * @var int FK network device ID
     */
    public $MN_networkdeviceid;
    /**
     * @var int FK network ID
     */
    public $MN_networkid;
} // End of HasManagedNetwork class
