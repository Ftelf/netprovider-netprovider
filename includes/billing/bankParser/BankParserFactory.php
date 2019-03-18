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
require_once($core->getAppRoot() . "includes/billing/bankParser/RBTXTParser/RBTXTParser.php");
require_once($core->getAppRoot() . "includes/billing/bankParser/RBPDFParser/RBPDFParser.php");
require_once($core->getAppRoot() . "includes/billing/bankParser/IsoSepaXmlParser/IsoSepaXmlParser.php");

/**
 * BankParserFactory
 */
class BankParserFactory {
    private $parser = null;

    /**
     * Constructor BankParserFactory
     * @param String $content plain text with list
     */
    public function __construct($type, $content) {
        switch ($type) {
            case BankAccount::DATASOURCE_TYPE_RB_ATTACHMENT_TXT:
                $this->parser = new RBTXTParser($content);
                break;

            case BankAccount::DATASOURCE_TYPE_RB_ATTACHMENT_PDF:
                $this->parser = new RBPDFParser($content);
                break;

            case BankAccount::DATASOURCE_TYPE_ISO_SEPA_XML:
                $this->parser = new IsoSepaXmlParser($content);
                break;

            default:
                throw Exception("Invalid datasource type: '{$type}'");
        }
    }

    /**
     * facade method, calls factory parse method
     */
    function parse() {
        $this->parser->parse();

    }

    /**
     * facade method, calls factory getDocument method
     * @return array parsed document
     */
    function getDocument() {
        return $this->parser->getDocument();
    }
} // End of BankParserFactory class
?>