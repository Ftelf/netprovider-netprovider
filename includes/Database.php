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

global $core;
require_once $core->getAppRoot() . "includes/tables/Log.php";
require_once $core->getAppRoot() . "includes/utils/DateUtil.php";
require_once $core->getAppRoot() . "includes/utils/Utils.php";

/**
 * Database
 */
class Database
{
    /**
     * @var string Internal variable to hold the query sql
     */
    var $_sql='';
    /**
     * @var Internal variable to hold the connector resource
     */
    var $_mysqli;

    /**
     * Database object constructor
     *
     * @param string Database host
     * @param string Database user name
     * @param string Database user password
     * @param string Database name
     * @param string Common prefix for all tables
     */
    public function __construct($host, $username, $password, $database)
    {
        $this->_mysqli = new mysqli($host, $username, $password, $database);

        if (mysqli_connect_errno()) {
            throw new Exception("Connect failed: " . mysqli_connect_error());
        }

        $this->query('SET CHARACTER SET utf8');
        $this->query("SET NAMES 'utf8'");
    }

    /**
     * Get a quoted database escaped string
     *
     * @return string
     */
    function quote($text)
    {
        return '\'' . $this->_mysqli->escape_string($text) . '\'';
    }

    /**
     * Sets the SQL query string for later execution.
     *
     * @param string The SQL query
     */
    function setQuery($sql)
    {
        $this->_sql = $sql;
    }

    /**
     * @return string The current value of the internal SQL vairable
     */
    function getQuery()
    {
        return "<pre>" . htmlspecialchars($this->_sql) . "</pre>";
    }
    /**
     * Execute the query
     *
     * @return mixed A database resource if successful, FALSE if not.
     */
    function query($sql=null)
    {
        if (!$sql) { $sql = $this->_sql;
        }
        if (($result = $this->_mysqli->query($sql)) !== false) {
            return $result;
        } else {
            throw new Exception("Error no: " . $this->_mysqli->errno . " " . $this->_mysqli->error . " SQL:" . $this->_sql);
        }
    }

    function query_batch($sqlArray)
    {
        try {
            $this->query('START TRANSACTION;');
            foreach ($sqlArray as $sql) {
                $this->query($sql);
            }
            $this->query('COMMIT;');
        } catch (Exception $e) {
            $this->query('ROLLBACK;');
            throw new Exception("Transaction failed at: " . $e->getMessage());
        }
    }

    function startTransaction()
    {
        $this->query('START TRANSACTION;');
    }

    function rollback()
    {
        $this->query('ROLLBACK;');
    }

    function commit()
    {
        $this->query('COMMIT;');
    }

    /**
     * Diagnostic function
     */
    function explain()
    {
        if (!($cur = $this->query("EXPLAIN ".$this->_sql))) { return null;
        }
        $headline = $header = $body = '';
        $buf = '<table cellspacing="1" cellpadding="2" border="0" bgcolor="#000000" align="center">';
        $buf .= $this->getQuery("EXPLAIN ".$this->_sql);
        while ($row = $cur->fetch_assoc()) {
            $body .= "<tr>";

            foreach ($row as $k=>$v) {
                if ($headline == '') { $header .= "<th bgcolor=\"#ffffff\">$k</th>";
                }
                $body .= "<td bgcolor=\"#ffffff\">$v</td>";
            }
            $headline = $header;
            $body .= "</tr>";
        }
        $buf .= "<tr>$headline</tr>$body</table><br />&nbsp;";
        $cur->close();

        return "<div style=\"background-color:#FFFFCC\" align=\"left\">$buf</div>";
    }

    /**
     * Load an array of retrieved database objects or values
     *
     * @param  int Database cursor
     * @param  string The field name of a primary key
     * @return array If <var>key</var> is empty as sequential list of returned records.
     * If <var>key</var> is not empty then the returned array is indexed by the value
     * the database key.  Returns <var>null</var> if the query fails.
     */
    function &retrieveResults($key='', $max=0, $result_type='row')
    {
        $results = array();
        $sql_method = 'fetch_'.$result_type;
        $result = $this->query();
        while ($row = $result->$sql_method()) {
            if ($key != '') {
                $results[$row->$key] = $row;
            } else {
                $results[] = $row;
            }
            if ($max && count($results) >= $max) { break;
            }
        }
        $result->close();
        return $results;
    }

    /**
     * This method loads the first field of the first row returned by the query.
     *
     * @return The value returned in the query or null if the query failed.
     */
    function loadResult()
    {
        $results =& $this->retrieveResults('', 1, 'row');
        if (count($results)) { return $results[0][0];
        } else { return null;
        }
    }

