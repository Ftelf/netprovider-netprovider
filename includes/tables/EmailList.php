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
    /** @var int type of maillist */
    var $EL_listtype = null;
    /** @var text list entry count */
    var $EL_entrycount = null;
    /** @var int status of maillist */
    var $EL_status = null;

    const STATUS_PENDING = 1;
    const STATUS_ERROR = 2;
    const STATUS_COMPLETED = 3;

    const LISTTYPE_NONE = 1;
    const LISTTYPE_TXT = 2;
    const LISTTYPE_PDF = 3;
    const LISTTYPE_SEPA_XML = 5;

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

    public static function getLocalizedListType($listtype) {
            switch ($listtype) {
                case self::LISTTYPE_NONE:
                    return _("No attachment");

                case self::LISTTYPE_TXT:
                    return _("TXT file");

                case self::LISTTYPE_PDF:
                    return _("PDF file");

                case self::LISTTYPE_SEPA_XML:
                    return _("ISO SEPA XML file");
            }
        }
} // End of EmailList class
?>
