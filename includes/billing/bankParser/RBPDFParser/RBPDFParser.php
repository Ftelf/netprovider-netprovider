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

require_once($core->getAppRoot() . "includes/PdfParser/Parser.php");

/**
 * RBPDFParser class
 */
class RBPDFParser {
    private $fcontents = array();
    private $p;
    private $count;

    private $document = null;

    const HEADER = '^Banka inspirovaná klienty Výpis z běžného účtu$';
    const BANKOVNI_VYPIS = '^poř. č. ([[:digit:]]+) za období ([[:digit:]]{1,2})\.([[:digit:]]{1,2})\.([[:digit:]]{4}) - ([[:digit:]]{1,2})\.([[:digit:]]{1,2})\.([[:digit:]]{4})$';

    const ADDRESS_1 = 'OVJIH.NET';
    const ADDRESS_2 = 'Dr. Martínka 1418/18';
    const ADDRESS_3 = 'Ostrava - Hrabůvka';

    const NAZEV_UCTU = '^Název účtu: ([[:print:]]+)$';
    const CISLO_UCTU = 'Číslo účtu: ([[:digit:]]+)\/([[:digit:]]{4}) ([[:alpha:]]{3})$';
    const IBAN = '^IBAN: ([[:alnum:]]{4} [[:alnum:]]{4} [[:alnum:]]{4} [[:alnum:]]{4} [[:alnum:]]{4} [[:alnum:]]{4})$';
    const BIC = '^BIC: ([[:alnum:]]+)$';

    const NAZEV_UCTU_ALTERNATE = '^Účet: +([[:digit:]]+)/([[:digit:]]{4}) +v měně +[[:alpha:]]{3}$';

    const ACCOUNT_HEADER1 = '^Datum Kategorie transakce Typ transakce VS Poplatek Částka$';
    const ACCOUNT_HEADER2 = '^Valuta Číslo protiúčtu Zpráva KS Původní částka$';
    const ACCOUNT_HEADER3 = '^Kód transakce Název protiúčtu Poznámka SS Kurz$';

    const ACCOUNT_ENTRY_LINE_1 = '^([[:digit:]]{1,2})\.([[:digit:]]{1,2})\.([[:digit:]]{4})([^[:digit:]]*)([[:digit:]]{1,10})? (-?[[:digit:]]{1,3}?\s?[[:digit:]]{1,3}\.[[:digit:]]{2}) ([[:alpha:]]{3})$';
    const ACCOUNT_ENTRY_LINE_2_1 = '^([[:digit:]]{1,2})\.([[:digit:]]{1,2})\.([[:digit:]]{4})( ([[:digit:]]{1,6}-)?([[:digit:]]+)\/([[:digit:]]{2,4}))? ?([[:digit:]]{1,10})?$';
    const ACCOUNT_ENTRY_LINE_2_2 = '^([[:digit:]]{1,4}) ?([[:digit:]]{1,10})?$';
    const ACCOUNT_ENTRY_LINE_3_1 = '^([[:digit:]]+)( [\S]+[,\s]+[\S]+)?( \S.*)?';
    const ACCOUNT_ENTRY_LINE_3_X = '^(.*)$';



    const TRAILING_TEXT_1 = '^Zpráva pro klienta$';

    const FOOTER_1 = '^Raiffeisenbank a.s., Hvězdova 1716/2b, 140 78 Praha 4, zapsaná v OR vedeném Městským soudem v Praze, oddíl B, vložka 2051, IČO 49240901|Raiffeisenbank a.s. , Hvězdova 1716/2b • PO box 64 • 140 78 Praha 4 • tel.: 800 900 900 • e-mail: info@rb.cz • web: www.rb.cz • IČ: 49240901$';
    const FOOTER_2 = '^e-mail: info@rb.cz, www.rb.cz, infolinka 800 900 900 Strana   [[:digit:]]{1,3} / [[:digit:]]{1,3}|zapsaná v obchodním rejstříku vedeném Městským soudem v Praze, sp. zn. B, 2051 Strana   [[:digit:]]{1,3} / [[:digit:]]{1,3}$';

    static $KNOWN_TRANSACTION_CATEGORY_ARRAY = array(
        0  => 'Platba',
        1  => 'Poplatek',
        2  => 'Trvalá platba'
    );

