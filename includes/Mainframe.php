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
require_once($core->getAppRoot() . "includes/Database.php");

/**
 * MainFrame class
 */
class MainFrame {
    /** @var database Internal database class pointer */
    var $_db = null;
    /** @var object An object of path variables */
    var $_path = null;
    /** @var option */
    var $_option = null;
    /** @var action */
    var $_action = null;
    /** @var tstart */
    private float $_tstart;
    /** @var tvalue */
    private float $_tvalue;
    /** @var array messages */
    var $_msg = null;

    /**
    * Class constructor
    * @param database A database connection object
    * @param string The url option
    * @param string The path of the mos directory
    */
    public function __construct(&$db, $option, $basePath, $action) {

        $this->_db = &$db;
        $this->_option = $option;
        $this->_path = $basePath;
        $this->_action = $action;
        $this->_msg = array();
    }

    function getPath() {
        global $core;
        $indexFile = str_replace("com_", "", $this->_option);
        $path = $core->getAppRoot() . "modules/" . $this->_option . "/" . $indexFile . ".index.php";
        if (file_exists($path)) {
            define("VALID_MODULE", 1);
            return $path;
        } else {
            $this->_option = "com_admin";
            $indexFile = str_replace("com_", "", $this->_option);
            define("VALID_MODULE", 1);
            $path = $core->getAppRoot() . "modules/" . $this->_option . "/" . $indexFile . ".index.php";
            return $path;
        }
    }

    function timerStart() {
        $this->_tstart = Utils::getmicrotime();
    }

    function timerStop() {
        $this->_tvalue = Utils::getmicrotime() - $this->_tstart;
    }

    function getTimer() {
        return $this->_tvalue;
    }

    function setMessages($msg) {
        $this->_msg = $msg;
    }

    function getMessages() {
        return $this->_msg;
    }

    function getMsgPanel() {
        if (sizeOf($this->_msg)) {
            echo '<div style="border-left: 1px solid #ccc; border-right: 1px solid #ccc; padding: 10px;"><div id="message-box"><pre style="margin:0; padding:0;">';
            echo implode("<br/>", $this->getMessages());
            echo '</pre></div></div>';
        }
    }
} // End of MainFrame class
?>
