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
class NetworkDeviceInterface {
    /** @var int networkdeviceinterface ID PK */
    var $NI_networkdeviceinterfaceid;
    /** @var int NetworkDevice ID FK */
    var $NI_networkdeviceid;
    /** @var int ip ID FK */
    var $NI_ipid;
    /** @var varchar(10) ifname */
    var $NI_ifname;
    /** @var int type of interface */
    var $NI_type;
    /** @var varchar(255) description */
    var $NI_description;

    const TYPE_UNSPECIFIED = 0;
    const TYPE_LAN = 1;
    const TYPE_WAN = 2;

    public static $PLATFORM_ARRAY = array(
        0, //Unspecified
        1, //Lan
        2  //Wan
    );
} // End of NetworkDeviceInterface class
?>