    /**
     * Copy the named array content into the object as properties
     * only existing properties of object are filled. when undefined in hash, properties wont be deleted
     *
     * @param array the input array
     * @param obj byref the object to fill of any class
     * @param string
     * @param boolean
     */
    static function bindArrayToObject($array, &$obj)
    {
        if (!is_array($array) || !is_object($obj)) {
            throw new Exception("bind failed.");
        }
        foreach (get_object_vars($obj) as $k => $v) {
            if ($k[0] !== '_' && isset($array[$k])) {
                $obj->$k = $array[$k];
            }
        }
    }

    /**
     * This global function loads the first row of a query into an object
     *
     * If an object is passed to this function, the returned row is bound to the existing elements of <var>object</var>.
     * If <var>object</var> has a value of null, then all of the returned query fields returned in the object.
     *
     * @param  string The SQL query
     * @param  object The address of variable
     * @throws Exception if operation fail
     */
    function loadObject(&$object)
    {
        if ($object != null) {
            $results =& $this->retrieveResults('', 1, 'assoc');
            if (count($results)) {
                self::bindArrayToObject($results[0], $object);
                return;
            }
        } else {
            $results =& $this->retrieveResults('', 1, 'object');
            if (count($results)) {
                $object = $results[0];
                return;
            }
        }
        throw new Exception("Object cannot be loaded :" . $this->_sql);
    }

    /**
     * Load a list of database objects
     *
     * @param  string The field name of a primary key
     * @return array If <var>key</var> is empty as sequential list of returned records.
     * If <var>key</var> is not empty then the returned array is indexed by the value
     * the database key.  Returns <var>null</var> if the query fails.
     */
    function loadObjectList($key='')
    {
        $results =& $this->retrieveResults($key, 0, 'object');
        return $results;
    }

    /**
     * Document::db_insertObject()
     *
     * { Description }
     *
     * @param [type] $keyName
     * @param [type] $verbose
     */
    function insertObject($table, &$object, $keyName=null, $verbose=false)
    {
        $fmtsql = "INSERT INTO `$table` ( %s ) VALUES ( %s ) ";
        $fields = array();
        foreach (get_object_vars($object) as $k => $v) {
            if (is_array($v) || is_object($v) || $v === null || $k[0] == '_') { continue;
            }
            $fields[] = "`$k`";
            if ($k == $keyName) { $values[] = "NULL";
            } else { $values[] = $this->quote($v);
            }
        }
        if (!isset($fields)) {
            throw new Exception('class database method insertObject - no fields');
        }
        $this->setQuery(sprintf($fmtsql, implode(", ", $fields), implode(", ", $values)));
        ($verbose) && print "$this->_sql<br />\n";
        $this->query();
        $id = $this->_mysqli->insert_id;
        ($verbose) && print "id=[$id]<br />\n";
        if ($keyName && $id) { $object->$keyName = $id;
        }
    }

    /**
     * Document::db_updateObject()
     *
     * { Description }
     *
     * @param [type] $updateNulls
     */
    function updateObject($table, &$object, $keyName, $updateNulls=true, $verbose=false)
    {
        $fmtsql = "UPDATE `$table` SET %s WHERE %s";
        $tmp = array();
        foreach (get_object_vars($object) as $k => $v) {
            if (is_array($v) || is_object($v) || $k[0] == '_' OR ($v === null AND !$updateNulls)) { continue;
            }

            if ($k == $keyName) { // PK not to be updated
                $where = "$keyName=" . $this->quote($v);
                continue;
            }
            if ($v === null) {
                $tmp[] = "`$k`=NULL";
            } else {
                $tmp[] = "`$k`=" . $this->quote($v);
            }
        }
        if (!isset($tmp)) { return true;
        }
        if (!isset($where)) {
            throw new Exception('database class updateObject method - no key value');
        }
        $this->setQuery(sprintf($fmtsql, implode(",", $tmp), $where));
        ($verbose) && print "$this->_sql<br />\n";
        $this->query();
    }

    function getInsertid()
    {
        return $this->_mysqli->insert_id;
    }

    /**
     *   binds a named array/hash to this object
     *
     *   can be overloaded/supplemented by the child class
     *
     * @param  array $hash named array
     * @return null|string null is operation was satisfactory, otherwise returns an error
     */
    static function bind($array, &$object)
    {
        return Database::bindArrayToObject($array, $object);
    }

    function log($text, $level=0)
    {
        $logDate = new DateUtil();

        $log = new Log();

        $log->LO_personid = Utils::getParam($_SESSION, 'SE_personid', 0);
        $log->LO_log = $text;
        $log->LO_datetime = $logDate->getFormattedDate(DateUtil::DB_DATETIME);
        $log->LO_level = $level;

        $this->insertObject("log", $log, "LO_logid", false);
    }
} // End of Database class
?>
