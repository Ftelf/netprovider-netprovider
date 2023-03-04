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
 * Core
 */
Class Core {
    private $_appRoot = null;
    private $ini = null;

    // Database
    const DATABASE_HOST = "Database Host";
    const DATABASE_NAME = "Database Name";
    const DATABASE_USERNAME = "Database Username";
    const DATABASE_PASSWORD = "Database Password";

    // Financial
    const BLANK_CHARGES_ADVANCE_COUNT = "Blank charges advance count";
    const ENABLE_VAT_PAYER_SPECIFICS = "Enable VAT Payer specifics";
    const ALLOW_FIRM_REGISTRATION = "Allow firm registration";

    // SMTP
    const SMTP_SERVER = "SMTP Server";
    const SMTP_PORT = "SMTP Port";
    const SMTP_AUTH = "SMTP Auth";
    const SMTP_USERNAME = "SMTP Username";
    const SMTP_PASSWORD = "SMTP Password";
    const SMTP_FROM = "SMTP From";
    const SUPERVISOR_EMAIL = "SMTP Supervisor EMail";
    const SEND_EMAIL_ON_CRITICAL_ERROR = "SMTP Send EMail on critical error";

    // UI
    const UI_TITLE = "Title";
    const UI_VENDOR = "Vendor";
    const UI_LOCALE = "Locale";

    // SMS
    const SMS_USERNAME = "SMS Username";
    const SMS_PASSWORD = "SMS Password";

    // Network Device
    const NETWORK_DEVICE_PLATFORM = "Network Device Platform";
    const NETWORK_DEVICE_HOST = "Network Device Host";
    const NETWORK_DEVICE_PORT = "Network Device Port";
    const NETWORK_DEVICE_LOGIN = "Network Device Login";
    const NETWORK_DEVICE_PASSWORD = "Network Device Password";
    const NETWORK_DEVICE_WAN_INTERFACE = "Network Device WAN interface";
    const NETWORK_DEVICE_COMMAND_SUDO = "Network Device Command sudo";
    const NETWORK_DEVICE_COMMAND_IPTABLES = "Network Device Command iptables";
    const NETWORK_DEVICE_IP_ACCOUNTING = "Network Device IP accounting";
    const NETWORK_DEVICE_IP_FILTER = "Network Device IP filter";

    // System
    const SYSTEM_DEBUG = "Debug";

    public function __construct() {
        $this->_appRoot = realpath(dirname(__FILE__) . '/../') . '/';

        mb_regex_encoding("UTF-8");
        mb_internal_encoding("UTF-8");

        $this->ini = parse_ini_file($this->_appRoot . 'config/netprovider.ini', false);

        // I18N support information here()
        $locale = $this->getProperty(self::UI_LOCALE);

        putenv("LC_ALL=$locale");
        putenv("LANG=$locale");
        putenv("LANGUAGE=$locale");

        setlocale(LC_ALL, $locale);

        // Set the text domain as 'messages'
        $domain = 'messages';
        bindtextdomain($domain, $this->_appRoot . "translation");
        bind_textdomain_codeset($domain, "UTF-8");
        textdomain($domain);
    }

    static function redirect($url, $msg=null) {
        // specific filters
        if ($msg) {
            echo "<script>alert('" . str_replace("'", "\\'", $msg) . "');</script>";
            ob_end_flush();
        }
        if (headers_sent()) {
            echo "<script>document.location.href='$url';</script>\n";
        } else {
            @ ob_end_clean(); // clear output buffer
            header("Location: $url");
        }
        exit();
    }

    static function alert($msg) {
        echo "<script>alert('" . str_replace("'", "\\'", $msg) . "');</script>";
    }

    static function backWithAlert($msg=null) {
        echo '<script type="text/javascript">';
        if ($msg) {
            echo "alert('" . str_replace("'", "\\'", $msg) . "');";
        }
        echo 'window.history.go(-1);</script>';
        exit();
    }

    static function dprint($var) {
        echo '<pre>';
        print_r($var);
        echo '</pre>';
    }

    function getAppRoot() {
        return $this->_appRoot;
    }

    function getConfig() {
        return $this->ini;
    }

    /**
     * @param $property
     * @return mixed
     * @throws PropertyException
     */
    function getProperty($property) {
        if (isset($this->ini[$property])) {
            return $this->ini[$property];
        } else {
            throw new PropertyException("Misconfigured property: $property");
        }
    }

    function getBooleanProperty($property) {
        if (isset($this->ini[$property])) {
            return ($this->ini[$property]) ? true : false;
        } else {
            throw new PropertyException("Misconfigured property: $property");
        }
    }
} // End of Core class

class PropertyException extends Exception {
}
?>
