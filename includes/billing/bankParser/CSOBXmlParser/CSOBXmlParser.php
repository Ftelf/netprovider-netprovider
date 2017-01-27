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
 * CSOBXmlParser
 */
class CSOBXmlParser {
	private $xml = null;
	
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
	 * Constructor CSOBXmlParser
	 * @param String $content plain text with list
	 */
	public function __construct($xml) {
		$this->xml = $xml;
		
		$this->document = array();
		$this->document['LIST'] = array();
	}
	
	/**
	 * implementation of parse method
	 */
	function parse() {
		$xml = new SimpleXMLElement($this->xml);
		
		if ($xml->STA_VER == '01.0000') {
			if (isset($xml->FINSTA03)) {
				$FINSTA03 = $xml->FINSTA03;
				
				if (isset($FINSTA03->S28_CISLO_VYPISU)) {
					$this->document['LIST_NO'] = $FINSTA03->S28_CISLO_VYPISU."";
				} else {
					throw new Exception("Missing &lt;S28_CISLO_VYPISU&gt; element");
				}
				
				if (isset($FINSTA03->S25_CISLO_UCTU)) {	//212842322/0300
					$matches = null;
					if (mb_ereg('^([[:digit:]]{1,30})/([[:digit:]]{4})$', $FINSTA03->S25_CISLO_UCTU, $matches)) {
						$this->document['ACCOUNT_NUMBER'] = $matches[1];
						$this->document['IBAN'] = $this->document['ACCOUNT_NUMBER'];
						$this->document['BANK_NUMBER'] = $matches[2];
					} else {
						throw new Exception("Incorrect account number");
					}
				} else {
					throw new Exception("Missing &lt;S25_CISLO_UCTU&gt; element");
				}
				
				if (isset($FINSTA03->SHORTNAME)) {
					$this->document['ACCOUNT_NAME'] = $FINSTA03->SHORTNAME[0]."";
				}
				
				if (isset($FINSTA03->S60_MENA)) {
					$this->document['CURRENCY'] = $FINSTA03->S60_MENA."";
				} else {
					throw new Exception("Missing &lt;S60_MENA&gt; element");
				}
				
				if (isset($FINSTA03->S60_DATUM)) {
					if (mb_ereg('^([[:digit:]]{1,2})\.([[:digit:]]{1,2})\.([[:digit:]]{4})$', $FINSTA03->S60_DATUM, $matches)) {
						$this->document['LIST_DATE_FROM'] = $matches[3] . "-" . $matches[2] . "-" . $matches[1];
					} else {
						throw new Exception("Incorrect S60_DATUM date format");
					}
				} else {
					throw new Exception("Missing &lt;S60_DATUM&gt; element");
				}
				
				if (isset($FINSTA03->S62_DATUM)) {
					if (mb_ereg('^([[:digit:]]{1,2})\.([[:digit:]]{1,2})\.([[:digit:]]{4})$', $FINSTA03->S62_DATUM, $matches)) {
						$this->document['LIST_DATE_TO'] = $matches[3] . "-" . $matches[2] . "-" . $matches[1];
					} else {
						throw new Exception("Incorrect S62_DATUM date format");
					}
				} else {
					throw new Exception("Missing &lt;S62_DATUM&gt; element");
				}
			} else {
				throw new Exception("Missing &lt;FINSTA03&gt; element");
			}
			
			if (isset($FINSTA03->FINSTA05)) {
				foreach ($FINSTA03->FINSTA05 as $FINSTA05) {
					$bankAccountEntry = new BankAccountEntry();
					
					if (isset($FINSTA05->DPROCD)) {
						if (mb_ereg('^([[:digit:]]{1,2})\.([[:digit:]]{1,2})\.([[:digit:]]{4})$', $FINSTA05->DPROCD, $matches)) {
							$bankAccountEntry->BE_datetime = $matches[3] . "-" . $matches[2] . "-" . $matches[1];
							$bankAccountEntry->BE_writeoff_date = $bankAccountEntry->BE_datetime;
						} else {
							throw new Exception("Incorrect DPROCD date format");
						}
					} else {
						throw new Exception("Missing &lt;DPROCD&gt; element");
					}
					
//					if (isset($FINSTA05->DPROCOTHER)) {
//						if (mb_ereg('^([[:digit:]]{1,2})\.([[:digit:]]{1,2})\.([[:digit:]]{4})$', $FINSTA05->DPROCOTHER, $matches)) {
//							$bankAccountEntry->BE_writeoff_date = $matches[3] . "-" . $matches[2] . "-" . $matches[1];
//						} else {
//							throw new Exception(sprintf("Incorrect DPROCOTHER date format: %s", $FINSTA05->DPROCOTHER));
//						}
//					} else {
//						throw new Exception("Missing &lt;DPROCOTHER&gt; element");
//					}
					
					if (isset($FINSTA05->REMARK)) {
						$bankAccountEntry->BE_note = $FINSTA05->REMARK."";
					} else {
						$bankAccountEntry->BE_note = null;
					}
					
					if (isset($FINSTA05->PART_ACCNO)) {
						$bankAccountEntry->BE_accountname = $FINSTA05->PART_ACCNO."";
					} else {
						throw new Exception("Missing &lt;PART_ACCNO&gt; element");
					}
					
					if (isset($FINSTA05->PART_ACC_ID)) {
						$bankAccountEntry->BE_accountnumber = $FINSTA05->PART_ACC_ID."";
					} else {
						throw new Exception("Missing &lt;PART_ACC_ID&gt; element");
					}
					
					if (isset($FINSTA05->PART_BANK_ID)) {
						$bankAccountEntry->BE_banknumber = $FINSTA05->PART_BANK_ID."";
					} else {
						throw new Exception("Missing &lt;PART_BANK_ID&gt; element");
					}
					
					if (isset($FINSTA05->S86_VARSYMOUR)) {
						$bankAccountEntry->BE_variablesymbol = ($FINSTA05->S86_VARSYMOUR == '') ? null : $FINSTA05->S86_VARSYMOUR."";
					} else {
						$bankAccountEntry->BE_variablesymbol = null;
					}
					
					if (isset($FINSTA05->S86_KONSTSYM)) {
						$bankAccountEntry->BE_constantsymbol = ($FINSTA05->S86_KONSTSYM == '') ? null : $FINSTA05->S86_KONSTSYM."";
					} else {
						$bankAccountEntry->BE_constantsymbol = null;
					}
					
					if (isset($FINSTA05->S86_SPECSYMOUR)) {
						$bankAccountEntry->BE_specificsymbol = ($FINSTA05->S86_SPECSYMOUR == '') ? null : $FINSTA05->S86_SPECSYMOUR."";
					} else {
						$bankAccountEntry->BE_specificsymbol = null;
					}
					
					if (isset($FINSTA05->S61_CASTKA)) {
						$bankAccountEntry->BE_amount = doubleval($FINSTA05->S61_CASTKA);
					} else {
						throw new Exception("Missing &lt;S61_CASTKA&gt; element");
					}
					
					$bankAccountEntry->BE_charge = 0;
					
					if (isset($FINSTA05->PART_MSG_1)) {
						$bankAccountEntry->BE_message = $FINSTA05->PART_MSG_1."";
					} else {
						$bankAccountEntry->BE_message = null;
					}
					
					$bankAccountEntry->BE_typeoftransaction = 0;
					

					$this->document['LIST'][] = $bankAccountEntry;
				}
			}
		} else {
			throw new Exception("Version of xml document is not compatible");
		}
		
//		echo "<pre>";
//		print_r($this->document);
//		
//		
		$this->document['BANK_NAME'] = null;
		$this->document['YEAR'] = null;
//		
//		print_r($xml);
//		echo "</pre>";
//		exit();
	}
	
	/**
	 * implementation of getDocument method
	 * @return array
	 */
	function getDocument() {
		return $this->document;
	}
} // End of CSOBXmlParser class
?>