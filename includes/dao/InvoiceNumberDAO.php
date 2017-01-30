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
require_once($core->getAppRoot() . "/includes/tables/InvoiceNumber.php");

/**
 *  InvoiceNumberDAO
 */
class InvoiceNumberDAO {
    static function getInvoiceNumberByID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $invoiceNumber = new InvoiceNumber();
        $query = "SELECT * FROM `invoicenumber` WHERE `IV_invoicenumberid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($invoiceNumber);
        return $invoiceNumber;
    }
    static function getInvoiceByYear($year) {
        if (!$year) throw new Exception("no ID specified");
        global $database;
        $invoiceNumber = new InvoiceNumber();
        $query = "SELECT * FROM `invoicenumber` WHERE `IV_year`='$year' LIMIT 1";
        $database->setQuery($query);
        $database->loadObject($invoiceNumber);
        return $invoiceNumber;
    }
    static function removeInvoiceNumberByID($id) {
        if (!$id) throw new Exception("no ID specified");
        global $database;
        $query = "DELETE FROM `invoicenumber` WHERE `IV_invoicenumberid`='$id' LIMIT 1";
        $database->setQuery($query);
        $database->query();
    }
} // End of InvoiceDAO class
?>