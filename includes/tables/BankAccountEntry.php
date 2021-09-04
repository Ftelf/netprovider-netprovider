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
// | Authors: Lukas Dziadkowiec <i.ftelf@gmail.com>                  |
// +----------------------------------------------------------------------+

/**
 * @author  Lukas Dziadkowiec <i.ftelf@gmail.com>
 */

/**
 * BankAccountEntry
 */
class BankAccountEntry {
    /** @var int bankaccountentry id PK */
    var $BE_bankaccountentryid = null;
    /** @var int bankaccount id FK */
    var $BE_bankaccountid = null;
    /** @var int personaccountentryid FK: specified if entry is identified as internet payment */
    var $BE_personaccountentryid = null;
    /** @var datetime datetime when entry is received */
    var $BE_datetime = null;
    /** @var date writeoff_date */
    var $BE_writeoff_date = null;
    /** @var varchar(255) note */
    var $BE_note = null;
    /** @var varchar(255) name of account provided by bank */
    var $BE_accountname = null;
    /** @var varchar(255) account number of incoming payment */
    var $BE_accountnumber = null;
    /** @var integer account number of incoming payment */
    var $BE_banknumber = null;
    /** @var integer variable symbol */
    var $BE_variablesymbol = null;
    /** @var integer constant symbol */
    var $BE_constantsymbol = null;
    /** @var integer specific symbol */
    var $BE_specificsymbol = null;
    /** @var DECIMAL(10,2) amount */
    var $BE_amount = null;
    /** @var DECIMAL(10,2) charge */
    var $BE_charge = null;
    /** @var varchar(255) message */
    var $BE_message = null;
    /** @var integer type of transaction, default '0' */
    var $BE_typeoftransaction = null;
    /** @var integer status, default '0' */
    var $BE_status = null;
    /** @var integer identifycode, default '0' */
    var $BE_identifycode = null;
    /** @var varchar(255) comment, default '' */
    var $BE_comment = null;

    const TYPE_OTHER = 0;
    const TYPE_TRANSACTION = 1;
    const TYPE_INCOMEPAYMENT = 2;
    const TYPE_CASHDEPOSIT = 3;
    const TYPE_CONTINUINGTRANSFER = 4;
    const TYPE_DIFFENTTRANSACTIONCHARGE = 5;
    const TYPE_POSITIVEINCREASE = 6;
    const TYPE_CASHDISPENCERDRAFT = 7;
    const TYPE_BANKCARDPAYMENT = 8;
    const TYPE_GENERATEBANK = 9;
    const TYPE_CREDITCARDYEARLYFEE = 10;
    const TYPE_DISTRIBUTION_OF_BANK_LIST = 11;
    const TYPE_POOL_TRANSACTION = 12;
    const TYPE_USING_AND_MANAGING_INTERNET = 13;
    const TYPE_INCREASE_OF_LOAN = 14;
    const TYPE_LOAN_PAYMENT = 15;
    const TYPE_USING_AND_MANAGING_ACCOUNT = 16;
    const TYPE_EXTERNAL_FEE = 17;
    const TYPE_BANK_CERTIFICATE = 18;
    const TYPE_DISSAVING_INVESTMENT = 19;
    const TYPE_TRANSACTION_FEE = 20;
    const TYPE_CASH_WITHDRAW = 21;
    const TYPE_FOREIGN_SEPA = 22;
    const TYPE_OUTCOME = 23;
    const TYPE_INTEREST_TAX = 24;
    const TYPE_PERMANENT = 25;
    const TYPE_ONETIME = 26;
    const TYPE_INCOMEPAYMENT2 = 27;
    const TYPE_ONETIME2 = 28;
    const TYPE_PERMANENT_ORDER = 29;
    const TYPE_OUTGOING_CHARGE = 30;

    public static $TYPE_ARRAY = array(
        0,	//Other
        1,	//Transfer
        2,	//Incoming payment
        3,	//Cash deposit
        4,	//Continuing transfer
        5,	//Other transaction fee
        6,	//Positive increase
        7,	//Cash dispencer draft
        8,	//Credit card payment
        9,	//Account list generation
        10,	//Credit card administration fee
        11,	//Distribution of bank list
        12,	//Pool transfer
        13,	//Using and managing of Internet
        14,	//Loan increase
        15,	//Loan payment
        16,	//Using and managing of Account
        17,	//External fee
        18,	//Bank certificate,
        19, //Dissaving investment
        20, //Transaction fee,
        21, //Cash withdraw
        22, //TYPE_FOREIGN_SEPA
        23, //OUTGOING PAYMENT
        24, //INTEREST_TAX
        25, //PERMANENT
        26, //ONETIME
        27, //Incoming payment #2
        28, //ONETIME #2
        29, //PERMANENT_ORDER
        30  //OUTGOING CHARGE
    );

