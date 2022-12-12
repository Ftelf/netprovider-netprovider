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
 * NetworkDeviceProperty
 */
class NetworkDeviceProperty
{
    /**
     * @var int networkdeviceproproperty ID PK
     */
    public $NP_networkdevicepropertyid;
    /**
     * @var int NetworkDevice ID FK
     */
    public $NP_networkdeviceid;
    /**
     * @var varchar(255) propertynamename
     */
    public $NP_name;
    /**
     * @var varchar(255) propertyvalue
     */
    public $NP_value;
} // End of NetworkDeviceProperty class
