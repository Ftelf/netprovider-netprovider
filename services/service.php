#!/usr/bin/php
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

require_once __DIR__ . "/../includes/Core.php";
putenv("LANGUAGE=");
$core = new Core();
require_once $core->getAppRoot() . "includes/EmailBankAccountList.php";
require_once $core->getAppRoot() . "includes/billing/AccountEntryUtil.php";
require_once $core->getAppRoot() . "includes/Database.php";
require_once $core->getAppRoot() . "includes/billing/ChargesUtil.php";
require_once $core->getAppRoot() . "includes/net/CommanderCrossbar.php";
require_once $core->getAppRoot() . "includes/net/email/EmailUtil.php";
require_once $core->getAppRoot() . "includes/event/EventCrossBar.php";
require_once $core->getAppRoot() . "includes/dao/IpAccountDAO.php";

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

// Init Event crossbar
$eventCrossBar = new EventCrossBar();

$scheduler = new Service($argv);
$scheduler->exec();


/**
 * Service
 */
class Service
{
    public bool $isRunPayments;

    public bool $isProceedNetworking;

    public bool $isIPFilterDown;
    public bool $isIPFilterUp;

    public bool $isIPAccount;

    public bool $isCleanUp;

    private bool $isDryRun;

    public function __construct($argv)
    {

        $this->isDryRun = in_array('--dry-run', $argv, true);

        $this->isRunPayments = in_array('--proceed-payments', $argv, true);

        $this->isProceedNetworking = in_array('--proceed-networking', $argv, true);

        $this->isIPFilterDown = in_array('--ip-filter-down', $argv, true);
        $this->isIPFilterUp = in_array('--ip-filter-up', $argv, true);

        $this->isIPAccount = in_array('--ip-account', $argv, true);

        $this->isCleanUp = in_array('--clean-up', $argv, true);
    }

    public function exec(): void
    {
        global $core, $database;

        openlog("NetProvider", LOG_PERROR, LOG_DAEMON);

        try {
            if ($this->isRunPayments) {
                $this->payments();
                $msg = "Payments service completed successfully";
                syslog(LOG_INFO, $msg);
                $database->log($msg, Log::LEVEL_INFO);
            }

            if ($this->isProceedNetworking) {
                $this->proceedNetworking();

                $msg = "networking service completed successfully";
                syslog(LOG_INFO, $msg);
                $database->log($msg, Log::LEVEL_INFO);
            }

            if (!$this->isProceedNetworking && $this->isIPFilterDown) {
                $this->ipFilterDown();

                $msg = "ip filter down service completed successfully";
                syslog(LOG_INFO, $msg);
                $database->log($msg, Log::LEVEL_INFO);
            }

            if (!$this->isProceedNetworking && $this->isIPFilterUp) {
                $this->ipFilterUp();

                $msg = "ip filter up service completed successfully";
                syslog(LOG_INFO, $msg);
                $database->log($msg, Log::LEVEL_INFO);
            }

            if (!$this->isProceedNetworking && $this->isIPAccount) {
                $this->ipAccount();
                $msg = "ip accounting service completed successfully";
                syslog(LOG_INFO, $msg);
                $database->log($msg, Log::LEVEL_INFO);
            }

            if ($this->isCleanUp) {
                $this->cleanUp();
                $msg = "database cleanup completed successfully";
                syslog(LOG_INFO, $msg);
                $database->log($msg, Log::LEVEL_INFO);
            }
        } catch (Exception $e) {
            $msg = "service fail: " . $e->getMessage();

            echo $msg;
            syslog(LOG_ERR, $msg);
            $database->log($msg, Log::LEVEL_ERROR);

            if ($core->getProperty(Core::SEND_EMAIL_ON_CRITICAL_ERROR)) {
                $emailUtil = new EmailUtil();
                try {
                    $emailUtil->sendEmailMessage($core->getProperty(Core::SUPERVISOR_EMAIL), "Net provider error", $msg);
                } catch (Exception $e) {
                    syslog(LOG_ERR, "Send email with error failed: " . $e->getMessage());
                }
            }
        }

        closelog();
    }

