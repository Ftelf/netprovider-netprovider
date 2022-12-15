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
class IpAccountAbs
{
    /**
     * @var int ip id PK
     */
    public $IB_ipaccountabsid;
    /**
     * @var int ip id FK
     */
    public $IB_ipid;
    /**
     * @var integer bytes in count
     */
    public $IB_bytes_in;
    /**
     * @var integer bytes out count
     */
    public $IB_bytes_out;
    /**
     * @var integer packet in count
     */
    public $IB_packets_in;
    /**
     * @var integer packet out count
     */
    public $IB_packets_out;
} // End of IpAccountAbs class