    static $KNOWN_TRANSACTION_ARRAY = array(
        0  => 'Jiný',
        1  => 'Převod',
        2  => 'Příchozí platba',
        3  => 'Vklad hotovosti',
        4  => 'Trvalý převod',
        5  => 'Jiný trans. poplatek',
        6  => 'Kladný úrok',
        7  => 'Výběr z bankomatu',
        8  => 'Platba kartou',
        9  => 'Generování bankovních',
        10 => 'Poplatek za vedení ka',
        11 => 'Distribuce bankovního',
        12 => 'Poolovací převod',
        13 => 'Využívání a správa In',
        14 => 'Úročení úvěru',
        15 => 'Splácení úvěru',
        16 => 'Využívání a správa Ko',
        17 => 'Poplatek za externí z',
        18 => 'Vydání dávkového cert',
        19 => 'Čerpání Investičního',
        20 => 'Poplatek za transakci',
        21 => 'Výběr hotovost',
        22 => 'Odchozí ZPS SEPA plat',
        23 => 'Odchozí platba',
        24 => 'Srážka daně z úroků',
        25 => 'Trvalá platba',
        26 => 'Jednorázová platba'
    );

    /**
     * Constructor RBPDFParser
     * @param String $content plain text with list
     */
    public function __construct($content) {
        $parser = new \Smalot\PdfParser\Parser();

        $pdf = $parser->parseContent($content);

        $text = $pdf->getText();

//        echo "<pre>".$text."</pre>";

        $tok = strtok($text, "\r\n");
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

        $this->searchHeader();

        $this->searchAccounts();

//        echo '<pre>';
//        print_r($this->document);
//        echo '</pre>';
    }

    function searchHeader() {
        while ($this->hasNext()){
            if ($matches = $this->tryMatchNextLine(self::HEADER)) {
                // bank header
                //
                $matches = $this->matchNextLine(self::BANKOVNI_VYPIS);
                $this->document['LIST_NO'] = $matches[1];
                $this->document['LIST_DATE_FROM'] = $matches[4] . "-" . $matches[3] . "-" . $matches[2];
                $this->document['LIST_DATE_TO'] = $matches[7] . "-" . $matches[6] . "-" . $matches[5];
                $this->document['YEAR'] = $matches[4];

                $matches = $this->matchNextLine(self::ADDRESS_1);
                $matches = $this->matchNextLine(self::ADDRESS_2);
                $matches = $this->matchNextLine(self::ADDRESS_3);

                $matchesAcountNumber = $this->matchNextLine(self::CISLO_UCTU);
                $this->document['ACCOUNT_NUMBER'] = $matchesAcountNumber[1];
                $this->document['BANK_NUMBER'] = $matchesAcountNumber[2];
                $this->document['CURRENCY'] = $matchesAcountNumber[3];
                $matches = $this->matchNextLine(self::NAZEV_UCTU);
                $matches = $this->matchNextLine(self::IBAN);
                $matches = $this->matchNextLine(self::BIC);

                break;
            }
        }
    }

    /**
     * implementation of searchAccounts method
     */
    function searchAccounts() {
        global $database;

        while ($this->hasNext()) {
            if ($this->tryMatchNextLine(self::ACCOUNT_HEADER1)) {
                $this->matchNextLine(self::ACCOUNT_HEADER2);
                $this->matchNextLine(self::ACCOUNT_HEADER3);

                break;
            }
        }

        while ($accountEntry = $this->searchAccountEntry()) {
            $this->document['LIST'][] = $accountEntry;
        }
    }

