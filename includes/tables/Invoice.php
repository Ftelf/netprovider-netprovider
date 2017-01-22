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
 * Invoice
 */
class Invoice {
	/** @var int charge id PK */
	var $IN_invoiceid = null;
	/** @var int charge id PK */
	var $IN_invoicenumber = null;
	/** @var varchar(255) name of the charge */
	var $IN_personid = null;
	/** @var varchar(255) name of the charge */
	var $IN_chargeentryid = null;
	/** @var varchar(255) description */
	var $IN_dateofpay = null;
	/** @var int charge every time period */
	var $IN_invoicedate = null;
	/** @var DECIMAL(10,2) charge amount */
	var $IN_taxdate = null;
	/** @var datetime recomended date of payment */
	var $IN_recommendedpaydate = null;
	/** @var int bank account number */
	var $IN_bankaccount = null;
	/** @var int payment constant symbol */
	var $IN_constantsymbol = null;
	/** @var int payment variable symbol */
	var $IN_variablesymbol = null;
	/** @var int payment specific symbol */
	var $IN_specificsymbol = null;
	/** @var DECIMAL(10,2) base amount for vat */
	var $IN_baseamount = null;
	/** @var DECIMAL(10,2) amount */
	var $IN_amount = null;
	/** @var varchar(10) currency of account */
	var $IN_currency = null;
} // End of Invoice class
?>