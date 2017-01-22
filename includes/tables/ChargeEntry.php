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
 * ChargeEntry
 */
class ChargeEntry {
	/** @var int chargeEntry id PK */
	var $CE_chargeentryid = null;
	/** @var int haschargeid FK */
	var $CE_haschargeid = null;
	/** @var date date period charge is intend for*/
	var $CE_period_date = null;
	/** @var int write-off offset in days */
	var $CE_writeoffoffset = null;
	/** @var date date charge was realized */
	var $CE_realize_date = null;
	/** @var int overdue days */
	var $CE_overdue = null;
	/** @var DECIMAL(10,2) */
	var $CE_vat = null;
	/** @var DECIMAL(10,2) */
	var $CE_baseamount = null;
	/** @var DECIMAL(10,2) charge amount */
	var $CE_amount = null;
	/** @var varchar(10) currency of account */
	var $CE_currency = null;
	/** @var int status of chargeEntry */
	var $CE_status = null;
	
	const STATUS_FINISHED = 1;
	const STATUS_PENDING = 2;
	const STATUS_PENDING_INSUFFICIENTFUNDS = 3;
	const STATUS_TESTINGFREEOFCHARGE = 4;
	const STATUS_DISABLED = 5;
	const STATUS_ERROR = 6;
	
	public static $STATUS_ARRAY = array(
		1, //Finished
		2, //Pending
		3, //Pending, insufficient funds
		4, //Testing, free of charge
		5, //Disabled
		6, //Error
	);
	
	public static function getLocalizedStatus($status) {
		switch ($status) {
			case self::STATUS_FINISHED:
				return _("Finished");
			
			case self::STATUS_PENDING:
				return _("Pending");
			
			case self::STATUS_PENDING_INSUFFICIENTFUNDS:
				return _("Pending, insufficient funds");
			
			case self::STATUS_TESTINGFREEOFCHARGE:
				return _("Testing, free of charge");
			
			case self::STATUS_DISABLED:
				return _("Disabled for this period");
			
			case self::STATUS_ERROR:
				return _("Error");
		}
	}
} // End of ChargeEntry class
?>