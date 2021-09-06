<?php
//
// +----------------------------------------------------------------------+
// | Stealth ISP QOS system                                               |
// +----------------------------------------------------------------------+
// | Copyright (c) 2006-2007 Ing. Lukas Dziadkowiec                       |
// +----------------------------------------------------------------------+
// | This source file is part of Stealth ISP QOS system,                  |
// | see LICENSE for licence details.                                     |
// +----------------------------------------------------------------------+
// | Authors: Lukas Dziadkowiec <i.ftelf@gmail.com>                  |
// +----------------------------------------------------------------------+

/**
 * @author  Lukas Dziadkowiec <i.ftelf@gmail.com>
 */

/**
 * NetworkDevice
 */
class NetworkDevice {
    /** @var int networkdevice id PK */
    var $ND_networkdeviceid = null;
    /** @var varchar(255) name of the device */
    var $ND_name = null;
    /** @var varchar(255) vendor */
    var $ND_vendor = null;
    /** @var varchar(255) type */
    var $ND_type = null;
    /** @var int platform */
    var $ND_platform = null;
    /** @var varchar(255) description */
    var $ND_description = null;
    /** @var int management interface id */
    var $ND_managementInterfaceId = null;
    /** @var varchar(255) login name */
    var $ND_login = null;
    /** @var varchar(255) login password */
    var $ND_password = null;
    /** @var boolean use sudo command */
    var $ND_useCommandSudo = null;
    /** @var varchar(255) sudo command */
    var $ND_commandSudo = null;
    /** @var varchar(255) iptables command */
    var $ND_commandIptables = null;
    /** @var varchar(255) ip command */
    var $ND_commandIp = null;
    /** @var varchar(255) iptables command */
    var $ND_commandTc = null;
    /** @var boolean use QOS on this device */
    var $ND_ipFilterEnabled = null;
    /** @var int ID wan interface */
    var $ND_wanInterfaceid = null;

//	const PLATFORM_UNSPECIFIED = 1;
    const PLATFORM_GNU_LINUX_DEBIAN = 2;
    const PLATFORM_ROUTEROS = 3;
//	const PLATFORM_HWAP_CLIENT = 4;

    public static $PLATFORM_ARRAY = array(
//		1, //Unspecified
        2,  //GNU Linux Debian
        3  //RouterOS (Mikrotik)
//		4, //HW AP/Client
    );

    public static function getLocalizedPlatform($platform) {
        switch ($platform) {
//			case self::PLATFORM_UNSPECIFIED :
//				return _("Unspecified");

            case self::PLATFORM_GNU_LINUX_DEBIAN :
                return _("GNU Linux Debian");

            case self::PLATFORM_ROUTEROS :
                return _("RouterOS (Mikrotik)");

//			case self::PLATFORM_HWAP_CLIENT :
//				return _("HW AP/Client");
        }
    }
} // End of NetworkDevice class
?>
