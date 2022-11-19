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
 * Ip
 */
class Ip {
    /** @var int ip id PK */
    var $IP_ipid = null;
    /** @var int networkid id PK */
    var $IP_networkid = null;
    /** @var int personid FK */
    var $IP_personid = null;
    /** @var varchar ip address */
    var $IP_address = null;
    /** @var varchar dns record */
    var $IP_dns = null;
} // End of Ip class
?>
