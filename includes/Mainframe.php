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
    var $_tstart;
    /** @var tvalue */
    var $_tvalue;
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