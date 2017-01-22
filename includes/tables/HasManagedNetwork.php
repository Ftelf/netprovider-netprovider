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
 *  Rolemember
 */
class HasManagedNetwork {
	/** @var int has managed network id PK */
	var $MN_hasmanagednetworkid = null;
	/** @var int FK network device ID */
	var $MN_networkdeviceid = null;
	/** @var int FK network ID */
	var $MN_networkid = null;
} // End of HasManagedNetwork class
?>