    public static function getLocalizedType($type) {
        switch ($type) {
            case self::TYPE_OTHER:
                return _("Other");

            case self::TYPE_TRANSACTION:
                return _("Transfer");

            case self::TYPE_INCOMEPAYMENT:
                return _("Incoming payment");

            case self::TYPE_CASHDEPOSIT:
                return _("Cash deposit");

            case self::TYPE_CONTINUINGTRANSFER:
                return _("Continuing transfer");

            case self::TYPE_DIFFENTTRANSACTIONCHARGE:
                return _("Other transaction fee");

            case self::TYPE_POSITIVEINCREASE:
                return _("Positive increase");

            case self::TYPE_CASHDISPENCERDRAFT:
                return _("Cash dispencer draft");

            case self::TYPE_BANKCARDPAYMENT:
                return _("Credit card payment");

            case self::TYPE_GENERATEBANK:
                return _("Account list generation");

            case self::TYPE_CREDITCARDYEARLYFEE:
                return _("Credit card administration fee");

            case self::TYPE_DISTRIBUTION_OF_BANK_LIST:
                return _("Distribution of bank list");

            case self::TYPE_POOL_TRANSACTION:
                return _("Pool transfer");

            case self::TYPE_USING_AND_MANAGING_INTERNET:
                return _("Using and managing of Internet");

            case self::TYPE_INCREASE_OF_LOAN:
                return _("Loan increase");

            case self::TYPE_LOAN_PAYMENT:
                return _("Loan payment");

            case self::TYPE_USING_AND_MANAGING_ACCOUNT:
                return _("Using and managing of Account");

            case self::TYPE_EXTERNAL_FEE:
                return _("External fee");

            case self::TYPE_BANK_CERTIFICATE:
                return _("Bank certificate");

            case self::TYPE_DISSAVING_INVESTMENT:
                return _("Dissaving investment");

            case self::TYPE_TRANSACTION_FEE:
                return _("Transaction fee");

            case self::TYPE_CASH_WITHDRAW:
                return _("Cash withdraw");

            case self::TYPE_FOREIGN_SEPA:
                return _("Foreign SEPA");

            case self::TYPE_OUTCOME:
                return _("Outgoing Payment");

            case self::TYPE_INTEREST_TAX:
                return _("Interest tax");

            case self::TYPE_PERMANENT:
                return _("Permanent payment");

            case self::TYPE_ONETIME:
                return _("One time payment");

            case self::TYPE_INCOMEPAYMENT2:
                return _("Incoming payment #2");

            case self::TYPE_ONETIME2:
                return _("One time payment #2");

            case self::TYPE_PERMANENT_ORDER:
                return _("Permanent Order");

            case self::TYPE_OUTGOING_CHARGE:
                return _("Outgoing Charge");
        }
    }

    const STATUS_PENDING = 0;
    const STATUS_PROCESSED = 1;

    public static $STATUS_ARRAY = array(
        0, //Pending
        1  //Processed
    );

    public static function getLocalizedStatus($status) {
        switch ($status) {
            case self::STATUS_PENDING :
                return _("Pending");

            case self::STATUS_PROCESSED :
                return _("Processed");
        }
    }

    const IDENTIFY_UNIDENTIFIED = 0;
    const IDENTIFY_PERSONACCOUNT = 1;
    const IDENTIFY_INTERNALTRANSACTION = 2;
    const IDENTIFY_IGNORE = 3;

    public static $IDENTIFICATION_ARRAY = array(
        0, //Waiting for identification
        1, //Person account
        2, //Internal transaction
        3  //Ignore
    );

    public static function getLocalizedIdentification($identification) {
        switch ($identification) {
            case self::IDENTIFY_UNIDENTIFIED:
                return _("Waiting for identification");

            case self::IDENTIFY_PERSONACCOUNT:
                return _("Person account");

            case self::IDENTIFY_INTERNALTRANSACTION:
                return _("Internal transaction");

            case self::IDENTIFY_IGNORE:
                return _("Ignore");
        }
    }
} // End of BankAccountEntry class
?>
