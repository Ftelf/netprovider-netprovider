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
class Person
{
    /**
     * @var int user id PK
     */
    public $PE_personid;
    /**
     * @var string user login name
     */
    public $PE_username;
    /**
     * @var string user login password as md5
     */
    public $PE_password;
    /**
     * @var string user group FK
     */
    public $PE_groupid;
    /**
     * @var int personaccount id FK
     */
    public $PE_personaccountid;
    /**
     * @var string firstname
     */
    public $PE_firstname;
    /**
     * @var string surname
     */
    public $PE_surname;
    /**
     * @var string degree prefix
     */
    public $PE_degree_prefix;
    /**
     * @var string degree suffix
     */
    public $PE_degree_suffix;
    /**
     * @var string gender
     */
    public $PE_gender;
    /**
     * @var date date of birth
     */
    public $PE_birthdate;
    /**
     * @var string nickname
     */
    public $PE_nick;
    /**
     * @var string email
     */
    public $PE_email;
    /**
     * @var string telephone number
     */
    public $PE_tel;
    /**
     * @var string secondary phone number
     */
    public $PE_secondary_phone_number;
    /**
     * @var string icq number
     */
    public $PE_icq;
    /**
     * @var string jabber
     */
    public $PE_jabber;
    /**
     * @var string address
     */
    public $PE_address;
    /**
     * @var string city
     */
    public $PE_city;
    /**
     * @var string zip code
     */
    public $PE_zip;
    /**
     * @var int ic
     */
    public $PE_ic;
    /**
     * @var int dic
     */
    public $PE_dic;
    /**
     * @var int shortcompanyname
     */
    public $PE_shortcompanyname;
    /**
     * @var int companyname
     */
    public $PE_companyname;
    /**
     * @var int status
     */
    public $PE_status;

    /**
     * @var datetime register date
     */
    public $PE_registerdate;

    /**
     * @var datetime lastlogged in date
     */
    public $PE_lastloggedin;

    /**
     * @var text serialized uistate
     */
    public $PE_uistate;

    public const STATUS_PASSIVE = 0;
    public const STATUS_ACTIVE = 1;
    public const STATUS_DISCARTED = 9;

    public static array $STATUS_ARRAY = [
        self::STATUS_PASSIVE,
        self::STATUS_ACTIVE,
        self::STATUS_DISCARTED
    ];

    public static function getLocalizedStatus($status): string
    {
        return match ((int)$status) {
            self::STATUS_PASSIVE => _("Passive"),
            self::STATUS_ACTIVE => _("Active"),
            self::STATUS_DISCARTED => _("Discarted"),
            default => "",
        };
    }
} // End of Person class
