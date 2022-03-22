<?php
//
// +----------------------------------------------------------------------+
// | Ftelf ISP billing system                                               |
// +----------------------------------------------------------------------+
// | Copyright (c) 2006-2007 Ing. Lukas Dziadkowiec                       |
// +----------------------------------------------------------------------+
// | This source file is part of Ftelf ISP billing system,                  |
// | see LICENSE for licence details.                                     |
// +----------------------------------------------------------------------+
// | Authors: Lukas Dziadkowiec <i.ftelf@gmail.com>                  |
// +----------------------------------------------------------------------+

/**
 * @author  Lukas Dziadkowiec <i.ftelf@gmail.com>
 */

global $core;
require_once($core->getAppRoot() . "includes/tables/BankAccountEntry.php");

/**
 * IsoSepaXmlParser
 */
class IsoSepaXmlParser {
    private $xml = null;

    /**
     * Constructor IsoSepaXmlParser
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
        $document = new SimpleXMLElement($this->xml);

        $nss = $document->getDocNamespaces();
        if (count($nss) != 1) {
            throw new Exception("Incorrect document namespace: '{$nss}'");
        }
        $ns = reset($nss);
        if (strcmp($ns, 'urn:iso:std:iso:20022:tech:xsd:camt.053.001.02') != 0) {
            throw new Exception("Incorrect document namespace: '{$ns}'");
        }

        $this->parseBkToCstmrStmtElement($this->getChild($document, 'BkToCstmrStmt'));
    }

    function parseBkToCstmrStmtElement($bkToCstmrStmtElement) {
        $this->parseGrpHdrElement($this->getChild($bkToCstmrStmtElement, 'GrpHdr'));
        $this->parseStmtElement($this->getChild($bkToCstmrStmtElement, 'Stmt'));
    }

    function parseGrpHdrElement($grpHdr) {
        $listPeriod = $this->getTextValue($grpHdr, 'AddtlInf');
        if (strcmp($listPeriod, 'Denní') !== 0) {
            throw new Exception("This is not a daily list");
        }
    }

    function parseStmtElement($stmtElement) {
        $this->document['LIST_NO'] = $this->getNumberValue($stmtElement, 'LglSeqNb');
        $this->document['IBAN'] = $this->getTextValue($stmtElement, ['Acct', 'Id', 'IBAN']);
        $this->document['CURRENCY'] = $this->getTextValue($stmtElement, ['Acct', 'Ccy']);

        $this->document['ACCOUNT_NUMBER'] = ltrim(mb_substr($this->document['IBAN'], 8, 16), '0');
        $this->document['BANK_NUMBER'] = mb_substr($this->document['IBAN'], 4, 4);


        $this->document['LIST_DATE_FROM'] = $this->getDateTimeValue($stmtElement, ['FrToDt', 'FrDtTm']);
        $this->document['LIST_DATE_TO'] = $this->getDateTimeValue($stmtElement, ['FrToDt', 'ToDtTm']);

        $this->document['NUMBER_OF_ENTRIES'] = $this->getNumberValue($stmtElement, ['TxsSummry', 'TtlNtries', 'NbOfNtries']);

        foreach ($this->getChild($stmtElement, 'Ntry') as $ntry) {
            $this->parseNtryElement($ntry);
        }

        if ($this->document['NUMBER_OF_ENTRIES'] != count($this->document['LIST'])) {
            throw new Exception("Incorrect number of entries");
        }
    }

    function parseNtryElement($ntryElement) {
        $bankAccountEntry = new BankAccountEntry();

        $bankAccountEntry->BE_charge = 0;
        $bankAccountEntry->BE_note = '';

        $amount = $this->getNumberValue($ntryElement, 'Amt');
        $creditDebitIndicator = $this->getTextValue($ntryElement, 'CdtDbtInd');
        if (strcmp($creditDebitIndicator, 'CRDT') == 0) {
            $bankAccountEntry->BE_typeoftransaction = BankAccountEntry::TYPE_INCOMEPAYMENT;
            $bankAccountEntry->BE_amount = number_format(floatval($amount), 2, '.', '');
        } else if (strcmp($creditDebitIndicator, 'DBIT') == 0) {
            $bankAccountEntry->BE_typeoftransaction = BankAccountEntry::TYPE_TRANSACTION;
            $bankAccountEntry->BE_amount = number_format(-floatval($amount), 2, '.', '');
        } else {
            throw new Exception("Unknown CdtDbtInd");
        }

//        $currency = $this->getAttributeTextValue($ntryElement, 'Amt', 'Ccy');

        $bankAccountEntry->BE_writeoff_date = $this->getDateTimeValue($ntryElement, ['BookgDt', 'DtTm']);
        $bankAccountEntry->BE_datetime = $this->getDateTimeValue($ntryElement, ['ValDt', 'DtTm']);

        $ntryDtls = $this->getChild($ntryElement, 'NtryDtls');
        $this->parseTxDtlsElement($this->getSingleChild($ntryDtls, 'TxDtls'), $bankAccountEntry);

        $this->document['LIST'][] = $bankAccountEntry;
    }

    function parseTxDtlsElement($txDtlsElement, $bankAccountEntry) {
        $bankAccountEntry->BE_variablesymbol = $this->getTextValue($txDtlsElement, ['Refs', 'EndToEndId'], true);
        $bankAccountEntry->BE_constantsymbol = $this->getTextValue($txDtlsElement, ['Refs', 'InstrId'], true);
        $bankAccountEntry->BE_specificsymbol = $this->getTextValue($txDtlsElement, ['Refs', 'PmtInfId'], true);

        $bankAccountEntry->BE_accountname = $this->getTextValue($txDtlsElement, ['RltdPties', 'DbtrAcct', 'Nm'], true);
        if ($bankAccountEntry->BE_accountname === null) {
            $bankAccountEntry->BE_accountname = $this->getTextValue($txDtlsElement, ['RltdPties', 'CdtrAcct', 'Nm'], true);
        }
        if ($bankAccountEntry->BE_accountname === null) {
            $bankAccountEntry->BE_accountname = '';
        }
        $bankAccountEntry->BE_accountnumber = $this->getTextValue($txDtlsElement, ['RltdPties', 'DbtrAcct', 'Id', 'Othr', 'Id'], true);
        if ($bankAccountEntry->BE_accountnumber === null) {
            $bankAccountEntry->BE_accountnumber = $this->getTextValue($txDtlsElement, ['RltdPties', 'CdtrAcct', 'Id', 'Othr', 'Id'], true);
        }
        if ($bankAccountEntry->BE_accountnumber === null) {
            $bankAccountEntry->BE_accountnumber = '';
        }
        $bankAccountEntry->BE_banknumber = $this->getNumberValue($txDtlsElement, ['RltdAgts', 'DbtrAgt', 'FinInstnId', 'Othr', 'Id'], true);
        if ($bankAccountEntry->BE_banknumber === null) {
            $bankAccountEntry->BE_banknumber = $this->getTextValue($txDtlsElement, ['RltdAgts', 'CdtrAgt', 'FinInstnId', 'Othr', 'Id'], true);
        }
        if ($bankAccountEntry->BE_banknumber === null) {
            $bankAccountEntry->BE_banknumber = 0;
        }
        $bankAccountEntry->BE_message = $this->getTextValue($txDtlsElement, 'AddtlTxInf', true);
        if ($bankAccountEntry->BE_message === null) {
            $bankAccountEntry->BE_message = '';
        }
    }

    function getChild($parentElement, $elements, $optional = false, $message = '') {
        if (is_array($elements) && count($elements) < 1) {
            throw new Exception("Invalid call parameter, {$message}");
        }

        if (!($parentElement instanceof SimpleXMLElement)) {
            throw new Exception("Element \"{$parentElement}\" is not instance of SimpleXMLElement, {$message}");
        }

        if (is_array($elements) && count($elements) == 1) {
            $elements = $elements[0];
        }

        if (is_array($elements)) {
            $firstElement = array_shift($elements);
            $child = $this->getChild($parentElement, $firstElement, $optional, $message);
            if ($child === null) {
                return null;
            }
            return $this->getChild($child, $elements, $optional, $message);
        }

        if (!isset($parentElement->$elements)) {
            if ($optional) {
                return null;
            }

            throw new Exception("Missing <{$elements}> element in parent <{$parentElement->getName()}>, {$message}");
        }

        return $parentElement->$elements;
    }

    function getSingleChild($parentElement, $element) {
        if (!($parentElement instanceof SimpleXMLElement) || $parentElement->count() != 1 || !isset($parentElement->$element)) {
            throw new Exception("Missing or extra <$element> element in parent <$parentElement>");
        }

        return $parentElement->$element;
    }

    function getTextValue($parentElement, $elements, $optional = false, $message = '') {
        $child = $this->getChild($parentElement, $elements, $optional, $message);

        if ($optional && $child === null) {
            return null;
        }

        if (!($child instanceof SimpleXMLElement)) {
            throw new Exception("Element <$child> is not instanceof SimpleXMLElement, {$message}");
        }

        return $child->__toString();
    }

    function getNumberValue($parentElement, $element, $optional = false, $message = '') {
        $textValue = $this->getTextValue($parentElement, $element, $optional, $message);

        if ($optional && $textValue === null) {
            return null;
        }

        if (is_numeric($textValue)) {
            return $textValue;
        } else {
            throw new Exception("Cannot parse as Number: '$textValue', {$message}");
        }
    }

    function getDateTimeValue($parentElement, $element, $optional = false, $message = '') {
        $textValue = $this->getTextValue($parentElement, $element, $optional, $message);

        if ($optional && $textValue === null) {
            return null;
        }

        $dateTime = DateTime::createFromFormat('Y-m-d\TH:i:s', $textValue);
        if ($dateTime === false) {
            throw new Exception("Cannot parse as DateTime: '$textValue', {$message}");
        }

        return $dateTime->format('Y-m-d H:i:s');
    }

    function getAttributeTextValue($parentElement, $elements, $attributeName, $optional = false, $message = '') {
        $child = $this->getChild($parentElement, $elements, $optional, $message);

        if ($optional && $child === null) {
            return null;
        }

        if (!($child instanceof SimpleXMLElement)) {
            throw new Exception("Element <$child> is not instanceof SimpleXMLElement, {$message}");
        }

        if (!isset($child[$attributeName])) {
            if ($optional) {
                return null;
            }

            throw new Exception("Missing attribute: '{$attributeName}' in element '{$child->getName()}', {$message}");
        }

        return $child[$attributeName]->__toString();
    }

    /**
     * implementation of getDocument method
     * @return array
     */
    function getDocument() {
        return $this->document;
    }
} // End of IsoSepaXmlParser class
?>
