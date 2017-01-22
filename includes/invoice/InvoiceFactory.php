<?php

global $core;
require_once($core->getAppRoot() . "includes/invoice/defaultInvoice/DefaultInvoice.php");
require_once($core->getAppRoot() . "includes/invoice/style1Invoice/Style1Invoice.php");

/**
 * InvoiceFactory
 */
Class InvoiceFactory {
	private $invoice = null;
	
	function InvoiceFactory($iid) {
		$this->invoice = new DefaultInvoice($iid);
		$this->invoice->generate();
	}
	
	function getFilename() {
		return $this->invoice->getFilename();
	}
	
	function output() {
		$this->invoice->output();
	}
	
	function getBlob() {
		return $this->invoice->getBlob();
	}
} // End of InvoiceFactory class
?>