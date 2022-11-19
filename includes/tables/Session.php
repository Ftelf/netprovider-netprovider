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
