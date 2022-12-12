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
class Session
{
    /**
     * @var int session ID PK
     */
    public $SE_sessionid;
    /**
     * @var int time of creation timestamp
     */
    public $SE_time;
    /**
     * @var int personid FK
     */
    public $SE_personid;
    /**
     * @var int access list
     */
    public $SE_acl;
    /**
     * @var varchar(255) username
     */
    public $SE_username;
    /**
     * @var varchar(15) IP address
     */
    public $SE_ip;
} // End of Session class
