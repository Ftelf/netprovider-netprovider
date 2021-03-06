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