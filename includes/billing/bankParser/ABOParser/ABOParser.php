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

/**
 * ABOParser class
 */
class ABOParser {
	private $fcontents = array();
	private $p;
	private $count;
	
	private $document = null;
	
	const EBANKA = '^Raiffeisenbank a\.s\.$';
	const BANKOVNI_VYPIS = '^Bankovní výpis č. ([[:digit:]]*)$';
	const ZA = '^za ([[:digit:]]{2}).([[:digit:]]{2}).([[:digit:]]{4})$';
	const ZA_OBDOBI = '^Za období ([[:digit:]]{2}).([[:digit:]]{2}).([[:digit:]]{4})/([[:digit:]]{2}).([[:digit:]]{2}).([[:digit:]]{4})$';
		
	const NAZEV_UCTU = '^Název účtu:[[:space:]]*([[:print:]]*)$';
	const CISLO_UCTU = '^Číslo účtu:[[:space:]]*([[:digit:]]*)/([[:digit:]]{4})$';
	const IBAN = '^IBAN:[[:space:]]*([[:alnum:]]*)$';
	const MENA = '^Měna:[[:space:]]*([[:alpha:]]*)$';
	
	const HARD_DELIMETER = '^=+$';	
	const ACCOUNT_DELIMETER = '^-+$';
	const ACCOUNT_LINE_1 = '^[[:space:]]*[[:digit:]{2}.[[:digit:]{2}.$';
	const ACCOUNT_LINE_2 = '^-+$';
	const ACCOUNT_LINE_3 = '^-+$';
	const ACCOUNT_LINE_4 = '^-+$';
	const DATE_PATTERN = '^([[:digit:]]{2}).([[:digit:]]{2}).$';
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
		21 => "Výběr hotovost",
		22 => "Odchozí ZPS SEPA plat"
	);
	
	/**
	 * Constructor ABOParser
	 * @param String $content plain text with list
	 */
	public function __construct($content) {
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
		
		$hardDelimeter = 0;
		$matches = null;
		while ($this->hasNext()) {
			if (mb_ereg(self::EBANKA, trim($this->getNext()), $matches)) {
				// bank header
				//
				$this->document['BANK_NAME'] = $matches[0];
				if (mb_ereg(self::BANKOVNI_VYPIS, $this->getNext(), $matches)) {
					$this->document['LIST_NO'] = $matches[1];
				} else {
					throw new Exception("nelze provést match $this->getCurrent()");
				}
				if (mb_ereg(self::ZA, $this->getNext(), $matches)) {
					$this->document['LIST_DATE_FROM'] = $matches[3] . "-" . $matches[2] . "-" . $matches[1];
					
					$this->document['LIST_DATE_TO'] = $matches[3] . "-" . $matches[2] . "-" . $matches[1];
				} else if (mb_ereg(self::ZA_OBDOBI, $this->getCurrent(), $matches)) {
					$this->document['LIST_DATE_FROM'] = $matches[3] . "-" . $matches[2] . "-" . $matches[1];
					$this->document['LIST_DATE_TO'] = $matches[6] . "-" . $matches[5] . "-" . $matches[4];
				} else {
					throw new Exception("nelze provést match $this->getCurrent()");
				}
				$darr = explode("-", $this->document['LIST_DATE_FROM']);
				$this->document['YEAR'] = $darr[0];
			}
			// bank account header
			//
			if (mb_ereg(self::NAZEV_UCTU, $this->getCurrent(), $matches)) {
					$this->document['ACCOUNT_NAME'] = $matches[1];
				if (mb_ereg(self::CISLO_UCTU, $this->getNext(), $matches)) {
					$this->document['ACCOUNT_NUMBER'] = $matches[1];
					$this->document['BANK_NUMBER'] = $matches[2];
				} else {
					throw new Exception("nelze provést match $this->getCurrent()");
				}
				if (mb_ereg(self::IBAN, $this->getNext(), $matches)) {
					$this->document['IBAN'] = $matches[1];
				} else {
					throw new Exception("nelze provést match $this->getCurrent()");
				}
				if (mb_ereg(self::MENA, $this->getNext(), $matches)) {
					$this->document['CURRENCY'] = $matches[1];
				} else {
					throw new Exception("nelze provést match $this->getCurrent()");
				}
			}
			if (mb_ereg_match(self::HARD_DELIMETER, $this->getCurrent())) {
				if (++$hardDelimeter == 5) {
					$this->parseAccounts();
				};
			}
		}
	}
	
	/**
	 * implementation of parseAccounts method
	 */
	function parseAccounts() {
		global $database;
		
		while (true) {
			if ($this->hasNext()) {
				$line_1 = $this->getNext();
			}
			if (!mb_strlen(trim($line_1))) {
				return $this->document;
			}

			$matches = null;
			if (mb_ereg(self::PREHLED_UVERU_K_UCTU_C, $line_1, $matches)) {
				return;
			}
			
			if ($this->hasNext()) {
				$line_2 = $this->getNext();
			}
			if ($this->hasNext()) {
				$line_3 = $this->getNext();
			}
			$line_4 = array();
			while ($this->hasNext()) {
				$line = $this->getNext();
				if (mb_ereg_match(self::ACCOUNT_DELIMETER, $line)) {
					$line = null;
					break;
				} else {
					$line_4[] = trim($line);
				}
			}
			
			$acc = array();
			$bae = new BankAccountEntry();
			// validate date
			$date = mb_substr($line_1, 5, 6);
			if (mb_ereg(self::DATE_PATTERN, $date, $matches)) {
				$dateP = $this->document['YEAR'] . "-" . $matches[2] . "-" . $matches[1];
			} else {
				throw new Exception("řádek: '$this->p', nelze provést match pattern: '" . self::DATE_PATTERN . "', text: '$date', line: '$line'");
			}
			$bae->BE_note = trim((mb_substr($line_1, 11, 22)));
			$write_off = mb_substr($line_1, 33, 6);
			if (mb_ereg(self::DATE_PATTERN, $write_off, $matches)) {
				$bae->BE_writeoff_date = $this->document['YEAR'] . "-" . $matches[2] . "-" . $matches[1];
			} else {
				throw new Exception("řádek: $this->p, nelze provést match WRITE_OFF $write_off");
			}
			$specific_symbol = trim(mb_substr($line_1, 44, 11));
			if (!$specific_symbol == "") {
				if (ctype_digit($specific_symbol)) {
					$bae->BE_specificsymbol = $specific_symbol;
				} else {
					throw new Exception("řádek: $this->p, nelze provést match SPECIFIC_SYMBOL $specific_symbol");
				}
			}
			$amount = trim(mb_substr($line_1, 60, 16));
			if ($amount == "") {
				$bae->BE_amount = "0.00";
			} else {
				$amount = $line = str_replace(" ", "", $amount);
				if (is_numeric($amount)) {
					$bae->BE_amount = $amount;
				} else {
					throw new Exception("řádek: $this->p, nelze provést match AMOUNT $amount");
				}
			}
			$charge = trim(mb_substr($line_1, 77, 9));
			if ($charge == "") {
				$bae->BE_charge = "0.00";
			} else {
				$charge = $line = str_replace(" ", "", $charge);
				if (is_numeric($charge)) {
					$bae->BE_charge = $charge;
				} else {
					throw new Exception("řádek: $this->p, nelze provést match CHARGE $charge");
				}
			}
			//
			// line #2
			// validate time
			$time = mb_substr($line_2, 5, 5);
			if (mb_ereg(self::TIME_PATTERN, $time, $matches)) {
				$timeP = $matches[1] . ":" . $matches[2] . ":00";
			} else {
				throw new Exception("řádek: $this->p, nelze provést match TIME $time");
			}
			$bae->BE_datetime = $dateP . " " . $timeP;
			$bae->BE_accountname = trim(mb_substr($line_2, 11, 33));
			$variable_symbol = trim(mb_substr($line_2, 44, 11));
			if (!$variable_symbol == "") {
				if (ctype_digit($variable_symbol)) {
					$bae->BE_variablesymbol = $variable_symbol;
				} else {
					throw new Exception("řádek: $this->p, nelze provést match VARIABLE_SYMBOL $variable_symbol");
				}
			}
			$account_number = trim(mb_substr($line_3, 11, 33));
			if (!mb_strlen($account_number)) {
				$bae->BE_accountnumber = "";
				$bae->BE_banknumber = 0;
			} else if (mb_ereg(self::ACCOUNT_NAME, $account_number, $matches)) {
				$bae->BE_accountnumber = $matches[1];
				$bae->BE_banknumber = $matches[2];
			} else {
				throw new Exception("řádek: $this->p, nelze provést match ACCOUNT_NUMBER/BANK_NUMBER $account_number");
			}
			$constant_symbol = trim(mb_substr($line_3, 44, 11));
			if (!$constant_symbol == "") {
				if (ctype_digit($constant_symbol)) {
					$bae->BE_constantsymbol = $constant_symbol;
				} else {
					throw new Exception("řádek: $this->p, nelze provést match CONSTANT_SYMBOL $constant_symbol");
				}
			}
			$type = trim(mb_substr($line_3, 55, 21));
			$found = false;
			foreach (self::$KNOWN_TRANSACTION_ARRAY as $k => $typeOfTransaction) {
				if ($type == $typeOfTransaction) {
					$bae->BE_typeoftransaction = $k;
					$found = true;
					break;
				}
			}
			if (!$found) {
				$database->log(sprintf("Line: %s, transaction type is unknown: '%s'", $this->p, $type), LOG::LEVEL_DEBUG);
				
				$bae->BE_typeoftransaction = 0;
//				throw new Exception(sprintf("Line: %s, transaction type is unknown: '%s'", $this->p, $type));
			}
			if (count($line_4) == 0) {
				$bae->BE_message = "";
			} else {
				$bae->BE_message = implode(" ", $line_4);
			}
			$this->document['LIST'][] = $bae;
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
} // End of ABOParser class
?>
