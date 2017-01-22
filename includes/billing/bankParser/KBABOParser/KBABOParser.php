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
require_once($core->getAppRoot() . "includes/tables/BankAccountEntry.php");

//0123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789
//0         1         2         3         4         5         6         7         8         9
//Komerční banka a.s.                        VÝPIS                       poř.č.:                    42
//SWIFT: KOMBCZPPXXX                       PERIODICKÝ                    strana:                     1
//                               k účtu:    43-4885660257     CZK        způsob zaslání:  elektronicky
//                               období:    12.10.09-18.10.09 (42)       frekvence:            týdenní
//                                 IBAN:    CZ6201000000434885660257                                  
//                                  typ:    BĚŽNÝ ÚČET                                                
//                                                                                                    
//Předchozí výpis ze dne:                    09-10-2009                                               
//Počáteční zůstatek:                         40.309,01                                               
//Ve prospěch:                                 7.389,00  
//Na vrub:                                         0,00  FAJNCOM S.R.O.                               
//Konečný zůstatek:                           47.698,01  ROZSOCHA 11                                  
//Počet položek                                      13  562 01 ORLICKÉ PODHŮŘÍ                       
//                                                                                                    
// DATUM       POPIS                             VARIABILNÍ       ČÁSTKA MÁ DÁTI         ČÁSTKA DAL
// SPLATNOSTI  NÁZEV PROTIÚČTU                   KONSTANTNÍ         (NA VRUB)          (VE PROSPĚCH)
// DATUM       PROTIÚČET/BANKA                   SPECIFICKÝ
// ODEPSÁNÍ JB IDENTIFIKACE TRANSAKCE              SYMBOL
//____________________________________________________________________________________________________
// 12-10-2009  Úhrada z jiné banky                    35509                                   199,00 
// 11-10-2009  BARTOS PAVEL                               0                                          
//             660749113/0800                             0                                          
//             120-20091011 I091 0107N9MJ5                                                           
//____________________________________________________________________________________________________

/**
 * KBABOParser class
 */
class KBABOParser {
	private $fcontents = array();
	private $p;
	private $count;
	
	private $document = null;
	
	const KBBANKA = '^Komerční banka a\.s\.';
	const BANKOVNI_VYPIS = '^Bankovní výpis č. ([[:digit:]]*)$';
	const ZA_OBDOBI = '^([[:digit:]]{2}).([[:digit:]]{2}).([[:digit:]]{2})-([[:digit:]]{2}).([[:digit:]]{2}).([[:digit:]]{2})$';
	const ZA_OBDOBI2 = '^([[:digit:]]{2})-([[:digit:]]{2})-([[:digit:]]{4})[[:space:]]*do:[[:space:]]*([[:digit:]]{2})-([[:digit:]]{2})-([[:digit:]]{4})$';
		
	const NAZEV_UCTU = '^Název účtu:[[:space:]]*([[:print:]]*)$';
	const CISLO_UCTU = '^Číslo účtu:[[:space:]]*([[:digit:]]*)/([[:digit:]]{4})$';
	const IBAN = '^IBAN:[[:space:]]*([[:alnum:]]*)$';
	const MENA = '^Měna:[[:space:]]*([[:alpha:]]*)$';
	
	const ACCOUNT_DELIMETER = '^_+$';
	
	const ACCOUNT_LINE_1 = '^[[:space:]]*[[:digit:]{2}.[[:digit:]{2}.$';
	const ACCOUNT_LINE_2 = '^-+$';
	const ACCOUNT_LINE_3 = '^-+$';
	const ACCOUNT_LINE_4 = '^-+$';
	const DATE_PATTERN = '^([[:digit:]]{2})-([[:digit:]]{2})-([[:digit:]]{4})$';
	const TIME_PATTERN = '^([[:digit:]]{2}):([[:digit:]]{2})$';
	const ACCOUNT_NAME = '^(.+)/([[:digit:]]{4})$';
	
	const PREHLED_UVERU_K_UCTU_C = '^Přehled úvěru k účtu č.';
	
	
	static $KNOWN_TRANSACTION_ARRAY = array(
		0  => "Jiný",
		1  => "Převod",
		2  => "Příchozí platba",
		3  => "Vklad hotovosti",
		4  => "Trvalý převod",
		5  => "Jiný trans. poplatek",
		6  => "Kladný úrok",
		7  => "Výběr z bankomatu",
		8  => "Platba kartou",
		9  => "Generování bankovních",
		10 => "Poplatek za vedení ka",
		11 => "Distribuce bankovního",
		12 => "Poolovací převod",
		13 => "Využívání a správa In",
		14 => "Úročení úvěru",
		15 => "Splácení úvěru",
		16 => "Využívání a správa Ko",
		17 => "Poplatek za externí z",
		18 => "Vydání dávkového cert",
		19 => "Čerpání Investičního",
		20 => "Poplatek za transakci",
		21 => "Výběr hotovost"
	);
	
