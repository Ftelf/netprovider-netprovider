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
 * global constants
 */

define( "_NP_NOTRIM", 0x0001 );
define( "_NP_ALLOWHTML", 0x0002 );

/**
 *
 */
define( "_ACL_USERS",    0x0001 );
define( "_ACL_NETWORKS", 0x0002 );
define( "_ACL_PAYMENT",  0x0004 );
define( "_ACL_HARDWARE", 0x0008 );
define( "_ACL_SCRIPTS",  0x0010 );
?>
