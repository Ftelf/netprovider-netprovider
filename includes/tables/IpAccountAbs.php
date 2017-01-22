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