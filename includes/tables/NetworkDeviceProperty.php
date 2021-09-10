<?php
//
// +----------------------------------------------------------------------+
// | Ftelf ISP billing system                                               |
// +----------------------------------------------------------------------+
// | Copyright (c) 2006-2007 Ing. Lukas Dziadkowiec                       |
// +----------------------------------------------------------------------+
// | This source file is part of Ftelf ISP billing system,                  |
// | see LICENSE for licence details.                                     |
// +----------------------------------------------------------------------+
// | Authors: Lukas Dziadkowiec <i.ftelf@gmail.com>                  |
// +----------------------------------------------------------------------+

/**
 * @author  Lukas Dziadkowiec <i.ftelf@gmail.com>
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