	/**
	 * Constructor KBABOParser
	 * @param String $content plain text with list
	 */
	public function KBABOParser($content) {
		$tok = strtok($content, "\r\n");
		while ($tok) {
			$this->fcontents[] = $tok;
			$tok = strtok("\r\n");
		}
		$this->count = count($this->fcontents);
	}
	
	/**
	 * implementation of parse method
	 */
	function parse() {
		$this->p = 0;
		$this->document = array();
		$this->document['LIST'] = array();
		
		$matches = null;
		
		$lineArray = array();
		while ($this->hasNext()) {
			$line = $this->getNext();
			if (mb_ereg(self::KBBANKA, trim($line), $matches)) {
				$lineArray[] = $line;
				$lineArray[] = $this->getNext();
				// bank header
				//
				$this->document['BANK_NAME'] = $matches[0];
				
				while ($this->hasNext()) {
					$line = $this->getNext();
					
					if (mb_ereg_match(self::ACCOUNT_DELIMETER, $this->getCurrent())) {
						$this->document['ACCOUNT_NUMBER'] = str_replace("-", "", trim(mb_substr($lineArray[2], 42, 18)));
						$this->document['BANK_NUMBER'] = "0100";
						$this->document['CURRENCY'] = "CZK";
						
						$this->document['IBAN'] = trim(mb_substr($lineArray[4], 42, 30));
						
						$this->document['ACCOUNT_NAME'] = trim(mb_substr($lineArray[10], 55, 20));
						
						$dateSpan = trim(mb_substr($lineArray[3], 42, 17));
						if (mb_ereg(self::ZA_OBDOBI, $dateSpan, $matches)) {
							$this->document['LIST_DATE_FROM'] = "20" . $matches[3] . "-" . $matches[2] . "-" . $matches[1];
							$this->document['LIST_DATE_TO'] = "20" . $matches[6] . "-" . $matches[5] . "-" . $matches[4];
						} else {
							$dateSpan = trim(mb_substr($lineArray[3], 42, 28));
							if (mb_ereg(self::ZA_OBDOBI2, $dateSpan, $matches)) {
								$this->document['LIST_DATE_FROM'] = "20" . $matches[3] . "-" . $matches[2] . "-" . $matches[1];
								$this->document['LIST_DATE_TO'] = "20" . $matches[6] . "-" . $matches[5] . "-" . $matches[4];
							} else {
								throw new Exception("nelze provést match $dateSpan)");
							}
						}
						
						$darr = explode("-", $this->document['LIST_DATE_FROM']);
						$this->document['YEAR'] = $darr[0];
						
						$this->document['LIST_NO'] = trim(mb_substr($lineArray[0], 90, 10));
						
						$this->parseAccounts();
						return;
					} else {
						$lineArray[] = $line;
					}
				}
			}
			break;
		}
	}
	
