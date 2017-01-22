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
 * AppContext
 */
class AppContext {
	private $option;
	private $params;
	private $messages;

	public function AppContext() {
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