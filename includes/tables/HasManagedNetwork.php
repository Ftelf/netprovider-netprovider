<?php
//
// +----------------------------------------------------------------------+
// | Ftelf ISP billing system                                               |
// +----------------------------------------------------------------------+
// | Copyright (c) 2006-2007 Ing. Lukas Dziadkowiec                       |
// +----------------------------------------------------------------------+
// | This source file is part of Ftelf ISP billing system,                  |
// | see LICENSE for licence details.                                     |
// +----------------------------------------------------------------------+
// | Authors: Lukas Dziadkowiec <i.ftelf@gmail.com>                  |
// +----------------------------------------------------------------------+

/**
 * @author  Lukas Dziadkowiec <i.ftelf@gmail.com>
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
