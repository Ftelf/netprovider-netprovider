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

/**
 * Core
 */
Class Core {
    private $_appRoot = null;
    private $ini = null;

    const DATABASE_HOST = "host";
    const DATABASE_NAME = "database";
    const DATABASE_USERNAME = "username";
    const DATABASE_PASSWORD = "password";

    const SMTP_SERVER = "Server";
    const SMTP_PORT = "Port";
    const SMTP_AUTH = "Auth";
    const SMTP_FROM = "From";
    const SMTP_USERNAME = "SMTP_username";
    const SMTP_PASSWORD = "SMTP_password";

    const UI_TITLE = "Title";
    const UI_VENDOR = "Vendor";
    const UI_LOCALE = "Locale";

    const SYSTEM_DEBUG = "Debug";

    const BLANK_CHARGES_ADVANCE_COUNT = "Blank charges advance count";
    const ENABLE_INVOICE_MODULE = "Enable invoice module";
    const ENABLE_VAT_PAYER_SPECIFICS = "Enable VAT Payer specifics";
    const ALLOW_FIRM_REGISTRATION = "Allow firm registration";

    const GLOBAL_QOS_ENABLED = "global qos enabled";
    const GLOBAL_IP_FILTER_ENABLED = "global IP filter enabled";
    const QOS_BANDWIDTH_MARGIN_PERCENT = "bandwidth margin";
    const REJECT_UNKNOWN_IP = "Reject unknown IP";
    const REDIRECT_UNKNOWN_IP = "Redirect unknown IP";
    const REDIRECT_TO_IP = "Redirect to IP";
    const ALLOWED_HOSTS = "Allowed hosts";

    const SEND_EMAIL_ON_CRITICAL_ERROR = "Send EMail on critical error";
    const SUPERVISOR_EMAIL = "Supervisor EMail";

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
        echo '<script language="JavaScript" type="text/javascript">';
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