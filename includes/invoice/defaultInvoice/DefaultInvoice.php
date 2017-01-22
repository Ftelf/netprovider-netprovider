<?php

global $core;
require_once($core->getAppRoot() . "includes/tcpdf/tcpdf.php");
require_once($core->getAppRoot() . "includes/dao/InvoiceDAO.php");
require_once($core->getAppRoot() . "includes/dao/ChargeEntryDAO.php");
require_once($core->getAppRoot() . "includes/dao/HasChargeDAO.php");
require_once($core->getAppRoot() . "includes/dao/ChargeDAO.php");
require_once($core->getAppRoot() . "includes/dao/PersonDAO.php");
require_once($core->getAppRoot() . "includes/dao/PersonAccountDAO.php");
require_once($core->getAppRoot() . "includes/utils/DiacriticsUtil.php");

Class DefaultInvoice {
	private $invoice = null;
	private $chargeEntry = null;
	private $hasCharge = null;
	private $charge = null;
	private $person = null;
	private $personAccount = null;
	private $pdf = null;
	
	function DefaultInvoice($iid) {
		$this->invoice = InvoiceDAO::getInvoiceByID($iid);
		$this->chargeEntry = ChargeEntryDAO::getChargeEntryByID($this->invoice->IN_chargeentryid);
		$this->hasCharge = HasChargeDAO::getHasChargeByID($this->chargeEntry->CE_haschargeid);
		$this->charge = ChargeDAO::getChargeByID($this->hasCharge->HC_chargeid);
		$this->person = PersonDAO::getPersonByID($this->invoice->IN_personid);
		$this->personAccount = PersonAccountDAO::getPersonAccountByID($this->person->PE_personaccountid);
		
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		
		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('Net Provider');
		$pdf->SetTitle('Faktura za internetové služby');
		$pdf->SetSubject($this->person->PE_firstname.' '.$this->person->PE_surname);
		$pdf->SetKeywords('Net provider, qos, fakturace, ISP');
		
		// set default header data
		$pdf->SetHeaderData(null, null, "Net provider", "Faktura: ".$this->invoice->IN_invoicenumber);
//		$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

		// set header and footer fonts
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
		
		//set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		
		//set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		
		//set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO); 
		
		//set some language-dependent strings
		$pdf->setLanguageArray("eng");
		
		// set font
		$pdf->SetFont('arial');	//dejavusans  freesans

		$this->pdf = $pdf;
	}
	
	function generate() {
		$l = Array();
		
		// PAGE META DESCRIPTORS --------------------------------------
		
		$l['a_meta_charset'] = 'UTF-8';
		$l['a_meta_dir'] = 'ltr';
		$l['a_meta_language'] = 'cz';
		
		// TRANSLATIONS --------------------------------------
		$l['w_page'] = 'stránka';
		
		// set document information
		$pdf = $this->pdf;
		// add a page
		$pdf->AddPage();
		
		$pdf->SetFillColor(255, 0, 0);
		
		$table  = "<table border=\"1\" cellspacing=\"0\" cellpadding=\"0\">";
		$table .= "<tr>";
		$table .= "<td>";
		$table .= "<p><span style=\"font-size: xx-small;\">Dodavatel:</span><br/>";
		$table .= "<span style=\"font-size: medium; font-weight: bold;\">FajnCom s.r.o.</span><br/>";
		$table .= "<span style=\"font-size: small;\">Rozsocha 11</span><br/>";
		$table .= "<span style=\"font-size: small;\">Ústí nad Orlicí</span><br/>";
		$table .= "<span style=\"font-size: small;\">CZ-562 01</span><br/>";
		$table .= "<span style=\"font-size: small;\">IČ: 28773195</span><br/>";
		$table .= "<span style=\"font-size: small;\">DIČ: CZ28773195</span><br/>";
		$table .= "<span style=\"font-size: small;\">tel: +420 733 513 236</span><br/>";
		$table .= "<span style=\"font-size: small;\">WEB: www.fajncom.cz</span></p>";
		$table .= "</td>";
		$table .= "<td>";
		$table .= "<p><span style=\"font-size: xx-small;\">Odběratel:</span><br/>";
		if ($this->person->PE_shortcompanyname) $table .= "<span style=\"font-size: small;\">".$this->person->PE_shortcompanyname."</span><br/>";	//DIČ
		if ($this->person->PE_companyname) $table .= "<span style=\"font-size: small;\">".$this->person->PE_companyname."</span><br/>";	//DIČ
		if ($this->person->PE_firstname || $this->person->PE_surname) $table .= "<span style=\"font-size: medium; font-weight: bold;\">".$this->person->PE_degree_prefix.' '.$this->person->PE_firstname.' '.$this->person->PE_surname.' '.$this->person->PE_degree_suffix."</span><br/>";	//Title Firstname Surname Title
		if ($this->person->PE_address) $table .= "<span style=\"font-size: small;\">".$this->person->PE_address."</span><br/>";	//Street
		if ($this->person->PE_city) $table .= "<span style=\"font-size: small;\">".$this->person->PE_city."</span><br/>";	//City
		if ($this->person->PE_zip) $table .= "<span style=\"font-size: small;\">".$this->person->PE_zip."</span><br/>";	//ZIP
		if ($this->person->PE_ic) $table .= "<span style=\"font-size: small;\">IČ: ".$this->person->PE_ic."</span><br/>";	//IČ
		if ($this->person->PE_dic) $table .= "<span style=\"font-size: small;\">DIČ: ".$this->person->PE_dic."</span><br/>";	//DIČ
		if ($this->person->PE_tel) $table .= "<span style=\"font-size: small;\">tel: ".$this->person->PE_tel."</span><br/>";	//Phone
		if ($this->person->PE_email) $table .= "<span style=\"font-size: small;\">e-mail: ".$this->person->PE_email."</span>";	//E-mail
		$table .= "</p></td>";
		$table .= "</tr>";
		$table .= "<tr>";
		$table .= "<td>";
		$table .= "<p><span style=\"font-size: x-small;\">Způsob platby: platba na účet</span><br/>";
		
		$paymentDate = new DateUtil($this->chargeEntry->CE_period_date);
		$paymentDate->add(DateUtil::DAY, $this->charge->CH_tolerance);
		
		$invoiceDate = new DateUtil($this->chargeEntry->CE_period_date);
		
		$taxDate = new DateUtil($this->chargeEntry->CE_period_date);
		
		$payDate = new DateUtil($this->chargeEntry->CE_period_date);
		$payDate->add(DateUtil::DAY, $this->charge->CH_tolerance - 5);
		
		
		
		$table .= "<span style=\"font-size: x-small; font-weight: bold;\">Datum splatnosti: ".$paymentDate->getFormattedDate(DateUtil::FORMAT_DATE)."</span><br/>";
		$table .= "<span style=\"font-size: x-small;\">Datum vystavení daňového dokladu: ".$invoiceDate->getFormattedDate(DateUtil::FORMAT_DATE)."</span><br/>";
		$table .= "<span style=\"font-size: x-small;\">Datum uskutečnění zdanitelného plnění: ".$taxDate->getFormattedDate(DateUtil::FORMAT_DATE)."</span><br/>";
		$table .= "<span style=\"font-size: x-small; font-weight: bold;\">Doporučené datum úhrady: ".$payDate->getFormattedDate(DateUtil::FORMAT_DATE)."</span><br/></p>";
		$table .= "</td>";
		$table .= "<td>";
		$table .= "<p><span style=\"font-size: x-small; font-weight: bold;\">Bankovní účet:43-4885660257/0100</span><br/>";
		$table .= "<span style=\"font-size: x-small;\">Konstantní symbol: ".$this->personAccount->PA_constantsymbol."</span><br/>";
		$table .= "<span style=\"font-size: x-small;\">Variabilní symbol: ".$this->personAccount->PA_variablesymbol."</span><br/>";
		$table .= "<span style=\"font-size: x-small;\">Specifický symbol: ".$this->personAccount->PA_specificsymbol."</span><br/></p>";
		$table .= "</td>";
		$table .= "</tr>";
		$table .= "</table>";
		$pdf->writeHTML($table, true, 0, true, 0);
		
		$table  = "<table border=\"1\" cellspacing=\"0\" cellpadding=\"0\">";
		$table .= "<tr>";
		$table .= "<th style=\"background-color: #EEEEEE;\" colspan=\"3\">";
		$table .= "<span style=\"font-size: x-small; font-weight: bold;\">Vyučtování služeb elektronických komunikací</span>";
		$table .= "</th>";
		$table .= "</tr>";
		$table .= "<tr>";
		$table .= "<td width=\"300\">";
		$table .= "<span align=\"center\" style=\"font-size: x-small;\">Účtované služby</span>";
		$table .= "</td>";
		$table .= "<td width=\"75\">";
		$table .= "<span align=\"center\" style=\"font-size: x-small;\">DPH %</span>";
		$table .= "</td>";
		$table .= "<td width=\"129\">";
		$table .= "<span align=\"center\" style=\"font-size: x-small;\">Jednotková cena bez DPH</span>";
		$table .= "</td>";
		$table .= "</tr>";
		$table .= "<tr>";
		$table .= "<td width=\"300\">";
		$table .= "<span style=\"font-size: x-small;\">".$this->charge->CH_name."</span>";
		$table .= "</td>";
		$table .= "<td width=\"75\" align=\"right\">";
		$table .= "<span style=\"font-size: x-small;\">".$this->charge->CH_vat."</span>";
		$table .= "</td>";
		$table .= "<td width=\"129\" align=\"right\">";
		$table .= "<span style=\"font-size: x-small;\">".$this->charge->CH_baseamount." ".$this->charge->CH_currency."</span>";
		$table .= "</td>";
		$table .= "</tr>";
		$table .= "</table>";
		$pdf->writeHTML($table, true, 0, true, 0);
		
		$table  = "<table border=\"1\" cellspacing=\"0\" cellpadding=\"0\">";
		$table .= "<tr>";
		$table .= "<td width=\"375\">";
		$table .= "<span style=\"font-size: x-small;\">Celkový základ pro DPH ".$this->charge->CH_vat." %</span>";
		$table .= "</td>";
		$table .= "<td width=\"129\" align=\"right\">";
		$table .= "<span style=\"font-size: x-small;\">".$this->charge->CH_baseamount." ".$this->charge->CH_currency."</span>";
		$table .= "</td>";
		$table .= "</tr>";
		$table .= "<tr>";
		$table .= "<td width=\"375\">";
		$table .= "<span style=\"font-size: x-small;\">Celkem DPH ".$this->charge->CH_vat." %</span>";
		$table .= "</td>";
		$table .= "<td width=\"129\" align=\"right\">";
		$table .= "<span style=\"font-size: x-small;\">".($this->charge->CH_amount - $this->charge->CH_baseamount)." ".$this->charge->CH_currency."</span>";
		$table .= "</td>";
		$table .= "</tr>";
		$table .= "</table>";
		$pdf->writeHTML($table, true, 0, true, 0);
		
		$table  = "<table border=\"1\" cellspacing=\"0\" cellpadding=\"0\">";
		$table .= "<tr>";
		$table .= "<th width=\"375\" style=\"background-color: #EEEEEE;\" colspan=\"3\">";
		$table .= "<span style=\"font-size: x-small; font-weight: bold;\">Služby elektronických komunikací za běžné období vč. DPH:</span>";
		$table .= "</th>";
		$table .= "<th align=\"right\" width=\"129\" style=\"background-color: #EEEEEE;\" colspan=\"3\">";
		$table .= "<span style=\"font-size: x-small; font-weight: bold;\">".$this->charge->CH_amount." ".$this->charge->CH_currency."</span>";
		$table .= "</th>";
		$table .= "</tr>";
		$table .= "</table>";
		$pdf->writeHTML($table, true, 0, true, 0);
		
		// create some HTML content
		$htmlcontent = "<p><span style=\"font-size: xx-small;\"\>Důležité upozornění: Při platbě uvádějte vždy správný variabilní symbol. Umožníte tím včasné a rychlé zpracování Vaši platby. Děkujeme Vám!</span></p>";

		//output the HTML content
		$pdf->writeHTML($htmlcontent, true, 0, true, 0);

		// create some HTML content
		$htmlcontent = "<p><br/><span style=\"font-size: xx-small; font-weight: bold;\"\>Vystavil: </span><span style=\"font-size: xx-small;\"\>Bohumil Bartoš</span><br/>";
		$htmlcontent .= "<span style=\"font-size: xx-small; font-weight: bold;\"\>tel: </span><span style=\"font-size: xx-small;\"\>733 513 236</span><br/>";
		$htmlcontent .= "<span style=\"font-size: xx-small; font-weight: bold;\"\>email: </span><span style=\"font-size: xx-small;\"\>admin@fajnnet.com</span></p>";

		//output the HTML content
		$pdf->writeHTML($htmlcontent, true, 0, true, 0);
		
		// create some HTML content
		$htmlcontent = "<p><br/><span style=\"font-size: xx-small;\"\>Neplátce DPH. Firma je zapsána u Krajského soudu v Hradci Králové v oddílu C, složce číslo 26528 ,</span><br/>";
		$htmlcontent .= "<span style=\"font-size: xx-small; font-weight: bold;\"\>osvědčení ČTÚ č.1875 ze dne 6.2.2007.</span></p>";

		//output the HTML content
		$pdf->writeHTML($htmlcontent, true, 0, true, 0);
	}
	
	function getFilename() {
		$filename  = 'fa';
		$filename .= '-';
		$filename .= $this->person->PE_firstname.'_'.$this->person->PE_surname;
		$filename .= '-';
		$filename .= $this->charge->CH_name;
		$filename .= '-';
		$filename .= $this->chargeEntry->CE_period_date;
		$filename .= '.pdf';
		
		$diacriticsUtil = new DiacriticsUtil();
		
		return str_replace(' ', '_', $diacriticsUtil->removeDiacritic($filename));
	} 
	
	function output() {
		$pdf = $this->pdf;
		//Close and output PDF document
		$pdf->Output($this->getFilename(), 'D');	//F - file, I - stdout, D - download, S- string
	}
	
	function getBlob() {
		$pdf = $this->pdf;
		return $pdf->Output($this->getFilename(), 'S');	//F - file, I - stdout, D - download, S- string
	}
} // End of DefaultInvoice class
?>