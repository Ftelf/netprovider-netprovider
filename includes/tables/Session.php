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
 *  Session
 */
class Session {
    /** @var int session ID PK */
    var $SE_sessionid = null;
    /** @var int time of creation timestamp */
    var $SE_time = null;
    /** @var int personid FK */
    var $SE_personid = null;
    /** @var int access list */
    var $SE_acl = null;
    /** @var varchar(255) username */
    var $SE_username = null;
    /** @var varchar(15) IP address */
    var $SE_ip = null;
} // End of Session class
?>
