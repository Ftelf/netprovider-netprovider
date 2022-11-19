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
 * AppContext
 */
class AppContext {
    private $option;
    private $params;
    private $messages;

    public function __construct() {
        $this->option = 'com_admin';
        $this->params = array();
        $this->messages = array();
    }
    public function setOption($option) {
        $this->option = $option;
    }
    public function getOption() {
        return $this->option;
    }
    public function setParam($key, $value) {
        $this->params[$key] = $value;
    }
    public function getParam($key) {
        return $this->params[$key];
    }
    public function cleanParams() {
        $this->params = array();
    }
    public function insertMessage($message) {
        $this->messages[] = $message;
    }
    public function insertMessages($messages) {
        $this->messages = array_merge($this->messages, $messages);
    }
    public function getMessages() {
        return $this->messages;
    }
    public function cleanMessages() {
        $this->messages = array();
    }
} // End of AppContext class
?>
