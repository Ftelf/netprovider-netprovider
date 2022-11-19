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
    var $NI_networkdeviceinterfaceid = null;
    /** @var int NetworkDevice ID FK */
    var $NI_networkdeviceid = null;
    /** @var int ip ID FK */
    var $NI_ipid = null;
    /** @var varchar(10) ifname */
    var $NI_ifname = null;
    /** @var int type of interface */
    var $NI_type = null;
    /** @var varchar(255) description */
    var $NI_description = null;

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
