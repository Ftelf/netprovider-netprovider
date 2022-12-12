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
 * Ip
 */
class Ip
{
    /**
     * @var int ip id PK
     */
    public $IP_ipid;
    /**
     * @var int networkid id PK
     */
    public $IP_networkid;
    /**
     * @var int personid FK
     */
    public $IP_personid;
    /**
     * @var varchar ip address
     */
    public $IP_address;
    /**
     * @var varchar dns record
     */
    public $IP_dns;
} // End of Ip class
