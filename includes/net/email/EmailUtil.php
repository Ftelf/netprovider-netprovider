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

global $core;
require_once "Mail.php";
require_once($core->getAppRoot() . "includes/dao/PersonDAO.php");
require_once($core->getAppRoot() . "includes/dao/MessageDAO.php");
require_once($core->getAppRoot() . "includes/utils/DateUtil.php");
require_once "Mail/mime.php";

/**
 * ChargesUtil
 */
class EmailUtil {
	private $_database;
	private $_messages = array();
	private $_smtp_server;
	private $_smtp_port;
	private $_smtp_auth;
	private $_smtp_username;
	private $_smtp_password;
	private $_email_from;

	public function __construct() {
		global $database, $core;
		$this->_database = $database;
		$this->_smtp_server = $core->getProperty(Core::SMTP_SERVER);
		$this->_smtp_port = $core->getProperty(Core::SMTP_PORT);
		$this->_smtp_auth = $core->getBooleanProperty(Core::SMTP_AUTH);
		$this->_smtp_username = $core->getProperty(Core::SMTP_USERNAME);
		$this->_smtp_password = $core->getProperty(Core::SMTP_PASSWORD);
		$this->_email_from = $core->getProperty(Core::SMTP_FROM);
	}
	
	public function sendMessage($personid, $subject, $body) {
		global $database;
		
		$now = new DateUtil();
		
		$message = new Message();
		$message->ME_personid = $personid;
		$message->ME_subject = $subject;
		$message->ME_body = $body;
		$message->ME_datetime = $now->getFormattedDate(DateUtil::DB_DATETIME);
		$message->ME_status = Message::STATUS_PENDING;
		
		$person = PersonDAO::getPersonByID($message->ME_personid);
		
		if ($person->PE_email) {
			$this->sendEmailMessage($person->PE_email, $subject, $body);
		}
	}
	
	public function queueMessage($person, $subject, $body, $attachments) {
		global $database;
		
		$now = new DateUtil();
		
		try {
			$message = new Message();
			$message->ME_personid = $person->PE_personid;
			$message->ME_subject = $subject;
			$message->ME_body = $body;
			$message->ME_datetime = $now->getFormattedDate(DateUtil::DB_DATETIME);
			$message->ME_status = Message::STATUS_PENDING;
			
			$database->startTransaction();
			
			$database->insertObject("message", $message, "ME_messageid", false);
			
			if ($attachments) {
				foreach ($attachments as &$attachment) {
					$messageAttachment = new MessageAttachment();
					$messageAttachment->MA_messageid = $message->ME_messageid;
					$messageAttachment->MA_name = $attachment['NAME'];
					$messageAttachment->MA_attachment = $attachment['ATTACHMENT'];
					
					$database->insertObject("messageattachment", $messageAttachment, "MA_messageattachmentid", false);
				}
			}
			 
			$database->commit();
		} catch (Exception $e) {
			$database->rollback();
			$msg = "Error creating Message: " . $e->getMessage();
			$this->_messages[] = $msg;
			$database->log($msg, LOG::LEVEL_ERROR);
		}
	}
	
	public function sendMessages() {
		global $database;
		
		$messages = MessageDAO::getPendingMessageArray();
		
		foreach ($messages as &$message) {
			try {
				$person = PersonDAO::getPersonByID($message->ME_personid);
				
				if ($person->PE_email) {
					$messageAttachments = MessageAttachmentDAO::getMessageAttachmentArrayForAttachmentForMessageID($message->ME_messageid);
					
					$this->sendEmailMessage($person->PE_email, $message->ME_subject, $message->ME_body, $messageAttachments);
					
					$message->ME_status = Message::STATUS_SENDED;
					$database->updateObject("message", $message, "ME_messageid", false, false);
					
					$database->log(_("Email notification sent").": ".$message->ME_body, Log::LEVEL_INFO);
				}
			} catch (Exception $e) {
				$message->ME_status = Message::STATUS_CANNOT_BE_SEND;
				$database->updateObject("message", $message, "ME_messageid", false, false);
				$msg = "Error sending message: " . $e->getMessage();
				$database->log($msg, Log::LEVEL_ERROR);
				throw $e;
			}
		}
	}
	
	public function sendEmailMessage($recipient, $subject, $body, $messageAttachments=null) {
		$headers = array (
			'From' => $this->_email_from,
			'To' => $recipient,
			'Subject' => $subject
		);
		
		$mime = new Mail_mime();
		$mime->setTXTBody($body);

		if ($messageAttachments != null) {
			foreach($messageAttachments as $messageAttachment) {
				$filename = $messageAttachment->MA_name;
				if (strlen($filename) > 61) {
					$filename = substr($filename, 0, 57) . ".pdf";
				}
				$mime->addAttachment($messageAttachment->MA_attachment, 'application/octet-stream', $messageAttachment->MA_name, false, 'base64', 'attachments');
			}
		}
		
		$body2 = $mime->get(array("head_charset" => "UTF-8", "text_charset" => "UTF-8", "html_charset" => "UTF-8", "ignore_iconv" => true));
		$headers2 = $mime->headers($headers);
		
		$smtp = Mail::factory(
			'smtp',
			array (
				'host' => $this->_smtp_server,
				'port' => $this->_smtp_port,
				'auth' => $this->_smtp_auth,
				'username' => $this->_smtp_username,
				'password' => $this->_smtp_password
			)
		);

		$mail = $smtp->send($recipient, $headers2, $body2);
		
		if (PEAR::isError($mail)) {
			throw new Exception($mail->getMessage());
		}
	}
} // End of EmailUtil class
?>