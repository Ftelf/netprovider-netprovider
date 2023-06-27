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
 * BankAccountEntry
 */
class BankAccountEntry
{
    /**
     * @public int bankaccountentry id PK
     */
    public $BE_bankaccountentryid;
    /**
     * @public int bankaccount id FK
     */
    public $BE_bankaccountid;
    /**
     * @public int personaccountentryid FK: specified if entry is identified as internet payment
     */
    public $BE_personaccountentryid;
    /**
     * @public datetime datetime when entry is received
     */
    public $BE_datetime;
    /**
     * @public date writeoff_date
     */
    public $BE_writeoff_date;
    /**
     * @public varchar(255) note
     */
    public $BE_note;
    /**
     * @public varchar(255) name of account provided by bank
     */
    public $BE_accountname;
    /**
     * @public varchar(255) account number of incoming payment
     */
    public $BE_accountnumber;
    /**
     * @public integer account number of incoming payment
     */
    public $BE_banknumber;
    /**
     * @public integer variable symbol
     */
    public $BE_variablesymbol;
    /**
     * @public integer constant symbol
     */
    public $BE_constantsymbol;
    /**
     * @public integer specific symbol
     */
    public $BE_specificsymbol;
    /**
     * @public DECIMAL(10,2) amount
     */
    public $BE_amount;
    /**
     * @public DECIMAL(10,2) charge
     */
    public $BE_charge;
    /**
     * @public varchar(255) message
     */
    public $BE_message;
    /**
     * @public integer type of transaction, default '0'
     */
    public $BE_typeoftransaction;
    /**
     * @public integer status, default '0'
     */
    public $BE_status;
    /**
     * @public integer identifycode, default '0'
     */
    public $BE_identifycode;
    /**
     * @public varchar(255) comment, default ''
     */
    public $BE_comment;

    public const TYPE_OTHER = 0;
    public const TYPE_TRANSACTION = 1;
    public const TYPE_INCOMEPAYMENT = 2;
    public const TYPE_CASHDEPOSIT = 3;
    public const TYPE_CONTINUINGTRANSFER = 4;
    public const TYPE_DIFFENTTRANSACTIONCHARGE = 5;
    public const TYPE_POSITIVEINCREASE = 6;
    public const TYPE_CASHDISPENCERDRAFT = 7;
    public const TYPE_BANKCARDPAYMENT = 8;
    public const TYPE_GENERATEBANK = 9;
    public const TYPE_CREDITCARDYEARLYFEE = 10;
    public const TYPE_DISTRIBUTION_OF_BANK_LIST = 11;
    public const TYPE_POOL_TRANSACTION = 12;
    public const TYPE_USING_AND_MANAGING_INTERNET = 13;
    public const TYPE_INCREASE_OF_LOAN = 14;
    public const TYPE_LOAN_PAYMENT = 15;
    public const TYPE_USING_AND_MANAGING_ACCOUNT = 16;
    public const TYPE_EXTERNAL_FEE = 17;
    public const TYPE_BANK_CERTIFICATE = 18;
    public const TYPE_DISSAVING_INVESTMENT = 19;
    public const TYPE_TRANSACTION_FEE = 20;
    public const TYPE_CASH_WITHDRAW = 21;
    public const TYPE_FOREIGN_SEPA = 22;
    public const TYPE_OUTCOME = 23;
    public const TYPE_INTEREST_TAX = 24;
    public const TYPE_PERMANENT = 25;
    public const TYPE_ONETIME = 26;
    public const TYPE_INCOMEPAYMENT2 = 27;
    public const TYPE_ONETIME2 = 28;
    public const TYPE_PERMANENT_ORDER = 29;
    public const TYPE_OUTGOING_CHARGE = 30;

    public static array $TYPE_ARRAY = [
        self::TYPE_OTHER,
        self::TYPE_TRANSACTION,
        self::TYPE_INCOMEPAYMENT,
        self::TYPE_CASHDEPOSIT,
        self::TYPE_CONTINUINGTRANSFER,
        self::TYPE_DIFFENTTRANSACTIONCHARGE,
        self::TYPE_POSITIVEINCREASE,
        self::TYPE_CASHDISPENCERDRAFT,
        self::TYPE_BANKCARDPAYMENT,
        self::TYPE_GENERATEBANK,
        self::TYPE_CREDITCARDYEARLYFEE,
        self::TYPE_DISTRIBUTION_OF_BANK_LIST,
        self::TYPE_POOL_TRANSACTION,
        self::TYPE_USING_AND_MANAGING_INTERNET,
        self::TYPE_INCREASE_OF_LOAN,
        self::TYPE_LOAN_PAYMENT,
        self::TYPE_USING_AND_MANAGING_ACCOUNT,
        self::TYPE_EXTERNAL_FEE,
        self::TYPE_BANK_CERTIFICATE,
        self::TYPE_DISSAVING_INVESTMENT,
        self::TYPE_TRANSACTION_FEE,
        self::TYPE_CASH_WITHDRAW,
        self::TYPE_FOREIGN_SEPA,
        self::TYPE_OUTCOME,
        self::TYPE_INTEREST_TAX,
        self::TYPE_PERMANENT,
        self::TYPE_ONETIME,
        self::TYPE_INCOMEPAYMENT2,
        self::TYPE_ONETIME2,
        self::TYPE_PERMANENT_ORDER,
        self::TYPE_OUTGOING_CHARGE
    ];