	/**
	 * implementation of parseAccounts method
	 */
	function parseAccounts() {
		global $database;
		
		while (true) {
			$lineArray = array();
			$found = false;
			while ($this->hasNext()) {
				$line = $this->getNext();
				
				if (!$found) {
					$datetime = mb_substr($line, 1, 10);
					if (ereg(self::DATE_PATTERN, $datetime, $matches)) {
						$found = true;
					}
				}
				
				if (mb_ereg_match(self::ACCOUNT_DELIMETER, $line)) {
					if ($found) {
						break;
					}
				} else if ($found) {
					$lineArray[] = $line;
				}
			}
			
			if ($found) {
				$bae = new BankAccountEntry();
				
				//
				// validate datetime
				$datetime = mb_substr($lineArray[0], 1, 10);
				if (ereg(self::DATE_PATTERN, $datetime, $matches)) {
					$date = $matches[3] . "-" . $matches[2] . "-" . $matches[1];
					$bae->BE_datetime = $date . " 00:00:00";
				} else {
					throw new Exception("řádek: $this->p, nelze provést match DATE $datetime");
				}
				
				//
				// validate write_off
				$write_off = trim(mb_substr($lineArray[1], 1, 10));
				if ($write_off == "") {
					$write_off = $date;
				} else {
					if (ereg(self::DATE_PATTERN, $write_off, $matches)) {
						$bae->BE_writeoff_date = $matches[3] . "-" . $matches[2] . "-" . $matches[1];
					} else {
						throw new Exception("řádek: $this->p, nelze provést match DATE $write_off");
					}
				}
				
				$type = trim(mb_substr($lineArray[0], 13, 31));
				$bae->BE_typeoftransaction = 0;
				
				$bae->BE_accountname = "";
				$bae->BE_accountnumber = "";
				$bae->BE_banknumber = 0;
				
				$addMessage = false;
				$accountName = array();
				for ($i = 1; $i < count($lineArray); $i++) {
					$line = $lineArray[$i];
					if (strcmp(trim($line), "Zpráva pro příjemce") == 0) {
						$addMessage = true;
						break;
					}
					
					//
					//try to match account number
					$account_number = trim(mb_substr($line, 13, 31));
					if (ereg(self::ACCOUNT_NAME, $account_number, $matches)) {
						$bae->BE_accountnumber = $matches[1];
						$bae->BE_banknumber = $matches[2];
						
						break;
					}
					
					$accountName[] = trim(mb_substr($line, 13, 31));
				}
				
				$bae->BE_accountname = implode(" ", $accountName);
				
				//
				// Try to collect message
				$messages = array();
				$foundMessage = false;
				for ($i = 0; $i < count($lineArray); $i++) {
					$line = $lineArray[$i];
					if (!$foundMessage) {
						if (strcmp(trim($line), "Zpráva pro příjemce") == 0) {
							$foundMessage = true;
							continue;
						}
					}
					if ($foundMessage) {
						$messages[] = trim($lineArray[$i]);
					}
				}
				$bae->BE_message = implode(" ", $messages);
				
				$symbols = array();
				$i = 0;
				while (count($symbols) < 3 && $i < count($lineArray)) {
					$line = $lineArray[$i];
					
					$symbol = trim(mb_substr($line, 46, 11));
					if ($symbol != "") {
						if (ctype_digit($symbol)) {
							$symbols[] = $symbol;
						} else {
							throw new Exception("řádek: $line, nelze provést match V/K/S symbolu $symbol");
						}
					}
					
					$i++;
				}
				
				if (count($symbols) == 3) {
					$bae->BE_variablesymbol = $symbols[0];
					$bae->BE_constantsymbol = $symbols[1];
					$bae->BE_specificsymbol = $symbols[2];				
				} else {
					throw new Exception("cannot match all symbols");
				}
				
				
				$line = $lineArray[0];
				$amount = trim(mb_substr($line, 85, 13));
				if ($amount == "") {
					$amount = trim(mb_substr($line, 65, 13));
					
					$amount = $line = str_replace(" ", "", $amount);
					$amount = $line = str_replace(".", "", $amount);
					$amount = $line = str_replace(",", ".", $amount);
					if (is_numeric($amount)) {
						$bae->BE_amount = $amount;
					} else {
						throw new Exception("řádek: $this->p, nelze provést match AMOUNT $amount");
					}
				} else {
					$amount = $line = str_replace(" ", "", $amount);
					$amount = $line = str_replace(".", "", $amount);
					$amount = $line = str_replace(",", ".", $amount);
					if (is_numeric($amount)) {
						$bae->BE_amount = $amount;
					} else {
						throw new Exception("řádek: $this->p, nelze provést match AMOUNT $amount");
					}
				}
				
				$bae->BE_charge = "0.00";
				$this->document['LIST'][] = $bae;
			}
			
			if (!$this->hasNext()) return $this->document;
		}
	}
	
	/**
	 * 
	 * @return unknown_type
	 */
	function hasNext() {
		return ($this->p < $this->count);
	}
	
	/**
	 * 
	 * @return unknown_type
	 */
	function getCurrent() {
		$text = $this->fcontents[$this->p - 1];
		return $text;
	}
	
	/**
	 * 
	 * @return unknown_type
	 */
	function getNext() {
		if ($this->p == $this->count) {
			throw new Exception("getNext(): Not such element");
		}
		$text = $this->fcontents[$this->p++];
		return $text;
	}
	
	/**
	 * implementation of getDocument method
	 */
	function getDocument() {
		return $this->document;
	}
} // End of KBABOParser class
?>
