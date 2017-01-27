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
require_once($core->getAppRoot() . "includes/event/Event.php");
require_once($core->getAppRoot() . "includes/dao/HandleEventDAO.php");

/**
 * Core
 */
Class EventCrossBar {
	var $handleEventArray = null;
	var $templateArray = null;
	var $emailUtil = null;

	public function __construct() {
		global $core, $database;
		
		$this->emailUtil = new EmailUtil();
		
		$this->handleEventArray = HandleEventDAO::getHandleEventArray();
		$this->templateArray = array();
		
		foreach ($this->handleEventArray as &$handleEvent) {
			$path = $core->getAppRoot()."templates/events/".$handleEvent->HE_templatepath;
			if (!($template = file_get_contents($path))) {
				throw new Exception("Cannot open event template file: ".$path);
			}
			$this->templateArray[$handleEvent->HE_handleeventid] = $template;
		}
	}
	
	function dispatchEvent($event) {
		global $database;
		$now = new DateUtil();
		$now->set(DateUtil::HOUR, 0);
		$now->set(DateUtil::MINUTES, 0);
		$now->set(DateUtil::SECONDS, 0);
		
		if ($event instanceof ChargePaymentDeadlineEvent) {
			foreach ($this->handleEventArray as &$handleEvent) {
				if ($handleEvent->HE_type == HandleEvent::TYPE_CHARGE_PAYMENT_DEADLINE &&
					$handleEvent->HE_status == HandleEvent::STATUS_ENABLED) {
					
					$daysBeforeTurnOff = ($event->getToleranceDate()->getTime() - $now->getTime()) / 3600 / 24;
					
					if ($handleEvent->HE_notifydaysbeforeturnoff == null || $handleEvent->HE_notifydaysbeforeturnoff >= $daysBeforeTurnOff) {
						$template = $this->templateArray[$handleEvent->HE_handleeventid];
						$template = mb_ereg_replace("\|PERSON_NAME\|", $event->getPerson()->PE_firstname." ".$event->getPerson()->PE_surname, $template);
						$template = mb_ereg_replace("\|CHARGE_NAME\|", $event->getCharge()->CH_name, $template);
						$template = mb_ereg_replace("\|CHARGE_BASEAMOUNT\|", $event->getCharge()->CH_baseamount, $template);
						$template = mb_ereg_replace("\|CHARGE_VAT\|", $event->getCharge()->CH_vat, $template);
						$template = mb_ereg_replace("\|CHARGE_AMOUNT\|", $event->getCharge()->CH_amount, $template);
						$template = mb_ereg_replace("\|CHARGE_CURRENCY\|", $event->getCharge()->CH_currency, $template);
						$template = mb_ereg_replace("\|CHARGE_PERIOD\|", Charge::getLocalizedPeriod($event->getCharge()->CH_period), $template);
						$template = mb_ereg_replace("\|CHARGE_PERIOD_DATE\|", $event->getPeriodDate()->getFormattedDate(DateUtil::FORMAT_DATE), $template);
						$template = mb_ereg_replace("\|CHARGE_WRITE_OFF\|", $event->getWriteOffDate()->getFormattedDate(DateUtil::FORMAT_DATE), $template);
						$template = mb_ereg_replace("\|CHARGE_SWITCH_OFF\|", $event->getToleranceDate()->getFormattedDate(DateUtil::FORMAT_DATE), $template);

						if ($handleEvent->HE_notifypersonid) {
							$person = PersonDAO::getPersonByID($handleEvent->HE_notifypersonid);
						} else {
							$person = $event->getPerson();
						}
						
						$this->emailUtil->queueMessage($person, $handleEvent->HE_emailsubject, $template, null);
						
						try {
							$this->emailUtil->sendMessages();
						} catch (Exception $e) {
							$msg = "Error sending message: " . $e->getMessage();
							$database->log($msg, Log::LEVEL_ERROR);
						}
					}
				}
			}
		} else if ($event instanceof InvoiceCreated) {
			foreach ($this->handleEventArray as &$handleEvent) {
				if ($handleEvent->HE_type == HandleEvent::TYPE_INVOICE_CREATED &&
					$handleEvent->HE_status == HandleEvent::STATUS_ENABLED) {
					
					$invoicePdf = new InvoiceFactory($event->getInvoiceId());
									
					$blob = array(array(
						"NAME" => $invoicePdf->getFilename(),
						"ATTACHMENT" => $invoicePdf->getBlob()
					));
					
					if ($handleEvent->HE_notifypersonid) {
						$person = PersonDAO::getPersonByID($handleEvent->HE_notifypersonid);
					} else {
						$person = $event->getPerson();
					}
					
					$template = $this->templateArray[$handleEvent->HE_handleeventid];
					$template = mb_ereg_replace("\|PERSON_NAME\|", $event->getPerson()->PE_firstname." ".$event->getPerson()->PE_surname, $template);
					
					$this->emailUtil->queueMessage($person, $handleEvent->HE_emailsubject, $template, $blob);
					
					try {
						$this->emailUtil->sendMessages();
					} catch (Exception $e) {
						$msg = "Error sending message: " . $e->getMessage();
						$database->log($msg, Log::LEVEL_ERROR);
					}
				}
			}
		}
	}
} // End of EventCrossBar class
?>