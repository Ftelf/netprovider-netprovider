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
    require_once(dirname(__FILE__) . "/../includes/Core.php");
    $core = new Core();

    require_once($core->getAppRoot() . "includes/EmailBankAccountList.php");
    require_once($core->getAppRoot() . "includes/billing/AccountEntryUtil.php");
    require_once($core->getAppRoot() . "includes/Database.php");
    require_once($core->getAppRoot() . "includes/billing/ChargesUtil.php");
    require_once($core->getAppRoot() . "includes/net/CommanderCrossbar.php");
    require_once($core->getAppRoot() . "includes/net/email/EmailUtil.php");
    require_once($core->getAppRoot() . "includes/event/EventCrossBar.php");
    require_once($core->getAppRoot() . "includes/dao/IpAccountDAO.php");

    try {
        $database = new Database(
            $core->getProperty(Core::DATABASE_HOST),
            $core->getProperty(Core::DATABASE_USERNAME),
            $core->getProperty(Core::DATABASE_PASSWORD),
            $core->getProperty(Core::DATABASE_NAME)
        );
    } catch (Exception $e) {
        openlog("NetProvider", LOG_PERROR, LOG_DAEMON);
        syslog(LOG_INFO, "Service: Cannot connect do database");
        closelog();
        exit();
    }

    $query = "SELECT * FROM `ipaccount` WHERE `IA_datetime`='2010-03-01 00:00:00'";
    $database->setQuery($query);
    $ipAccountList = $database->loadObjectList();
    foreach ($ipAccountList as $ipAccount) {
        $database->startTransaction();

        $ipAccount->IA_bytes_in = floor($ipAccount->IA_bytes_in / 2);
        $ipAccount->IA_bytes_out = floor($ipAccount->IA_bytes_out / 2);
        $ipAccount->IA_packets_in = floor($ipAccount->IA_packets_in / 2);
        $ipAccount->IA_packets_out = floor($ipAccount->IA_packets_out / 2);

        $database->updateObject("ipaccount", $ipAccount, "IA_ipaccountid", false, false);


        $ipAccount2 = new IpAccount();
        $ipAccount2->IA_ipid = $ipAccount->IA_ipid;
        $ipAccount2->IA_datetime = "2010-02-01 00:00:00";
        $ipAccount2->IA_bytes_in = $ipAccount->IA_bytes_in;
        $ipAccount2->IA_bytes_out = $ipAccount->IA_bytes_out;
        $ipAccount2->IA_packets_in  = $ipAccount->IA_packets_in;
        $ipAccount2->IA_packets_out = $ipAccount->IA_packets_out;

        $database->insertObject("ipaccount", $ipAccount2, "IA_ipaccountid", false);

        $database->commit();
    }
?>
