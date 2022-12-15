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
 * NetworkDeviceInterface
 */
class NetworkDeviceInterface
{
    /**
     * @var int networkdeviceinterface ID PK
     */
    public $NI_networkdeviceinterfaceid;
    /**
     * @var int NetworkDevice ID FK
     */
    public $NI_networkdeviceid;
    /**
     * @var int ip ID FK
     */
    public $NI_ipid;
    /**
     * @var varchar(10) ifname
     */
    public $NI_ifname;
    /**
     * @var int type of interface
     */
    public $NI_type;
    /**
     * @var varchar(255) description
     */
    public $NI_description;

    public const TYPE_UNSPECIFIED = 0;
    public const TYPE_LAN = 1;
    public const TYPE_WAN = 2;

} // End of NetworkDeviceInterface class
