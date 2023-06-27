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

/**
 * EmailList
 */
class EmailList
{
    /**
     * @public int emaillist id PK
     */
    public $EL_emaillistid;
    /**
     * @public int bankaccount id FK
     */
    public $EL_bankaccountid;
    /**
     * @public varchar(255) imap id of email
     */
    public $EL_uidl;
    /**
     * @public varchar(255) name of file with list
     */
    public $EL_name;
    /**
     * @public varchar(255) currency
     */
    public $EL_currency;
    /**
     * @public int year
     */
    public $EL_year;
    /**
     * @public int order number
     */
    public $EL_no;
    /**
     * @public datetime start date of listing
     */
    public $EL_datefrom;
    /**
     * @public datetime end date of listing
     */
    public $EL_dateto;
    /**
     * @public text list
     */
    public $EL_list;
    /**
     * @public int type of maillist
     */
    public $EL_listtype;
    /**
     * @public text list entry count
     */
    public $EL_entrycount;
    /**
     * @public int status of maillist
     */
    public $EL_status;

    public const STATUS_PENDING = 1;
    public const STATUS_ERROR = 2;
    public const STATUS_COMPLETED = 3;

    public static function getLocalizedStatus($status): string
    {
        return match ($status) {
            self::STATUS_PENDING => "Waiting for process",
            self::STATUS_ERROR => "Error",
            self::STATUS_COMPLETED => "Completed",
            default => "",
        };
    }

    public const LISTTYPE_NONE = 1;
    public const LISTTYPE_TXT = 2;
    public const LISTTYPE_PDF = 3;
    public const LISTTYPE_SEPA_XML = 5;

    public static function getLocalizedListType($listType): string
    {
        return match ($listType) {
            self::LISTTYPE_NONE => "No attachment",
            self::LISTTYPE_TXT => "TXT file",
            self::LISTTYPE_PDF => "PDF file",
            self::LISTTYPE_SEPA_XML => "ISO SEPA XML file",
            default => "",
        };
    }
} // End of EmailList class
