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
            self::TYPE_OTHER => "Other",
            self::TYPE_TRANSACTION => "Transfer",
            self::TYPE_INCOMEPAYMENT => "Incoming payment",
            self::TYPE_CASHDEPOSIT => "Cash deposit",
            self::TYPE_CONTINUINGTRANSFER => "Continuing transfer",
            self::TYPE_DIFFENTTRANSACTIONCHARGE => "Other transaction fee",
            self::TYPE_POSITIVEINCREASE => "Positive increase",
            self::TYPE_CASHDISPENCERDRAFT => "Cash dispencer draft",
            self::TYPE_BANKCARDPAYMENT => "Credit card payment",
            self::TYPE_GENERATEBANK => "Account list generation",
            self::TYPE_CREDITCARDYEARLYFEE => "Credit card administration fee",
            self::TYPE_DISTRIBUTION_OF_BANK_LIST => "Distribution of bank list",
            self::TYPE_POOL_TRANSACTION => "Pool transfer",
            self::TYPE_USING_AND_MANAGING_INTERNET => "Using and managing of Internet",
            self::TYPE_INCREASE_OF_LOAN => "Loan increase",
            self::TYPE_LOAN_PAYMENT => "Loan payment",
            self::TYPE_USING_AND_MANAGING_ACCOUNT => "Using and managing of Account",
            self::TYPE_EXTERNAL_FEE => "External fee",
            self::TYPE_BANK_CERTIFICATE => "Bank certificate",
            self::TYPE_DISSAVING_INVESTMENT => "Dissaving investment",
            self::TYPE_TRANSACTION_FEE => "Transaction fee",
            self::TYPE_CASH_WITHDRAW => "Cash withdraw",
            self::TYPE_FOREIGN_SEPA => "Foreign SEPA",
            self::TYPE_OUTCOME => "Outgoing Payment",
            self::TYPE_INTEREST_TAX => "Interest tax",
            self::TYPE_PERMANENT => "Permanent payment",
            self::TYPE_ONETIME => "One time payment",
            self::TYPE_INCOMEPAYMENT2 => "Incoming payment #2",
            self::TYPE_ONETIME2 => "One time payment #2",
            self::TYPE_PERMANENT_ORDER => "Permanent Order",
            self::TYPE_OUTGOING_CHARGE => "Outgoing Charge",
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
            self::STATUS_PENDING => "Pending",
            self::STATUS_PROCESSED => "Processed",
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
            self::IDENTIFY_UNIDENTIFIED => "Waiting for identification",
            self::IDENTIFY_PERSONACCOUNT => "Person account",
            self::IDENTIFY_INTERNALTRANSACTION => "Internal transaction",
            self::IDENTIFY_IGNORE => "Ignore",
            default => "",
        };
    }
} // End of BankAccountEntry class