    /**
     * @return void
     * @throws Exception
     */
    public function payments(): void
    {
        if ($this->isDryRun) {
            return;
        }

        // Download new BankAccountLists
        $bankAccounts = BankAccountDAO::getBankAccountArray();

        foreach ($bankAccounts as $bankAccount) {
            $emailBankAccountList = new EmailBankAccountList($bankAccount);
            $emailBankAccountList->downloadNewAccountLists();
            $emailBankAccountList->importBankAccountEntries();

            $accountEntryUtil = new AccountEntryUtil($bankAccount);
            $accountEntryUtil->proceedAccountEntries();
        }

        $chargesUtil = new ChargesUtil();
        $chargesUtil->createBlankChargeEntries();
        $chargesUtil->proceedCharges(true);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function proceedNetworking(): void
    {
        $this->ipFilterUp();
    }

    /**
     * @return void
     * @throws Exception
     */
    public function ipFilterDown(): void
    {
        $commanderCrossbar = new CommanderCrossbar();
        $commanderCrossbar->setDryRun($this->isDryRun);
        $commanderCrossbar->inicialize();

        $this->showResult($commanderCrossbar->ipFilterDown());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function ipFilterUp(): void
    {
        $commanderCrossbar = new CommanderCrossbar();
        $commanderCrossbar->setDryRun($this->isDryRun);
        $commanderCrossbar->inicialize();

        $this->showResult($commanderCrossbar->ipFilterUp());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function ipAccount(): void
    {
        if ($this->isDryRun) {
            return;
        }

        $commanderCrossbar = new CommanderCrossbar();
        $commanderCrossbar->setDryRun($this->isDryRun);
        $commanderCrossbar->inicialize();

        $commanderCrossbar->accountIP();
    }

    /**
     * @return void
     * @throws Exception
     */
    public function cleanUp(): void
    {
        if ($this->isDryRun) {
            return;
        }

        function processNewIPAccount($ip, $date, $ipAccountEntry): void
        {
            global $database;

            $ipAccount = new IpAccount();
            $ipAccount->IA_ipid = $ip->IP_ipid;
            $ipAccount->IA_datetime = $date->getFormattedDate(DateUtil::DB_DATETIME);
            $ipAccount->IA_bytes_in = $ipAccountEntry->IA_bytes_in;
            $ipAccount->IA_bytes_out = $ipAccountEntry->IA_bytes_out;
            $ipAccount->IA_packets_in = $ipAccountEntry->IA_packets_in;
            $ipAccount->IA_packets_out = $ipAccountEntry->IA_packets_out;

            $database->insertObject("ipaccount", $ipAccount, "IA_ipaccountid");
            $database->commit();
        }

        global $database;

        $now = new DateUtil();
        $currentMonth = $now->get(DateUtil::MONTH);
        $currentYear = $now->get(DateUtil::YEAR);
        $currentThreshold = 12 * $currentYear + $currentMonth;

        $ips = IpDAO::getIpArray();
        foreach ($ips as $ip) {
            $ipAccountEntries = IpAccountDAO::getIpAccountMonthSumArrayByIpID($ip->IP_ipid, $currentThreshold - 2);
            foreach ($ipAccountEntries as $ipAccountEntry) {
                if ($ipAccountEntry->count > 1) {
                    $database->startTransaction();

                    IpAccountDAO::removeIpAccountByYearMonth($ip->IP_ipid, $ipAccountEntry->year, $ipAccountEntry->month);

                    $newDate = new DateUtil();
                    $newDate->set(DateUtil::YEAR, $ipAccountEntry->year);
                    $newDate->set(DateUtil::MONTH, $ipAccountEntry->month);
                    $newDate->set(DateUtil::DAY, 1);
                    $newDate->set(DateUtil::HOUR, 0);
                    $newDate->set(DateUtil::MINUTES, 0);
                    $newDate->set(DateUtil::SECONDS, 0);

                    processNewIPAccount($ip, $newDate, $ipAccountEntry);
                }
            }

            $ipAccountEntries = IpAccountDAO::getIpAccountDaySumArrayByIpID($ip->IP_ipid, $currentThreshold - 1);
            foreach ($ipAccountEntries as $ipAccountEntry) {
                if ($ipAccountEntry->count > 1) {
                    $database->startTransaction();

                    IpAccountDAO::removeIpAccountByYearMonthDay($ip->IP_ipid, $ipAccountEntry->year, $ipAccountEntry->month, $ipAccountEntry->day);

                    $newDate = new DateUtil();
                    $newDate->set(DateUtil::YEAR, $ipAccountEntry->year);
                    $newDate->set(DateUtil::MONTH, $ipAccountEntry->month);
                    $newDate->set(DateUtil::DAY, $ipAccountEntry->day);
                    $newDate->set(DateUtil::HOUR, 0);
                    $newDate->set(DateUtil::MINUTES, 0);
                    $newDate->set(DateUtil::SECONDS, 0);

                    processNewIPAccount($ip, $newDate, $ipAccountEntry);
                }
            }
        }
    }

    public function showResult($results): void
    {
        foreach ($results as $result) {
            echo "\n" . $result[0];

            if (isset($result[1]) && $result[1]) {
                echo "\n" . $result[1];
            }

            if (isset($result[2]) && $result[2]) {
                echo "\n" . $result[2];
            }
        }
        echo "\n";
    }
} // End of Service class
