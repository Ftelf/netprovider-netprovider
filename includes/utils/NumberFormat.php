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
 * NumberFormat class
 */
class NumberFormat
{
    /**
     * @var database Internal database class pointer
     */
    var $_value = null;
    /**
     * @var object An object of path variables
     */
    var $_unit = null;

    /**
     * Class constructor
     *
     * @param database A database connection object
     * @param string The url option
     * @param string The path of the mos directory
     */
    public function __construct($value, $unit)
    {
        $this->_value = $value;
        $this->_unit = $unit;
    }

    public static function formatMoney($value)
    {
        return number_format($value, 2, ',', ' ');
    }

    public static function parseMoney($value)
    {
        $dbAmount = str_replace(" ", "", $value);
        $exp = explode(",", $dbAmount);
        $amount = implode(".", $exp);
        if (is_numeric($amount)) {
            return $amount;
        } else {
            throw new Exception("Number in incorrect format");
        }
    }

    public static function parseInteger($value)
    {
        if (is_numeric($value)) {
            return (int)$value;
        } else {
            throw new Exception("Integer in incorrect format");
        }
    }

    public static function formatMB($value)
    {
        return number_format($value / 1048576, 2, ',', ' ') . " MB";
    }

    public static function formatMBps($value)
    {
        return number_format($value / 1024, 2, ',', ' ') . " KBps";
    }

    public static function formatMbitps($value)
    {
        return number_format($value / 131072, 2, ',', ' ') . " Mbps";
    }

    public static function parseSI($value, $unit)
    {
        $matches = null;
        if (mb_ereg("^([[:digit:]]{1,})(\.[[:digit:]]{1,})?\s{0,1}(T|G|M|K|k)?$unit$", $value, $matches)) {
            $times = 1;
            switch ($matches[3]) {
            case "":
                break;
            case "k":
            case "K":
                $times = 1000;
                break;
            case "M":
                $times = 1000*1000;
                break;
            case "G":
                $times = 1000*1000*1000;
                break;
            case "T":
                $times = 1000*1000*1000*1000;
                break;
            default:
                return null;
            }
            $result = doubleval(($matches[1] + $matches[2]) * $times);
            return $result;
        } else {
            return null;
        }
    }
} // End of NumberFormat class
?>
