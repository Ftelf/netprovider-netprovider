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
 * PersonAccountEntry
 */
class PersonAccountEntry {
	/** @var int personaccountentry id PK */
	var $PN_personaccountentryid = null;
	/** @var int bankaccountentry id FK */
	var $PN_bankaccountentryid = null;
	/** @var int personaccount id FK */
	var $PN_personaccountid = null;
	/** @var datetime datetime when entry is received */
	var $PN_date = null;
	/** @var DECIMAL(10,2) amount */
	var $PN_amount = null;
	/** @var varchar(10) currency of account */
	var $PN_currency = null;
	/** @var int source */
	var $PN_source = null;
	/** @var varchar(255) comment */
	var $PN_comment = null;
	
	const SOURCE_BANKACCOUNT = 1;
	const SOURCE_CASH = 2;
	const SOURCE_DISCOUNT = 3;
		
	public static $SOURCE_ARRAY = array(
		1, //Bank transaction
		2, //Cash
		3 //Discount
	);
	
	public static function getLocalizedSource($source) {
		switch ($source) {
			case self::SOURCE_BANKACCOUNT:
				return _("Bank transaction");
			
			case self::SOURCE_CASH:
				return _("Cash");
				
			case self::SOURCE_DISCOUNT:
				return _("Discount");
		}
	}
} // End of PersonAccountEntry class
?>