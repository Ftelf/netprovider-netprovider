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
 * Internet
 */
class Internet
{
    /**
     * @var int internet ID PK
     */
    public $IN_internetid;
    /**
     * @var varchar(255) name
     */
    public $IN_name;
    /**
     * @var varchar(255) description
     */
    public $IN_description;
    /**
     * @var int dnl_rate
     */
    public $IN_dnl_rate;
    /**
     * @var int dnl_ceil
     */
    public $IN_dnl_ceil;
    /**
     * @var int upl_rate
     */
    public $IN_upl_rate;
    /**
     * @var int upl_ceil
     */
    public $IN_upl_ceil;
    /**
     * @var priority
     */
    public $IN_prio;
} // End of Internet class
