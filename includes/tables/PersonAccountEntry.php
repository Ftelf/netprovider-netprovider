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
class PersonAccountEntry {
    /** @var int personaccountentry id PK */
    var $PN_personaccountentryid;
    /** @var int bankaccountentry id FK */
    var $PN_bankaccountentryid;
    /** @var int personaccount id FK */
    var $PN_personaccountid;
    /** @var datetime datetime when entry is received */
    var $PN_date;
    /** @var DECIMAL(10,2) amount */
    var $PN_amount;
    /** @var varchar(10) currency of account */
    var $PN_currency;
    /** @var int source */
    var $PN_source;
    /** @var varchar(255) comment */
    var $PN_comment;

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
