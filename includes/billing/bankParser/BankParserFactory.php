<?php
/**
 * Ftelf ISP billing system
 * This source file is part of Ftelf ISP billing system
 * see LICENSE for licence details.
 * php version 8.1.12
 *
 * @category Helper
 * @package  NetProvider
 * @author   Lukas Dziadkowiec <i.ftelf@gmail.com>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @link     https://www.ovjih.net
 */

global $core;
require_once $core->getAppRoot() . "includes/billing/bankParser/RBTXTParser/RBTXTParser.php";
require_once $core->getAppRoot() . "includes/billing/bankParser/RBPDFParser/RBPDFParser.php";
require_once $core->getAppRoot() . "includes/billing/bankParser/IsoSepaXmlParser/IsoSepaXmlParser.php";

/**
 * BankParserFactory
 */
class BankParserFactory
{
    private object $parser;

    /**
     * Constructor BankParserFactory
     *
     * @param String $content plain text with list
     */
    public function __construct($type, $content)
    {
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
    public function parse(): void
    {
        $this->parser->parse();
    }

    /**
     * facade method, calls factory getDocument method
     *
     * @return array parsed document
     */
    public function getDocument()
    {
        return $this->parser->getDocument();
    }
} // End of BankParserFactory class
