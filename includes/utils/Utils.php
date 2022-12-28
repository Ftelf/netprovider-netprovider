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
 * Utils
 */
class Utils {
    /**
     * Utility function to return a value from a named array or a specified default
     */
    public static function getParam(&$arr, $name, $def=null, $mask=0) {
        if (isset($arr[$name])) {
            if (is_string( $arr[$name] )) {
                if (!($mask&_NP_NOTRIM)) {
                    $arr[$name] = trim( $arr[$name] );
                }
                if (!($mask&_NP_ALLOWHTML)) {
                    $arr[$name] = strip_tags( $arr[$name] );
                }
                $arr[$name] = addslashes( $arr[$name] );
            }
            return $arr[$name];
        } else {
            return $def;
        }
    }

    public static function getmicrotime(): float {
        list($usec, $sec) = explode(" ",microtime());
        return ((float)$usec + (float)$sec);
    }

    public static function is_email($email) {
        $rBool=false;

        if (preg_match("/[\w\.\-]+@\w+[\w\.\-]*?\.\w{1,4}/", $email)){
            $rBool=true;
        }
        return $rBool;
    }

    public static function stringAsLineArray($text) {
        $arr = array();
        $tok = strtok($text, "\r\n");
        while ($tok) {
            $arr[] = $tok;
            $tok = strtok("\r\n");
        }
        return $arr;
    }
} // End of Utils class
?>
