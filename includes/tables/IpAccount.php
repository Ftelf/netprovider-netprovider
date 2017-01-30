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
// | Authors: Lukas Dziadkowiec <stealth.home@seznam.cz>                  |
// +----------------------------------------------------------------------+

/**
 * @author  Lukas Dziadkowiec <stealth.home@seznam.cz>
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