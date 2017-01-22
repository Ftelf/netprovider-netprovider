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
 * EmailList
 */
class EmailList {
	/** @var int emaillist id PK */
	var $EL_emaillistid = null;
	/** @var int bankaccount id FK */
	var $EL_bankaccountid = null;
	/** @var varchar(255) imap id of email */
	var $EL_uidl = null;
	/** @var varchar(255) name of file with list */
	var $EL_name = null;
	/** @var varchar(255) currency */
	var $EL_currency = null;
	/** @var int year */
    var $EL_year = null;
	/** @var int order number */
    var $EL_no = null;
    /** @var datetime start date of listing */
    var $EL_datefrom = null;
    /** @var datetime end date of listing */
    var $EL_dateto = null;
    /** @var text list */
    var $EL_list = null;
    /** @var text list entry count */
    var $EL_entrycount = null;
    /** @var int status of maillist */
    var $EL_status = null;
	
	const STATUS_PENDING = 1;
	const STATUS_ERROR = 2;
	const STATUS_COMPLETED = 3;
	
	public static function getLocalizedStatus($status) {
		switch ($status) {
			case self::STATUS_PENDING:
				return _("Waiting for process");
			
			case self::STATUS_ERROR:
				return _("Error");
			
			case self::STATUS_COMPLETED:
				return _("Completed");
		}
	}
} // End of EmailList class
?>