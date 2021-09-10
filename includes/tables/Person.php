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
 * Person
 */
class Person {
    /** @var int user id PK */
    var $PE_personid = null;
    /** @var string user login name */
    var $PE_username = null;
    /** @var string user login password as md5 */
    var $PE_password = null;
    /** @var string user group FK */
    var $PE_groupid = null;
    /** @var int personaccount id FK */
    var $PE_personaccountid = null;
    /** @var string firstname */
    var $PE_firstname = null;
    /** @var string surname */
    var $PE_surname = null;
    /** @var string degree prefix */
    var $PE_degree_prefix = null;
    /** @var string degree suffix */
    var $PE_degree_suffix = null;
    /** @var string gender */
    var $PE_gender = null;
    /** @var date date of birth  */
    var $PE_birthdate = null;
    /** @var string nickname */
    var $PE_nick = null;
    /** @var string email */
    var $PE_email = null;
    /** @var string telephone number */
    var $PE_tel = null;
    /** @var string secondary phone number */
    var $PE_secondary_phone_number = null;
    /** @var string icq number */
    var $PE_icq = null;
    /** @var string jabber */
    var $PE_jabber = null;
    /** @var string address */
    var $PE_address = null;
    /** @var string city */
    var $PE_city = null;
    /** @var string zip code */
    var $PE_zip = null;
    /** @var int status */
    var $PE_ic = null;
    /** @var int status */
    var $PE_dic = null;
    /** @var int status */
    var $PE_shortcompanyname = null;
    /** @var int status */
    var $PE_companyname = null;
    /** @var int status */
    var $PE_status = null;

    /** @var datetime register date */
    var $PE_registerdate = null;

    /** @var datetime lastlogged in date */
    var $PE_lastloggedin = null;

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
