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
 * NetworkDevice
 */
class NetworkDevice
{
    /**
     * @var int networkdevice id PK
     */
    public $ND_networkdeviceid;
    /**
     * @var varchar(255) name of the device
     */
    public $ND_name;
    /**
     * @var varchar(255) vendor
     */
    public $ND_vendor;
    /**
     * @var varchar(255) type
     */
    public $ND_type;
    /**
     * @var int platform
     */
    public $ND_platform;
    /**
     * @var varchar(255) description
     */
    public $ND_description;
    /**
     * @var int management interface id
     */
    public $ND_managementInterfaceId;
    /**
     * @var varchar(255) login name
     */
    public $ND_login;
    /**
     * @var varchar(255) login password
     */
    public $ND_password;
    /**
     * @var boolean use sudo command
     */
    public $ND_useCommandSudo;
    /**
     * @var varchar(255) sudo command
     */
    public $ND_commandSudo;
    /**
     * @var varchar(255) iptables command
     */
    public $ND_commandIptables;
    /**
     * @var varchar(255) ip command
     */
    public $ND_commandIp;
    /**
     * @var varchar(255) iptables command
     */
    public $ND_commandTc;
    /**
     * @var boolean use QOS on this device
     */
    public $ND_ipFilterEnabled;
    /**
     * @var int ID wan interface
     */
    public $ND_wanInterfaceid;

    //  public const PLATFORM_UNSPECIFIED = 1;
    public const PLATFORM_GNU_LINUX_DEBIAN = 2;
    public const PLATFORM_ROUTEROS = 3;
    //  public const PLATFORM_HWAP_CLIENT = 4;

    public static $PLATFORM_ARRAY = array(
        //      self::PLATFORM_UNSPECIFIED, //Unspecified
        self::PLATFORM_GNU_LINUX_DEBIAN,  //GNU Linux Debian
        self::PLATFORM_ROUTEROS  //RouterOS (Mikrotik)
        //      self::PLATFORM_HWAP_CLIENT, //HW AP/Client
    );

    public static array $platformLocalization = [
//        self::PLATFORM_UNSPECIFIED => "Unspecified",
        self::PLATFORM_GNU_LINUX_DEBIAN => "GNU Linux Debian",
        self::PLATFORM_ROUTEROS => "RouterOS (Mikrotik)"
//        self::PLATFORM_HWAP_CLIENT => "HW AP/Client"
    ];

    public static function getLocalizedPlatform($platform): string
    {
        return _(self::$platformLocalization[$platform] ?? '');
    }
} // End of NetworkDevice class
