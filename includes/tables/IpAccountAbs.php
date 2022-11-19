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
class IpAccountAbs {
    /** @var int ip id PK */
    var $IB_ipaccountabsid = null;
    /** @var int ip id FK */
    var $IB_ipid = null;
    /** @var integer bytes in count */
    var $IB_bytes_in = null;
    /** @var integer bytes out count */
    var $IB_bytes_out = null;
    /** @var integer packet in count */
    var $IB_packets_in = null;
    /** @var integer packet out count */
    var $IB_packets_out = null;
} // End of IpAccountAbs class
?>
