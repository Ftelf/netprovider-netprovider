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
// | Authors: Lukas Dziadkowiec <stealth.home@seznam.cz>                  |
// +----------------------------------------------------------------------+

/**
 * @author  Lukas Dziadkowiec <stealth.home@seznam.cz>
 */

require_once(dirname(__FILE__) . "/../includes/Core.php");
$core = new Core();
require_once($core->getAppRoot() . "includes/Constants.php");
require_once($core->getAppRoot() . "includes/Database.php");

try {
	$database = new Database(
		$core->getProperty(Core::DATABASE_HOST),
		$core->getProperty(Core::DATABASE_USERNAME),
		$core->getProperty(Core::DATABASE_PASSWORD),
		$core->getProperty(Core::DATABASE_NAME)
	);
} catch (Exception $e) {
	echo (_("Cannot connect to database"));
	exit();
}

	echo "connected";


?>