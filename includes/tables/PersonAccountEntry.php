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
 * PersonAccountEntry
 */
class PersonAccountEntry
{
    /**
     * @var int personaccountentry id PK
     */
    public $PN_personaccountentryid;
    /**
     * @var int bankaccountentry id FK
     */
    public $PN_bankaccountentryid;
    /**
     * @var int personaccount id FK
     */
    public $PN_personaccountid;
    /**
     * @var datetime datetime when entry is received
     */
    public $PN_date;
    /**
     * @var DECIMAL(10,2) amount
     */
    public $PN_amount;
    /**
     * @var varchar(10) currency of account
     */
    public $PN_currency;
    /**
     * @var int source
     */
    public $PN_source;
    /**
     * @var varchar(255) comment
     */
    public $PN_comment;

    public const SOURCE_BANKACCOUNT = 1;
    public const SOURCE_CASH = 2;
    public const SOURCE_DISCOUNT = 3;

    public static array $SOURCE_ARRAY = [
        self::SOURCE_BANKACCOUNT,
        self::SOURCE_CASH,
        self::SOURCE_DISCOUNT
    ];

    public static function getLocalizedSource($source): string
    {
        return match ($source) {
            self::SOURCE_BANKACCOUNT => _("Bank transaction"),
            self::SOURCE_CASH => _("Cash"),
            self::SOURCE_DISCOUNT => _("Discount"),
            default => "",
        };
    }
} // End of PersonAccountEntry class
