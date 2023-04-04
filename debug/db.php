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

require_once dirname(__FILE__) . "/../includes/Core.php";
$core = new Core();
require_once $core->getAppRoot() . "includes/Database.php";

try {
    $database = new Database(
        $core->getProperty(Core::DATABASE_HOST),
        $core->getProperty(Core::DATABASE_USERNAME),
        $core->getProperty(Core::DATABASE_PASSWORD),
        $core->getProperty(Core::DATABASE_NAME)
    );
} catch (Exception $e) {
    echo(_("Cannot connect to database"));
    exit();
}

echo "connected";


?>
