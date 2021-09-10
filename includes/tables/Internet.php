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
 * Internet
 */
class Internet {
    /** @var int internet ID PK */
    var $IN_internetid = null;
    /** @var varchar(255) name */
    var $IN_name = null;
    /** @var varchar(255) description */
    var $IN_description = null;
    /** @var int dnl_rate */
    var $IN_dnl_rate = null;
    /** @var int dnl_ceil */
    var $IN_dnl_ceil = null;
    /** @var int upl_rate */
    var $IN_upl_rate = null;
    /** @var int upl_ceil */
    var $IN_upl_ceil = null;
    /** @var priority */
    var $IN_prio = null;
} // End of Internet class
?>