    public static function getLocalizedType($type): string
    {
        return match ($type) {
            self::TYPE_OTHER => _("Other"),
            self::TYPE_TRANSACTION => _("Transfer"),
            self::TYPE_INCOMEPAYMENT => _("Incoming payment"),
            self::TYPE_CASHDEPOSIT => _("Cash deposit"),
            self::TYPE_CONTINUINGTRANSFER => _("Continuing transfer"),
            self::TYPE_DIFFENTTRANSACTIONCHARGE => _("Other transaction fee"),
            self::TYPE_POSITIVEINCREASE => _("Positive increase"),
            self::TYPE_CASHDISPENCERDRAFT => _("Cash dispencer draft"),
            self::TYPE_BANKCARDPAYMENT => _("Credit card payment"),
            self::TYPE_GENERATEBANK => _("Account list generation"),
            self::TYPE_CREDITCARDYEARLYFEE => _("Credit card administration fee"),
            self::TYPE_DISTRIBUTION_OF_BANK_LIST => _("Distribution of bank list"),
            self::TYPE_POOL_TRANSACTION => _("Pool transfer"),
            self::TYPE_USING_AND_MANAGING_INTERNET => _("Using and managing of Internet"),
            self::TYPE_INCREASE_OF_LOAN => _("Loan increase"),
            self::TYPE_LOAN_PAYMENT => _("Loan payment"),
            self::TYPE_USING_AND_MANAGING_ACCOUNT => _("Using and managing of Account"),
            self::TYPE_EXTERNAL_FEE => _("External fee"),
            self::TYPE_BANK_CERTIFICATE => _("Bank certificate"),
            self::TYPE_DISSAVING_INVESTMENT => _("Dissaving investment"),
            self::TYPE_TRANSACTION_FEE => _("Transaction fee"),
            self::TYPE_CASH_WITHDRAW => _("Cash withdraw"),
            self::TYPE_FOREIGN_SEPA => _("Foreign SEPA"),
            self::TYPE_OUTCOME => _("Outgoing Payment"),
            self::TYPE_INTEREST_TAX => _("Interest tax"),
            self::TYPE_PERMANENT => _("Permanent payment"),
            self::TYPE_ONETIME => _("One time payment"),
            self::TYPE_INCOMEPAYMENT2 => _("Incoming payment #2"),
            self::TYPE_ONETIME2 => _("One time payment #2"),
            self::TYPE_PERMANENT_ORDER => _("Permanent Order"),
            self::TYPE_OUTGOING_CHARGE => _("Outgoing Charge"),
            default => ""
        };
    }

    public const STATUS_PENDING = 0;
    public const STATUS_PROCESSED = 1;

    public static array $STATUS_ARRAY = [
        self::STATUS_PENDING,
        self::STATUS_PROCESSED
    ];

    public static function getLocalizedStatus($status): string
    {
        return match ($status) {
            self::STATUS_PENDING => _("Pending"),
            self::STATUS_PROCESSED => _("Processed"),
            default => "",
        };
    }

    public const IDENTIFY_UNIDENTIFIED = 0;
    public const IDENTIFY_PERSONACCOUNT = 1;
    public const IDENTIFY_INTERNALTRANSACTION = 2;
    public const IDENTIFY_IGNORE = 3;

    public static array $IDENTIFICATION_ARRAY = [
        self::IDENTIFY_UNIDENTIFIED,
        self::IDENTIFY_PERSONACCOUNT,
        self::IDENTIFY_INTERNALTRANSACTION,
        self::IDENTIFY_IGNORE
    ];

    public static function getLocalizedIdentification($identification): string
    {
        return match ($identification) {
            self::IDENTIFY_UNIDENTIFIED => _("Waiting for identification"),
            self::IDENTIFY_PERSONACCOUNT => _("Person account"),
            self::IDENTIFY_INTERNALTRANSACTION => _("Internal transaction"),
            self::IDENTIFY_IGNORE => _("Ignore"),
            default => "",
        };
    }
} // End of BankAccountEntry class
