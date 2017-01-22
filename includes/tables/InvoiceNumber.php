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

/**
 * InvoiceNumber
 */
class InvoiceNumber {
	/** @var int charge id PK */
	var $IV_invoicenumberid = null;
	/** @var int charge id PK */
	var $IV_year = null;
	/** @var varchar(255) name of the charge */
	var $IV_number = null;
} // End of InvoiceNumber class
?>