    function searchAccountEntry() {
        if ($this->tryMatchNextLine(self::TRAILING_TEXT_1)) {
            return false;
        }

        $this->moveBack();

        $bae = new BankAccountEntry();

        $matches1 = $this->matchNextLine(self::ACCOUNT_ENTRY_LINE_1);

        $descriptionString = trim($matches1[4]);
        $remainingString = null;
        $foundTransactionType = false;

        $bae->BE_note = "";

        foreach (self::$KNOWN_TRANSACTION_CATEGORY_ARRAY as $k => $transactionCategory) {
            if (mb_strpos($descriptionString, $transactionCategory) === 0) {
                $remainingString = trim(mb_substr($descriptionString, mb_strlen($transactionCategory)));
                break;
            }
        }
        if ($remainingString === null) {
            throw new Exception("Neznámá Kategorie transakce v textu: ".$descriptionString);
        }

        foreach (self::$KNOWN_TRANSACTION_ARRAY as $k => $transactionType) {
            if ($remainingString == $transactionType) {
                $bae->BE_typeoftransaction = $k;
                $foundTransactionType = true;
                break;
            }
        }
        if (!$foundTransactionType) {
            throw new Exception("Neznámý Typ transakce v textu: ".$remainingString);
        }

        $matches2_1 = $this->matchNextLine(self::ACCOUNT_ENTRY_LINE_2_1);
        if ($bae->BE_typeoftransaction === 20) {
            if ($matches2_1[4]) {
                throw new Exception('U položky "'.self::$KNOWN_TRANSACTION_ARRAY[20].'" by nemělo být uvedeno bankovní konto');
            }

            if ($matches2_1[8]) {
                $bae->BE_constantsymbol = $matches2_1[8];
            }
        } elseif (mb_strlen($matches2_1[7]) < 4) {
            $matches2_2 = $this->matchNextLine(self::ACCOUNT_ENTRY_LINE_2_2);

            $bae->BE_accountnumber = $matches2_1[5].$matches2_1[6];
            $bae->BE_banknumber = $matches2_1[7].$matches2_2[1];

            if ($matches2_2[2]) {
                $bae->BE_constantsymbol = $matches2_2[2];
            }
        } else {
            $bae->BE_accountnumber = $matches2_1[5].$matches2_1[6];
            $bae->BE_banknumber = $matches2_1[7];

            if ($matches2_1[8]) {
                $bae->BE_constantsymbol = $matches2_1[8];
            }
        }

        $matches3_1 = $this->matchNextLine(self::ACCOUNT_ENTRY_LINE_3_1);

        $followingNote = $this->parseVariableNoteLines();
        $bae->BE_note = trim($matches3_1[3]).$followingNote;

        $bae->BE_datetime = $matches1[3] . "-" . str_pad($matches1[2], 2, "0", STR_PAD_LEFT) . "-" . str_pad($matches1[1], 2, "0", STR_PAD_LEFT) . " 00:00:00";
        $bae->BE_writeoff_date = $matches2_1[3] . "-" . str_pad($matches2_1[2], 2, "0", STR_PAD_LEFT) . "-" . str_pad($matches2_1[1], 2, "0", STR_PAD_LEFT);

        $bae->BE_variablesymbol = $matches1[5];
        $bae->BE_amount = preg_replace("/\s|&nbsp;/",'', htmlentities($matches1[6]));

        $bae->BE_accountname = $matches3_1[2];

        $bae->BE_charge = 0;
        $bae->BE_message = "";

        if (!$bae->BE_variablesymbol) {
            $bae->BE_variablesymbol = null;
        }

        if (!$bae->BE_accountnumber) {
            $bae->BE_accountnumber = "0";
        }

        if (!$bae->BE_banknumber) {
            $bae->BE_banknumber = "0";
        }

        return $bae;
    }

    function parseVariableNoteLines() {
        $maxlines = 3;

        $note = "";
        while ($maxlines-- > 0) {
            $line3_x = $this->getNext();
            if ($this->tryMatch(self::ACCOUNT_ENTRY_LINE_1, $line3_x)) {
                $this->moveBack();

                return $note;
            } else if ($this->tryMatch(self::TRAILING_TEXT_1, $line3_x)) {
                $this->moveBack();

                return $note;
            } elseif ($this->tryMatch(self::FOOTER_1, $line3_x)) {
                // Multipage detected
                $this->matchNextLine(self::FOOTER_2);
                $this->matchNextLine(self::HEADER);
                $this->matchNextLine(self::BANKOVNI_VYPIS);
                $this->matchNextLine(self::NAZEV_UCTU_ALTERNATE);

                $linePage2 = $this->getNext();
                if ($this->tryMatch(self::ACCOUNT_HEADER1, $linePage2)) {
                    $this->matchNextLine(self::ACCOUNT_HEADER2);
                    $this->matchNextLine(self::ACCOUNT_HEADER3);
                } elseif ($this->tryMatch(self::TRAILING_TEXT_1, $linePage2)) {
                    $this->moveBack();
                } else {
                    throw new Exception("Nelze matchnout volitelný řádek 'Multipage detected' 3_$maxlines: $line3_x");
                }

                return $note;
            } elseif ($matches3_x = $this->tryMatch(self::ACCOUNT_ENTRY_LINE_3_X, $line3_x)) {
                $note .= $matches3_x[1];
            } else {
                throw new Exception("Nelze matchnout volitelný řádek 3_$maxlines: $line3_x");
            }
        }

        return $note;
    }

    function tryMatch($pattern, $string) {
        $matches = null;
        if (mb_ereg($pattern, $string, $matches)) {
            return $matches;
        }

        return null;
    }

    function tryMatchNextLine($pattern) {
        return $this->tryMatch($pattern, $this->getNext());
    }

    function matchNextLine($pattern) {
        $nextLine = $this->getNext();
        if ($matches = $this->tryMatch($pattern, $nextLine)) {
            return $matches;
        }

//        echo "<pre>".'nelze provést match na řádku: '.$this->p.', text: "'.$nextLine.'", pattern: "'.$pattern.'"'."</pre>";

        throw new Exception('nelze provést match na řádku: '.$this->p.', text: "'.$nextLine.'", pattern: "'.$pattern.'"');
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
        $text = trim($this->fcontents[$this->p++]);
        return $text;
    }

    /**
     *
     * @return unknown_type
     */
    function moveBack() {
        if ($this->p < 1) {
            throw new Exception("Cannot move back, already at the beginning");
        }

        $this->p--;
    }

    /**
     * implementation of getDocument method
     */
    function getDocument() {
        return $this->document;
    }
} // End of RBPDFParser class
?>
