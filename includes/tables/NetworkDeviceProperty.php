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
class NetworkDeviceProperty {
    /** @var int networkdeviceproproperty ID PK */
    var $NP_networkdevicepropertyid = null;
    /** @var int NetworkDevice ID FK */
    var $NP_networkdeviceid = null;
    /** @var varchar(255) propertynamename */
    var $NP_name = null;
    /** @var varchar(255) propertyvalue */
    var $NP_value = null;
} // End of NetworkDeviceProperty class
?>
