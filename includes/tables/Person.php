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
 * Person
 */
class Person {
    /** @var int user id PK */
    var $PE_personid;
    /** @var string user login name */
    var $PE_username;
    /** @var string user login password as md5 */
    var $PE_password;
    /** @var string user group FK */
    var $PE_groupid;
    /** @var int personaccount id FK */
    var $PE_personaccountid;
    /** @var string firstname */
    var $PE_firstname;
    /** @var string surname */
    var $PE_surname;
    /** @var string degree prefix */
    var $PE_degree_prefix;
    /** @var string degree suffix */
    var $PE_degree_suffix;
    /** @var string gender */
    var $PE_gender;
    /** @var date date of birth  */
    var $PE_birthdate;
    /** @var string nickname */
    var $PE_nick;
    /** @var string email */
    var $PE_email;
    /** @var string telephone number */
    var $PE_tel;
    /** @var string secondary phone number */
    var $PE_secondary_phone_number;
    /** @var string icq number */
    var $PE_icq;
    /** @var string jabber */
    var $PE_jabber;
    /** @var string address */
    var $PE_address;
    /** @var string city */
    var $PE_city;
    /** @var string zip code */
    var $PE_zip;
    /** @var int status */
    var $PE_ic;
    /** @var int status */
    var $PE_dic;
    /** @var int status */
    var $PE_shortcompanyname;
    /** @var int status */
    var $PE_companyname;
    /** @var int status */
    var $PE_status;

    /** @var datetime register date */
    var $PE_registerdate;

    /** @var datetime lastlogged in date */
    var $PE_lastloggedin;

    /** @var text serialized uistate */
    var $PE_uistate;

    const STATUS_PASSIVE = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_DISCARTED = 9;

    public static $STATUS_ARRAY = array(
        0, //Passive
        1, //Active
        9  //Discarted
    );

    public static function getLocalizedStatus($source) {
        switch ($source) {
            case self::STATUS_PASSIVE:
                return _("Passive");

            case self::STATUS_ACTIVE:
                return _("Active");

            case self::STATUS_DISCARTED:
                return _("Discarted");
        }
    }
} // End of Person class
?>
