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
 * DateUtil
 */
class DateUtil {
    /** @var timestamp */
    private $_timestamp = null;
    private $_date = null;
    private $_isDebug = null;

    const DB_NULL_DATE = '0000-00-00';
    const DB_NULL_DATETIME = '0000-00-00 00:00:00';
    const DB_MIN_DATE = '1000-01-01';
    const DB_MAX_DATE = '9999-12-31';

    const FORMAT_MONTHLY = 'm/Y';
    const FORMAT_FULL = 'H:i:s d.m.Y';
    const FORMAT_DATE = 'd.m.Y';
    const FORMAT_SHORTDATE = 'd.m.y';
    const FORMAT_TIME = 'H:i:s';
    const FORMAT_SHORTTIME = 'H:i';

    const DB_DATE = 'Y-m-d';
    const DB_DATETIME = 'Y-m-d H:i:s';

    const YEAR = 1;
    const MONTH = 2;
    const DAY = 3;
    const HOUR = 4;
    const MINUTES = 5;
    const SECONDS = 6;

    /**
     * @param $date sets timestamp or parse database date format to timestamp
     */
    public function __construct($date=null) {
        global $core;

        $this->_isDebug = $core->getProperty(Core::SYSTEM_DEBUG);

        if ($date == null) {
            $this->_timestamp = time();
        } else if ($date == self::DB_NULL_DATE) {
            $this->_timestamp = null;
        } else if ($date == self::DB_NULL_DATETIME){
            $this->_timestamp = null;
        } else {
            $this->_timestamp = strtotime($date);
        }

        if ($this->_isDebug) {
            $this->_date = $this->getFormattedDate(self::DB_DATETIME);
        }
    }
    /**
     * Gets the value for a given time field.
     * @params field - the time field.
     */
    public function get($field) {
        if ($this->_timestamp == null) {
            return null;
        }
        switch ($field) {
            case self::YEAR :
                return intval(date("Y", $this->_timestamp));
                break;
            case self::MONTH :
                return intval(date("m", $this->_timestamp));
                break;
            case self::DAY :
                return intval(date("d", $this->_timestamp));
                break;
            case self::HOUR :
                return intval(date("H", $this->_timestamp));
                break;
            case self::MINUTES :
                return intval(date("i", $this->_timestamp));
                break;
            case self::SECONDS :
                return intval(date("s", $this->_timestamp));
                break;
        }
    }
    /**
     * Sets the time field with the given value.
     * @params field - the time field.
     * @params amount - the amount of date or time to be added to the field.
     */
    public function set($field, $value) {
        switch ($field) {
            case self::YEAR :
                $this->_timestamp = mktime(
                            date("H", $this->_timestamp),
                            date("i", $this->_timestamp),
                            date("s", $this->_timestamp),
                            date("m", $this->_timestamp),
                            date("d", $this->_timestamp),
                            $value
                            );
                break;
            case self::MONTH :
                $this->_timestamp = mktime(
                            date("H", $this->_timestamp),
                            date("i", $this->_timestamp),
                            date("s", $this->_timestamp),
                            $value,
                            date("d", $this->_timestamp),
                            date("Y", $this->_timestamp)
                            );
                break;
            case self::DAY :
                $this->_timestamp = mktime(
                            date("H", $this->_timestamp),
                            date("i", $this->_timestamp),
                            date("s", $this->_timestamp),
                            date("m", $this->_timestamp),
                            $value,
                            date("Y", $this->_timestamp)
                            );
                break;
            case self::HOUR :
                $this->_timestamp = mktime(
                            $value,
                            date("i", $this->_timestamp),
                            date("s", $this->_timestamp),
                            date("m", $this->_timestamp),
                            date("d", $this->_timestamp),
                            date("Y", $this->_timestamp)
                            );
                    break;
            case self::MINUTES :
                $this->_timestamp = mktime(
                            date("H", $this->_timestamp),
                            $value,
                            date("s", $this->_timestamp),
                            date("m", $this->_timestamp),
                            date("d", $this->_timestamp),
                            date("Y", $this->_timestamp)
                            );
                break;
            case self::SECONDS :
                $this->_timestamp = mktime(
                            date("H", $this->_timestamp),
                            date("i", $this->_timestamp),
                            $value,
                            date("m", $this->_timestamp),
                            date("d", $this->_timestamp),
                            date("Y", $this->_timestamp)
                            );
                break;
        }

        if ($this->_isDebug) {
            $this->_date = $this->getFormattedDate(self::DB_DATETIME);
        }
    }
    /**
     * Date Arithmetic function. Adds the specified (signed) amount of time to the given time field, based on the calendar's rules. For example, to subtract 5 days from the current time of the calendar, you can achieve it by calling:
     * @example add(Calendar.DATE, -5).
     * @params field - the time field.
     * @params amount - the amount of date or time to be added to the field.
     */
    public function add($field, $value) {
        switch ($field) {
            case self::YEAR :
                $this->_timestamp = mktime(
                            date("H", $this->_timestamp),
                            date("i", $this->_timestamp),
                            date("s", $this->_timestamp),
                            date("m", $this->_timestamp),
                            date("d", $this->_timestamp),
                            date("Y", $this->_timestamp) + $value
                            );
                break;
            case self::MONTH :
                $this->_timestamp = mktime(
                            date("H", $this->_timestamp),
                            date("i", $this->_timestamp),
                            date("s", $this->_timestamp),
                            date("m", $this->_timestamp) + $value,
                            date("d", $this->_timestamp),
                            date("Y", $this->_timestamp)
                            );
                break;
            case self::DAY :
                $this->_timestamp = mktime(
                            date("H", $this->_timestamp),
                            date("i", $this->_timestamp),
                            date("s", $this->_timestamp),
                            date("m", $this->_timestamp),
                            date("d", $this->_timestamp) + $value,
                            date("Y", $this->_timestamp)
                            );
                break;
            case self::HOUR :
                $this->_timestamp = mktime(
                            date("H", $this->_timestamp) + $value,
                            date("i", $this->_timestamp),
                            date("s", $this->_timestamp),
                            date("m", $this->_timestamp),
                            date("d", $this->_timestamp),
                            date("Y", $this->_timestamp)
                            );
                break;
            case self::MINUTES :
                $this->_timestamp = mktime(
                            date("H", $this->_timestamp),
                            date("i", $this->_timestamp) + $value,
                            date("s", $this->_timestamp),
                            date("m", $this->_timestamp),
                            date("d", $this->_timestamp),
                            date("Y", $this->_timestamp)
                            );
                break;
            case self::SECONDS :
                $this->_timestamp = mktime(
                            date("H", $this->_timestamp),
                            date("i", $this->_timestamp),
                            date("s", $this->_timestamp) + $value,
                            date("m", $this->_timestamp),
                            date("d", $this->_timestamp),
                            date("Y", $this->_timestamp)
                            );
                break;
        }

        if ($this->_isDebug) {
            $this->_date = $this->getFormattedDate(self::DB_DATETIME);
        }
    }
    /**
     * Gets this Calendar's current time.
     * @return the current time.
     */
    public function getTime() {
        return $this->_timestamp;
    }
    /**
     * Sets this Calendar's current time with the given Date.
     * @param date - the given Date.
     */
    public function setTime($timestamp) {
        $this->_timestamp = $timestamp;

        if ($this->_isDebug) {
            $this->_date = $this->getFormattedDate(self::DB_DATETIME);
        }
    }
    /**
     * Compares the time field records. Equivalent to comparing result of conversion to UTC.
     * @param when - the Calendar to be compared with this Calendar.
     * @return boolean true if the current time of this Calendar is after the time of Calendar when; false otherwise.
     */
    public function after($when) {
        if (!$when instanceof DateUtil) {
            throw new Exception('Parameter not instance of DateUtil');
        }
        if ($this->_timestamp == null || $when->_timestamp == null) {
            throw new Exception('One of DateUtil is not initialized');
        }
        return ($this->_timestamp > $when->_timestamp);
    }
    /**
     * Compares the time field records. Equivalent to comparing result of conversion to UTC.
     * @param when - the Calendar to be compared with this Calendar.
     * @return boolean true if the current time of this Calendar is before the time of Calendar when; false otherwise.
     */
    public function before($when) {
        if (!$when instanceof DateUtil) {
            throw new Exception('Parameter not instance of DateUtil');
        }
        if ($this->_timestamp == null || $when->_timestamp == null) {
            throw new Exception('One of DateUtil is not initialized');
        }
        return ($this->_timestamp < $when->_timestamp);
    }
    /**
     * @param $dateUtil
     * @return the value 0 if the argument Date is equal to this Date; a value less than 0 if this Date is before the Date argument; and a value greater than 0 if this Date is after the Date argument.
     */
    public function compareTo($dateUtil) {
        if (!$dateUtil instanceof DateUtil) {
            return null;
        }
        if ($this->_timestamp == $dateUtil->_timestamp) {
            return 0;
        } else if ($this->_timestamp < $dateUtil->_timestamp) {
            return -1;
        } else {
            return 1;
        }
    }
    public function getFormattedDate($format) {
        if ($format == null) {
            return "";
        } else if ($format == self::DB_DATE && $this->_timestamp == null) {
            return self::DB_NULL_DATE;
        } else if ($format == self::DB_DATETIME && $this->_timestamp == null) {
            return self::DB_NULL_DATETIME;
        } else if ($format == self::DB_DATETIME && $this->_timestamp == null) {
            return self::DB_NULL_DATETIME;
        } else if ($this->_timestamp == null) {
            return "";
        } else {
            if (!($dateString = date($format, $this->_timestamp))) $dateString = "";
            return $dateString;
        }
    }
    public function parseDate($dateString, $format) {
        $matches = null;
        if ($format == self::FORMAT_MONTHLY) {
            if (mb_ereg("^([[:digit:]]{1,2})/([[:digit:]]{4})$", $dateString, $matches)) {
                $this->_timestamp = mktime(0, 0, 0, $matches[1], 1, $matches[2]);
            } else {
                $this->_timestamp = null;
                throw new Exception("Date parse Error: '$dateString', format: '$format'");
            }
        } else if ($format == self::FORMAT_FULL) {
            if (mb_ereg("^([[:digit:]]{1,2}):([[:digit:]]{1,2}):([[:digit:]]{1,2}) ([[:digit:]]{1,2}).([[:digit:]]{1,2}).([[:digit:]]{4})$", $dateString, $matches)) {
                $this->_timestamp = mktime($matches[1], $matches[2], $matches[3], $matches[5], $matches[4], $matches[6]);
            } else {
                $this->_timestamp = null;
                throw new Exception("Date parse Error: '$dateString', format: '$format'");
            }
        } else if ($format == self::FORMAT_DATE) {
            if (mb_ereg("^([[:digit:]]{1,2}).([[:digit:]]{1,2}).([[:digit:]]{4})$", $dateString, $matches)) {
                $this->_timestamp = mktime(0, 0, 0, $matches[2], $matches[1], $matches[3]);
            } else {
                $this->_timestamp = null;
                throw new Exception("Date parse Error: '$dateString', format: '$format'");
            }
        } else {
            $this->_timestamp = null;
            throw new Exception("Unsupported date format '$format'");
        }

        if ($this->_isDebug) {
            $this->_date = $this->getFormattedDate(self::DB_DATETIME);
        }
    }

    function __toString() {
        return $this->getFormattedDate(self::DB_DATETIME);
    }
} // End of DateUtil class
?>
