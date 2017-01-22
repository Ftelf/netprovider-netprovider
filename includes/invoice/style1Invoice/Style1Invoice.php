<?php

global $core;
require_once($core->getAppRoot() . "includes/tcpdf/tcpdf.php");
require_once($core->getAppRoot() . "includes/dao/ChargeEntryDAO.php");
require_once($core->getAppRoot() . "includes/dao/HasChargeDAO.php");
require_once($core->getAppRoot() . "includes/dao/ChargeDAO.php");
require_once($core->getAppRoot() . "includes/dao/PersonDAO.php");

Class Style1Invoice {
	private $pdf = null;
	private $chargeEntry = null;
	
	function Style1Invoice($cid) {
		
		$this->chargeEntry = ChargeEntryDAO::getChargeEntryByID($cid);
		$this->hasCharge = HasChargeDAO::getHasChargeByID($this->chargeEntry->CE_haschargeid);
		$this->charge = ChargeDAO::getChargeByID($this->hasCharge->HC_chargeid);
		$this->person = PersonDAO::getPersonByID($this->hasCharge->HC_personid);
		
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		
		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('Net Provider');
		$pdf->SetTitle('Faktura za internetové služby');
		$pdf->SetSubject($this->person->PE_firstname.' '.$this->person->PE_surname);
		$pdf->SetKeywords('Net provider, qos, fakturace, ISP');
		
		// set default header data
		$pdf->SetHeaderData(null, null, "Net provider", "Faktura");
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
		$pdf->SetFont('freesans');	//dejavusans

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
		
		$table  = '
<div class="pos" id="n0:0" style="top: 0px;"><a name="page00001"></a></div>
<div class="pos" id="a30:30Z0p1" style="top: 30px; left: 30px;"><span id="a14Z1p1" style="font-size: 14px;"><b><font color="#000000" face="arial,bold,Arial, Helvetica, sans-serif"><span style="">Faktura</span></font></b></span></div>
<div class="pos" id="a37:80Z2p1" style="top: 80px; left: 37px;"><span id="a7Z3p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><span style="">Dodavatel:</span></font></span></div>
<div class="pos" id="a665:83Z4p1" style="top: 83px; left: 665px;"><span id="a14Z5p1" style="font-size: 14px;"><b><font face="arial,bold,Arial, Helvetica, sans-serif"><span style="">812137</span></font></b></span></div>
<div class="pos" id="a422:90Z6p1" style="top: 90px; left: 422px;"><span id="a7Z7p1" style="font-size: 10px;"><b><font face="arial,bold,Arial, Helvetica, sans-serif"><span style="">Faktura</span> <span style="">číslo:</span></font></b></span></div>
<div class="pos" id="a37:95Z8p1" style="top: 95px; left: 37px;"><span id="a12Z9p1" style="font-size: 12px;"><b><font face="arial,bold,Arial, Helvetica, sans-serif"><span style="">Bohumil</span> <span style="">Bartoš</span></font></b></span></div>
<div class="pos" id="a37:117Z10p1" style="top: 117px; left: 37px;"><span id="a10Z11p1" style="font-size: 10px;"><b><font face="arial,bold,Arial, Helvetica, sans-serif"><span style="">Rozsocha</span> 11</font></b></span></div>
<div class="pos" id="a422:118Z12p1" style="top: 118px; left: 422px;"><span id="a7Z13p1" style="font-size: 10px;"><b><font face="arial,bold,Arial, Helvetica, sans-serif"><span style="">Objednávka:</span></font></b></span></div>
<div class="pos" id="a37:134Z14p1" style="top: 134px; left: 37px;"><span id="a10Z15p1" style="font-size: 10px;"><b><font face="arial,bold,Arial, Helvetica, sans-serif"><span style="">56201</span> <span style="">Ústí</span> <span style="">nad</span> <span style="">Orlicí</span></font></b></span></div>
<div class="pos" id="a422:143Z16p1" style="top: 143px; left: 422px;"><span id="a7Z17p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><span style="">Konstantní</span> <span style="">symbol:</span> <span style="">0308</span></font></span></div>
<div class="pos" id="a37:151Z18p1" style="top: 151px; left: 37px;"><span id="a10Z19p1" style="font-size: 10px;"><b><font face="arial,bold,Arial, Helvetica, sans-serif">Česká <span style="">republika</span></font></b></span></div>
<div class="pos" id="a422:159Z20p1" style="top: 159px; left: 422px;"><span id="a7Z21p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><span style="">Variabilní</span> <span style="">symbol:</span> &nbsp;<span style="">0000012507</span></font></span></div>
<div class="pos" id="a37:169Z22p1" style="top: 169px; left: 37px;"><span id="a7Z23p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><span style="">Tel.:</span></font></span></div>
<div class="pos" id="a422:174Z24p1" style="top: 174px; left: 422px;"><span id="a7Z25p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><span style="">Specifický</span> &nbsp;<span style="">symbol:</span></font></span></div>
<div class="pos" id="a37:184Z26p1" style="top: 184px; left: 37px;"><span id="a7Z27p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><span style="">Fax:</span></font></span></div>
<div class="pos" id="a37:198Z28p1" style="top: 198px; left: 37px;"><span id="a7Z29p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><span style="">Mobil:</span></font></span></div>
<div class="pos" id="a75:198Z30p1" style="top: 198px; left: 75px;"><span id="a7Z31p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><span style="">+420-733</span> <span style="">513</span> <span style="">236</span></font></span></div>
<div class="pos" id="a422:199Z32p1" style="top: 199px; left: 422px;"><span id="a7Z33p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif">IČ <span style="">odběratele: </span></font></span></div>
<div class="pos" id="a422:212Z34p1" style="top: 212px; left: 422px;"><span id="a7Z35p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><span style="">DIČ</span> <span style="">odběratele: </span></font></span></div>
<div class="pos" id="a37:214Z36p1" style="top: 214px; left: 37px;"><span id="a7Z37p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><span style="">E-mail:</span> <span style="">admin@fajnnet.com</span></font></span></div>
<div class="pos" id="a37:229Z38p1" style="top: 229px; left: 37px;"><span id="a7Z39p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><span style="">WWW:</span> <span style="">www.fajnnet.com</span></font></span></div>
<div class="pos" id="a422:240Z40p1" style="top: 240px; left: 422px;"><span id="a7Z41p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><span style="">Odběratel</span> :</font></span></div>
<div class="pos" id="a35:248Z42p1" style="top: 248px; left: 35px;"><span id="a7Z43p1" style="font-size: 10px;"><b><font face="arial,bold,Arial, Helvetica, sans-serif"><span style="">IČ:</span></font></b></span></div>
<div class="pos" id="a75:250Z44p1" style="top: 250px; left: 75px;"><span id="a7Z45p1" style="font-size: 10px;"><b><font face="arial,bold,Arial, Helvetica, sans-serif"><span style="">750</span> 99 <span style="">985</span></font></b></span></div>
<div class="pos" id="a422:260Z46p1" style="top: 260px; left: 422px;"><span id="a10Z47p1" style="font-size: 10px;"><b><font face="arial,bold,Arial, Helvetica, sans-serif"><span style="">Josef</span> Coufal</font></b></span></div>
<div class="pos" id="a35:267Z48p1" style="top: 267px; left: 35px;"><span id="a7Z49p1" style="font-size: 10px;"><b><font face="arial,bold,Arial, Helvetica, sans-serif"><span style="">DIČ:</span></font></b></span></div>
<div class="pos" id="a75:267Z50p1" style="top: 267px; left: 75px;"><span id="a7Z51p1" style="font-size: 10px;"><b><font face="arial,bold,Arial, Helvetica, sans-serif"><span style="">CZ8304123674</span></font></b></span></div>
<div class="pos" id="a37:287Z52p1" style="top: 287px; left: 37px;"><span id="a7Z53p1" style="font-size: 10px;"><b><font face="arial,bold,Arial, Helvetica, sans-serif"><span style="">Bankovní</span> <span style="">účet:</span></font></b></span></div>
<div class="pos" id="a321:287Z54p1" style="top: 287px; left: 321px;"><span id="a10Z55p1" style="font-size: 10px;"><b><font face="arial,bold,Arial, Helvetica, sans-serif"><span style="">CEKO</span></font></b></span></div>
<div class="pos" id="a422:303Z56p1" style="top: 303px; left: 422px;"><span id="a10Z57p1" style="font-size: 10px;"><b><font face="arial,bold,Arial, Helvetica, sans-serif"><span style="">Kadlčíkova 281</span></font></b></span></div>
<div class="pos" id="a104:308Z58p1" style="top: 308px; left: 104px;"><span id="a11Z59p1" style="font-size: 11px;"><b><font face="arial,bold,Arial, Helvetica, sans-serif"><span style="">212</span> <span style="">842</span> <span style="">322</span></font></b></span></div>
<div class="pos" id="a324:308Z60p1" style="top: 308px; left: 324px;"><span id="a11Z61p1" style="font-size: 11px;"><b><font face="arial,bold,Arial, Helvetica, sans-serif"><span style="">0300</span></font></b></span></div>
<div class="pos" id="a422:324Z62p1" style="top: 324px; left: 422px;"><span id="a10Z63p1" style="font-size: 10px;"><b><font face="arial,bold,Arial, Helvetica, sans-serif"><span style="">561 12&nbsp;&nbsp;Brandýs nad Orlicí</span></font></b></span></div>
<div class="pos" id="a37:340Z66p1" style="top: 340px; left: 37px;"><span id="a7Z67p1" style="font-size: 10px;"><b><font face="arial,bold,Arial, Helvetica, sans-serif"><span style="">Způsob</span> <span style="">dopravy:</span></font></b></span></div>
<div class="pos" id="a422:367Z68p1" style="top: 367px; left: 422px;"><span id="a10Z69p1" style="font-size: 10px;"><b><font face="arial,bold,Arial, Helvetica, sans-serif">Česká <span style="">republika</span></font></b></span></div>
<div class="pos" id="a37:403Z70p1" style="top: 403px; left: 37px;"><span id="a7Z71p1" style="font-size: 10px;"><b><font face="arial,bold,Arial, Helvetica, sans-serif"><span style="">Konečný</span> příjemce: </font></b><font face="arial,bold,Arial, Helvetica, sans-serif"></font></span></div>
<div class="pos" id="a140:441Z76x1" style="top: 403px; left: 140px;"><span id="a7Z77p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><span style="">Josef Coufal</span></font></span></div>
<div class="pos" id="a417:418Z72p1" style="top: 418px; left: 417px;"><span id="a7Z73p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><font face="arial,Arial, Helvetica, sans-serif"><span style="">Způsob</span> <span style="">platby:</span></font></font></span></div>
<div class="pos" id="a613:419Z74p1" style="top: 419px; left: 613px;"><span id="a7Z75p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><b><font face="arial,bold,Arial, Helvetica, sans-serif"><span style="">převodem</span></font></b></font></span></div>
<div class="pos" id="a140:441Z76p1" style="top: 441px; left: 140px;"><span id="a7Z77p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><font face="arial,Arial, Helvetica, sans-serif"><span style="">Kadlčíkova 281</span></font></font></span></div>
<div class="pos" id="a417:444Z78p1" style="top: 444px; left: 417px;"><span id="a7Z79p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><font face="arial,Arial, Helvetica, sans-serif"><span style="">Datum vystavení:</span></font></font></span></div>
<div class="pos" id="a613:444Z82p1" style="top: 444px; left: 613px;"><span id="a7Z83p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><font face="arial,Arial, Helvetica, sans-serif"><span style="">01.01.2009</span></font></font></span></div>
<div class="pos" id="a140:456Z84p1" style="top: 456px; left: 140px;"><span id="a7Z85p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><font face="arial,Arial, Helvetica, sans-serif"><span style="">561 12</span> &nbsp;<span style="">Brandýs nad Orlicí</span></font></font></span></div>
<div class="pos" id="a611:467Z86p1" style="top: 467px; left: 611px;"><span id="a10Z87p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><b><font face="arial,bold,Arial, Helvetica, sans-serif">14.01.2009</font></b></font></span></div>
<div class="pos" id="a417:468Z88p1" style="top: 468px; left: 417px;"><span id="a7Z89p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><font face="arial,Arial, Helvetica, sans-serif"><span style="">Datum</span> <span style="">splatnosti:</span></font></font></span></div>
<div class="pos" id="a140:477Z90p1" style="top: 477px; left: 140px;"><span id="a7Z91p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><font face="arial,Arial, Helvetica, sans-serif"><span style="">Česká</span> <span style="">republika</span></font></font></span></div>
<div class="pos" id="a30:521Z92p1" style="top: 521px; left: 30px;"><span id="a9Z93p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><font face="arial,Arial, Helvetica, sans-serif"><span style="">Označení</span> <span style="">dodávky</span></font></font></span></div>
<div class="pos" id="a209:521Z94p1" style="top: 521px; left: 209px;"><span id="a9Z95p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><font face="arial,Arial, Helvetica, sans-serif"><span style="">Katalogové</span> <span style="">označení</span></font></font></span></div>
<div class="pos" id="a342:521Z96p1" style="top: 521px; left: 342px;"><span id="a9Z97p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><font face="arial,Arial, Helvetica, sans-serif"><span style="">Počet</span> M.J.</font></font></span></div>
<div class="pos" id="a422:521Z98p1" style="top: 521px; left: 422px;"><span id="a9Z99p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><font face="arial,Arial, Helvetica, sans-serif">M.J.</font></font></span></div>
<div class="pos" id="a510:521Z100p1" style="top: 521px; left: 510px;"><span id="a9Z101p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><font face="arial,Arial, Helvetica, sans-serif"><span style="">Cena</span> za M.J.</font></font></span></div>
<div class="pos" id="a675:521Z102p1" style="top: 521px; left: 675px;"><span id="a9Z103p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><font face="arial,Arial, Helvetica, sans-serif"><span style="">Cena</span> <span style="">celkem</span></font></font></span></div>
<div class="pos" id="a30:540Z104p1" style="top: 540px; left: 30px;"><span id="a9Z105p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><font face="arial,Arial, Helvetica, sans-serif"><span style="">Služba</span> <span style="">připojení</span> do <span style="">sítě</span> <span style="">internet</span></font></font></span></div>
<div class="pos" id="a373:540Z106p1" style="top: 540px; left: 373px;"><span id="a9Z107p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><font face="arial,Arial, Helvetica, sans-serif"><span style="">1,00</span></font></font></span></div>
<div class="pos" id="a541:540Z108p1" style="top: 540px; left: 541px;"><span id="a9Z109p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><font face="arial,Arial, Helvetica, sans-serif"><span style="">400,00</span></font></font></span></div>
<div class="pos" id="a705:540Z110p1" style="top: 540px; left: 705px;"><span id="a9Z111p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><font face="arial,Arial, Helvetica, sans-serif"><span style="">400,00</span></font></font></span></div>
<div class="pos" id="a83:588Z114p1" style="top: 588px; left: 83px;"><span id="a9Z115p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><b><font face="arial,bold,Arial, Helvetica, sans-serif"><span style="">Sleva</span> v %:</font></b></font></span></div>
<div class="pos" id="a222:588Z116p1" style="top: 588px; left: 222px;"><span id="a9Z117p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><font face="arial,Arial, Helvetica, sans-serif"><span style="">0,00</span></font></font></span></div>
<div class="pos" id="a68:607Z118p1" style="top: 607px; left: 68px;"><span id="a9Z119p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><b><font face="arial,bold,Arial, Helvetica, sans-serif"><span style="">Zaokrouhlení:</span></font></b></font></span></div>
<div class="pos" id="a222:607Z120p1" style="top: 607px; left: 222px;"><span id="a9Z121p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><font face="arial,Arial, Helvetica, sans-serif"><span style="">0,00</span></font></font></span></div>
<div class="pos" id="a308:631Z122p1" style="top: 631px; left: 308px;"><span id="a12Z123p1" style="font-size: 12px;"><font face="arial,Arial, Helvetica, sans-serif"><b><font face="arial,bold,Arial, Helvetica, sans-serif"><span style="">Celkem</span> k <span style="">úhradě</span> v <span style="">Kč:</span></font></b></font></span></div>
<div class="pos" id="a683:632Z124p1" style="top: 632px; left: 683px;"><span id="a12Z125p1" style="font-size: 12px;"><font face="arial,Arial, Helvetica, sans-serif"><b><font face="arial,bold,Arial, Helvetica, sans-serif"><span style="">400,00</span></font></b></font></span></div>
<div class="pos" id="a337:667Z126p1" style="top: 667px; left: 329px;"><span id="a12Z127p1" style="font-size: 12px;"><font face="arial,Arial, Helvetica, sans-serif"><b><font face="arial,bold,Arial, Helvetica, sans-serif"><span style="">Uhrazeno</span> <span style="">zálohou:</span></font></b></font></span></div>
<div class="pos" id="a701:667Z128p1" style="top: 667px; left: 701px;"><span id="a12Z129p1" style="font-size: 12px;"><font face="arial,Arial, Helvetica, sans-serif"><b><font face="arial,bold,Arial, Helvetica, sans-serif"><span style="">0,00</span></font></b></font></span></div>
<div class="pos" id="a426:698Z130p1" style="top: 698px; left: 390px;"><span id="a12Z131p1" style="font-size: 12px;"><font face="arial,Arial, Helvetica, sans-serif"><b><font face="arial,bold,Arial, Helvetica, sans-serif"><span style="">Uhradit:</span></font></b></font></span></div>
<div class="pos" id="a683:698Z132p1" style="top: 698px; left: 683px;"><span id="a12Z133p1" style="font-size: 12px;"><font face="arial,Arial, Helvetica, sans-serif"><b><font face="arial,bold,Arial, Helvetica, sans-serif"><span style="">400,00</span></font></b></font></span></div>
<div class="pos" id="a39:753Z134p1" style="top: 753px; left: 39px;"><span id="a7Z135p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><font face="arial,Arial, Helvetica, sans-serif">Vystavil: <span style="">Bohumil</span> Bartoš</font></font></span></div>
<div class="pos" id="a39:766Z136p1" style="top: 766px; left: 39px;"><span id="a7Z137p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><font face="arial,Arial, Helvetica, sans-serif"><span style="">tel:</span> <span style="">733</span> <span style="">513</span> <span style="">236</span></font></font></span></div>
<div class="pos" id="a39:779Z138p1" style="top: 779px; left: 39px;"><span id="a7Z139p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><font face="arial,Arial, Helvetica, sans-serif"><span style="">email:</span> <span style="">admin@fajnnet.com</span></font></font></span></div>
<div class="pos" id="a39:806Z140p1" style="top: 806px; left: 39px;"><span id="a7Z141p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><font face="arial,Arial, Helvetica, sans-serif"><span style="">Neplátce</span> <span style="">DPH. Registrován u Okresního živnostenského úřadu v Ústí nad Orlicí,</span></font></font></span></div>
<div class="pos" id="a39:823Z142p1" style="top: 818px; left: 39px;"><span id="a7Z143p1" style="font-size: 10px;"><font face="arial,Arial, Helvetica, sans-serif"><b><font face="arial,bold,Arial, Helvetica, sans-serif"><span style="">pod č.j. ZIV/U900/2007/Re, ev.č. 361101-51813; osvědčení ČTÚ č.1875 ze dne 6.2.2007.</span></font></b></font></span></div>
<div class="pos" id="a510:854Z144p1" style="top: 854px; left: 530px;"><span id="a8Z145p1" style="font-size: 8px;"><font face="arial,Arial, Helvetica, sans-serif"><font face="arial,Arial, Helvetica, sans-serif"><span style="">razítko,</span> <span style="">podpis</span> <span style="">dodavatele</span></font></font></span></div>
<div class="frame1" style="left: 30px; top: 74px; width: 704px; height: 425px;"></div>
<div class="frame1" style="left: 30px; top: 74px; width: 379px; height: 425px;"></div>
<div class="frame1" style="left: 30px; top: 301px; width: 379px; height: 21px;"></div>
<div class="frame1" style="left: 30px; top: 301px; width: 237px; height: 21px;"></div>
<div class="frame1" style="left: 30px; top: 327px; width: 379px; height: 66px;"></div>
<div class="frame2" style="left: 30px; top: 535px; width: 704px; height: 36px;"></div>
<div class="frame3" style="left: 30px; top: 53px; width: 704px; height: 0px;"></div>
<div class="frame4" style="left: 410px; top: 227px; width: 317px; height: 161px;"></div>
<div class="frame4" style="left: 496px; top: 623px; width: 234px; height: 26px;"></div>
<div class="frame5" style="left: 440px; top: 850px; width: 294px; height: 0px;"></div>
';
		$pdf->writeHTML($table, true, 0, true, 0, '');
	}
	
	function output() {
		$pdf = $this->pdf;
		//Close and output PDF document
//		$pdf->Output('example_001.pdf', 'I');	//D - download
		$pdf->Output('C:/example_001.pdf', 'F');
	}
} // End of Style1Invoice class
?>