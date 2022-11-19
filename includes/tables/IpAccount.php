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
 * IpAccount
 */
class IpAccount {
    /** @var int ip id PK */
    var $IA_ipaccountid = null;
    /** @var int ip id FK */
    var $IA_ipid = null;
    /** @var datetime datetime FK */
    var $IA_datetime = null;
    /** @var integer bytes in count */
    var $IA_bytes_in = null;
    /** @var integer bytes out count */
    var $IA_bytes_out = null;
    /** @var integer packet in count */
    var $IA_packets_in = null;
    /** @var integer packet out count */
    var $IA_packets_out = null;
} // End of IpAccount class
?>
